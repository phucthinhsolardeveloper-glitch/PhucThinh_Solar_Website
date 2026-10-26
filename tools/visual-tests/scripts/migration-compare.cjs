#!/usr/bin/env node
/**
 * Read-only PHP/Nuxt comparison harness.
 *
 * Usage:
 *   node scripts/migration-compare.cjs <legacy-url> <preview-url> [options]
 *
 * The two origins are deliberately separate from route mapping. A preview can
 * therefore use an internal pilot path without pretending that public routes
 * already exist. No login, mutation or baseline update is performed.
 */
const fs = require('node:fs/promises')
const path = require('node:path')

const root = path.resolve(__dirname, '../../..')
const DEFAULT_OUTPUT = path.join(root, 'frontend/.migration-artifacts/migration-comparison')
const DEFAULT_MANIFEST = {
  routes: [{ id: 'root', legacyPath: '/', previewPath: '/' }],
  interactions: [],
}

function usage() {
  return `Usage: node scripts/migration-compare.cjs <legacy-url> <preview-url> [options]

Options:
  --manifest <file>       JSON route map (legacyPath/previewPath, interactions)
  --output <dir>          Artifact directory (default: frontend/.migration-artifacts/migration-comparison)
  --viewport <WxH>        Repeatable viewport, e.g. 390x844 or 1440x900
  --browser <name>        chromium (default), firefox, or webkit
  --reduced-motion <x>    reduce (default) or no-preference
  --allow-remote          Explicitly allow non-loopback URLs (never use production by default)
  --help                  Show this help

Example:
  node scripts/migration-compare.cjs http://127.0.0.1:8080 http://127.0.0.1:3100 \\
    --manifest ./migration-routes.json --viewport 390x844 --viewport 1440x900

Manifest example:
  {"routes":[{"id":"solutions","legacyPath":"/giai-phap/","previewPath":"/migration-preview"}],
   "interactions":[{"id":"mobile-menu","route":"solutions","action":"click","selector":"[data-menu-toggle]"}]}`
}

function parseArgs(argv) {
  const args = { viewports: [] }
  const positionals = []
  for (let index = 0; index < argv.length; index += 1) {
    const value = argv[index]
    if (value === '--help' || value === '-h') return { help: true }
    if (value === '--allow-remote') { args.allowRemote = true; continue }
    if (['--manifest', '--output', '--viewport', '--browser', '--reduced-motion'].includes(value)) {
      const next = argv[++index]
      if (!next) throw new Error(`${value} requires a value`)
      if (value === '--viewport') args.viewports.push(parseViewport(next))
      else if (value === '--manifest') args.manifest = next
      else if (value === '--output') args.output = path.resolve(next)
      else if (value === '--browser') args.browser = next
      else args.reducedMotion = next
      continue
    }
    if (value.startsWith('-')) throw new Error(`Unknown option: ${value}`)
    positionals.push(value)
  }
  if (positionals.length !== 2) throw new Error('Exactly two URLs are required')
  args.legacyUrl = validateOrigin(positionals[0], args.allowRemote)
  args.previewUrl = validateOrigin(positionals[1], args.allowRemote)
  args.browser = args.browser || 'chromium'
  args.reducedMotion = args.reducedMotion || 'reduce'
  if (!['chromium', 'firefox', 'webkit'].includes(args.browser)) throw new Error('Browser must be chromium, firefox, or webkit')
  if (!['reduce', 'no-preference'].includes(args.reducedMotion)) throw new Error('reduced-motion must be reduce or no-preference')
  args.viewports = args.viewports.length ? args.viewports : [{ width: 390, height: 844 }, { width: 768, height: 900 }, { width: 1440, height: 900 }]
  args.output = args.output || DEFAULT_OUTPUT
  return args
}

function parseViewport(value) {
  const match = /^(\d{2,5})x(\d{2,5})$/.exec(value)
  if (!match) throw new Error(`Invalid viewport: ${value}; expected WIDTHxHEIGHT`)
  return { width: Number(match[1]), height: Number(match[2]) }
}

function validateOrigin(raw, allowRemote = false) {
  let url
  try { url = new URL(raw) } catch { throw new Error(`Invalid URL: ${raw}`) }
  if (!['http:', 'https:'].includes(url.protocol)) throw new Error(`Only HTTP(S) URLs are supported: ${raw}`)
  if (url.username || url.password) throw new Error('URLs with embedded credentials are not allowed')
  for (const key of url.searchParams.keys()) {
    if (/(token|secret|password|passwd|auth|credential|session|cookie|key)/i.test(key)) {
      throw new Error(`Credential-like query parameter is not allowed: ${key}`)
    }
  }
  const local = ['127.0.0.1', 'localhost', '::1'].includes(url.hostname)
  if (!allowRemote && !local) throw new Error(`Remote URL blocked by default: ${raw}; use --allow-remote only for an explicit exception`)
  return url.toString().replace(/\/$/, '')
}

function loadManifest(raw) {
  if (!raw) return DEFAULT_MANIFEST
  const manifest = typeof raw === 'string' ? JSON.parse(raw) : raw
  if (!Array.isArray(manifest.routes) || !manifest.routes.length) throw new Error('Manifest must contain a non-empty routes array')
  const routes = manifest.routes.map((route, index) => {
    if (!route || !route.id || typeof route.legacyPath !== 'string' || typeof route.previewPath !== 'string') {
      throw new Error(`Manifest route ${index + 1} needs id, legacyPath and previewPath`)
    }
    return { ...route, id: String(route.id) }
  })
  const ids = new Set()
  for (const route of routes) {
    if (ids.has(route.id)) throw new Error(`Duplicate route id: ${route.id}`)
    ids.add(route.id)
  }
  return { routes, interactions: Array.isArray(manifest.interactions) ? manifest.interactions : [] }
}

function joinUrl(origin, pathname, allowRemote = false) {
  if (/^https?:\/\//i.test(pathname)) return validateOrigin(pathname, allowRemote)
  return `${origin}${pathname.startsWith('/') ? pathname : `/${pathname}`}`
}

function safeFilePart(value) {
  return String(value).replace(/[^a-z0-9._-]+/gi, '-').replace(/^-+|-+$/g, '').slice(0, 100) || 'item'
}

async function capturePage(browser, url, viewport, settings, fileStem, output) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: 1, locale: 'vi-VN', reducedMotion: settings.reducedMotion })
  const consoleMessages = []
  const pageErrors = []
  const failedRequests = []
  page.on('console', message => { if (message.type() === 'error' || message.type() === 'warning') consoleMessages.push(`${message.type()}: ${message.text()}`.slice(0, 500)) })
  page.on('pageerror', error => pageErrors.push(String(error.message || error).slice(0, 500)))
  page.on('requestfailed', request => failedRequests.push(`${request.method()} ${request.url()} — ${request.failure()?.errorText || 'failed'}`.slice(0, 500)))
  const started = Date.now()
  let response
  let navigationError
  try {
    response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 })
    await page.evaluate(() => document.fonts?.ready).catch(() => {})
    await page.waitForTimeout(settings.reducedMotion === 'reduce' ? 250 : 700)
  } catch (error) {
    navigationError = String(error.message || error).slice(0, 700)
  }

  const metadata = await page.evaluate(() => {
    const content = selector => document.querySelector(selector)?.getAttribute('content') || ''
    const hrefs = [...document.querySelectorAll('a[href]')].map(link => link.href).filter(Boolean)
    const nav = [...document.querySelectorAll('header a[href], nav a[href]')].map(link => ({ text: link.textContent?.trim() || '', href: link.href }))
    const h1 = [...document.querySelectorAll('h1')].map(node => node.textContent?.trim()).filter(Boolean)
    const normalize = value => String(value || '').replace(/\s+/g, ' ').trim()
    const hash = value => {
      let result = 2166136261
      for (const character of value) {
        result ^= character.charCodeAt(0)
        result = Math.imul(result, 16777619)
      }
      return (result >>> 0).toString(16)
    }
    const visible = node => {
      const rect = node.getBoundingClientRect()
      const style = getComputedStyle(node)
      return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden'
    }
    const layoutRows = [...document.querySelectorAll('body *')].filter(visible).slice(0, 2500).map(node => {
      const rect = node.getBoundingClientRect()
      return [node.tagName, Math.round(rect.x), Math.round(rect.y), Math.round(rect.width), Math.round(rect.height)]
    })
    const textRows = [...document.querySelectorAll('h1,h2,h3,h4,p,a,button,li,dt,dd,label')].filter(visible).map(node => normalize(node.textContent))
    const mediaRows = [...document.querySelectorAll('img,video,iframe')].filter(visible).map(node => {
      const rect = node.getBoundingClientRect()
      return [node.tagName, node.getAttribute('src') || node.getAttribute('poster') || '', Math.round(rect.x), Math.round(rect.y), Math.round(rect.width), Math.round(rect.height)]
    })
    const dynamicSelectors = ['[data-dynamic]', 'time', '[aria-live]', '.price', '.cart-count', '.view-count']
    const dynamicSignals = dynamicSelectors.flatMap(selector => [...document.querySelectorAll(selector)].filter(visible).map(node => `${selector}:${normalize(node.textContent)}`))
    return {
      title: document.title,
      canonical: document.querySelector('link[rel="canonical"]')?.href || content('link[rel="canonical"]'),
      robots: content('meta[name="robots"]'),
      h1,
      hrefs: [...new Set(hrefs)].sort(),
      nav: nav.filter(item => item.text || item.href),
      scrollWidth: document.documentElement.scrollWidth,
      viewportWidth: document.documentElement.clientWidth,
      horizontalOverflow: Math.max(0, document.documentElement.scrollWidth - document.documentElement.clientWidth),
      screenshotViewport: { width: window.innerWidth, height: window.innerHeight, deviceScaleFactor: window.devicePixelRatio },
      fingerprints: {
        layout: hash(JSON.stringify(layoutRows)),
        text: hash(JSON.stringify(textRows)),
        media: hash(JSON.stringify(mediaRows)),
        links: hash(JSON.stringify([...new Set(hrefs)].sort())),
      },
      elementCounts: { layout: layoutRows.length, text: textRows.length, media: mediaRows.length },
      dynamicSignals: dynamicSignals.slice(0, 40),
    }
  }).catch(() => ({ title: '', canonical: '', robots: '', h1: [], hrefs: [], nav: [], scrollWidth: null, viewportWidth: null, horizontalOverflow: null, screenshotViewport: viewport, fingerprints: { layout: '', text: '', media: '', links: '' }, elementCounts: { layout: 0, text: 0, media: 0 }, dynamicSignals: [] }))

  const screenshotPath = path.join(output, `${fileStem}.png`)
  const headerPath = path.join(output, `${fileStem}-header.png`)
  let screenshotBase64 = null
  let headerBase64 = null
  if (!navigationError) {
    await page.screenshot({ path: screenshotPath, fullPage: false, animations: 'disabled', timeout: 20000 })
    screenshotBase64 = (await fs.readFile(screenshotPath)).toString('base64')
    const header = page.locator('header, [role="banner"]').first()
    if (await header.count() && await header.isVisible().catch(() => false)) {
      await header.screenshot({ path: headerPath, animations: 'disabled', timeout: 10000 }).catch(() => {})
      try { headerBase64 = (await fs.readFile(headerPath)).toString('base64') } catch {}
    }
  }
  const result = {
    url, status: response?.status() ?? null, ok: Boolean(response?.ok()), navigationError,
    durationMs: Date.now() - started, ...metadata, consoleMessages, pageErrors, failedRequests,
    screenshot: screenshotBase64 ? path.basename(screenshotPath) : null,
    headerScreenshot: headerBase64 ? path.basename(headerPath) : null,
    screenshotBase64, headerBase64,
  }
  await page.close()
  return result
}

function classifyDifference(legacy, preview, diff) {
  const failedRequests = [...(legacy.failedRequests || []), ...(preview.failedRequests || [])]
  const externalEmbedOnly = failedRequests.length > 0 && failedRequests.every(item => /https?:\/\/(?:www\.)?google\.com\/maps\/embed/i.test(item))
  const hardRuntimeGaps = [legacy.navigationError, preview.navigationError, legacy.status === null, preview.status === null, legacy.status >= 400, preview.status >= 400, legacy.pageErrors?.length > 0, preview.pageErrors?.length > 0, legacy.consoleMessages?.length > 0, preview.consoleMessages?.length > 0, legacy.horizontalOverflow > 1, preview.horizontalOverflow > 1].some(Boolean)
  if (hardRuntimeGaps) return { kind: 'runtime-or-route-error', confidence: 'high', reason: 'HTTP, browser console/pageerror hoặc responsive gate không đạt' }
  if (externalEmbedOnly) return { kind: 'external-resource-blocked', confidence: 'high', reason: 'Third-party Google Maps embed bị browser/network abort; không phải lỗi Nuxt UI' }
  if (failedRequests.length > 0) return { kind: 'runtime-or-route-error', confidence: 'high', reason: 'Có request media/resource bị lỗi' }
  if (diff.status !== 'compared') return { kind: 'unknown', confidence: 'low', reason: diff.reason || 'Không đủ ảnh để phân loại' }
  const dataSignals = []
  if (legacy.fingerprints?.text !== preview.fingerprints?.text) dataSignals.push('text/content')
  if (legacy.fingerprints?.media !== preview.fingerprints?.media) dataSignals.push('media')
  if (legacy.fingerprints?.links !== preview.fingerprints?.links) dataSignals.push('links')
  if (legacy.title !== preview.title || JSON.stringify(legacy.h1) !== JSON.stringify(preview.h1)) dataSignals.push('SEO/headings')
  const layoutChanged = legacy.fingerprints?.layout !== preview.fingerprints?.layout
  if (!layoutChanged && dataSignals.length) return { kind: 'dynamic-data-difference', confidence: 'medium', reason: dataSignals.join(', ') }
  if (layoutChanged && dataSignals.length) return { kind: 'mixed-data-and-ui-difference', confidence: 'medium', reason: `layout + ${dataSignals.join(', ')}` }
  if (layoutChanged || diff.changedRatio > 0.01) return { kind: 'ui-difference', confidence: 'medium', reason: layoutChanged ? 'geometry/layout fingerprint khác' : 'pixel diff vượt 1% nhưng DOM data không đổi' }
  return { kind: 'stable-or-rendering-noise', confidence: 'medium', reason: 'DOM geometry và data fingerprint tương đồng' }
}

async function compareScreenshots(browser, leftBase64, rightBase64, diffPath) {
  if (!leftBase64 || !rightBase64) return { status: 'not-compared', reason: 'one-or-both-screenshots-missing' }
  const page = await browser.newPage()
  try {
    await page.setContent('<canvas id="diff"></canvas>')
    const result = await page.evaluate(async ({ leftBase64: left, rightBase64: right }) => {
      const load = value => new Promise((resolve, reject) => { const image = new Image(); image.onload = () => resolve(image); image.onerror = reject; image.src = `data:image/png;base64,${value}` })
      const [a, b] = await Promise.all([load(left), load(right)])
      const width = Math.max(a.naturalWidth, b.naturalWidth)
      const height = Math.max(a.naturalHeight, b.naturalHeight)
      const canvas = document.querySelector('#diff'); canvas.width = width; canvas.height = height
      const context = canvas.getContext('2d'); const leftCanvas = document.createElement('canvas'); const rightCanvas = document.createElement('canvas')
      leftCanvas.width = rightCanvas.width = width; leftCanvas.height = rightCanvas.height = height
      const leftContext = leftCanvas.getContext('2d'); const rightContext = rightCanvas.getContext('2d')
      leftContext.drawImage(a, 0, 0); rightContext.drawImage(b, 0, 0)
      const pixelsA = leftContext.getImageData(0, 0, width, height).data; const pixelsB = rightContext.getImageData(0, 0, width, height).data
      const output = context.createImageData(width, height); let changed = 0; const threshold = 16
      for (let index = 0; index < pixelsA.length; index += 4) {
        const delta = Math.max(Math.abs(pixelsA[index] - pixelsB[index]), Math.abs(pixelsA[index + 1] - pixelsB[index + 1]), Math.abs(pixelsA[index + 2] - pixelsB[index + 2]))
        if (delta > threshold || pixelsA[index + 3] !== pixelsB[index + 3]) { changed += 1; output.data[index] = 255; output.data[index + 1] = 40; output.data[index + 2] = 40; output.data[index + 3] = 220 }
        else { const gray = Math.round((pixelsA[index] + pixelsA[index + 1] + pixelsA[index + 2]) / 3); output.data[index] = gray; output.data[index + 1] = gray; output.data[index + 2] = gray; output.data[index + 3] = 100 }
      }
      context.putImageData(output, 0, 0)
      return { width, height, changedPixels: changed, totalPixels: width * height, changedRatio: changed / (width * height), dataUrl: canvas.toDataURL('image/png') }
    }, { leftBase64, rightBase64 })
    await fs.writeFile(diffPath, Buffer.from(result.dataUrl.split(',')[1], 'base64'))
    delete result.dataUrl
    return { status: 'compared', diff: path.basename(diffPath), ...result, threshold: 16, note: 'Heuristic screenshot diff; not a pixel-perfect approval gate.' }
  } finally { await page.close() }
}

async function performInteraction(browser, route, interaction, viewport, settings, output, side) {
  const page = await browser.newPage({ viewport, deviceScaleFactor: 1, locale: 'vi-VN', reducedMotion: settings.reducedMotion })
  const result = { id: interaction.id, action: interaction.action, selector: interaction.selector, side, status: 'skipped' }
  try {
    await page.goto(joinUrl(side === 'legacy' ? settings.legacyUrl : settings.previewUrl, side === 'legacy' ? route.legacyPath : route.previewPath, settings.allowRemote), { waitUntil: 'domcontentloaded', timeout: 30000 })
    const target = page.locator(interaction.selector).first()
    if (!await target.count()) { result.status = 'missing-selector'; return result }
    if (interaction.action === 'click') await target.click({ timeout: 5000 })
    else if (interaction.action === 'hover') await target.hover({ timeout: 5000 })
    else if (interaction.action === 'fill') await target.fill(String(interaction.value ?? ''))
    else throw new Error(`Unsupported interaction action: ${interaction.action}`)
    await page.waitForTimeout(100)
    const file = `${safeFilePart(route.id)}-${safeFilePart(interaction.id)}-${side}.png`
    await page.screenshot({ path: path.join(output, file), fullPage: false, animations: 'disabled' })
    result.status = 'captured'; result.screenshot = file
  } catch (error) { result.status = 'error'; result.error = String(error.message || error).slice(0, 500) }
  finally { await page.close() }
  return result
}

function stripBuffers(value) {
  const copy = JSON.parse(JSON.stringify(value))
  for (const row of copy.routes || []) for (const viewport of row.viewports || []) for (const side of ['legacy', 'preview']) {
    delete viewport[side]?.screenshotBase64
    delete viewport[side]?.headerBase64
  }
  return copy
}

function renderMarkdown(report) {
  const lines = ['# Migration visual comparison', '', `- Generated: ${report.generatedAt}`, `- Legacy: \`${report.legacyUrl}\``, `- Preview: \`${report.previewUrl}\``, `- Browser: ${report.browser}; reduced motion: ${report.reducedMotion}`, `- Viewports: ${report.viewports.map(item => `${item.width}x${item.height}`).join(', ')}`, '', 'This is read-only evidence, not an approved baseline and not a pixel-perfect gate.', 'Classification is heuristic: `dynamic-data-difference` means the DOM geometry matched while content/media/links changed; it is not an approval.', '', '## Route results', '', '| Route | Viewport | Legacy | Preview | Classification | Changed pixels | Gaps |', '| --- | --- | ---: | ---: | --- | ---: | --- |']
  for (const route of report.routes) for (const viewport of route.viewports) {
    const gap = [viewport.legacy.navigationError && 'legacy navigation error', viewport.preview.navigationError && 'preview navigation error', viewport.legacy.status >= 400 && `legacy HTTP ${viewport.legacy.status}`, viewport.preview.status >= 400 && `preview HTTP ${viewport.preview.status}`, viewport.preview.status === null && 'preview route unavailable', viewport.legacy.pageErrors?.length > 0 && 'legacy pageerror', viewport.preview.pageErrors?.length > 0 && 'preview pageerror', viewport.legacy.consoleMessages?.length > 0 && 'legacy console', viewport.preview.consoleMessages?.length > 0 && 'preview console', viewport.legacy.failedRequests?.length > 0 && 'legacy failed request', viewport.preview.failedRequests?.length > 0 && 'preview failed request', viewport.legacy.horizontalOverflow > 1 && 'legacy overflow', viewport.preview.horizontalOverflow > 1 && 'preview overflow'].filter(Boolean).join('; ') || '—'
    lines.push(`| ${route.id} | ${viewport.width}x${viewport.height} | ${viewport.legacy.status ?? 'ERR'} | ${viewport.preview.status ?? 'ERR'} | ${viewport.classification?.kind || 'unknown'} | ${viewport.diff.changedRatio === undefined ? '—' : `${(viewport.diff.changedRatio * 100).toFixed(2)}%`} | ${gap} |`)
  }
  lines.push('', '## Interaction evidence', '', '| Route | Interaction | Legacy | Preview |', '| --- | --- | --- | --- |')
  for (const item of report.interactions) lines.push(`| ${item.route} | ${item.id} | ${item.legacy.status} | ${item.preview.status} |`)
  lines.push('', '## Limitations', '', '- Route availability is reported, not inferred; an absent preview path is evidence of a gap.', '- Screenshots are first-fold viewport captures with a heuristic PNG diff. Fonts, remote media, animations and data may still vary.', '- `dynamic-data-difference` is based on content/media/link and geometry fingerprints; inspect `report.json` and both screenshots before accepting a gap.', '- No credentials, Admin login, production crawl, mutation, baseline update, push, merge or deploy was performed.')
  return `${lines.join('\n')}\n`
}

async function run(args) {
  const { chromium, firefox, webkit } = require('@playwright/test')
  const manifest = loadManifest(args.manifest ? JSON.parse(await fs.readFile(args.manifest, 'utf8')) : null)
  await fs.mkdir(args.output, { recursive: true })
  const browserLauncher = { chromium, firefox, webkit }[args.browser]
  const browser = await browserLauncher.launch({ headless: true })
  const report = { generatedAt: new Date().toISOString(), legacyUrl: args.legacyUrl, previewUrl: args.previewUrl, browser: args.browser, reducedMotion: args.reducedMotion, viewports: args.viewports, routes: [], interactions: [] }
  try {
    for (const route of manifest.routes) {
      const routeReport = { id: route.id, legacyPath: route.legacyPath, previewPath: route.previewPath, viewports: [] }
      for (const viewport of args.viewports) {
        const prefix = `${safeFilePart(route.id)}-${viewport.width}x${viewport.height}`
        const legacy = await capturePage(browser, joinUrl(args.legacyUrl, route.legacyPath, args.allowRemote), viewport, args, `${prefix}-legacy`, args.output)
        const preview = await capturePage(browser, joinUrl(args.previewUrl, route.previewPath, args.allowRemote), viewport, args, `${prefix}-preview`, args.output)
        const diff = await compareScreenshots(browser, legacy.screenshotBase64, preview.screenshotBase64, path.join(args.output, `${prefix}-diff.png`))
        const classification = classifyDifference(legacy, preview, diff)
        routeReport.viewports.push({ ...viewport, legacy, preview, diff, classification })
        console.log(`${route.id} ${viewport.width}x${viewport.height}: legacy=${legacy.status ?? 'ERR'} preview=${preview.status ?? 'ERR'} diff=${diff.status} class=${classification.kind}`)
      }
      report.routes.push(routeReport)
      for (const interaction of manifest.interactions.filter(item => item.route === route.id)) {
        for (const viewport of args.viewports) {
          report.interactions.push({
            route: route.id,
            id: interaction.id,
            viewport,
            legacy: await performInteraction(browser, route, interaction, viewport, args, args.output, 'legacy'),
            preview: await performInteraction(browser, route, interaction, viewport, args, args.output, 'preview'),
          })
        }
      }
    }
  } finally { await browser.close() }
  const clean = stripBuffers(report)
  await fs.writeFile(path.join(args.output, 'report.json'), `${JSON.stringify(clean, null, 2)}\n`)
  await fs.writeFile(path.join(args.output, 'summary.md'), renderMarkdown(clean))
  return report
}

if (require.main === module) {
  (async () => {
    try {
      const args = parseArgs(process.argv.slice(2))
      if (args.help) { console.log(usage()); return }
      await run(args)
    } catch (error) { console.error(`migration-compare: ${error.message || error}`); process.exitCode = 1 }
  })()
}

module.exports = { DEFAULT_MANIFEST, parseArgs, parseViewport, validateOrigin, loadManifest, renderMarkdown, safeFilePart, classifyDifference }

#!/usr/bin/env node
/**
 * Read-only status/SEO crawler for the PHP origin and the Nuxt preview.
 *
 * This intentionally uses GET only and never sends cookies, credentials or
 * mutation payloads. Origins and redirect targets are loopback-only unless
 * --allow-remote is explicitly supplied by a caller outside the normal QA
 * workflow. A HTTP 200 is only one signal; metadata, redirects and ownership
 * checks remain visible in the report.
 */
const fs = require('node:fs/promises')
const path = require('node:path')
const net = require('node:net')

const root = path.resolve(__dirname, '../../..')
const DEFAULT_OUTPUT = path.join(root, 'frontend/.migration-artifacts/seo-audit')
const DEFAULT_MANIFEST = path.join(root, 'tools/visual-tests/fixtures/migration-routes.json')
const CREDENTIAL_QUERY = /^(?:(?:token|secret|password|passwd|auth|credential|session|cookie|key)(?:[_-][a-z0-9]+)*|(?:access|api|private|csrf)[_-](?:token|key|secret|password))$/i

function usage() {
  return `Usage: node scripts/migration-seo-audit.cjs <php-origin> <nuxt-origin> [options]

Options:
  --manifest <file>       JSON route map (default: tools/visual-tests/fixtures/migration-routes.json)
  --output <dir>          Artifact directory (default: frontend/.migration-artifacts/seo-audit)
  --timeout-ms <number>   Per GET timeout (default: 15000)
  --max-redirects <n>     Redirect hop limit (default: 10)
  --allow-remote          Explicitly disable loopback-only protection
  --help                  Show this help

Example:
  node scripts/migration-seo-audit.cjs http://127.0.0.1:8080 http://127.0.0.1:3100 \\
    --manifest tools/visual-tests/fixtures/migration-routes.json \\
    --output frontend/.migration-artifacts/seo-audit`
}

function parseArgs(argv) {
  const args = { allowRemote: false }
  const positionals = []
  for (let index = 0; index < argv.length; index += 1) {
    const value = argv[index]
    if (value === '--help' || value === '-h') return { help: true }
    if (value === '--allow-remote') { args.allowRemote = true; continue }
    if (['--manifest', '--output', '--timeout-ms', '--max-redirects'].includes(value)) {
      const next = argv[++index]
      if (!next) throw new Error(`${value} requires a value`)
      if (value === '--manifest') args.manifest = path.resolve(next)
      else if (value === '--output') args.output = path.resolve(next)
      else if (value === '--timeout-ms') args.timeoutMs = parsePositiveInteger(next, value)
      else args.maxRedirects = parsePositiveInteger(next, value)
      continue
    }
    if (value.startsWith('-')) throw new Error(`Unknown option: ${value}`)
    positionals.push(value)
  }
  if (positionals.length !== 2) throw new Error('Exactly two origins are required')
  args.phpOrigin = validateOrigin(positionals[0], args.allowRemote)
  args.nuxtOrigin = validateOrigin(positionals[1], args.allowRemote)
  args.manifest = args.manifest || DEFAULT_MANIFEST
  args.output = args.output || DEFAULT_OUTPUT
  args.timeoutMs = args.timeoutMs || 15000
  args.maxRedirects = args.maxRedirects || 10
  return args
}

function parsePositiveInteger(value, option) {
  if (!/^\d+$/.test(String(value)) || Number(value) < 1 || Number(value) > 120000) {
    throw new Error(`${option} must be an integer between 1 and 120000`)
  }
  return Number(value)
}

function isLoopbackHost(hostname) {
  if (hostname === 'localhost' || hostname === '::1') return true
  return net.isIP(hostname) === 4 && hostname.split('.').map(Number).every((part, index) => index === 0 ? part === 127 : part >= 0 && part <= 255)
}

function validateOrigin(raw, allowRemote = false) {
  let url
  try { url = new URL(raw) } catch { throw new Error(`Invalid URL: ${raw}`) }
  if (!['http:', 'https:'].includes(url.protocol)) throw new Error(`Only HTTP(S) origins are supported: ${raw}`)
  if (url.username || url.password) throw new Error('Origins with embedded credentials are not allowed')
  if (url.search || url.hash) throw new Error('Origins must not contain query parameters or fragments')
  if (!allowRemote && !isLoopbackHost(url.hostname)) throw new Error(`Remote origin blocked by default: ${raw}`)
  return `${url.protocol}//${url.host}`
}

function validateTarget(raw, allowRemote = false) {
  const url = new URL(raw)
  if (!['http:', 'https:'].includes(url.protocol)) throw new Error(`Unsupported redirect protocol: ${url.protocol}`)
  if (url.username || url.password) throw new Error('Redirect target contains embedded credentials')
  for (const key of url.searchParams.keys()) if (CREDENTIAL_QUERY.test(key)) throw new Error(`Redirect target contains credential-like query parameter: ${key}`)
  if (!allowRemote && !isLoopbackHost(url.hostname)) throw new Error(`Remote redirect blocked by default: ${url.toString()}`)
  return url
}

function validateRoutePath(value, label) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) throw new Error(`${label} must be an absolute-path reference`)
  if (/[\u0000-\u001F\u007F]/.test(value) || value.includes('#')) throw new Error(`${label} contains a control character or fragment`)
  const parsed = new URL(value, 'http://127.0.0.1')
  for (const key of parsed.searchParams.keys()) if (CREDENTIAL_QUERY.test(key)) throw new Error(`${label} contains credential-like query parameter: ${key}`)
  return value
}

function validateManifest(raw) {
  const manifest = typeof raw === 'string' ? JSON.parse(raw) : raw
  if (!manifest || !Array.isArray(manifest.routes) || manifest.routes.length === 0) throw new Error('Manifest must contain a non-empty routes array')
  const ids = new Set()
  const routes = manifest.routes.map((route, index) => {
    if (!route || typeof route !== 'object' || !route.id || typeof route.legacyPath !== 'string' || typeof route.previewPath !== 'string') {
      throw new Error(`Manifest route ${index + 1} needs id, legacyPath and previewPath`)
    }
    const id = String(route.id)
    if (!/^[a-z0-9][a-z0-9-]*$/.test(id)) throw new Error(`Invalid route id: ${id}`)
    if (ids.has(id)) throw new Error(`Duplicate route id: ${id}`)
    ids.add(id)
    const publicOwner = route.publicOwner || 'php'
    const previewIndexing = route.previewIndexing || 'noindex'
    if (!['php', 'excluded'].includes(publicOwner)) throw new Error(`Invalid publicOwner for ${id}: ${publicOwner}`)
    if (!['noindex', 'excluded'].includes(previewIndexing)) throw new Error(`Invalid previewIndexing for ${id}: ${previewIndexing}`)
    if (route.expectedCanonicalPath !== undefined) validateRoutePath(route.expectedCanonicalPath, `${id}.expectedCanonicalPath`)
    if (route.expectedRedirectStatus !== undefined && ![301, 302, 303, 307, 308].includes(Number(route.expectedRedirectStatus))) throw new Error(`Invalid expectedRedirectStatus for ${id}`)
    return {
      ...route,
      id,
      legacyPath: validateRoutePath(route.legacyPath, `${id}.legacyPath`),
      previewPath: validateRoutePath(route.previewPath, `${id}.previewPath`),
      expectedCanonicalPath: route.expectedCanonicalPath === undefined ? null : validateRoutePath(route.expectedCanonicalPath, `${id}.expectedCanonicalPath`),
      expectedRedirectStatus: route.expectedRedirectStatus === undefined ? null : Number(route.expectedRedirectStatus),
      publicOwner,
      previewIndexing,
    }
  })
  return { ...manifest, routes }
}

function joinOrigin(origin, routePath) {
  return new URL(routePath, `${origin}/`).toString()
}

function decodeEntities(value) {
  return String(value || '')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;|&apos;/gi, "'")
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&amp;/gi, '&')
    .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)))
    .replace(/&#x([\da-f]+);/gi, (_, code) => String.fromCodePoint(parseInt(code, 16)))
}

function stripMarkup(value) {
  return decodeEntities(String(value || '').replace(/<script\b[^>]*>[\s\S]*?<\/script>|<style\b[^>]*>[\s\S]*?<\/style>/gi, '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim())
}

function tags(html, name) {
  return [...String(html || '').matchAll(new RegExp(`<${name}\\b[^>]*>`, 'gi'))].map(match => match[0])
}

function attribute(tag, name) {
  const match = String(tag).match(new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`, 'i'))
  if (match) return decodeEntities(match[2])
  const unquoted = String(tag).match(new RegExp(`\\b${name}\\s*=\\s*([^\\s>]+)`, 'i'))
  return unquoted ? decodeEntities(unquoted[1]) : ''
}

function metaValue(html, attributeName, attributeValue) {
  const wanted = String(attributeValue).toLowerCase()
  const tag = tags(html, 'meta').find(item => attribute(item, attributeName).toLowerCase() === wanted)
  return tag ? attribute(tag, 'content') : ''
}

function linkValue(html, rel) {
  const wanted = String(rel).toLowerCase()
  const tag = tags(html, 'link').find(item => attribute(item, 'rel').toLowerCase().split(/\s+/).includes(wanted))
  return tag ? attribute(tag, 'href') : ''
}

function elementTexts(html, name) {
  return [...String(html || '').matchAll(new RegExp(`<${name}\\b[^>]*>([\\s\\S]*?)<\\/${name}>`, 'gi'))]
    .map(match => stripMarkup(match[1])).filter(Boolean)
}

function jsonLdTypes(value, output = new Set()) {
  if (Array.isArray(value)) for (const item of value) jsonLdTypes(item, output)
  else if (value && typeof value === 'object') {
    if (typeof value['@type'] === 'string') output.add(value['@type'])
    if (Array.isArray(value['@type'])) for (const item of value['@type']) if (typeof item === 'string') output.add(item)
    for (const item of Object.values(value)) jsonLdTypes(item, output)
  }
  return output
}

function parseJsonLd(html) {
  const blocks = [...String(html || '').matchAll(/<script\b[^>]*type\s*=\s*(["'])application\/ld\+json\1[^>]*>([\s\S]*?)<\/script>/gi)]
  const types = new Set()
  const errors = []
  for (const block of blocks) {
    try { jsonLdTypes(JSON.parse(block[2].trim()), types) } catch { errors.push('invalid application/ld+json') }
  }
  return { blocks: blocks.length, types: [...types].sort(), errors }
}

function parseHrefs(html, finalUrl) {
  const base = new URL(finalUrl)
  const links = []
  for (const tag of tags(html, 'a')) {
    const raw = attribute(tag, 'href')
    if (!raw) continue
    try {
      const absolute = new URL(raw, base)
      absolute.hash = ''
      links.push({ raw, absolute: absolute.toString(), path: `${absolute.pathname}${absolute.search}`, internal: absolute.origin === base.origin })
    } catch { links.push({ raw, absolute: '', path: '', internal: false, invalid: true }) }
  }
  const unique = new Map()
  for (const link of links) unique.set(`${link.absolute}|${link.raw}`, link)
  return [...unique.values()].sort((left, right) => `${left.path}|${left.raw}`.localeCompare(`${right.path}|${right.raw}`))
}

function parseDocument(html, finalUrl) {
  const canonicalRaw = linkValue(html, 'canonical')
  let canonical = ''
  try { canonical = canonicalRaw ? new URL(canonicalRaw, finalUrl).toString() : '' } catch { canonical = canonicalRaw }
  return {
    title: elementTexts(html, 'title')[0] || '',
    h1: elementTexts(html, 'h1'),
    canonical,
    robots: metaValue(html, 'name', 'robots'),
    og: {
      type: metaValue(html, 'property', 'og:type'),
      title: metaValue(html, 'property', 'og:title'),
      description: metaValue(html, 'property', 'og:description'),
      url: metaValue(html, 'property', 'og:url'),
      image: metaValue(html, 'property', 'og:image'),
    },
    jsonLd: parseJsonLd(html),
    hrefs: parseHrefs(html, finalUrl),
  }
}

async function get(url, settings) {
  const redirectChain = []
  const visited = new Set()
  let current = url
  for (let hop = 0; hop <= settings.maxRedirects; hop += 1) {
    const target = validateTarget(current, settings.allowRemote)
    current = target.toString()
    if (visited.has(current)) return { status: null, finalUrl: current, redirectChain, error: 'redirect loop detected' }
    visited.add(current)
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), settings.timeoutMs)
    let response
    try {
      response = await fetch(current, {
        method: 'GET',
        redirect: 'manual',
        headers: { accept: 'text/html, text/plain, application/xml;q=0.9, */*;q=0.1', 'user-agent': 'pts-local-seo-audit/1.0' },
        signal: controller.signal,
      })
    } catch (error) {
      return { status: null, finalUrl: current, redirectChain, error: String(error.message || error).slice(0, 500) }
    } finally { clearTimeout(timer) }
    const location = response.headers.get('location') || ''
    if (response.status >= 300 && response.status < 400 && location) {
      let next
      try { next = new URL(location, current).toString(); validateTarget(next, settings.allowRemote) } catch (error) {
        return { status: response.status, finalUrl: current, redirectChain, error: String(error.message || error).slice(0, 500) }
      }
      redirectChain.push({ status: response.status, from: current, location, to: next })
      if (hop === settings.maxRedirects) return { status: response.status, finalUrl: current, redirectChain, error: 'maximum redirect count exceeded' }
      current = next
      continue
    }
    const body = await response.text().catch(() => '')
    return {
      status: response.status,
      finalUrl: current,
      redirectChain,
      headers: { contentType: response.headers.get('content-type') || '', robots: response.headers.get('x-robots-tag') || '' },
      body,
    }
  }
  return { status: null, finalUrl: current, redirectChain, error: 'maximum redirect count exceeded' }
}

function parseRobots(body) {
  const lines = String(body || '').split(/\r?\n/).map(line => line.replace(/#.*/, '').trim()).filter(Boolean)
  return {
    userAgents: lines.filter(line => /^user-agent\s*:/i.test(line)).map(line => line.replace(/^user-agent\s*:\s*/i, '')),
    disallow: lines.filter(line => /^disallow\s*:/i.test(line)).map(line => line.replace(/^disallow\s*:\s*/i, '')),
    allow: lines.filter(line => /^allow\s*:/i.test(line)).map(line => line.replace(/^allow\s*:\s*/i, '')),
    sitemaps: lines.filter(line => /^sitemap\s*:/i.test(line)).map(line => line.replace(/^sitemap\s*:\s*/i, '')),
  }
}

function parseSitemap(body) {
  const locs = [...String(body || '').matchAll(/<loc>([\s\S]*?)<\/loc>/gi)].map(match => decodeEntities(stripMarkup(match[1])))
  return { kind: /<sitemapindex\b/i.test(body) ? 'index' : 'urlset', locs, count: locs.length }
}

function summarizeResource(result, kind) {
  const summary = { status: result.status, finalUrl: result.finalUrl, redirectChain: result.redirectChain, error: result.error || null, contentType: result.headers?.contentType || '' }
  if (kind === 'robots') summary.robots = parseRobots(result.body)
  if (kind === 'sitemap') summary.sitemap = parseSitemap(result.body)
  return summary
}

function noindexValue(value) {
  return /(?:^|[\s,])noindex(?:$|[\s,])/i.test(String(value || '').replace(/\s+/g, ' '))
}

function ownership(side, page, route) {
  const previewNoindex = side === 'preview' ? noindexValue(page.robots) : null
  return {
    publicOwner: route.publicOwner,
    previewIndexing: route.previewIndexing,
    previewNoindex,
    status: side === 'legacy' ? 'public-candidate' : previewNoindex ? 'preview-noindex' : 'preview-indexable-risk',
    canonicalPresent: Boolean(page.canonical),
  }
}

function urlPath(value) {
  try { return new URL(value).pathname } catch { return '' }
}

function routeChecks(route, legacy, preview) {
  const legacyPage = legacy.page || { title: '', h1: [], canonical: '', robots: '', og: {}, jsonLd: { types: [] }, hrefs: [] }
  const previewPage = preview.page || { title: '', h1: [], canonical: '', robots: '', og: {}, jsonLd: { types: [] }, hrefs: [] }
  return {
    legacyReachable: legacy.status !== null && legacy.status < 500,
    previewReachable: preview.status !== null && preview.status < 500,
    legacyStatusObserved: legacy.status,
    previewStatusObserved: preview.status,
    legacyCanonicalPresent: Boolean(legacyPage.canonical),
    legacyRobotsPresent: Boolean(legacyPage.robots || legacy.headers?.robots),
    previewNoindex: noindexValue(previewPage.robots || preview.headers?.robots),
    expectedRedirectStatus: route.expectedRedirectStatus,
    redirectStatusPass: route.expectedRedirectStatus === null || legacy.redirectChain[0]?.status === route.expectedRedirectStatus,
    expectedCanonicalPath: route.expectedCanonicalPath,
    legacyCanonicalPath: urlPath(legacyPage.canonical),
    canonicalPathPass: route.expectedCanonicalPath === null || urlPath(legacyPage.canonical) === route.expectedCanonicalPath,
    legacyOgBasicPresent: Boolean(legacyPage.og.type && legacyPage.og.title && legacyPage.og.description && legacyPage.og.url && legacyPage.og.image),
    previewOgBasicPresent: Boolean(previewPage.og.type && previewPage.og.title && previewPage.og.description && previewPage.og.url && previewPage.og.image),
    legacyJsonLdTypes: legacyPage.jsonLd.types,
    previewJsonLdTypes: previewPage.jsonLd.types,
  }
}

function hrefMapping(legacyPage, previewPage) {
  const byPath = links => [...new Set((links || []).filter(item => item.internal).map(item => item.path))].sort()
  const legacy = byPath(legacyPage.hrefs)
  const preview = byPath(previewPage.hrefs)
  return {
    legacyInternalPaths: legacy,
    previewInternalPaths: preview,
    onlyLegacy: legacy.filter(item => !preview.includes(item)),
    onlyPreview: preview.filter(item => !legacy.includes(item)),
  }
}

async function crawlSide(origin, routePath, settings) {
  const requestedUrl = joinOrigin(origin, routePath)
  const result = await get(requestedUrl, settings)
  const page = result.body && result.finalUrl ? parseDocument(result.body, result.finalUrl) : null
  return { requestedUrl, status: result.status, finalUrl: result.finalUrl, redirectChain: result.redirectChain, headers: result.headers || {}, error: result.error || null, page }
}

function renderMarkdown(report) {
  const lines = [
    '# Migration SEO/status audit', '',
    `- Generated: ${report.generatedAt}`,
    `- PHP origin: \`${report.phpOrigin}\``,
    `- Nuxt preview: \`${report.nuxtOrigin}\``,
    `- Read-only GET crawl; loopback-only: ${report.allowRemote ? 'disabled by explicit --allow-remote' : 'enabled'}`,
    '',
    'A HTTP 200 is not a parity decision. Review status/redirects, public PHP canonical ownership, preview noindex, metadata, JSON-LD, href mapping and site resources together.', '',
    '## Site resources', '',
    '| Origin | robots.txt | sitemap.xml |', '| --- | ---: | ---: |',
  ]
  for (const side of ['php', 'nuxt']) lines.push(`| ${side} | ${report.resources[side].robots.status ?? 'ERR'} | ${report.resources[side].sitemap.status ?? 'ERR'} |`)
  lines.push('', '## Routes', '', '| Route | PHP | Nuxt | Redirect hops | Ownership / checks |', '| --- | ---: | ---: | ---: | --- |')
  for (const route of report.routes) {
    const check = route.checks
    const flags = [
      check.previewNoindex ? 'preview noindex' : 'preview noindex missing',
      check.legacyCanonicalPresent ? 'PHP canonical' : 'PHP canonical missing',
      check.legacyOgBasicPresent ? 'PHP OG' : 'PHP OG incomplete',
      check.previewOgBasicPresent ? 'preview OG' : 'preview OG incomplete',
      `JSON-LD PHP:${check.legacyJsonLdTypes.join(',') || '—'} Nuxt:${check.previewJsonLdTypes.join(',') || '—'}`,
    ]
    lines.push(`| ${route.id} | ${route.legacy.status ?? 'ERR'} | ${route.preview.status ?? 'ERR'} | ${route.legacy.redirectChain.length}/${route.preview.redirectChain.length} | ${flags.join('; ')} |`)
  }
  lines.push('', '## Remaining gates', '', '- 301/302/307/308 aliases: verify every intended alias has the expected destination and no loop.', '- 404/410: verify missing, hidden and retired routes do not become soft-200 pages.', '- Canonical/OG/robots: PHP remains public metadata owner; Nuxt preview must stay noindex until cutover approval.', '- Sitemap/robots: sitemap URLs must be canonical and reachable; robots must expose the intended sitemap without staging leakage.', '- JSON-LD: compare types and entity URLs to canonical; structured data must not be accepted from status 200 alone.', '- Account, Admin, media transforms and public APIs remain PHP-owned/excluded gates unless separately approved.')
  return `${lines.join('\n')}\n`
}

async function run(args) {
  const manifest = validateManifest(JSON.parse(await fs.readFile(args.manifest, 'utf8')))
  await fs.mkdir(args.output, { recursive: true })
  const settings = { allowRemote: args.allowRemote, timeoutMs: args.timeoutMs, maxRedirects: args.maxRedirects }
  const report = { generatedAt: new Date().toISOString(), phpOrigin: args.phpOrigin, nuxtOrigin: args.nuxtOrigin, allowRemote: args.allowRemote, manifest: args.manifest, routes: [], resources: { php: {}, nuxt: {} } }
  for (const [side, origin] of [['php', args.phpOrigin], ['nuxt', args.nuxtOrigin]]) {
    const robots = await get(joinOrigin(origin, '/robots.txt'), settings)
    const sitemap = await get(joinOrigin(origin, '/sitemap.xml'), settings)
    report.resources[side] = { robots: summarizeResource(robots, 'robots'), sitemap: summarizeResource(sitemap, 'sitemap') }
  }
  for (const route of manifest.routes) {
    const [legacy, preview] = await Promise.all([
      crawlSide(args.phpOrigin, route.legacyPath, settings),
      crawlSide(args.nuxtOrigin, route.previewPath, settings),
    ])
    const legacyPage = legacy.page || { hrefs: [] }
    const previewPage = preview.page || { hrefs: [] }
    report.routes.push({
      id: route.id,
      kind: route.kind || 'core',
      publicOwner: route.publicOwner,
      previewIndexing: route.previewIndexing,
      expectedCanonicalPath: route.expectedCanonicalPath || null,
      legacy,
      preview,
      ownership: { legacy: ownership('legacy', legacyPage, route), preview: ownership('preview', previewPage, route) },
      checks: routeChecks(route, legacy, preview),
      hrefMapping: hrefMapping(legacyPage, previewPage),
    })
    console.log(`${route.id}: php=${legacy.status ?? 'ERR'} nuxt=${preview.status ?? 'ERR'} redirects=${legacy.redirectChain.length}/${preview.redirectChain.length}`)
  }
  await fs.writeFile(path.join(args.output, 'report.json'), `${JSON.stringify(report, null, 2)}\n`)
  await fs.writeFile(path.join(args.output, 'summary.md'), renderMarkdown(report))
  return report
}

if (require.main === module) {
  (async () => {
    try {
      const args = parseArgs(process.argv.slice(2))
      if (args.help) { console.log(usage()); return }
      await run(args)
    } catch (error) {
      console.error(`migration-seo-audit: ${error.message || error}`)
      process.exitCode = 1
    }
  })()
}

module.exports = {
  parseArgs,
  validateOrigin,
  validateTarget,
  validateRoutePath,
  validateManifest,
  parseDocument,
  parseRobots,
  parseSitemap,
  noindexValue,
  renderMarkdown,
}

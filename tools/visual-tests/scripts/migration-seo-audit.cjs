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
const NEGATIVE_FIXTURE_STATES = new Set(['verified-hidden', 'NEEDS VERIFICATION'])
const NEGATIVE_ROUTE_FAMILIES = new Set(['catalog', 'pdp', 'news', 'project', 'solution'])
const EXPECTED_NEGATIVE_STATUSES = new Set([404, 410])
const MAX_SITEMAPS = 1000
const MAX_SITEMAP_URLS = 50000
const MAX_HREF_TARGETS = 5000

function usage() {
  return `Usage: node scripts/migration-seo-audit.cjs <php-origin> <nuxt-origin> [options]

Options:
  --manifest <file>       JSON route map (default: tools/visual-tests/fixtures/migration-routes.json)
  --output <dir>          Artifact directory (default: frontend/.migration-artifacts/seo-audit)
  --timeout-ms <number>   Per GET timeout (default: 15000)
  --max-redirects <n>     Redirect hop limit (default: 10)
  --public-paths          Audit public legacy paths on Nuxt instead of internal preview paths
  --allow-remote          Explicitly disable loopback-only protection
  --help                  Show this help

Example:
  node scripts/migration-seo-audit.cjs http://127.0.0.1:8080 http://127.0.0.1:3100 \\
    --manifest tools/visual-tests/fixtures/migration-routes.json \\
    --output frontend/.migration-artifacts/seo-audit`
}

function parseArgs(argv) {
  const args = { allowRemote: false, publicPaths: false }
  const positionals = []
  for (let index = 0; index < argv.length; index += 1) {
    const value = argv[index]
    if (value === '--help' || value === '-h') return { help: true }
    if (value === '--allow-remote') { args.allowRemote = true; continue }
    if (value === '--public-paths') { args.publicPaths = true; continue }
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
  const negativeFixtures = (manifest.negativeFixtures || []).map((fixture, index) => {
    if (!fixture || typeof fixture !== 'object' || !fixture.id || !fixture.family) throw new Error(`Negative fixture ${index + 1} needs id and family`)
    const id = String(fixture.id)
    if (!/^[a-z0-9][a-z0-9-]*$/.test(id)) throw new Error(`Invalid negative fixture id: ${id}`)
    if (ids.has(id)) throw new Error(`Duplicate manifest id: ${id}`)
    ids.add(id)
    if (!NEGATIVE_ROUTE_FAMILIES.has(fixture.family)) throw new Error(`Invalid negative fixture family for ${id}`)
    if (!NEGATIVE_FIXTURE_STATES.has(fixture.verification)) throw new Error(`Invalid verification state for negative fixture ${id}`)
    if (fixture.expectedStatuses !== undefined && !Array.isArray(fixture.expectedStatuses)) throw new Error(`Invalid expectedStatuses for negative fixture ${id}`)
    const expectedStatuses = fixture.expectedStatuses === undefined ? [404, 410] : fixture.expectedStatuses.map(Number)
    if (expectedStatuses.length === 0 || expectedStatuses.some(status => !EXPECTED_NEGATIVE_STATUSES.has(status))) {
      throw new Error(`Invalid expectedStatuses for negative fixture ${id}`)
    }
    if (fixture.verification === 'verified-hidden') {
      if (typeof fixture.slug !== 'string' || !fixture.slug || typeof fixture.legacyPath !== 'string') throw new Error(`Verified hidden fixture ${id} needs a DB-backed slug and legacyPath`)
      if (!fixture.databaseEvidence || typeof fixture.databaseEvidence !== 'object') throw new Error(`Verified hidden fixture ${id} needs databaseEvidence`)
    } else if (fixture.slug || fixture.legacyPath) {
      throw new Error(`Unverified fixture ${id} must not contain an invented slug or route path`)
    }
    return {
      ...fixture,
      id,
      expectedStatuses,
      legacyPath: fixture.legacyPath === undefined || fixture.legacyPath === null ? null : validateRoutePath(fixture.legacyPath, `${id}.legacyPath`),
    }
  })
  return { ...manifest, routes, negativeFixtures }
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

function documentBase(html, finalUrl) {
  const pageUrl = new URL(finalUrl)
  const declared = attribute(tags(html, 'base')[0] || '', 'href')
  if (!declared) return pageUrl
  try { return new URL(declared, pageUrl) } catch { return pageUrl }
}

function parseHrefs(html, finalUrl) {
  const pageOrigin = new URL(finalUrl).origin
  const resolutionBase = documentBase(html, finalUrl)
  const links = []
  for (const tag of tags(html, 'a')) {
    const raw = attribute(tag, 'href')
    if (!raw) continue
    try {
      const absolute = new URL(raw, resolutionBase)
      absolute.hash = ''
      links.push({ raw, absolute: absolute.toString(), path: `${absolute.pathname}${absolute.search}`, internal: absolute.origin === pageOrigin })
    } catch { links.push({ raw, absolute: '', path: '', internal: false, invalid: true }) }
  }
  const unique = new Map()
  for (const link of links) unique.set(`${link.absolute}|${link.raw}`, link)
  return [...unique.values()].sort((left, right) => `${left.path}|${left.raw}`.localeCompare(`${right.path}|${right.raw}`))
}

function parseDocument(html, finalUrl) {
  const resolutionBase = documentBase(html, finalUrl)
  const canonicalRaw = linkValue(html, 'canonical')
  let canonical = ''
  try { canonical = canonicalRaw ? new URL(canonicalRaw, resolutionBase).toString() : '' } catch { canonical = canonicalRaw }
  return {
    title: elementTexts(html, 'title')[0] || '',
    h1: elementTexts(html, 'h1'),
    canonical,
    documentBase: resolutionBase.toString(),
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
      if (settings.readOnlyHrefAudit) {
        const nextTarget = new URL(next)
        const unsafeRedirectReason = hrefTargetSafetyReason(nextTarget.pathname + nextTarget.search)
        if (unsafeRedirectReason) {
          await response.body?.cancel().catch(() => {})
          return { status: response.status, finalUrl: current, redirectChain, unsafeRedirectReason }
        }
      }
      if (hop === settings.maxRedirects) return { status: response.status, finalUrl: current, redirectChain, error: 'maximum redirect count exceeded' }
      if (settings.readBody === false) await response.body?.cancel().catch(() => {})
      current = next
      continue
    }
    if (settings.readBody === false) {
      await response.body?.cancel().catch(() => {})
      return {
        status: response.status,
        finalUrl: current,
        redirectChain,
        headers: { contentType: response.headers.get('content-type') || '', robots: response.headers.get('x-robots-tag') || '' },
        body: '',
      }
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
  const locs = [...String(body || '').matchAll(/<(?:[\w.-]+:)?loc\b[^>]*>([\s\S]*?)<\/(?:[\w.-]+:)?loc\s*>/gi)].map(match => decodeEntities(stripMarkup(match[1])))
  return { kind: /<(?:[\w.-]+:)?sitemapindex\b/i.test(body) ? 'index' : 'urlset', locs, count: locs.length }
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

function hasNoindex(metaRobots, headerRobots) {
  // Staging is protected at the edge with X-Robots-Tag. A permissive HTML
  // robots meta must not mask that stricter response-level directive.
  return noindexValue(metaRobots) || noindexValue(headerRobots)
}

function ownership(side, page, route, headers = {}) {
  const previewNoindex = side === 'preview' ? hasNoindex(page.robots, headers.robots) : null
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
    previewNoindex: hasNoindex(previewPage.robots, preview.headers?.robots),
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
    decision: 'manual-review-only',
  }
}

function hrefTargetSafetyReason(routePath) {
  try { validateRoutePath(routePath, 'internal href') } catch (error) {
    return /credential-like query parameter/.test(String(error.message)) ? 'credential-like query parameter' : 'invalid internal path'
  }
  const target = new URL(routePath, 'http://127.0.0.1')
  if (/^\/(?:api|administrator)(?:\/|$)/i.test(target.pathname)) return 'protected or API route'
  if (/(?:^|\/)(?:logout|signout|delete|remove|clear|mutate|submit|callback)(?:\/|$)/i.test(target.pathname)) return 'potential mutation endpoint'
  const mutationToken = /(?:^|[-_])(?:add|remove|delete|clear|logout|signout|submit|checkout|payment|order|cart|buy|mutate|callback)(?:[-_]|$)/i
  for (const [key, value] of target.searchParams) {
    if (mutationToken.test(key)) return 'potential mutation query parameter'
    if (/^(?:action|do|op|operation|command|cmd|task|mode)$/i.test(key) && /^(?:add|create|update|edit|delete|remove|clear|logout|signout|submit|checkout|payment|order|cart|buy|mutate)$/i.test(value)) {
      return 'potential mutation action'
    }
  }
  return ''
}

async function auditHrefTargets(routes, legacyOrigin, previewOrigin, settings) {
  const paths = { legacy: new Set(), preview: new Set() }
  for (const route of routes) {
    for (const href of route.hrefMapping.onlyLegacy) paths.legacy.add(href)
    for (const href of route.hrefMapping.onlyPreview) paths.preview.add(href)
  }
  const entries = [
    ...[...paths.legacy].map(routePath => ({ side: 'legacy', origin: legacyOrigin, routePath })),
    ...[...paths.preview].map(routePath => ({ side: 'preview', origin: previewOrigin, routePath })),
  ]
  const limitErrors = entries.length > MAX_HREF_TARGETS ? [`Unique href target count exceeded ${MAX_HREF_TARGETS}`] : []
  const results = await mapConcurrent(entries.slice(0, MAX_HREF_TARGETS), 8, async item => {
    const unsafeReason = hrefTargetSafetyReason(item.routePath)
    if (unsafeReason) return { side: item.side, href: item.routePath, status: null, finalUrl: '', redirectChain: [], skipped: true, reason: unsafeReason, pass: false }
    let requestedUrl
    try { requestedUrl = joinOrigin(item.origin, item.routePath) } catch {
      return { side: item.side, href: item.routePath, status: null, finalUrl: '', redirectChain: [], skipped: true, reason: 'invalid target', pass: false }
    }
    const crawl = await get(requestedUrl, { ...settings, readBody: false, readOnlyHrefAudit: true })
    if (crawl.unsafeRedirectReason) {
      return { side: item.side, href: item.routePath, requestedUrl, status: crawl.status, finalUrl: crawl.finalUrl, redirectChain: crawl.redirectChain, skipped: true, reason: `unsafe redirect target: ${crawl.unsafeRedirectReason}`, pass: false }
    }
    return {
      side: item.side,
      href: item.routePath,
      requestedUrl,
      status: crawl.status,
      finalUrl: crawl.finalUrl,
      redirectChain: crawl.redirectChain,
      error: crawl.error || null,
      skipped: false,
      pass: crawl.status === 200,
    }
  })
  const skippedTargets = results.filter(item => item.skipped)
  const failedTargets = results.filter(item => !item.skipped && !item.pass)
  const statusCounts = {}
  for (const item of results) {
    const key = item.skipped ? 'SKIPPED' : item.status === null ? 'ERR' : String(item.status)
    statusCounts[key] = (statusCounts[key] || 0) + 1
  }
  return {
    status: failedTargets.length ? 'FAIL' : skippedTargets.length || limitErrors.length ? 'NEEDS VERIFICATION' : 'PASS',
    targetCount: entries.length,
    checkedCount: results.length - skippedTargets.length,
    skippedCount: skippedTargets.length,
    failedCount: failedTargets.length,
    statusCounts,
    limitErrors,
    failedTargets,
    skippedTargets,
  }
}

function evaluateNegativeFixture(fixture, crawl) {
  if (fixture.verification !== 'verified-hidden') return { status: 'NEEDS VERIFICATION', reason: fixture.reason || 'No DB-backed hidden record is available; no synthetic URL was created.' }
  const page = crawl.page
  const statusPass = fixture.expectedStatuses.includes(crawl.status) && crawl.redirectChain.length === 0
  const canonicalAbsent = !page?.canonical
  return {
    status: statusPass && canonicalAbsent ? 'PASS' : 'FAIL',
    observedStatus: crawl.status,
    finalUrl: crawl.finalUrl,
    redirectChain: crawl.redirectChain,
    canonical: page?.canonical || '',
    checks: { expectedStatus: statusPass, noCanonical: canonicalAbsent },
    error: crawl.error,
  }
}

async function mapConcurrent(items, concurrency, task) {
  const results = new Array(items.length)
  let next = 0
  const workers = Array.from({ length: Math.min(concurrency, items.length) }, async () => {
    while (true) {
      const index = next++
      if (index >= items.length) return
      results[index] = await task(items[index], index)
    }
  })
  await Promise.all(workers)
  return results
}

async function auditSitemap(rootRequestedUrl, rootResult, settings) {
  const sitemapUrls = new Set([new URL(rootRequestedUrl).toString()])
  const queue = [{ url: new URL(rootRequestedUrl).toString(), result: rootResult, parent: null }]
  const sitemapResults = []
  const pageUrlMap = new Map()
  const limitErrors = []

  while (queue.length) {
    const item = queue.shift()
    const result = item.result || await get(item.url, settings)
    const record = {
      loc: item.url,
      parent: item.parent,
      status: result.status,
      finalUrl: result.finalUrl,
      redirectChain: result.redirectChain,
      error: result.error || null,
      kind: null,
      locCount: 0,
      canonicalUrlPass: result.status === 200 && result.finalUrl === item.url,
    }
    if (result.status === 200 && result.body) {
      const parsed = parseSitemap(result.body)
      record.kind = parsed.kind
      record.locCount = parsed.count
      if (parsed.kind === 'index') {
        for (const loc of parsed.locs) {
          let child
          try { child = validateTarget(loc, settings.allowRemote).toString() } catch (error) {
            sitemapResults.push({ loc, parent: item.url, status: null, finalUrl: '', redirectChain: [], error: String(error.message || error), kind: 'blocked-sitemap', locCount: 0, canonicalUrlPass: false })
            continue
          }
          if (sitemapUrls.has(child)) continue
          if (sitemapUrls.size >= MAX_SITEMAPS) {
            limitErrors.push(`Sitemap count exceeded ${MAX_SITEMAPS}`)
            break
          }
          sitemapUrls.add(child)
          queue.push({ url: child, result: null, parent: item.url })
        }
      } else {
        for (const loc of parsed.locs) {
          if (!pageUrlMap.has(loc)) pageUrlMap.set(loc, { loc, sourceSitemap: item.url })
          if (pageUrlMap.size > MAX_SITEMAP_URLS) {
            limitErrors.push(`Sitemap URL count exceeded ${MAX_SITEMAP_URLS}`)
            break
          }
        }
      }
    }
    sitemapResults.push(record)
  }

  const pageResults = await mapConcurrent([...pageUrlMap.values()], 8, async item => {
    let declaredUrl
    try { declaredUrl = validateTarget(item.loc, settings.allowRemote).toString() } catch (error) {
      return { ...item, requestedUrl: '', status: null, finalUrl: '', redirectChain: [], error: String(error.message || error), canonicalUrlPass: false, pass: false }
    }
    const result = await get(declaredUrl, settings)
    const canonicalUrlPass = result.status === 200 && result.finalUrl === declaredUrl
    return {
      ...item,
      requestedUrl: declaredUrl,
      status: result.status,
      finalUrl: result.finalUrl,
      redirectChain: result.redirectChain,
      error: result.error || null,
      canonicalUrlPass,
      pass: canonicalUrlPass,
    }
  })
  const failedSitemaps = sitemapResults.filter(item => !item.canonicalUrlPass)
  const failedUrls = pageResults.filter(item => !item.pass)
  const status = failedSitemaps.length || failedUrls.length || limitErrors.length ? 'FAIL' : sitemapResults.length ? 'PASS' : 'FAIL'
  return {
    status,
    rootUrl: rootRequestedUrl,
    sitemapCount: sitemapResults.length,
    pageUrlCount: pageResults.length,
    sitemapResults,
    pageResults,
    failedSitemaps,
    failedUrls,
    limitErrors,
  }
}

async function auditNegativeFixtures(fixtures, origin, settings) {
  const results = []
  for (const fixture of fixtures) {
    if (fixture.verification !== 'verified-hidden') {
      results.push({ id: fixture.id, family: fixture.family, verification: fixture.verification, databaseEvidence: fixture.databaseEvidence || null, ...evaluateNegativeFixture(fixture, null) })
      continue
    }
    const crawl = await crawlSide(origin, fixture.legacyPath, settings)
    results.push({
      id: fixture.id,
      family: fixture.family,
      verification: fixture.verification,
      slug: fixture.slug,
      legacyPath: fixture.legacyPath,
      expectedStatuses: fixture.expectedStatuses,
      databaseEvidence: fixture.databaseEvidence,
      ...evaluateNegativeFixture(fixture, crawl),
    })
  }
  const failures = results.filter(item => item.status === 'FAIL').length
  const needsVerification = results.filter(item => item.status === 'NEEDS VERIFICATION').length
  return {
    status: failures ? 'FAIL' : needsVerification ? 'NEEDS VERIFICATION' : results.length ? 'PASS' : 'NEEDS VERIFICATION',
    fixtureCount: results.length,
    passCount: results.filter(item => item.status === 'PASS').length,
    failCount: failures,
    needsVerificationCount: needsVerification,
    results,
  }
}

function summarizeHrefGate(routes) {
  const incomplete = routes.filter(route => !route.legacy.page || !route.preview.page)
  const routesWithDifferences = routes.filter(route => route.hrefMapping.onlyLegacy.length || route.hrefMapping.onlyPreview.length)
  return {
    status: incomplete.length ? 'NEEDS VERIFICATION' : 'PASS',
    decision: 'manual-review-only',
    routeCount: routes.length,
    capturedRouteCount: routes.length - incomplete.length,
    routesWithDifferences: routesWithDifferences.map(route => ({
      id: route.id,
      onlyLegacy: route.hrefMapping.onlyLegacy,
      onlyPreview: route.hrefMapping.onlyPreview,
    })),
    note: 'Different internal href sets are listed for human review and do not fail the crawl automatically.',
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
  lines.push('', '## Negative route fixtures', '', `- Gate: **${report.gates.negativeFixtures.status}** (${report.negativeFixtures.passCount} PASS; ${report.negativeFixtures.failCount} FAIL; ${report.negativeFixtures.needsVerificationCount} NEEDS VERIFICATION).`, '', '| Fixture | Family | Verification | PHP status | Canonical | Result / DB evidence |', '| --- | --- | --- | ---: | --- | --- |')
  for (const item of report.negativeFixtures.results) {
    const evidence = item.databaseEvidence ? `${item.databaseEvidence.table} / ${item.databaseEvidence.type}; ${item.databaseEvidence.status ?? item.databaseEvidence.reason ?? ''}` : (item.reason || '—')
    lines.push(`| ${item.id} | ${item.family} | ${item.verification} | ${item.observedStatus ?? '—'} | ${item.canonical || '—'} | ${item.status}; ${evidence} |`)
  }
  lines.push('', '## Sitemap loc crawl (PHP public sitemap)', '', `- Gate: **${report.gates.sitemapLocCrawl.status}**; crawled ${report.sitemapAudit.pageUrlCount} unique URL loc(s) across ${report.sitemapAudit.sitemapCount} sitemap file(s).`, `- Failed sitemap locs: ${report.sitemapAudit.failedSitemaps.length}; failed page locs: ${report.sitemapAudit.failedUrls.length}.`)
  if (report.sitemapAudit.limitErrors.length) lines.push(`- Crawl limits: ${report.sitemapAudit.limitErrors.join('; ')}.`)
  if (report.sitemapAudit.failedSitemaps.length || report.sitemapAudit.failedUrls.length) {
    lines.push('', '| Failed loc | HTTP | Final URL | Error |', '| --- | ---: | --- | --- |')
    for (const item of [...report.sitemapAudit.failedSitemaps, ...report.sitemapAudit.failedUrls]) lines.push(`| ${item.loc} | ${item.status ?? 'ERR'} | ${item.finalUrl || '—'} | ${(item.error || (item.canonicalUrlPass ? '' : 'status/final URL mismatch')).replaceAll('|', '\\|')} |`)
  } else lines.push('- Failed URL list: none.')
  lines.push('', '## Internal href differences — manual review only', '', `- Capture gate: **${report.gates.hrefMapping.status}** across ${report.gates.hrefMapping.capturedRouteCount}/${report.gates.hrefMapping.routeCount} routes. Path-set differences do not fail the crawl automatically.`, `- Read-only GET reachability: **${report.gates.hrefTargetReachability.status}**; checked ${report.hrefTargetAudit.checkedCount}/${report.hrefTargetAudit.targetCount}; failed ${report.hrefTargetAudit.failedCount}; skipped ${report.hrefTargetAudit.skippedCount}.`, '', '| Route | onlyLegacy | onlyPreview |', '| --- | --- | --- |')
  const hrefRows = report.gates.hrefMapping.routesWithDifferences
  if (hrefRows.length) {
    for (const item of hrefRows) lines.push(`| ${item.id} | ${item.onlyLegacy.map(value => `\`${value}\``).join('<br>') || '—'} | ${item.onlyPreview.map(value => `\`${value}\``).join('<br>') || '—'} |`)
  } else lines.push('| — | — | — |')
  const hrefReviews = [...report.hrefTargetAudit.failedTargets, ...report.hrefTargetAudit.skippedTargets]
  if (hrefReviews.length) {
    lines.push('', '| Href requiring review | Side | HTTP | Final URL | Reason |', '| --- | --- | ---: | --- | --- |')
    for (const item of hrefReviews) lines.push(`| \`${item.href}\` | ${item.side} | ${item.status ?? '—'} | ${item.finalUrl || '—'} | ${(item.reason || item.error || 'non-200 response').replaceAll('|', '\\|')} |`)
  } else lines.push('- Href reachability review list: none; every checked href ended at HTTP 200.')
  lines.push('', '## Gate summary', '', '| Gate | Result | Evidence |', '| --- | --- | --- |', `| Negative hidden routes | ${report.gates.negativeFixtures.status} | ${report.negativeFixtures.passCount}/${report.negativeFixtures.fixtureCount} checked; unresolved DB fixture types ${report.negativeFixtures.needsVerificationCount}. |`, `| Sitemap loc crawl | ${report.gates.sitemapLocCrawl.status} | ${report.sitemapAudit.pageUrlCount} page locs; ${report.sitemapAudit.failedUrls.length} failed; ${report.sitemapAudit.failedSitemaps.length} sitemap files failed. |`, `| Href mapping | ${report.gates.hrefMapping.status} | ${hrefRows.length} route(s) have path differences; manual review, not an automatic failure. |`, `| Href target reachability | ${report.gates.hrefTargetReachability.status} | ${report.hrefTargetAudit.checkedCount}/${report.hrefTargetAudit.targetCount} checked; ${report.hrefTargetAudit.failedCount} non-200; ${report.hrefTargetAudit.skippedCount} skipped as unsafe. |`)
  lines.push('', '## Remaining gates', '', '- 301/302/307/308 aliases: verify every intended alias has the expected destination and no loop.', '- Canonical/OG/robots: PHP remains public metadata owner; Nuxt preview must stay noindex until cutover approval.', '- JSON-LD: compare types and entity URLs to canonical; structured data must not be accepted from status 200 alone.', '- Account, Admin, media transforms and public APIs remain PHP-owned/excluded gates unless separately approved.')
  return `${lines.join('\n')}\n`
}

async function run(args) {
  const manifest = validateManifest(JSON.parse(await fs.readFile(args.manifest, 'utf8')))
  await fs.mkdir(args.output, { recursive: true })
  const settings = { allowRemote: args.allowRemote, timeoutMs: args.timeoutMs, maxRedirects: args.maxRedirects }
  const report = { generatedAt: new Date().toISOString(), phpOrigin: args.phpOrigin, nuxtOrigin: args.nuxtOrigin, allowRemote: args.allowRemote, manifest: args.manifest, routes: [], negativeFixtures: { results: [] }, sitemapAudit: null, hrefTargetAudit: null, gates: {}, resources: { php: {}, nuxt: {} } }
  const rawResources = {}
  for (const [side, origin] of [['php', args.phpOrigin], ['nuxt', args.nuxtOrigin]]) {
    const robots = await get(joinOrigin(origin, '/robots.txt'), settings)
    const sitemap = await get(joinOrigin(origin, '/sitemap.xml'), settings)
    rawResources[side] = { robots, sitemap }
    report.resources[side] = { robots: summarizeResource(robots, 'robots'), sitemap: summarizeResource(sitemap, 'sitemap') }
  }
  for (const route of manifest.routes) {
    const [legacy, preview] = await Promise.all([
      crawlSide(args.phpOrigin, route.legacyPath, settings),
      crawlSide(args.nuxtOrigin, args.publicPaths ? route.legacyPath : route.previewPath, settings),
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
      ownership: { legacy: ownership('legacy', legacyPage, route), preview: ownership('preview', previewPage, route, preview.headers) },
      checks: routeChecks(route, legacy, preview),
      hrefMapping: hrefMapping(legacyPage, previewPage),
    })
    console.log(`${route.id}: php=${legacy.status ?? 'ERR'} nuxt=${preview.status ?? 'ERR'} redirects=${legacy.redirectChain.length}/${preview.redirectChain.length}`)
  }
  report.negativeFixtures = await auditNegativeFixtures(manifest.negativeFixtures, args.phpOrigin, settings)
  report.sitemapAudit = await auditSitemap(joinOrigin(args.phpOrigin, '/sitemap.xml'), rawResources.php.sitemap, settings)
  report.hrefTargetAudit = await auditHrefTargets(report.routes, args.phpOrigin, args.nuxtOrigin, settings)
  report.gates = {
    negativeFixtures: { status: report.negativeFixtures.status },
    sitemapLocCrawl: { status: report.sitemapAudit.status },
    hrefMapping: summarizeHrefGate(report.routes),
    hrefTargetReachability: { status: report.hrefTargetAudit.status },
  }
  for (const result of report.negativeFixtures.results) console.log(`negative:${result.id}=${result.status}${result.observedStatus ? ` php=${result.observedStatus}` : ''}`)
  console.log(`sitemap-loc-crawl=${report.sitemapAudit.status} pages=${report.sitemapAudit.pageUrlCount} failed=${report.sitemapAudit.failedUrls.length} sitemap-files=${report.sitemapAudit.sitemapCount}`)
  console.log(`href-mapping=${report.gates.hrefMapping.status} routes-with-differences=${report.gates.hrefMapping.routesWithDifferences.length} (manual review)`)
  console.log(`href-target-reachability=${report.gates.hrefTargetReachability.status} checked=${report.hrefTargetAudit.checkedCount}/${report.hrefTargetAudit.targetCount} failed=${report.hrefTargetAudit.failedCount} skipped=${report.hrefTargetAudit.skippedCount}`)
  await fs.writeFile(path.join(args.output, 'report.json'), `${JSON.stringify(report, null, 2)}\n`)
  await fs.writeFile(path.join(args.output, 'summary.md'), renderMarkdown(report))
  return report
}

if (require.main === module) {
  (async () => {
    try {
      const args = parseArgs(process.argv.slice(2))
      if (args.help) { console.log(usage()); return }
      const report = await run(args)
      if ([report.gates.negativeFixtures.status, report.gates.sitemapLocCrawl.status].some(status => status !== 'PASS')) process.exitCode = 1
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
  hrefMapping,
  hrefTargetSafetyReason,
  auditHrefTargets,
  evaluateNegativeFixture,
  auditSitemap,
  noindexValue,
  hasNoindex,
  renderMarkdown,
}

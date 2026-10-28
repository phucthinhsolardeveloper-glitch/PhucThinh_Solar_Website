import fs from 'node:fs'
import path from 'node:path'

const LOOPBACK_HOSTS = new Set(['127.0.0.1', 'localhost', '::1'])
const SAFE_METHODS = new Set(['GET', 'HEAD'])

export function readJson(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'))
}

export function validateLoopbackOrigin(value) {
  const url = new URL(value)
  if (!['http:', 'https:'].includes(url.protocol)) throw new Error('Origin must use http or https')
  if (!LOOPBACK_HOSTS.has(url.hostname)) throw new Error(`Remote origin blocked: ${url.hostname}`)
  if (url.username || url.password) throw new Error('Origin must not contain embedded credentials')
  if (url.pathname !== '/' || url.search || url.hash) throw new Error('Origin must not contain path, query or fragment')
  return url.origin
}

function validatePath(value, label) {
  if (typeof value !== 'string' || !value.startsWith('/') || value.startsWith('//')) {
    throw new Error(`${label} must be an absolute path`)
  }
  if (value.includes('?') || value.includes('#') || value.includes('..')) {
    throw new Error(`${label} must not contain query, fragment or traversal`)
  }
}

export function validateAllowlist(manifest) {
  if (!manifest || manifest.version !== 1) throw new Error('Allowlist version must be 1')
  if (manifest.featureFlag?.default !== '0') throw new Error('Feature flag must default to 0 (OFF)')
  if (manifest.featureFlag?.name !== 'PTS_NUXT_HYBRID_ENABLED') throw new Error('Unexpected feature flag name')
  const pilot = manifest.pilot
  if (!pilot || typeof pilot.id !== 'string') throw new Error('Pilot route is required')
  validatePath(pilot.publicPath, 'pilot.publicPath')
  validatePath(pilot.nuxtPath, 'pilot.nuxtPath')
  validatePath(pilot.previewHealthPath, 'pilot.previewHealthPath')
  if (!Array.isArray(pilot.methods) || pilot.methods.length === 0 || pilot.methods.some(method => !SAFE_METHODS.has(method))) {
    throw new Error('Pilot methods must be GET/HEAD only')
  }
  for (const key of ['phpOwnedPrefixes', 'forbiddenNuxtPrefixes']) {
    if (!Array.isArray(manifest[key]) || manifest[key].length === 0) throw new Error(`${key} must not be empty`)
    manifest[key].forEach((prefix, index) => validatePath(prefix, `${key}[${index}]`))
  }
  const blockedPrefix = [...manifest.phpOwnedPrefixes, ...manifest.forbiddenNuxtPrefixes]
    .find(prefix => pilot.publicPath.startsWith(prefix) || pilot.nuxtPath.startsWith(prefix))
  if (blockedPrefix) throw new Error(`Pilot overlaps protected prefix ${blockedPrefix}`)
  if (!manifest.forbiddenNuxtPrefixes.some(prefix => pilot.previewHealthPath.startsWith(prefix))) {
    throw new Error('pilot.previewHealthPath must stay under a forbidden preview prefix')
  }
  if (pilot.readiness !== 'blocked' && pilot.readiness !== 'ready') throw new Error('Pilot readiness must be blocked or ready')
  return manifest
}

export function auditRepository({ root, manifest }) {
  const caddyPath = path.join(root, 'deploy/vps/Caddyfile')
  const composePath = path.join(root, 'deploy/vps/docker-compose.yml')
  const offPath = path.join(root, 'deploy/local-nuxt-hybrid/Caddyfile.off')
  const caddy = fs.readFileSync(caddyPath, 'utf8')
  const compose = fs.readFileSync(composePath, 'utf8')
  const off = fs.readFileSync(offPath, 'utf8')
  const checks = [
    ['feature flag defaults OFF', manifest.featureFlag.default === '0'],
    ['production Caddy still sends traffic to PHP web:80', /reverse_proxy\s+web:80/.test(caddy)],
    ['production Caddy has no Nuxt upstream', !/reverse_proxy\s+nuxt(?::|\s)/i.test(caddy)],
    ['production Compose has no Nuxt service', !/^\s{2}nuxt:\s*$/m.test(compose)],
    ['local OFF fixture binds loopback only', /http:\/\/127\.0\.0\.1:8090/.test(off)],
    ['local OFF fixture sends traffic to PHP only', /reverse_proxy\s+127\.0\.0\.1:8080/.test(off) && !/3100/.test(off)],
    ['pilot is not ready to enable', manifest.pilot.readiness === 'blocked'],
    ['pilot does not expose preview routes', !manifest.forbiddenNuxtPrefixes.some(prefix => manifest.pilot.nuxtPath.startsWith(prefix))],
  ]
  return checks.map(([name, pass]) => ({ name, pass }))
}

export function validateApproval(approval, manifest, commit) {
  if (!approval || approval.approved !== true) throw new Error('Owner approval is not granted')
  if (typeof approval.approvedBy !== 'string' || approval.approvedBy.trim() === '') throw new Error('approvedBy is required')
  if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/.test(approval.approvedAt || '')) throw new Error('approvedAt must be UTC ISO seconds')
  if (!Array.isArray(approval.routeIds) || !approval.routeIds.includes(manifest.pilot.id)) throw new Error('Pilot route is not approved')
  if (approval.commit !== commit || !/^[0-9a-f]{40}$/.test(commit)) throw new Error('Approval commit does not match current commit')
  return true
}

export function rollbackPlan() {
  return [
    '# GENERATED ONLY — review on the approved production change ticket; this script executes nothing.',
    '# 1. Set PTS_NUXT_HYBRID_ENABLED=0 through the approved configuration-management step.',
    'docker compose config --quiet',
    'docker compose exec -T proxy caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile',
    'docker compose restart proxy',
    './scripts/verify-stack.sh',
    '# 2. Verify pilot, alias, 404/410 and protected PHP routes; record timestamp and metrics.',
    '# 3. Do not restore DB/upload: route rollback does not mutate either.',
  ].join('\n')
}

function requiredProbeResult(results, routePath, owner) {
  const result = results.find(item => item.path === routePath)
  if (!result) throw new Error(`${owner} probe result missing for ${routePath}`)
  return result
}

export function validateProbeResults({ manifest, php, nuxt }) {
  for (const result of [...php, ...nuxt]) {
    if (result.status >= 500) throw new Error(`Health probe received ${result.status} for ${result.path}`)
  }

  for (const routePath of ['/', manifest.pilot.publicPath]) {
    const result = requiredProbeResult(php, routePath, 'PHP')
    if (result.status !== 200) throw new Error(`PHP public health path ${routePath} expected 200, got ${result.status}`)
  }

  const preview = requiredProbeResult(nuxt, manifest.pilot.previewHealthPath, 'Nuxt')
  if (preview.status !== 200) {
    throw new Error(`Nuxt preview health path ${manifest.pilot.previewHealthPath} expected 200, got ${preview.status}`)
  }
  if (!preview.noindex) throw new Error('Nuxt preview is reachable without noindex')

  if (manifest.pilot.readiness === 'blocked') {
    for (const routePath of new Set(['/', manifest.pilot.nuxtPath])) {
      const result = requiredProbeResult(nuxt, routePath, 'Nuxt')
      if (result.status !== 404) {
        throw new Error(`Blocked pilot expects Nuxt public path ${routePath} to remain unowned (404), got ${result.status}`)
      }
    }
  } else {
    const publicPilot = requiredProbeResult(nuxt, manifest.pilot.nuxtPath, 'Nuxt')
    if (publicPilot.status !== 200) {
      throw new Error(`Ready pilot path ${manifest.pilot.nuxtPath} expected 200, got ${publicPilot.status}`)
    }
    if (publicPilot.noindex) throw new Error('Ready Nuxt pilot route must not remain noindex')
  }

  return true
}

export async function probeOrigin(origin, paths, { timeoutMs = 5000, fetchImpl = fetch } = {}) {
  const safeOrigin = validateLoopbackOrigin(origin)
  const results = []
  for (const routePath of paths) {
    validatePath(routePath, 'probe path')
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), timeoutMs)
    try {
      const response = await fetchImpl(new URL(routePath, safeOrigin), {
        method: 'GET',
        redirect: 'manual',
        signal: controller.signal,
        headers: { 'user-agent': 'pts-nuxt-cutover-dry-run/1.0' },
      })
      const body = await response.text()
      results.push({
        path: routePath,
        status: response.status,
        location: response.headers.get('location'),
        noindex: /<meta[^>]+name=["']robots["'][^>]+content=["'][^"']*noindex/i.test(body)
          || /noindex/i.test(response.headers.get('x-robots-tag') || ''),
      })
    } finally {
      clearTimeout(timer)
    }
  }
  return results
}

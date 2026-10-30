import { spawn } from 'node:child_process'
import { strict as assert } from 'node:assert'
import { createServer } from 'node:http'
import { createServer as createTcpServer } from 'node:net'
import { setTimeout as sleep } from 'node:timers/promises'

async function availablePort() {
  const server = createTcpServer()
  await new Promise((resolve, reject) => {
    server.once('error', reject)
    server.listen(0, '127.0.0.1', resolve)
  })
  const address = server.address()
  if (!address || typeof address === 'string') throw new Error('Could not reserve a local TCP port')
  await new Promise((resolve, reject) => server.close((error) => error ? reject(error) : resolve()))
  return String(address.port)
}

const port = await availablePort()
const base = `http://127.0.0.1:${port}`
const mockPhp = process.env.NITRO_SMOKE_MOCK_PHP === '1' ? createServer((_request, response) => {
  // Unknown public slugs are a PHP-classified 404. CI uses this narrow stub
  // because the SSR smoke runner has no PHP service; all paths are unpublished.
  response.writeHead(404, { 'content-type': 'application/json' })
  response.end(JSON.stringify({ error: 'not_found' }))
}) : null
let phpOrigin = process.env.NITRO_PHP_ORIGIN || 'http://127.0.0.1:8088'
let phpAllowlist = process.env.NITRO_PHP_ORIGIN_ALLOWLIST || process.env.NUXT_PHP_ORIGIN_ALLOWLIST
if (mockPhp) {
  // CI has no PHP service. Bind a local fixture on an ephemeral port and
  // classify unknown public slugs as the PHP resolver's 404 response.
  await new Promise((resolve, reject) => {
    mockPhp.once('error', reject)
    mockPhp.listen(0, '127.0.0.1', resolve)
  })
  const address = mockPhp.address()
  if (!address || typeof address === 'string') throw new Error('Mock PHP server did not bind to a TCP port')
  phpOrigin = `http://127.0.0.1:${address.port}`
  phpAllowlist = `127.0.0.1:${address.port}`
}
const child = spawn(process.execPath, ['.output/server/index.mjs'], {
  cwd: new URL('../', import.meta.url).pathname,
  env: {
    ...process.env,
    NITRO_PORT: port,
    NITRO_HOST: '127.0.0.1',
    NITRO_PHP_ORIGIN: phpOrigin,
    NUXT_PHP_ORIGIN: phpOrigin,
    ...(phpAllowlist ? {
      NITRO_PHP_ORIGIN_ALLOWLIST: phpAllowlist,
      NUXT_PHP_ORIGIN_ALLOWLIST: phpAllowlist,
    } : {}),
    NITRO_PREVIEW_MEDIA_PROXY: process.env.NITRO_PREVIEW_MEDIA_PROXY || '1',
    NUXT_PREVIEW_MEDIA_PROXY: process.env.NUXT_PREVIEW_MEDIA_PROXY || '1',
  },
  stdio: 'ignore',
})
try {
  let live = false
  for (let i = 0; i < 60; i++) {
    if (child.exitCode !== null) throw new Error('Nuxt process exited early')
    try {
      const res = await fetch(base + '/migration-preview', { signal: AbortSignal.timeout(800) })
      if (res.ok) { live = true; break }
    } catch { /* retry until ready */ }
    await sleep(150)
  }
  assert.equal(live, true, 'Nuxt server not reachable')
  const preview = await fetch(base + '/migration-preview')
  assert.equal(preview.status, 200, 'preview SSR status')
  assert.match(await preview.text(), /Nuxt 4 runtime checkpoint/, 'SSR HTML present')
  assert.match(preview.headers.get('x-robots-tag') || '', /noindex/, 'preview not indexable')
  const routeChecks = [
    '/migration-hub-preview',
    '/migration-gallery-preview',
    '/migration-gallery-detail-preview?slug=album-that',
    '/migration-video-preview',
  ]
  for (const path of routeChecks) {
    const response = await fetch(base + path)
    assert.equal(response.status, 200, `${path} SSR status`)
    const html = await response.text()
    const robots = `${response.headers.get('x-robots-tag') || ''}\n${html}`
    assert.match(robots, /noindex/i, `${path} not indexable`)
  }
  const missing = await fetch(base + '/nonexistent-pts-route')
  assert.equal(missing.status, 404, 'unknown route must not claim legacy URLs')
  console.log(`PASS: Nuxt SSR preview (base + ${routeChecks.length} hub/media routes 200/noindex, unknown route 404)`)
} finally {
  child.kill('SIGTERM')
  mockPhp?.close()
}

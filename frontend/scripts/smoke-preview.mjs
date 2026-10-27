import { spawn } from 'node:child_process'
import { strict as assert } from 'node:assert'
import { setTimeout as sleep } from 'node:timers/promises'

const port = '3139'
const base = `http://127.0.0.1:${port}`
const child = spawn(process.execPath, ['.output/server/index.mjs'], {
  cwd: new URL('../', import.meta.url).pathname,
  env: { ...process.env, NITRO_PORT: port, NITRO_HOST: '127.0.0.1' },
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
}

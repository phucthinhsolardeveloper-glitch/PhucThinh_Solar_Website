import assert from 'node:assert/strict'
import test from 'node:test'
import {
  probeOrigin,
  rollbackPlan,
  validateAllowlist,
  validateApproval,
  validateLoopbackOrigin,
} from '../lib/nuxt-cutover.mjs'
import { parseArgs } from '../nuxt-cutover-preflight.mjs'

const manifest = {
  version: 1,
  featureFlag: { name: 'PTS_NUXT_HYBRID_ENABLED', default: '0' },
  pilot: { id: 'about', publicPath: '/gioi-thieu/', nuxtPath: '/gioi-thieu/', methods: ['GET', 'HEAD'], readiness: 'blocked' },
  phpOwnedPrefixes: ['/account/', '/administrator/', '/api/', '/gio-hang/'],
  forbiddenNuxtPrefixes: ['/migration-', '/_migration/'],
}

test('allowlist requires OFF default, GET-only pilot and no protected overlap', () => {
  assert.equal(validateAllowlist(structuredClone(manifest)).pilot.id, 'about')
  const enabled = structuredClone(manifest)
  enabled.featureFlag.default = '1'
  assert.throws(() => validateAllowlist(enabled), /default to 0/)
  const post = structuredClone(manifest)
  post.pilot.methods.push('POST')
  assert.throws(() => validateAllowlist(post), /GET\/HEAD only/)
  const protectedRoute = structuredClone(manifest)
  protectedRoute.pilot.publicPath = '/account/profile/'
  assert.throws(() => validateAllowlist(protectedRoute), /protected prefix/)
  const preview = structuredClone(manifest)
  preview.pilot.nuxtPath = '/migration-about-preview'
  assert.throws(() => validateAllowlist(preview), /protected prefix/)
})

test('origins are loopback-only and arguments default to static preflight', () => {
  assert.equal(validateLoopbackOrigin('http://127.0.0.1:8080'), 'http://127.0.0.1:8080')
  assert.throws(() => validateLoopbackOrigin('https://example.test'), /Remote origin blocked/)
  assert.throws(() => validateLoopbackOrigin('http://user:pass@localhost:3100'), /credentials/)
  assert.deepEqual(parseArgs([]), { probe: false, rollbackPlan: false, cutoverPlan: false })
  assert.throws(() => parseArgs(['--probe', '--rollback-plan']), /only one/)
})

test('owner approval is commit- and route-bound', () => {
  const commit = 'a'.repeat(40)
  const approval = { approved: true, approvedBy: 'owner', approvedAt: '2026-10-27T10:00:00Z', routeIds: ['about'], commit }
  assert.equal(validateApproval(approval, manifest, commit), true)
  assert.throws(() => validateApproval({ ...approval, approved: false }, manifest, commit), /not granted/)
  assert.throws(() => validateApproval({ ...approval, commit: 'b'.repeat(40) }, manifest, commit), /does not match/)
})

test('rollback output is generation only and contains validation before restart', () => {
  const plan = rollbackPlan()
  assert.match(plan, /GENERATED ONLY/)
  assert.ok(plan.indexOf('config --quiet') < plan.indexOf('restart proxy'))
  assert.match(plan, /does not mutate either/)
})

test('health probe records status and noindex without following redirects', async () => {
  const fetchImpl = async url => {
    if (url.pathname === '/preview/') {
      return new Response('<meta name="robots" content="noindex, nofollow">', { status: 200 })
    }
    return new Response('', { status: 301, headers: { location: '/preview/' } })
  }
  const results = await probeOrigin('http://127.0.0.1:8080', ['/', '/preview/'], { fetchImpl })
  assert.equal(results[0].status, 301)
  assert.equal(results[0].location, '/preview/')
  assert.equal(results[1].noindex, true)
})

import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const source = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')
const leadProxy = source('../server/routes/_migration/lead-form.post.ts')
const reviewProxy = source('../server/routes/_migration/review.post.ts')
const leadGet = source('../server/routes/_migration/lead-form.get.ts')
const leadUi = source('../app/composables/useLeadForm.ts')
const reviewUi = source('../app/components/product-preview/ProductReviews.vue')

test('lead metadata GET stays available while the obsolete rate-limit-collapsing POST proxy is rejected', () => {
  assert.match(leadGet, /fetchPhpSessionResponse[\s\S]*method: 'GET'/)
  assert.match(leadProxy, /Allow', 'GET'/)
  assert.match(leadProxy, /statusCode: 405/)
  assert.doesNotMatch(leadProxy, /fetchPhpSessionResponse/)
  assert.match(leadUi, /\$fetch<unknown>\('\/_migration\/lead-form'/)
  assert.match(leadUi, /\$fetch<unknown>\('\/api\/pts_v1_lead_form\.php'/)
})

test('reviews keep the PHP same-origin mutation and the unused Nitro POST proxy is gone', () => {
  assert.match(reviewProxy, /statusCode: 410/)
  assert.doesNotMatch(reviewProxy, /fetchPhpSessionResponse/)
  assert.match(reviewUi, /\$fetch\('\/api\/pts_v1_review\.php'/)
})

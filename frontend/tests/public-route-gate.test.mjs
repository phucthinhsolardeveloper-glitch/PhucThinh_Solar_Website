import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import {
  brandDetailPublicPath,
  isRuntimeFlagEnabled,
  productCategoryPublicPath,
  productDetailPublicPath,
} from '../shared/public-route-gate.ts'

test('indexability setting accepts only explicit true values', () => {
  assert.equal(isRuntimeFlagEnabled(false), false)
  assert.equal(isRuntimeFlagEnabled(undefined), false)
  assert.equal(isRuntimeFlagEnabled('0'), false)
  assert.equal(isRuntimeFlagEnabled('true'), true)
  assert.equal(isRuntimeFlagEnabled(true), true)
})

test('product detail paths retain the PHP no-trailing-slash canonical', () => {
  assert.equal(productDetailPublicPath('den-ufo-1000w'), '/san-pham/den-ufo-1000w')
  assert.throws(() => productDetailPublicPath('../../etc/passwd'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath(''), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('<script>'), /Invalid product slug/)
})

test('brand and nested category routes preserve their canonical URL shapes', () => {
  assert.equal(brandDetailPublicPath('longi'), '/thuong-hieu/longi/')
  assert.throws(() => brandDetailPublicPath('../etc'), /Invalid brand slug/)
  assert.equal(productCategoryPublicPath(['den-nlmt', 'den-pha-nang-luong-mat-troi']), '/danh-muc-san-pham/den-nlmt/den-pha-nang-luong-mat-troi/')
  assert.throws(() => productCategoryPublicPath(['den-nlmt', '../unsafe']), /Invalid product category path/)
  assert.throws(() => productCategoryPublicPath(['one', 'two', 'three', 'four', 'five']), /Invalid product category path/)
})

test('public Nuxt page middleware cannot redirect browser navigation back to PHP templates', () => {
  for (const filename of ['brand-detail-public-route.ts', 'product-detail-public-route.ts']) {
    const source = readFileSync(new URL(`../app/middleware/${filename}`, import.meta.url), 'utf8')
    assert.doesNotMatch(source, /phpPublicRouteUrl|phpPublicOrigin|external:\s*true/)
  }
  const config = readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8')
  assert.doesNotMatch(config, /PublicRouteEnabled|phpPublicOrigin/)
})

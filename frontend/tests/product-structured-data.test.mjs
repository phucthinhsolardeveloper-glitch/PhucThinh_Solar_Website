import { test } from 'node:test'
import assert from 'node:assert/strict'
import { productIdentifierFields, productOfferNode } from '../shared/product-structured-data.ts'

const origin = 'https://phucthinhsolar.com'
const url = `${origin}/san-pham/den-a`
const priced = { mode: 'price', currency: 'VND', regularPrice: 1200000, salePrice: 990000 }

test('a priced product keeps its Offer and price but states no availability until it is verified', () => {
  const offer = productOfferNode(priced, undefined, url, origin)
  assert.deepEqual(offer, {
    '@type': 'Offer', url, priceCurrency: 'VND', price: 990000,
    itemCondition: 'https://schema.org/NewCondition', seller: { '@id': `${origin}/#organization` },
  })
  assert.equal('availability' in offer, false)
  assert.equal('availability' in productOfferNode(priced, {}, url, origin), false)
})

test('Admin-verified availability becomes InStock, matching the verified Merchant feed', () => {
  assert.equal(productOfferNode(priced, { availability: 'in_stock' }, url, origin).availability, 'https://schema.org/InStock')
  assert.equal(productOfferNode({ ...priced, salePrice: null }, { availability: 'in_stock' }, url, origin).price, 1200000)
})

test('quote-only or unpriced products get no Offer at all', () => {
  assert.equal(productOfferNode({ mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null }, { availability: 'in_stock' }, url, origin), null)
  assert.equal(productOfferNode({ mode: 'price', currency: 'VND', regularPrice: null, salePrice: null }, undefined, url, origin), null)
  assert.equal(productOfferNode(null, undefined, url, origin), null)
})

test('only validated manufacturer identifiers reach the Product node', () => {
  assert.deepEqual(productIdentifierFields(undefined), {})
  assert.deepEqual(productIdentifierFields({ availability: 'in_stock' }), {})
  assert.deepEqual(productIdentifierFields({ gtin: '042100005264', mpn: 'MPN-22' }), { gtin: '042100005264', mpn: 'MPN-22' })
})

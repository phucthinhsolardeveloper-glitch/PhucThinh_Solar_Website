import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('catalog navigation exposes a responsive disclosure and current category', () => {
  const navigation = read('components/catalog/CatalogCategoryNav.vue')
  const branch = read('components/catalog/CatalogCategoryBranch.vue')
  assert.match(navigation, /aria-controls="catalog-category-list"/)
  assert.match(navigation, /:aria-expanded="expanded"/)
  assert.match(branch, /:aria-current="selected === node\.url \? 'page' : undefined"/)
})

test('commerce media failures render explicit fallbacks instead of blank frames', () => {
  const grid = read('components/catalog/CatalogProductGrid.vue')
  const gallery = read('components/product-preview/ProductPreviewGallery.vue')
  assert.match(grid, /markImageFailed\(product\.id\)/)
  assert.match(grid, /Chưa có hình ảnh/)
  assert.match(gallery, /markMediaFailed\(selectedMedia\)/)
  assert.match(gallery, /CMS chưa có ảnh sản phẩm hợp lệ/)
})

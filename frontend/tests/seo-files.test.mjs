import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('robots points at the sitemap and keeps admin, API and checkout out of search', () => {
  const robots = read('../public/robots.txt')
  assert.match(robots, /^Sitemap: https:\/\/phucthinhsolar\.com\/sitemap\.xml$/m)
  for (const path of ['/admin/', '/api/', '/gio-hang/', '/thanh-toan/']) assert.match(robots, new RegExp(`^Disallow: ${path}$`, 'm'))
})

test('robots avoids faceted crawl traps without blocking product, category or pagination URLs', () => {
  const robots = read('../public/robots.txt')
  const rules = [...robots.matchAll(/^Disallow: (.+)$/gm)].map((match) => match[1])

  const denied = (urlPath) => rules.some((pattern) => {
    const regex = '^' + pattern.replaceAll('?', '\\?').replaceAll('*', '.*')
    return new RegExp(regex).test(urlPath)
  })

  for (const value of ['brand', 'category', 'subcategory', 'price', 'need', 'sort', 'per_page', 'keyword']) {
    assert.ok(rules.includes('/*?*' + value + '='), 'Missing crawl-budget rule for ' + value)
  }

  for (const urlPath of [
    '/danh-muc-san-pham/den-nlmt/?brand=19&price=2',
    '/san-pham/?page=3&subcategory=5',
    '/san-pham/?price=1&brand=19',
    '/san-pham/?need=du-an-cong-trinh',
  ]) assert.ok(denied(urlPath), 'Should not crawl filtered URL ' + urlPath)

  for (const urlPath of [
    '/san-pham/',
    '/san-pham/?page=2',
    '/danh-muc-san-pham/den-nlmt/',
    '/danh-muc-san-pham/den-nlmt/?page=3',
    '/san-pham/inverter-hybrid-bat-ky',
    '/giai-phap/',
    '/sitemap.xml',
  ]) assert.ok(!denied(urlPath), 'Must preserve crawling of ' + urlPath)
})

test('the sitemap comes from Laravel and the Google verification file still ships', () => {
  assert.match(read('../server/routes/sitemap.xml.get.ts'), /new URL\('\/sitemap\.xml', origin\)/)
  assert.ok(existsSync(new URL('../public/googlefc5686c8f466515c.html', import.meta.url)))
  assert.match(read('../public/site.webmanifest'), /"name": "Phúc Thịnh Solar"/)
})

import assert from 'node:assert/strict'
import { readFileSync, existsSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('robots points at the sitemap and keeps admin, API and checkout out of search', () => {
  const robots = read('../public/robots.txt')
  assert.match(robots, /^Sitemap: https:\/\/phucthinhsolar\.com\/sitemap\.xml$/m)
  for (const path of ['/admin/', '/api/', '/gio-hang/', '/thanh-toan/']) assert.match(robots, new RegExp(`^Disallow: ${path}$`, 'm'))
})

test('the sitemap comes from Laravel and the Google verification file still ships', () => {
  assert.match(read('../server/routes/sitemap.xml.get.ts'), /new URL\('\/sitemap\.xml', origin\)/)
  assert.ok(existsSync(new URL('../public/googlefc5686c8f466515c.html', import.meta.url)))
  assert.match(read('../public/site.webmanifest'), /"name": "Phúc Thịnh Solar"/)
})

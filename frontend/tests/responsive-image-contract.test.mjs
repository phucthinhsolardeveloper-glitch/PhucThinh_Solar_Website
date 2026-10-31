import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const frontendRoot = resolve(new URL('..', import.meta.url).pathname)
const read = (path) => readFileSync(resolve(frontendRoot, path), 'utf8')

test('catalog media reserves intrinsic space and chooses responsive product sizes', () => {
  const catalog = read('app/components/catalog/CatalogPage.vue')
  const masthead = read('app/components/catalog/CatalogMasthead.vue')
  const products = read('app/components/catalog/CatalogProductGrid.vue')
  assert.match(masthead, /hero\?\.image[\s\S]*width="1800"[\s\S]*height="600"[\s\S]*sizes="100vw"/)
  assert.match(catalog, /categoryContent\.applications[\s\S]*width="760"[\s\S]*height="475"[\s\S]*sizes=/)
  assert.match(catalog, /support\.image[\s\S]*width="1600"[\s\S]*height="500"[\s\S]*sizes="100vw"/)
  assert.match(products, /width="640"[\s\S]*height="640"[\s\S]*sizes=/)
})

test('news cards use allowlisted PHP thumbnails and match the rendered 16:9 frame', () => {
  const news = read('app/components/news/NewsCard.vue')
  assert.match(news, /\^\\\/upload\\\/news\\\//)
  assert.match(news, /thumbs\/\$\{width\}x\$\{height\}x1\$\{src\}/)
  assert.match(news, /cardImage\(src, 600, 338\).*600w,.*cardImage\(src, 900, 506\).*900w/s)
  assert.match(news, /variant === 'lead' \? 900 : 600/)
  assert.match(news, /variant === 'lead' \? 'eager' : 'lazy'/)
  assert.match(news, /variant === 'lead' \? 'high' : 'low'/)
  assert.doesNotMatch(news, /rounded-btn border border-slate-200\/90 bg-white/)
})

test('homepage media uses the same PHP thumbnail contracts and prioritizes the first hero', () => {
  const hero = read('app/components/home/HomeHero.vue')
  const editorial = read('app/components/home/HomeEditorial.vue')
  const phpHome = read('../domains/phucthinhsolar.com/public_html/templates/index/index_tpl.php')
  assert.match(phpHome, /sizes' => '1600x720x1'/)
  assert.match(hero, /thumbs\/\$\{width\}x\$\{height\}x1\$\{src\}/)
  assert.match(hero, /heroImageCandidate\(src, 960\).*960w,.*heroImageCandidate\(src, 1600\).*1600w/s)
  assert.match(hero, /:fetchpriority="index === 0 \? 'high' : 'low'"/)
  assert.match(hero, /:loading="index === 0 \? 'eager' : 'lazy'"/)
  assert.match(phpHome, /sizes' => '1200x750x1'/)
  assert.match(editorial, /thumbs\/\$\{width\}x\$\{height\}x1\$\{src\}/)
  assert.match(editorial, /editorialImageCandidate\(src, 760, 475\).*760w,.*editorialImageCandidate\(src, 1200, 750\).*1200w/s)
  assert.match(editorial, /thumbs\/48x48x1\$\{src\}/)
})

test('CMS logos and trust badge use the PHP fit thumbnail contract without cropping', () => {
  const header = read('app/components/SiteHeader.vue')
  const footer = read('app/components/SiteFooter.vue')
  const phpFooter = read('../domains/phucthinhsolar.com/public_html/templates/layout/footer.php')
  const thumbRenderer = read('../domains/phucthinhsolar.com/public_html/libraries/class/class.Functions.php')
  assert.match(header, /thumbs\/520x112x3\$\{src\}/)
  assert.match(header, /width="520"[\s\S]*height="73"/)
  assert.match(phpFooter, /sizes' => '368x144x3'[\s\S]*\$logof\['photo'\]/)
  assert.match(phpFooter, /sizes' => '400x152x3'[\s\S]*\$bct\['photo'\]/)
  assert.match(thumbRenderer, /if\(\$zoom_crop == 3\)\s*\{\s*\$final_height = \$height \* \(\$new_width \/ \$width\);\s*if\(\$final_height > \$new_height\)\s*\$new_width = \(int\) round\(\$width \* \(\$new_height \/ \$height\)\);\s*else \$new_height = \(int\) round\(\$final_height\);/)
  assert.match(thumbRenderer, /else if\(\$zoom_crop > 0\)\s*\{[\s\S]*?\$src_x = round\(\(\$width - \(\$width \/ \$cmp_x \* \$cmp_y\)\) \/ 2\);/)
  assert.match(footer, /thumbs\/400x152x3\$\{src\}/)
  assert.match(footer, /thumbs\/400x152x3\$\{src\}/)
  assert.match(footer, /width="400"[\s\S]*height="152"[\s\S]*object-contain/)
  assert.match(footer, /width="400"[\s\S]*height="152"[\s\S]*object-contain/)
})

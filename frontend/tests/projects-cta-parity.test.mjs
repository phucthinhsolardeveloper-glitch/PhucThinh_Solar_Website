import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../', import.meta.url)
const readFrontend = (path) => readFileSync(new URL(path, root), 'utf8')
const phpRoot = new URL('../../domains/phucthinhsolar.com/public_html/', import.meta.url)
const readPhp = (path) => readFileSync(new URL(path, phpRoot), 'utf8')

test('project listing CTA stays wired to the PHP visibility and image contract', () => {
  const phpTemplate = readPhp('templates/news/project_cta_tpl.php')
  const phpSource = readPhp('sources/project_hub.php')
  const phpApi = readPhp('api/pts_v1_projects.php')
  const phpDto = readPhp('libraries/pts_public_projects_api.php')
  const page = readFrontend('app/pages/migration-projects-preview.vue')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')

  assert.match(phpTemplate, /if\(\$projectShowCta\)/)
  assert.match(phpTemplate, /getThumbUrl\('1600x500x1',\s*\$projectCtaImage\)/)
  assert.match(phpSource, /\$projectShowCta\s*=\s*!\$projectCtaRows\s*\|\|\s*\(bool\)\$projectCtaVisible/)
  assert.match(phpApi, /if\(!\$ctaRows\)\s*\$ctaRows\[\]\s*=\s*array\(\s*'namevi'\s*=>\s*'Bạn có công trình cần tư vấn\?'/)
  assert.match(phpApi, /'anh-quan-gl-4-9449\.jpg'/)
  assert.match(phpApi, /foreach\(\$ctaRows as &\$row\)[\s\S]*?is_file\(ROOT\.UPLOAD_NEWS_L\.'anh-quan-gl-4-9449\.jpg'\)/)
  assert.match(phpDto, /function ptsProjectsCtaDto[\s\S]*?foreach\(\$rows as \$row\)[\s\S]*?ptsProjectsPublished[\s\S]*?return null;/)
  assert.match(page, /<ProjectsCta v-if="projects\.cta"/)
  assert.match(cta, /1600x500x1\$\{src\}/)
})

test('project listing CTA uses the shared editorial banner and keeps PHP image/trust data', () => {
  const phpCss = readPhp('assets/css/project-hub.css')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')

  assert.match(phpCss, /\.pts-ph-contact-copy\s*\{max-width:590px;\}/)
  assert.match(phpCss, /\.pts-ph-contact-trust li\{display:flex;align-items:center;gap:10px/)
  assert.match(phpCss, /\.pts-ph-contact-actions\{display:flex;flex-direction:column/)

  assert.match(cta, /<PublicCtaBanner variant="editorial" :image="backgroundImage"/)
  assert.match(cta, /const backgroundImage = computed\(\(\) =>[\s\S]*1600x500x1\$\{src\}/)
  assert.match(cta, /cta\.eyebrow[\s\S]*cta\.title[\s\S]*cta\.description[\s\S]*trustItems/)
  assert.match(cta, /cta\.hotline\?\.tel[\s\S]*cta\.hotline\.text[\s\S]*cta\.buttonLabel/)
  assert.match(sharedCta, /\.public-cta-banner__image \{ object-fit: cover; \}/)
  assert.match(sharedCta, /\.public-cta-banner__shade \{ background: linear-gradient\(90deg/)
  assert.match(sharedCta, /\.public-cta-banner--editorial \{[^}]*min-height: 214px/)
  assert.match(sharedCta, /@media \(max-width: 760px\)[\s\S]*grid-template-columns: minmax\(0, 1fr\)/)
  assert.match(cta, /text-\[15\.5px\] leading-\[1\.6\]/)
  assert.match(cta, /gap-y-\[14px\]/)
  assert.match(cta, /h-\[34px\] w-\[34px\].*bg-white\/10/)
  assert.match(cta, /Liên hệ ngay[\s\S]*text-\[22px\]/)
})

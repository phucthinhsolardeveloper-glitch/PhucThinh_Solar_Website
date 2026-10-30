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

test('project listing CTA matches PHP navy gradient and photo treatment', () => {
  const phpCss = readPhp('assets/css/project-hub.css')
  const phpTokens = readPhp('assets/css/design-tokens.css')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')

  assert.match(phpCss, /\.pts-ph-contact-band\s*\{[^}]*linear-gradient\(112deg,#061735 0%,#0b2b59 68%,#123c72 100%\)/s)
  assert.match(phpCss, /\.pts-ph-contact-bg\s*\{\s*opacity:\.25!important/s)
  assert.match(phpTokens, /\.pts-sh-contact-background,\s*\.pts-ph-contact-bg,\s*\.nh-cta-bg\s*\{opacity:\.5!important;/s)
  assert.match(phpTokens, /\.pts-ph-contact-band:before[\s\S]*?linear-gradient\(90deg,[\s\S]*rgba\(10,34,81,\.97\)[\s\S]*rgba\(10,34,81,\.3\)/)
  assert.match(phpTokens, /@media\(max-width:900px\)[\s\S]*?\.pts-ph-contact-band:before[\s\S]*?linear-gradient\(180deg,rgba\(10,34,81,\.96\)/)
  assert.match(phpCss, /\.pts-ph-contact-copy\s*\{max-width:590px;\}/)
  assert.match(phpCss, /\.pts-ph-contact-trust li\{display:flex;align-items:center;gap:10px/)
  assert.match(phpCss, /\.pts-ph-contact-actions\{display:flex;flex-direction:column/)

  assert.match(cta, /bg-\[linear-gradient\(112deg,#061735_0%,#0b2b59_68%,#123c72_100%\)\]/)
  assert.match(cta, /object-cover opacity-50/)
  assert.match(cta, /-z-20 bg-\[linear-gradient\(180deg[\s\S]*?<img v-if="backgroundImage"[\s\S]*?-z-10/)
  assert.match(cta, /linear-gradient\(180deg,rgba\(10,34,81,\.96\)_0,rgba\(10,34,81,\.86\)_100%\)/)
  assert.match(cta, /linear-gradient\(90deg,rgba\(10,34,81,\.97\)_0,rgba\(10,34,81,\.9\)_38%,rgba\(10,34,81,\.55\)_68%,rgba\(10,34,81,\.3\)_100%\)/)
  assert.match(cta, /min-\[901px\]:max-w-\[590px\]/)
  assert.match(cta, /h-\[34px\] w-\[34px\].*bg-white\/10/)
  assert.match(cta, /Liên hệ ngay[\s\S]*text-\[22px\]/)
})

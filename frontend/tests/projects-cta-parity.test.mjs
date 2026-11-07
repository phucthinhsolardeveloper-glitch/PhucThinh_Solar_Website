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
  const page = readFrontend('app/components/views/ProjectsView.vue')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')

  assert.match(phpTemplate, /if\(\$projectShowCta\)/)
  assert.match(phpTemplate, /getThumbUrl\('1600x500x1',\s*\$projectCtaImage\)/)
  assert.match(phpSource, /\$projectShowCta\s*=\s*!\$projectCtaRows\s*\|\|\s*\(bool\)\$projectCtaVisible/)
  assert.match(phpApi, /if\(!\$ctaRows\)\s*\$ctaRows\[\]\s*=\s*array\(\s*'namevi'\s*=>\s*'Bạn có công trình cần tư vấn\?'/)
  assert.match(phpApi, /'anh-quan-gl-4-9449\.jpg'/)
  assert.match(phpApi, /foreach\(\$ctaRows as &\$row\)[\s\S]*?is_file\(ROOT\.UPLOAD_NEWS_L\.'anh-quan-gl-4-9449\.jpg'\)/)
  assert.match(phpDto, /function ptsProjectsCtaDto[\s\S]*?foreach\(\$rows as \$row\)[\s\S]*?ptsProjectsPublished[\s\S]*?return null;/)
  assert.match(page, /<ProjectsCta v-if="projects\.cta"/)
  assert.match(cta, /thumbUrl\(src, 1600, 500\)/)
})

test('project listing CTA uses the shared editorial banner and keeps PHP image/trust data', () => {
  const phpCss = readPhp('assets/css/project-hub.css')
  const cta = readFrontend('app/components/projects/ProjectsCta.vue')
  const sharedCta = readFrontend('app/components/PublicCtaBanner.vue')

  assert.match(phpCss, /\.pts-ph-contact-copy\s*\{max-width:590px;\}/)
  assert.match(phpCss, /\.pts-ph-contact-trust li\{display:flex;align-items:center;gap:10px/)
  assert.match(phpCss, /\.pts-ph-contact-actions\{display:flex;flex-direction:column/)

  // Owner decision 2026-11-04: the shared CTA band renders copy, trust and actions from props.
  assert.match(cta, /<PublicCtaBanner\s+variant="editorial"\s+:image="backgroundImage"/)
  assert.match(cta, /const backgroundImage = computed\(\(\) =>[\s\S]*thumbUrl\(src, 1600, 500\)/)
  assert.match(cta, /:title="cta\.title"[\s\S]*:description="cta\.description"[\s\S]*:trust="trustItems"/)
  assert.match(cta, /:hotline="cta\.hotline\?\.text"[\s\S]*:button-label="cta\.buttonLabel \|\| label"/)
  assert.match(sharedCta, /\.cta-band__image \{[^}]*object-fit: cover/)
  assert.match(sharedCta, /\.cta-band__trust li \{/)
})

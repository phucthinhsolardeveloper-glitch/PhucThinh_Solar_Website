import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { projectVideoEmbedUrl } from '../shared/project-gallery.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('project video embed accepts known providers and rejects unsafe or unsupported hosts', () => {
  assert.equal(projectVideoEmbedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'), 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
  assert.equal(projectVideoEmbedUrl('https://youtu.be/dQw4w9WgXcQ'), 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
  assert.equal(projectVideoEmbedUrl('https://vimeo.com/12345678'), 'https://player.vimeo.com/video/12345678')
  assert.equal(projectVideoEmbedUrl('https://evil.test/watch?v=dQw4w9WgXcQ'), '')
  assert.equal(projectVideoEmbedUrl('http://www.youtube.com/watch?v=dQw4w9WgXcQ'), '')
  assert.equal(projectVideoEmbedUrl('https://www.youtube.com:8443/watch?v=dQw4w9WgXcQ'), '')
})

test('project detail gallery uses an accessible in-page media dialog', () => {
  const gallery = read('../app/components/project-detail/ProjectDetailGallery.vue')
  assert.match(gallery, /role="dialog"\s+aria-modal="true"/)
  assert.match(gallery, /event\.key === 'Escape'/)
  assert.match(gallery, /trapFocus\(event, lightboxPanel\.value\)/)
  assert.match(gallery, /:src="selectedEmbedUrl"/)
  assert.match(gallery, /@click="open\(item,/)
  assert.match(gallery, /Không tìm thấy kết quả/)
})

test('news detail centers single-column articles when no generated TOC is available', () => {
  const page = read('../app/pages/migration-news-detail-preview.vue')
  const article = read('../app/components/news-detail/NewsDetailArticle.vue')
  assert.match(page, /<NewsDetailArticle[^>]+\/>[\s\S]*?<NewsDetailProducts[^>]+\/>[\s\S]*?<NewsDetailRelated/)
  assert.match(page, /<main class="pts-container py-8 nav:py-16">/)
  assert.match(article, /nav:grid-cols-\[minmax\(240px,1fr\)_minmax\(0,2fr\)\]/)
  assert.match(article, /<aside v-if="article\.content\.html && editorial\.toc\.length"/)
  assert.match(article, /editorial\.toc\.length \? '' : 'mx-auto w-full max-w-\[920px\]'/)
  assert.match(article, /class="nav:sticky[^"]*"[\s\S]*isHeaderHidden \? 'nav:top-6' : 'nav:top-\[120px\]/)
  assert.match(article, /<UBadge v-if="categoryName"/)
  assert.match(article, /<UIcon name="i-lucide-list"/)
  assert.match(article, /<EditorialRichText :content="editorial\.content" variant="news" \/>/)
  assert.match(article, /<EditorialRichText :content="article\.quote" variant="news" \/>/)
  assert.equal((article.match(/<EditorialShare /g) || []).length, 1)
  assert.match(article, /sizes="\(min-width: 1024px\) 66vw, 100vw"/)
})

test('gallery and video pages rely on CMS titles without static marketing eyebrow copy', () => {
  const gallery = read('../app/pages/migration-gallery-preview.vue')
  const album = read('../app/pages/migration-gallery-detail-preview.vue')
  const video = read('../app/pages/migration-video-preview.vue')
  const visibleMarkup = [gallery, album, video].map((page) => page.split('<template>')[1] || '').join('\n')
  assert.doesNotMatch(visibleMarkup, /Hình ảnh thực tế|Video thực tế|Album ảnh|được quản lý trực tiếp từ CMS|được xuất từ nguồn CMS/)
})

test('policy TOC uses the shared anchor-preserving builder', () => {
  const page = read('../app/pages/migration-policy-preview.vue')
  assert.match(page, /buildEditorialToc\(data\.value\?\.content/)
  assert.doesNotMatch(page, /policy-section-/)
})

test('solution detail restages its content as chapters with a hero and switcher rail', () => {
  // Owner decision 2026-11-04: the sticky TOC rail is replaced by the chapter story used on the hub.
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')
  assert.match(page, /<SolutionsStory v-if="hasContent" :contents="\[solutionContent, solutionExtraContent\]" :show-index="false" \/>/)
  assert.match(page, /\.solution-rail__item \{[^}]*min-height: 44px/)
  assert.match(page, /prefers-reduced-motion: reduce/)
})

test('solution hub TOC stays compact until the two-column desktop layout is available', () => {
  const preview = read('../app/pages/migration-solutions-preview.vue')
  for (const page of [preview]) {
    assert.match(page, /max-h-\[220px\].*nav:sticky nav:top-24 nav:max-h-\[calc\(100vh-116px\)\]/)
    assert.doesNotMatch(page, /min-\[901px\]:sticky|min-\[901px\]:max-h-/)
    assert.match(page, /nav:grid-cols-\[270px_minmax\(0,1fr\)\]/)
  }
})

test('solution detail gallery opens an accessible lightbox with keyboard navigation', () => {
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const gallery = read('../app/components/solution-detail/SolutionDetailGallery.vue')
  const source = read('../../domains/phucthinhsolar.com/public_html/templates/news/service_detail_tpl.php')
  assert.match(page, /<SolutionDetailGallery :images="data\.solution\.gallery"/)
  assert.match(source, /data-fancybox="solution-gallery"/)
  assert.match(gallery, /role="dialog"\s+aria-modal="true"/)
  assert.match(gallery, /event\.key === 'Escape'/)
  assert.match(gallery, /event\.key === 'ArrowLeft'/)
  assert.match(gallery, /event\.key === 'ArrowRight'/)
  assert.match(gallery, /lastTrigger\.value\?\.focus\(\)/)
  assert.match(gallery, /aspect-\[8\/5\]/)
  assert.match(gallery, /object-contain/)
})

test('policy article keeps the PHP reading measure', () => {
  const page = read('../app/pages/migration-policy-preview.vue')
  assert.match(page, /<main class="pts-container py-8 nav:py-14"/)
  assert.match(page, /<div class="pts-container">[\s\S]*<SiteBreadcrumb/)
  assert.match(page, /<article v-else class="mx-auto w-full max-w-\[1280px\]">/)
  assert.match(page, /max-w-\[960px\]/)
})

test('editorial cover and related images use the documented news aspect ratio and crop behavior', () => {
  const article = read('../app/components/news-detail/NewsDetailArticle.vue')
  const related = read('../app/components/news-detail/NewsDetailRelated.vue')
  assert.match(article, /width="1000"\s+height="560"\s+sizes="\(min-width: 1024px\) 66vw, 100vw"\s+class="mb-8 block aspect-video w-full object-cover nav:mb-10"/)
  assert.match(related, /aspect-video w-full object-cover/)
  assert.doesNotMatch(related, /aspect-\[3\/2\]/)
})

test('gallery keeps the PHP album thumb ratio while removing decorative card surfaces', () => {
  const gallery = read('../app/pages/migration-gallery-preview.vue')
  const phpAlbum = read('../../domains/phucthinhsolar.com/public_html/templates/album/album_tpl.php')
  assert.match(phpAlbum, /sizes'\s*=>\s*'480x360x1'/)
  assert.match(gallery, /aspect-\[4\/3\]/)
  assert.doesNotMatch(gallery, /border border-gray-200 bg-white/)
  const video = read('../app/pages/migration-video-preview.vue')
  assert.doesNotMatch(video, /group overflow-hidden rounded-chip border border-gray-200 bg-white/)
})

test('news and project touch targets meet 44px and editorial images keep their route ratios', () => {
  const newsFilters = read('../app/components/news/NewsFilters.vue')
  const newsPage = read('../app/pages/migration-news-preview.vue')
  const projectFilters = read('../app/components/projects/ProjectFilters.vue')
  const projectCard = read('../app/components/projects/ProjectCard.vue')
  assert.match(newsFilters, /min-h-11/)
  assert.match(projectFilters, /min-h-11/)
  assert.match(projectCard, /aspect-\[4\/3\]/)
  assert.doesNotMatch(projectCard, /border-b border-gray-200/)
})

test('solution imagery stays 8:5, empty CTA follows shared links, and equipment uses flat surfaces', () => {
  const intro = read('../app/components/solutions/SolutionsIntro.vue')
  const cards = read('../app/components/solutions/SolutionCards.vue')
  const equipment = read('../app/components/solutions/SolutionsEquipment.vue')
  const detail = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const gallery = read('../app/components/solution-detail/SolutionDetailGallery.vue')
  assert.match(intro, /aspect-\[8\/5\]/)
  assert.match(intro, /width="1200" height="750"/)
  assert.match(intro, /v-if="intro\.image"[\s\S]*?min-\[801px\]:grid-cols-2/)
  assert.match(intro, /figure class="order-2[^"]*min-\[801px\]:order-1/)
  assert.match(intro, /v-else class="grid grid-cols-1 items-start gap-0 min-\[801px\]:grid-cols-\[minmax\(0,1\.05fr\)_minmax\(0,\.95fr\)\] min-\[801px\]:gap-x-\[72px\] min-\[801px\]:gap-y-0/)
  assert.match(intro, /text-\[clamp\(34px,3\.2vw,48px\)\][^\n]*leading-\[1\.18\]/)
  assert.match(intro, /text-\[18px\] leading-\[1\.7\]/)
  assert.doesNotMatch(intro, /bg-gradient-to-br|border border-navy-800\/15|shadow-md/)
  assert.match(cards, /class="mosaic"/)
  assert.match(cards, /\.mosaic\.is-single \{ grid-template-columns: 1fr;/)
  assert.match(cards, /\.tile-image[\s\S]*object-fit: cover/)
  assert.match(cards, /pt-\[42px\] pb-\[54px\]/)
  assert.doesNotMatch(cards, /rounded-btn border border-slate-200\/90 bg-white/)
  assert.match(cards, /links\.generic\('\/lien-he\/#yeu-cau-tu-van'\)/)
  assert.match(detail, /class="solution-hero" :class="\{ 'has-image': data\.solution\.image \}"/)
  assert.match(detail, /\.solution-hero__image \{[^}]*object-fit: cover/)
  assert.match(gallery, /aspect-\[8\/5\]/)
  assert.doesNotMatch(equipment, /bg-slate-50|border border-gray-200|rounded-btn/)
  assert.ok(equipment.includes('/\\.(?:png|webp|avif)(?:[?#]|$)/i'))
  assert.doesNotMatch(equipment, /showroom-backdrop|payload\.categoryHero\?\.image/)
})

test('editorial text controls meet the 44px hit-area rule and project tabs support arrow-key navigation', () => {
  const news = read('../app/pages/migration-news-preview.vue')
  const policy = read('../app/pages/migration-policy-preview.vue')
  const solutionsHub = read('../app/pages/giai-phap.vue')
  const solutionsPreview = read('../app/pages/migration-solutions-preview.vue')
  const richText = read('../app/components/editorial/EditorialRichText.vue')
  const phpSolutionHub = read('../../domains/phucthinhsolar.com/public_html/templates/news/service_list_tpl.php')
  const phpSolutionStyles = read('../../domains/phucthinhsolar.com/public_html/assets/css/solution-hub.css')
  const solutionDetail = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const solutionIntro = read('../app/components/solutions/SolutionsIntro.vue')
  const equipment = read('../app/components/solutions/SolutionsEquipment.vue')
  const projectGallery = read('../app/components/project-detail/ProjectDetailGallery.vue')

  assert.equal((news.match(/inline-flex min-h-11 items-center[^\n]*news\.labels\.reset/g) || []).length, 2)
  assert.match(policy, /flex min-h-11 min-w-11 items-center border-l-2/)
  // Owner decision 2026-11-04: the hub editorial is restaged as chapters; the preview route keeps the TOC.
  const story = read('../app/components/solutions/SolutionsStory.vue')
  assert.match(solutionsHub, /<SolutionsStory v-if="data && !error && data\.editorial\.content\.html" :contents="\[data\.editorial\.content\]"/)
  assert.match(story, /<EditorialRichText v-for="\(content, index\) in contents" :key="index" :content="content" variant="solution" \/>/)
  assert.match(story, /\.story-index a \{[^}]*min-height: 72px/)
  assert.match(solutionsPreview, /<EditorialRichText :content="data\.editorial\.content" variant="solution" \/>/)
  assert.match(richText, /cms-content--editorial-solution h2[\s\S]*?border: 0;/)
  assert.match(phpSolutionHub, /aria-label="Mục lục trang Giải pháp"/)
  assert.match(phpSolutionStyles, /\.pts-sh-editorial-grid>\.pts-sd-toc\{max-height:calc\(100vh - 150px\);[^}]*background:var\(--sh-navy\)!important/)
  assert.match(solutionDetail, /\.solution-hero__primary \{[^}]*min-height: 58px/)
  assert.match(solutionIntro, /inline-flex min-h-11 items-center[^\n]*intro\.links\.explore/)
  assert.match(equipment, /\.showroom-all \{ display: inline-flex; min-height: 44px; align-items: center;/)
  assert.match(read('../app/components/project-detail/ProjectDetailPage.vue'), /inline-flex min-h-11 items-center whitespace-nowrap/)
  assert.match(projectGallery, /:tabindex="activeTab === 'image' \? 0 : -1"/)
  assert.match(projectGallery, /event\.key === 'ArrowRight'.*event\.key === 'ArrowDown'/)
  assert.match(projectGallery, /nextTick\(\(\) => .*\.focus\(\)\)/)
  assert.doesNotMatch(news, /rounded-full/)
})

test('project detail uses one content gutter and avoids eyebrow/decorative CTA text', () => {
  const projectDetail = read('../app/components/project-detail/ProjectDetailPage.vue')
  const featured = read('../app/components/projects/ProjectsFeatured.vue')
  assert.match(projectDetail, /<header[^>]*class="project-story__hero grid/)
  assert.match(projectDetail, /<div class="pts-container pb-10">/)
  assert.doesNotMatch(projectDetail, /project\.cta\.eyebrow|class="mb-3 font-bold text-gold-700"/)
  assert.doesNotMatch(featured, /aria-hidden="true">01</)
  assert.doesNotMatch(featured, /grid grid-cols-1 bg-slate-50/)
})

test('project detail preserves PHP section order, project media ratio, and missing-image fallback', () => {
  const projectDetail = read('../app/components/project-detail/ProjectDetailPage.vue')
  const phpDetail = read('../../domains/phucthinhsolar.com/public_html/templates/news/project_detail_tpl.php')
  const placeholder = read('../public/assets/images/noimage.png')
  const galleryIndex = projectDetail.indexOf('<ProjectDetailGallery')
  const equipmentIndex = projectDetail.indexOf('<ProjectDetailEquipment')
  const relatedIndex = projectDetail.indexOf('Dự án khác')
  const ctaIndex = projectDetail.indexOf('<PublicCtaBanner\n        v-if="project.cta"')
  const phpImageHelper = read('../../domains/phucthinhsolar.com/public_html/libraries/class/class.Functions.php')
  assert.ok(galleryIndex >= 0 && galleryIndex < equipmentIndex && equipmentIndex < relatedIndex && relatedIndex < ctaIndex)
  assert.match(phpDetail, /getImage\(\['class' => '', 'sizes' => '640x400x1'/)
  assert.match(phpImageHelper, /'image-error' => 'noimage\.png'/)
  assert.match(projectDetail, /optimizedImage\(item\.image, 800, 600\) \|\| '\/assets\/images\/noimage\.png'/)
  assert.match(projectDetail, /aspect-\[4\/3\]/)
  assert.ok(placeholder.length > 0)
})

test('project detail CTA uses the shared editorial banner and keeps its CMS content/actions', () => {
  // Owner decision 2026-11-04: one shared CTA band renders hotline + consultation for every page.
  const projectDetail = read('../app/components/project-detail/ProjectDetailPage.vue')
  const banner = read('../app/components/PublicCtaBanner.vue')
  assert.match(projectDetail, /<PublicCtaBanner\s+v-if="project\.cta"[\s\S]*variant="editorial"[\s\S]*:image="project\.cta\.image"/)
  assert.match(projectDetail, /:title="project\.cta\.title"[\s\S]*:description="project\.cta\.description"[\s\S]*:trust="ctaTrust"[\s\S]*:hotline="project\.cta\.hotline"[\s\S]*:button-label="project\.cta\.buttonLabel"/)
  assert.match(projectDetail, /:subject="'Dự án: ' \+ project\.title"/)
  assert.match(banner, /editorial: '\/assets\/images\/site-visuals\/cta-editorial-bg\.webp'/)
  assert.match(banner, /\.cta-band__image \{[^}]*object-fit: cover/)
  assert.match(banner, /@click\.prevent="openConsultation\(subject \|\| heading\)"/)
  assert.doesNotMatch(projectDetail, /project-detail-cta-overlay/)
})

import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'
import { publicEmailHref, publicPhoneHref } from '../shared/contact-links.ts'

const root = new URL('../', import.meta.url)
const read = (path) => readFile(new URL(path, root), 'utf8')

test('contact CTA follows the validated CMS target and PHP visibility rule', async () => {
  const [hero, php] = await Promise.all([
    read('app/components/contact/ContactHero.vue'),
    read('../domains/phucthinhsolar.com/public_html/templates/contact/contact_tpl.php'),
  ])
  assert.match(hero, /v-if="contact\.visual\?\.href"/)
  assert.match(hero, /links\.generic\(contact\.visual\.href\)/)
  assert.match(hero, /Gửi yêu cầu tư vấn/)
  assert.match(php, /if\(\$contactVisual && !empty\(\$contactVisual\['link'\]\)\)/)
  assert.match(php, />Gửi yêu cầu tư vấn/)
})

test('contact CTA hides when PHP has no published visual and does not invent a route', async () => {
  const hero = await read('app/components/contact/ContactHero.vue')
  assert.doesNotMatch(hero, /links\.generic\('\/lien-he\/#yeu-cau-tu-van'\)/)
  assert.match(hero, /v-if="contact\.visual\?\.href"/)
})

test('dealer navigation omits links to empty overview/support sections', async () => {
  const [page, sections] = await Promise.all([
    read('app/components/dealer/DealerPageContent.vue'),
    read('app/components/dealer/DealerSections.vue'),
  ])
  assert.match(page, /const quickLinks = computed\(\(\) =>/)
  assert.match(page, /dealer\.value\.overview\.descriptionContent\.html[\s\S]*dealer\.value\.overview\.products\.length/)
  assert.match(page, /dealer\.value\.support\.title \|\| dealer\.value\.support\.descriptionContent\.html \|\| dealer\.value\.support\.description \|\| dealer\.value\.support\.items\.length/)
  assert.match(sections, /v-if="dealer\.support\.title \|\| dealer\.support\.descriptionContent\.html \|\| dealer\.support\.description \|\| dealer\.support\.items\.length"/)
  assert.match(page, /max-\[767px\]:grid max-\[767px\]:grid-cols-3/)
  assert.match(page, /max-\[767px\]:min-h-\[70px\][\s\S]*max-\[767px\]:text-center/)
})

test('dealer distribution products become an arrow-controlled touch rail on mobile', async () => {
  const overview = await read('app/components/dealer/DealerOverview.vue')
  assert.match(overview, /Sản phẩm tiếp theo/)
  assert.match(overview, /Sản phẩm trước/)
  assert.match(overview, /scrollProducts\(direction: -1 \| 1\)/)
  assert.match(overview, /dealer-products-track[^\n]*snap-x snap-mandatory/)
  assert.match(overview, /scrollbar-width: none/)
  assert.match(overview, /min-\[768px\]:grid-cols-2[\s\S]*min-\[992px\]:grid-cols-3/)
})

test('about section navigation sticks below the header and tracks smooth section jumps', async () => {
  const about = await read('app/components/about/AboutPreviewPage.vue')
  assert.match(about, /sticky z-30[\s\S]*isHeaderHidden \? 'top-0 shadow-sm'/)
  assert.match(about, /<div class="overflow-x-clip text-navy-800">/)
  assert.doesNotMatch(about, /<div class="overflow-hidden text-navy-800">/)
  assert.match(about, /new IntersectionObserver\([\s\S]*activeAboutSection\.value = current\.id/)
  assert.match(about, /target\.scrollIntoView\(\{ behavior: reducedMotion \? 'auto' : 'smooth'/)
  assert.match(about, /aboutNavRef\.value\?\.querySelector<HTMLAnchorElement>/)
  assert.match(about, /aria-current="activeAboutSection === item\.id \? 'location' : undefined"/)
  assert.match(about, /scroll-mt-\[160px\].*max-\[800px\]:scroll-mt-\[116px\]/)
})

test('dealer support CMS icons preserve the PHP gold surface and contain fit', async () => {
  const sections = await read('app/components/dealer/DealerSections.vue')
  assert.match(sections, /bg-gold-300 p-2[\s\S]*object-contain/)
})

test('dealer hero title keeps the source-measured width so tablet copy does not wrap to an extra line', async () => {
  const page = await read('app/components/dealer/DealerPageContent.vue')
  const phpCss = await read('../domains/phucthinhsolar.com/public_html/assets/css/dealer-v2.css')
  assert.match(page, /max-w-\[680px\][^\n]*min-\[1800px\]:max-w-\[760px\]/)
  assert.match(phpCss, /\.pts-dl-hero-banner-copy\{display:flex;max-width:680px/)
})

test('dealer form shows recovery when session security or the city list is unavailable', async () => {
  const [form, leadForm] = await Promise.all([
    read('app/components/dealer/DealerLeadForm.vue'),
    read('app/composables/useLeadForm.ts'),
  ])
  assert.match(form, /Không thể chuẩn bị biểu mẫu và danh sách tỉnh\/thành phố/)
  assert.match(form, /Chưa tải được danh sách tỉnh\/thành phố/)
  assert.match(form, /role="alert"/)
  assert.match(form, /@click="loadSecurity\(true\)"/)
  assert.match(form, /:disabled="submitting \|\| securityLoading \|\| !cityOptions\.length"/)
  assert.match(form, /refresh \? await refreshSecurity\(\) : await ensureSecurity\(\)/)
  assert.match(leadForm, /async function refreshSecurity\(\)[\s\S]*security\.value = null[\s\S]*return ensureSecurity\(\)/)
})

test('contact form exposes security-load failure and a usable retry before enabling submit', async () => {
  const form = await read('app/components/contact/ContactLeadForm.vue')
  assert.match(form, /const securityError = ref\(''\)/)
  assert.match(form, /if \(refresh\) await refreshSecurity\(\)[\s\S]*securityError\.value = 'Không thể chuẩn bị biểu mẫu/)
  assert.match(form, /v-if="securityError"[\s\S]*role="alert"[\s\S]*@click="loadSecurity\(true\)"/)
  assert.match(form, /:disabled="submitting \|\| loadingSecurity \|\| !security"/)
  assert.match(form, /if \(loadingSecurity\.value \|\| !security\.value\)/)
})

test('About contact band uses the shared corporate CTA and retains its image contrast treatment', async () => {
  const [page, php, css, banner] = await Promise.all([
    read('app/components/about/AboutPreviewPage.vue'),
    read('../domains/phucthinhsolar.com/public_html/templates/about/about_tpl.php'),
    read('../domains/phucthinhsolar.com/public_html/assets/css/about-v2.css'),
    read('app/components/PublicCtaBanner.vue'),
  ])
  assert.match(page, /<PublicCtaBanner variant="corporate" :image="about\.cta\.image"/)
  assert.match(banner, /corporate: '\/assets\/images\/site-visuals\/cta-corporate-bg\.webp'/)
  assert.match(banner, /public-cta-banner__image[\s\S]*object-fit: cover/)
  assert.match(banner, /public-cta-banner__shade[\s\S]*linear-gradient/)
  assert.match(php, /--about-cta-image:url\(/)
  assert.match(css, /\.pts-about-contact-band\.has-image:before[\s\S]*background-size:cover/)
})

test('contact methods remain readable when CMS contact details cannot form safe actions', async () => {
  assert.equal(publicPhoneHref('0901 234 567'), 'tel:0901234567')
  assert.equal(publicPhoneHref('phone support'), '')
  assert.equal(publicEmailHref('sales@example.vn'), 'mailto:sales@example.vn')
  assert.equal(publicEmailHref('not-an-email'), '')
  assert.equal(publicEmailHref('sales@example.vn\r\n?body=x'), '')
  const [home, hero] = await Promise.all([
    read('app/components/home/HomeContact.vue'),
    read('app/components/contact/ContactHero.vue'),
  ])
  assert.match(home, /publicPhoneHref\(props\.contact\.hotline\)/)
  assert.match(home, /publicEmailHref\(props\.contact\.email\)/)
  assert.match(home, /v-if="phoneHref"[\s\S]*v-else/)
  assert.match(home, /v-if="emailHref"[\s\S]*v-else/)
  assert.match(hero, /publicPhoneHref\(props\.contact\.contact\.hotline\)/)
  assert.match(hero, /publicEmailHref\(props\.contact\.contact\.email\)/)
})


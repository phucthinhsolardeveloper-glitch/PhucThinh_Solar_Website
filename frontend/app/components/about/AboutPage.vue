<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'
import { isPublicCatalogPayload, type PublicCatalogPayload } from '#shared/contracts/public-catalog'

const props = defineProps<{ about: PublicAboutPayload }>()
const { data: brandCatalogData } = await useAsyncData<PublicCatalogPayload | null>('about-brand-catalog-options', async () => {
  const payload: unknown = await $fetch('/_api/catalog', { query: { per_page: '1' } })
  return isPublicCatalogPayload(payload) ? payload : null
}, { default: () => null })
const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()
const { isHeaderHidden } = useSiteHeader()
const aboutNavRef = ref<HTMLElement | null>(null)
const aboutNavCanScrollNext = ref(false)
let aboutNavResizeObserver: ResizeObserver | null = null
useActiveHorizontalRail(aboutNavRef)
useSlidingPill(aboutNavRef, 'about-sections', { chip: '.about-chapter-link' })
const activeAboutSection = ref('ve-phuc-thinh')
const galleryCertificates = computed(() => props.about.certificates.items)
const visibleCertificates = computed(() => {
  const featured = props.about.certificates.items.filter((item) => item.featured)
  return (featured.length ? featured : props.about.certificates.items).slice(0, 3)
})
const certificateDialog = ref<HTMLDialogElement | null>(null)
const certificateCloseButton = ref<HTMLButtonElement | null>(null)
const certificateTrigger = ref<HTMLElement | null>(null)
const certificateGalleryOpen = ref(false)
const activeCertificateIndex = ref(0)
const activeCertificate = computed(() => galleryCertificates.value[activeCertificateIndex.value] ?? null)
const partnerBrands = PARTNER_BRANDS
const partnerBrandsWithProducts = computed(() => partnerBrands.map((brand) => {
  const match = (brandCatalogData.value?.filters?.brands ?? []).find((option) => brandKey(option.name) === brandKey(brand.name))
  return { ...brand, href: match ? `/san-pham/?brand=${match.id}` : null }
}))
const aboutSectionNavItems = computed(() => [
  { id: 've-phuc-thinh', label: props.about.labels.story },
  { id: 'lich-su-phat-trien', label: 'Lịch sử hình thành' },
  { id: 'con-nguoi', label: props.about.labels.people },
  ...(props.about.trust.items.length ? [{ id: 'vi-sao-chon-chung-toi', label: props.about.trust.title }] : []),
  { id: 'thuong-hieu-dong-hanh', label: 'Thương hiệu đồng hành' },
  ...(props.about.compass.items.length ? [{ id: 'tam-nhin-su-menh', label: props.about.labels.compass }] : []),
  ...(props.about.certificates.items.length ? [{ id: 'about-certificates', label: props.about.labels.certificates }] : []),
])

const aboutSpy = useSectionSpy(() => aboutSectionNavItems.value.map((item) => item.id), activeAboutSection, aboutNavRef)

function navigateAboutSection(id: string) {
  const target = document.getElementById(id)
  if (!target) return
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  aboutSpy.lockTo(id)
  window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${id}`)
  target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' })
}

function syncAboutNavControls() {
  const nav = aboutNavRef.value
  aboutNavCanScrollNext.value = Boolean(nav && nav.scrollLeft + nav.clientWidth < nav.scrollWidth - 2)
}

// Blocks further down the page rise in as they arrive, a little staggered within each section.
const aboutRoot = ref<HTMLElement | null>(null)
let revealObserver: IntersectionObserver | undefined
function prepareReveals() {
  if (!aboutRoot.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return
  revealObserver = new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue
      entry.target.classList.add('rv-in')
      revealObserver?.unobserve(entry.target)
    }
  }, { rootMargin: '0px 0px -8% 0px' })
  aboutRoot.value.querySelectorAll('section:not(#lich-su-phat-trien):not(#ve-phuc-thinh)').forEach((section) => {
    section.querySelectorAll<HTMLElement>('h2, figure, li, article').forEach((block, index) => {
      // Items in a sideways rail are revealed by swiping, which an observer on the page never sees.
      if (block.closest('dialog, .m-rail') || block.getBoundingClientRect().top < window.innerHeight) return
      block.classList.add('rv')
      block.style.setProperty('--rv-delay', `${Math.min(index, 6) * 70}ms`)
      revealObserver?.observe(block)
    })
  })
}

onMounted(() => nextTick(() => {
  prepareReveals()
  syncAboutNavControls()
  if (aboutNavRef.value && typeof ResizeObserver !== 'undefined') {
    aboutNavResizeObserver = new ResizeObserver(syncAboutNavControls)
    aboutNavResizeObserver.observe(aboutNavRef.value)
  }
}))
watch(aboutSectionNavItems, () => nextTick(() => {
  aboutSpy.refresh()
  syncAboutNavControls()
}), { deep: true })
onBeforeUnmount(() => {
  aboutNavResizeObserver?.disconnect()
  revealObserver?.disconnect()
})

async function openCertificate(item: PublicAboutPayload['certificates']['items'][number], event: MouseEvent) {
  const index = galleryCertificates.value.findIndex((candidate) => candidate.id === item.id)
  if (index < 0) return
  certificateTrigger.value = event.currentTarget instanceof HTMLElement ? event.currentTarget : null
  activeCertificateIndex.value = index
  certificateGalleryOpen.value = true
  await nextTick()
  if (certificateDialog.value && !certificateDialog.value.open) certificateDialog.value.showModal()
  certificateCloseButton.value?.focus()
}

function openAllCertificates(event: MouseEvent) {
  const first = galleryCertificates.value[0]
  if (first) void openCertificate(first, event)
}

function previousCertificate() {
  if (activeCertificateIndex.value > 0) activeCertificateIndex.value--
}

function nextCertificate() {
  if (activeCertificateIndex.value < galleryCertificates.value.length - 1) activeCertificateIndex.value++
}

function closeCertificateGallery() {
  if (certificateDialog.value?.open) certificateDialog.value.close()
  else certificateGalleryOpen.value = false
}

function onCertificateDialogClose() {
  certificateGalleryOpen.value = false
  const trigger = certificateTrigger.value
  certificateTrigger.value = null
  if (trigger?.isConnected) nextTick(() => trigger.focus())
}

function onCertificateDialogBackdrop(event: MouseEvent) {
  if (event.target === event.currentTarget) closeCertificateGallery()
}
// Admin "Giới thiệu - Ảnh showroom" replaces the default showroom photo.
const showroomImage = computed(() => {
  const src = props.about.showroomImage || ''
  if (/^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i.test(src)) return thumbUrl(src, 780, 600)
  return src || '/assets/images/site-visuals/about-showroom-solar-equipment-20261104.webp'
})
</script>

<template>
  <div ref="aboutRoot" class="overflow-x-clip text-navy-800">
    <!-- Chapter 1: Story / Bản sắc thương hiệu -->
    <section id="ve-phuc-thinh" class="about-story scroll-mt-[160px] pts-container pt-6 pb-14 max-[800px]:scroll-mt-[116px] min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-story-title">
      <div class="about-story__card grid min-h-[470px] grid-cols-[minmax(410px,.88fr)_minmax(0,1.12fr)] gap-0 overflow-hidden rounded-btn border border-navy-800/15 bg-[linear-gradient(135deg,#061735,#10366a)] shadow-md max-[800px]:min-h-0 max-[800px]:grid-cols-1">
        <div class="about-story__copy flex flex-col justify-center p-[clamp(38px,4vw,64px)] max-[800px]:block max-[800px]:px-6 max-[800px]:pb-[38px] max-[800px]:pt-[34px] max-[600px]:px-5 max-[600px]:pb-[34px] max-[600px]:pt-[30px]">
          <h1 id="about-story-title" class="m-0 max-w-[620px] text-[clamp(36px,3.6vw,54px)] font-black leading-[1.12] tracking-[-.035em] text-white max-[800px]:text-[34px] max-[600px]:text-[31px]">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="mt-[17px] max-w-[690px] text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#e1ebf8]"><ChunkedText :text="about.story.intro" /></p>
          <p v-if="about.story.body" class="mt-4 border-t border-white/15 pt-4 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#bfcfe3]"><ChunkedText :text="about.story.body" /></p>
          <div class="about-story__actions mt-6 flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="about-story__primary inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-btn bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
              @click="openConsultation('Giới thiệu: ' + about.story.title)"
            >
              <span>Nhận tư vấn hợp tác</span>
              <span aria-hidden="true">→</span>
            </button>
            <a
              v-if="about.certificates.items.length"
              href="#about-certificates"
              class="m-lg-dark inline-flex min-h-11 items-center gap-2 rounded-btn border border-white/25 bg-white/5 px-4 py-2.5 text-sm font-bold text-white no-underline transition-colors hover:border-gold-400 hover:text-gold-300"
              @click.prevent="navigateAboutSection('about-certificates')"
            >
              <span>Xem chứng nhận năng lực</span>
              <span aria-hidden="true">↓</span>
            </a>
          </div>
        </div>
        <figure v-if="about.story.image" class="about-story__media m-0 h-full min-h-0 overflow-hidden bg-[#102d58] max-[800px]:aspect-[16/10]">
          <img class="block h-full w-full object-cover" :src="'/assets/images/site-visuals/about-storefront-retouched-20261104.webp'" :alt="about.story.title" width="1600" height="900" fetchpriority="high">
        </figure>
      </div>
    </section>

    <!-- Chapter Navigation: Thanh điều hướng chương trình tự động dính -->
    <nav class="m-glass-light sticky z-30 border-b border-slate-200 bg-white/95 backdrop-blur-md transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]" :class="isHeaderHidden ? 'top-0 shadow-sm' : 'top-[65px] min-[821px]:top-[88px] min-[1321px]:top-[104px] min-[1501px]:top-[120px] min-[1800px]:top-[136px]'" aria-label="Đi nhanh trong trang Giới thiệu">
      <div class="relative">
        <div ref="aboutNavRef" class="about-chapter-rail pts-container flex gap-1.5 overflow-x-auto py-1.5 [scrollbar-width:none] max-[800px]:gap-1 [&::-webkit-scrollbar]:hidden" aria-label="Mục nội dung Giới thiệu" @scroll.passive="syncAboutNavControls">
        <a
          v-for="item in aboutSectionNavItems"
          :key="item.id"
          :data-about-section="item.id"
          class="about-chapter-link inline-flex min-h-11 min-w-11 flex-none items-center justify-center rounded-chip px-3.5 text-[15px] font-extrabold no-underline transition-colors hover:text-gold-600 focus-visible:border-gold-500 focus-visible:text-gold-600"
          :class="activeAboutSection === item.id ? 'text-navy-950' : 'text-navy-800'"
          :href="`#${item.id}`"
          :aria-current="activeAboutSection === item.id ? 'location' : undefined"
          @click.prevent="navigateAboutSection(item.id)"
        >{{ item.label }}</a>
        </div>
        <span v-if="aboutNavCanScrollNext" class="pointer-events-none absolute inset-y-0 right-0 z-10 w-9 bg-gradient-to-l from-white via-white/85 to-transparent" aria-hidden="true" />
      </div>
    </nav>

    <!-- Chapter 2: History Timeline / Dòng thời gian phát triển -->
    <AboutHistory :image="showroomImage" />

    <!-- Chapter 3: People & Core Principles / Con người & Nguyên tắc hành động -->
    <section id="con-nguoi" class="about-chapter scroll-mt-[160px] bg-white py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-people-title">
      <div class="pts-container">
        <div class="about-people-grid grid grid-cols-2 items-center gap-[clamp(28px,5.4vw,86px)] max-[800px]:grid-cols-1">
          <figure v-if="about.people.image" class="about-people-media m-0 aspect-[10/7] overflow-hidden rounded-btn bg-navy-50 max-[800px]:max-h-[26rem]">
            <img class="block h-full w-full object-cover" :src="optimizedImage(about.people.image, 1200, 900)" :alt="about.people.title" width="780" height="620" loading="lazy">
          </figure>
          <div>
            <h2 id="about-people-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.people.title }}</h2>
            <p v-if="about.people.intro" class="mt-[1.1rem] text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ about.people.intro }}</p>
            <p v-if="about.people.body" class="mt-[1.1rem] border-t border-slate-200 pt-4 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ about.people.body }}</p>
          </div>
        </div>

        <!-- Hợp nhất Card 1: Principles (Chuẩn Monograph Card System) -->
        <div v-if="about.people.principles.length" class="about-principles mt-14 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article v-for="(item, index) in about.people.principles" :key="`${item.title}-${index}`" class="group border-t-2 border-slate-200 pt-5 transition-colors duration-200 hover:border-gold-500">
            <header class="mb-3.5 flex items-center justify-end">
              <img v-if="item.image" class="size-9 object-contain" :src="item.image" alt="" width="56" height="56" loading="lazy">
            </header>
            <h3 v-if="item.title" class="text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-navy-800">{{ item.title }}</h3>
            <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ item.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Chapter 4: Trust / Vì sao chọn chúng tôi (2 cột trên desktop) -->
    <section v-if="about.trust.items.length" id="vi-sao-chon-chung-toi" class="about-chapter scroll-mt-[160px] bg-slate-50 py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-trust-title">
      <div class="about-trust-grid pts-container grid grid-cols-[minmax(0,.7fr)_minmax(0,1.3fr)] items-start gap-16 max-[800px]:grid-cols-1 max-[800px]:gap-7">
        <div>
          <h2 id="about-trust-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.trust.title }}</h2>
        </div>

        <!-- Hợp nhất Card 2: Trust (khớp regex grid grid-cols-2 ... max-[800px]:grid-cols-1) -->
        <div class="about-trust-list grid grid-cols-2 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article v-for="(item, index) in about.trust.items" :key="`${item.title}-${index}`" class="group border-t-2 border-slate-200 pt-5 transition-colors duration-200 hover:border-gold-500">
            <header class="mb-3.5 flex items-center justify-end">
              <img v-if="item.image" class="size-10 object-contain" :src="item.image" alt="" width="64" height="64" loading="lazy">
            </header>
            <h3 v-if="item.title" class="text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-navy-800">{{ item.title }}</h3>
            <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ item.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="thuong-hieu-dong-hanh" class="about-brand-section scroll-mt-[160px] max-[800px]:scroll-mt-[116px]" aria-labelledby="about-brands-title">
      <div class="pts-container about-brand-layout">
        <div class="about-brand-copy">
          <h2 id="about-brands-title">Thương hiệu đồng hành</h2>
          <p>Từ thiết bị năng lượng đến chiếu sáng, những thương hiệu đã đồng hành cùng Phúc Thịnh Solar.</p>
        </div>
        <div class="about-brand-board" aria-label="Các thương hiệu thiết bị">
          <span class="about-brand-circuit" aria-hidden="true" />
          <ul class="about-brand-grid">
            <li v-for="brand in partnerBrandsWithProducts" :key="brand.name" class="about-brand-cell" :class="{ 'about-brand-cell-link': brand.href }">
              <NuxtLink v-if="brand.href" :to="brand.href" :aria-label="`Xem sản phẩm ${brand.name}`">
                <img :src="`/assets/images/brand-logos/${brand.logo}`" :alt="brand.name" width="480" height="160" loading="lazy" decoding="async">
              </NuxtLink>
              <img v-else :src="`/assets/images/brand-logos/${brand.logo}`" :alt="brand.name" width="480" height="160" loading="lazy" decoding="async">
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Chapter 5: Compass / Tầm nhìn - Sứ mệnh - Giá trị (Dark Stage Tone) -->
    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="about-chapter scroll-mt-[160px] bg-navy-950 py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-compass-title">
      <div class="pts-container">
        <h2 id="about-compass-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22] text-white">{{ about.compass.title }}</h2>

        <!-- Hợp nhất Card 3: Compass (Đồng bộ ngôn ngữ với Dark Tone) -->
        <div class="about-compass-rail m-rail mt-10 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`" class="group border-t-2 border-white/20 pt-5 transition-colors duration-200 hover:border-gold-400">
            <header class="mb-3.5 flex items-center justify-between gap-4">
              <h3 v-if="item.title" class="m-0 text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-gold-300">{{ item.title }}</h3>
              <img v-if="item.image" class="about-compass-icon size-9 flex-none object-contain" :src="item.image" alt="" width="56" height="56" loading="lazy">
            </header>
            <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#d1daea]">{{ item.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Chapter 6: Certificates & Proof / Năng lực & Chứng nhận đã kiểm chứng -->
    <section v-if="about.certificates.items.length" id="about-certificates" class="about-chapter scroll-mt-[160px] bg-white py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-certificates-title">
      <div class="pts-container">
        <header class="about-cert-head grid grid-cols-1 items-end gap-5 min-[801px]:grid-cols-[minmax(0,1fr)_auto]">
          <div>
            <h2 id="about-certificates-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.certificates.title }}</h2>
            <p class="mt-3 max-w-[48rem] text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ about.certificates.intro }}</p>
          </div>
          <button
            v-if="galleryCertificates.length"
            type="button"
            class="mb-1 inline-flex min-h-11 items-center gap-2 justify-self-start font-bold text-navy-800 underline-offset-4 hover:underline min-[801px]:justify-self-end"
            @click="openAllCertificates"
          >
            {{ about.labels.certAll }} <span aria-hidden="true">→</span>
          </button>
        </header>

        <!-- Hợp nhất Card 4: Certificates (Đồng bộ ngôn ngữ Atelier Grid) -->
        <div class="about-cert-rail m-rail mt-10 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article
            v-for="item in visibleCertificates"
            :key="item.id"
            class="group flex flex-col justify-between border-t-2 border-slate-200 pt-5 transition-colors duration-200 hover:border-gold-500"
          >
            <div>
              <div v-if="item.image" class="about-cert-thumb-wrap mb-4 overflow-hidden rounded-sm bg-slate-50 p-4 border border-slate-100">
                <button
                  type="button"
                  class="about-cert-thumb block w-full h-48 focus:outline-none cursor-pointer"
                  :aria-label="`Mở chứng nhận ${item.name}`"
                  @click="openCertificate(item, $event)"
                >
                  <img class="block h-full w-full object-contain transition-transform duration-300 group-hover:scale-105" :src="optimizedImage(item.image, 600, 800)" :alt="item.name" width="260" height="360" loading="lazy">
                </button>
              </div>
              <span class="text-[15px] font-bold text-gold-700">{{ about.labels.certLabel }}</span>
              <h3 class="mt-1.5 text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-navy-800">{{ item.name }}</h3>
              <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ item.description }}</p>
            </div>
            <div v-if="item.image" class="about-cert-more-wrap mt-4 pt-3 border-t border-slate-100">
              <button type="button" class="about-cert-more inline-flex items-center gap-1.5 text-[15px] font-bold text-navy-800 hover:text-gold-700 transition-colors cursor-pointer" @click="openCertificate(item, $event)">
                {{ about.labels.certDetail }} <span aria-hidden="true">→</span>
              </button>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- Certificate Modal Dialog (A11y & Keyboard Navigation) -->
    <dialog
      v-if="certificateGalleryOpen && activeCertificate"
      ref="certificateDialog"
      class="about-certificate-dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="about-certificate-dialog-title"
      @close="onCertificateDialogClose"
      @cancel.prevent="closeCertificateGallery"
      @keydown.left.prevent="previousCertificate"
      @keydown.right.prevent="nextCertificate"
      @click="onCertificateDialogBackdrop"
    >
      <div class="about-certificate-modal">
        <header class="about-certificate-modal-header">
          <div class="about-certificate-modal-title">
            <span class="about-modal-mark" aria-hidden="true">PTS</span>
            <div>
              <h2 id="about-certificate-dialog-title">{{ activeCertificate.name || about.labels.certLabel }}</h2>
              <span aria-live="polite">{{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</span>
            </div>
          </div>
          <button ref="certificateCloseButton" type="button" class="about-certificate-close" aria-label="Đóng xem chứng nhận" @click="closeCertificateGallery">Đóng <span aria-hidden="true">×</span></button>
        </header>
        <div class="about-certificate-modal-image">
          <button type="button" aria-label="Chứng nhận trước" :disabled="activeCertificateIndex === 0" @click="previousCertificate">←</button>
          <img v-if="activeCertificate.image" :key="activeCertificate.id" :src="activeCertificate.image" :alt="activeCertificate.name">
          <div v-else role="status">Ảnh chứng nhận này chưa được cập nhật.</div>
          <button type="button" aria-label="Chứng nhận tiếp theo" :disabled="activeCertificateIndex === galleryCertificates.length - 1" @click="nextCertificate">→</button>
        </div>
        <footer class="about-certificate-modal-footer">
          <p v-if="activeCertificate.description">{{ activeCertificate.description }}</p>
          <span>Hồ sơ {{ activeCertificateIndex + 1 }} / {{ galleryCertificates.length }}</span>
        </footer>
      </div>
    </dialog>

    <!-- Chapter 7: Corporate CTA / Điểm chốt chuyển đổi đồng hành -->
    <section class="about-contact py-12">
      <div class="pts-container">
        <PublicCtaBanner
          variant="corporate"
          :image="about.cta.image"
          title-id="about-cta-title"
          :title="about.cta.title"
          :description="about.cta.description"
          :hotline="about.cta.hotline?.text"
          :zalo="about.cta.zaloUrl || undefined"
          :button-label="about.cta.buttonLabel"
          :subject="'Giới thiệu: ' + about.cta.title"
          :fallback-href="about.cta.href || '/lien-he/#yeu-cau-tu-van'"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-compass-icon { filter: brightness(0) saturate(100%) invert(79%) sepia(38%) saturate(620%) hue-rotate(358deg) brightness(101%) contrast(92%); }
.about-brand-section {
  position: relative;
  overflow: clip;
  padding-block: clamp(64px, 8vw, 112px);
  background:
    radial-gradient(ellipse at 82% 50%, rgb(24 65 119 / 36%), transparent 48%),
    linear-gradient(112deg, #061537 0%, #081b3d 56%, #0b2450 100%);
  color: #fff;
}
.about-brand-layout {
  display: grid;
  grid-template-columns: minmax(220px, .62fr) minmax(0, 1.38fr);
  align-items: center;
  gap: clamp(32px, 6vw, 92px);
}
.about-brand-copy h2 {
  max-width: 10ch;
  margin: 0;
  font-size: clamp(34px, 4.3vw, 60px);
  font-weight: 850;
  line-height: 1.02;
  letter-spacing: -.04em;
  text-wrap: balance;
}
.about-brand-copy p {
  max-width: 34ch;
  margin: 20px 0 0;
  color: #c3cfdf;
  font-size: clamp(16px, 1.2vw, 18px);
  line-height: 1.7;
}
.about-brand-board {
  position: relative;
  isolation: isolate;
  padding: 1px;
  border: 1px solid rgb(231 190 103 / 42%);
  border-radius: 12px;
  background: linear-gradient(135deg, rgb(237 190 94 / 62%), rgb(255 255 255 / 12%) 40%, rgb(237 190 94 / 24%));
  box-shadow: 0 28px 80px rgb(1 10 30 / 34%), inset 0 1px 0 rgb(255 255 255 / 34%);
  transform: perspective(1400px) rotateY(-3deg) rotateX(1deg);
  transition: transform .55s cubic-bezier(.22, 1, .36, 1), box-shadow .55s cubic-bezier(.22, 1, .36, 1);
}
.about-brand-board:hover { transform: perspective(1400px) rotateY(0) rotateX(0) translateY(-3px); box-shadow: 0 34px 88px rgb(1 10 30 / 40%), inset 0 1px 0 rgb(255 255 255 / 34%); }
.about-brand-circuit {
  position: absolute;
  top: -14px;
  left: 12%;
  z-index: -1;
  width: 42%;
  height: 14px;
  border-top: 1px solid rgb(235 187 91 / 72%);
  border-right: 1px solid rgb(235 187 91 / 72%);
  border-radius: 0 10px 0 0;
}
.about-brand-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 1px;
  overflow: hidden;
  margin: 0;
  padding: 1px;
  border-radius: 11px;
  background: linear-gradient(135deg, rgb(7 25 58 / 96%), rgb(15 39 76 / 96%));
  list-style: none;
}
.about-brand-cell {
  position: relative;
  display: grid;
  min-width: 0;
  min-height: clamp(68px, 6.4vw, 100px);
  place-items: center;
  overflow: hidden;
  padding: 16px clamp(10px, 1.5vw, 24px);
  background: linear-gradient(145deg, rgb(239 244 250 / 96%), rgb(218 228 239 / 95%));
  transition: background .3s ease, transform .3s ease;
}
.about-brand-cell::after {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 120%, rgb(224 176 79 / 27%), transparent 64%);
  content: '';
  opacity: 0;
  transition: opacity .3s ease;
}
.about-brand-cell > a { position: relative; z-index: 1; display: grid; width: 100%; min-height: inherit; place-items: center; outline-offset: -4px; }
.about-brand-cell > a:focus-visible { outline: 2px solid var(--color-gold-600); }
.about-brand-cell img { position: relative; z-index: 1; display: block; width: min(100%, 150px); max-height: 42px; object-fit: contain; transition: transform .35s cubic-bezier(.22, 1, .36, 1), filter .35s ease; }
.about-brand-cell:hover { z-index: 2; background: #fff; }
.about-brand-cell:focus-within { z-index: 2; background: #fff; }
.about-brand-cell:hover::after { opacity: 1; }
.about-brand-cell:focus-within::after { opacity: 1; }
.about-brand-cell:hover img { transform: scale(1.08); filter: drop-shadow(0 5px 8px rgb(6 21 55 / 14%)); }
@media (max-width: 800px) {
  .about-brand-layout { grid-template-columns: 1fr; gap: 30px; }
  .about-brand-copy h2 { max-width: 14ch; }
  .about-brand-board { transform: none; }
  .about-brand-board:hover { transform: translateY(-2px); }
  .about-brand-cell { min-height: 82px; }
  .about-brand-cell img { max-width: 138px; }
}
@media (max-width: 520px) {
  .about-brand-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .about-brand-cell { min-height: 78px; padding-inline: 12px; }
}
@media (prefers-reduced-motion: reduce) {
  .about-brand-board, .about-brand-cell, .about-brand-cell::after, .about-brand-cell img { transition: none; }
}
/* Certificate Dialog & Modal */
.about-certificate-dialog { position: fixed; inset: 0; width: min(calc(100% - 24px), 1040px); max-width: none; max-height: calc(100dvh - 24px); margin: auto; overflow: visible; border: 0; padding: 0; background: transparent; color: var(--color-navy-800); }
.about-certificate-dialog::backdrop { background: rgb(240 245 252 / 58%); -webkit-backdrop-filter: blur(22px) saturate(150%); backdrop-filter: blur(22px) saturate(150%); }
.about-certificate-modal { display: flex; max-height: calc(100dvh - 24px); flex-direction: column; overflow: hidden; border: 0; border-radius: var(--radius-btn); background: transparent; }
.about-certificate-modal-header, .about-certificate-modal-footer { display: flex; flex: none; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 22px; }
.about-certificate-modal-header { border-bottom: 0; background: transparent; color: var(--color-navy-950); }
.about-certificate-modal-title { display: flex; min-width: 0; align-items: center; gap: 14px; }
.about-certificate-modal-title h2 { overflow: hidden; margin: 0; color: var(--color-navy-950); font-size: 18px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.about-certificate-modal-title span:not(.about-modal-mark) { display: inline-block; margin-top: 5px; color: #475569; font-size: 12px; }
.about-modal-mark { display: grid; width: 40px; height: 40px; flex: none; place-items: center; border: 1px solid var(--color-gold-500); border-radius: var(--radius-btn); background: rgb(255 255 255 / 80%); color: var(--color-gold-700); font-size: 11px; font-weight: 800; }
.about-certificate-close { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; border: 1px solid rgb(255 255 255 / 90%); border-radius: var(--radius-btn); padding: 0 14px; background: rgb(255 255 255 / 92%); box-shadow: 0 6px 18px rgb(6 21 55 / 16%); color: var(--color-navy-950); font: inherit; font-weight: 700; cursor: pointer; }
.about-certificate-close span { color: var(--color-gold-700); font-size: 23px; line-height: 1; }
.about-certificate-modal-image { position: relative; display: flex; min-height: 260px; flex: 1; align-items: center; justify-content: center; gap: 12px; overflow-y: auto; padding: clamp(16px, 4vw, 36px); background: transparent; }
.about-certificate-modal-image img { display: block; max-width: min(100%, 760px); max-height: min(66dvh, 680px); object-fit: contain; filter: drop-shadow(0 16px 26px rgb(6 21 55 / 26%)); }
.about-certificate-modal-image > div { display: grid; min-height: 180px; place-items: center; padding: 24px; color: var(--color-navy-800); text-align: center; }
.about-certificate-modal-image button { display: grid; width: 44px; height: 44px; flex: none; place-items: center; border: 1px solid rgb(255 255 255 / 90%); border-radius: 50%; background: rgb(255 255 255 / 92%); box-shadow: 0 6px 18px rgb(6 21 55 / 16%); color: var(--color-navy-950); font-size: 20px; cursor: pointer; }
.about-certificate-modal-image button:disabled { cursor: not-allowed; opacity: .35; }
.about-certificate-modal-image button:focus-visible { outline: 2px solid var(--color-gold-700); outline-offset: 2px; }
.about-certificate-modal-footer { justify-content: center; border-top: 0; }
.about-certificate-modal-footer p { margin: 0; color: var(--color-navy-800); font-size: 16px; line-height: 1.6; text-align: center; }
.about-certificate-modal-footer > span { flex: none; padding: 4px 12px; border-radius: var(--radius-btn); background: rgb(255 255 255 / 92%); color: var(--color-navy-950); font-size: 12px; font-weight: 700; }

@media (max-width: 560px) {
  .about-certificate-modal-header, .about-certificate-modal-footer { padding: 13px 14px; }
  .about-certificate-modal-image { min-height: 200px; gap: 8px; padding: 12px; }
  .about-certificate-modal-image button { width: 40px; height: 40px; }
  .about-certificate-modal-footer { align-items: flex-start; flex-direction: column; gap: 8px; }
}
/* Phones (<=820px): one clear hierarchy per chapter, sized for a thumb. Desktop rules above stay untouched. */
@media (max-width: 820px) {
  .about-chapter { padding-block: 40px; scroll-margin-top: 128px; }
  .about-chapter h2 { font-size: 26px; font-weight: 800; line-height: 1.18; letter-spacing: -.02em; text-wrap: balance; }

  /* Story: the storefront photo opens the card, then title, intro and two full-width actions. */
  .about-story { padding-block: 12px 24px; scroll-margin-top: 128px; }
  .about-story__card { display: flex; min-height: 0; flex-direction: column; border-radius: var(--radius-lg); }
  .about-story__media { order: -1; height: auto; aspect-ratio: 16 / 9; }
  .about-story__copy { display: block; padding: 20px 18px; }
  .about-story__copy p { margin-top: 12px; font-size: 16px; line-height: 1.6; }
  .about-story__actions { display: grid; gap: 10px; margin-top: 18px; }
  .about-story__actions > * { justify-content: space-between; min-height: 48px; padding-inline: 16px; }
  .about-story__primary { background: var(--pts-gold-gradient); }

  /* Chapter bar: pills, so the current chapter reads at a glance. */
  .about-chapter-rail { gap: 4px; padding-block: 6px; scroll-padding-inline: 16px; }
  .about-chapter-link { padding-inline: 14px; border-bottom-width: 0; border-radius: 999px; font-size: 14px; font-weight: 700; }
  .about-chapter-link[aria-current="location"] { color: var(--color-navy-950); }
  .about-chapter-link:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: -3px; }

  /* History */

  /* People: compact icon rows instead of a right-aligned icon floating above each paragraph. */
  .about-people-grid { gap: 18px; }
  .about-people-media { max-height: none; aspect-ratio: 16 / 9; }
  .about-people-grid p { margin-top: 10px; font-size: 16px; line-height: 1.6; }
  .about-principles { gap: 0; margin-top: 20px; }
  .about-principles > article { display: grid; grid-template-columns: 40px minmax(0, 1fr); column-gap: 14px; padding-block: 14px; border-top-width: 1px; }
  .about-principles header { grid-row: 1 / span 2; align-items: flex-start; justify-content: flex-start; margin: 0; }
  .about-principles h3 { grid-column: 2; font-size: 17px; }
  .about-principles p { grid-column: 2; margin-top: 3px; font-size: 15px; line-height: 1.5; }

  /* Trust: short peer statements read best as a two-column tile grid. */
  .about-trust-grid { gap: 16px; }
  .about-trust-list { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
  .about-trust-list > article { padding: 14px 12px; border: 1px solid rgb(10 34 81 / 8%); border-radius: var(--radius-md); background: #fff; box-shadow: var(--pts-shadow-soft); }
  .about-trust-list > article:last-child:nth-child(odd) { grid-column: 1 / -1; }
  .about-trust-list header { justify-content: flex-start; margin-bottom: 10px; }
  .about-trust-list img { width: 32px; height: 32px; }
  .about-trust-list h3 { font-size: 16px; }
  .about-trust-list p { margin-top: 0; font-size: 15px; line-height: 1.45; }

  /* Brands: same gold-framed board of light tiles on navy, tightened. */
  .about-brand-section { padding-block: 44px; scroll-margin-top: 128px; }
  .about-brand-layout { gap: 26px; }
  .about-brand-copy h2 { max-width: 16ch; font-size: 30px; line-height: 1.08; }
  .about-brand-copy p { margin-top: 10px; font-size: 16px; line-height: 1.6; }

  /* Compass and certificates: peers on a swipe rail. */
  .about-compass-rail { margin-top: 20px; }
  .about-compass-rail > article { padding: 16px; border: 1px solid rgb(255 255 255 / 12%); border-radius: var(--radius-md); background: linear-gradient(160deg, rgb(255 255 255 / 8%), rgb(255 255 255 / 2%)); }
  .about-compass-rail > article { flex-basis: min(82%, 320px); }
  .about-compass-rail header { justify-content: flex-start; margin-bottom: 10px; }
  .about-compass-rail img { filter: none; opacity: 1; }
  .about-compass-rail p { margin-top: 0; font-size: 16px; line-height: 1.55; }

  .about-cert-head { gap: 6px; }
  .about-cert-head p { margin-top: 8px; font-size: 16px; line-height: 1.6; }
  .about-cert-rail { margin-top: 12px; }
  .about-cert-rail > article { flex-basis: min(66%, 260px); padding: 10px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md); background: #fff; }
  .about-cert-thumb-wrap { margin-bottom: 10px; padding: 8px; }
  .about-cert-thumb { height: 176px; }
  .about-cert-rail h3 { margin-top: 2px; font-size: 18px; }
  .about-cert-more-wrap { margin-top: 6px; padding-top: 0; }
  .about-cert-more { min-height: 44px; }

  .about-contact { padding-block: 24px; }

  /* Lightbox arrows float over the certificate so the document keeps the full width. */
  .about-certificate-modal-image { gap: 0; padding: 12px; }
  .about-certificate-modal-image button { position: absolute; top: 50%; z-index: 1; width: 44px; height: 44px; transform: translateY(-50%); }
  .about-certificate-modal-image button:first-child { left: 8px; }
  .about-certificate-modal-image button:last-child { right: 8px; }
  .about-certificate-modal-image img { max-width: 100%; }
}
@media (max-width: 520px) {
  /* Four logos per row keeps all twenty brands in five even rows. */
  .about-brand-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
  .about-brand-cell { min-height: 62px; padding: 6px; }
  .about-brand-cell img { width: min(100%, 80px); max-height: 30px; }
}
@media (hover: none) {
  .about-brand-board:hover { transform: none; }
  .about-brand-cell:hover img { transform: none; filter: none; }
}
@media (max-width: 359px) {
  .about-compass-rail, .about-cert-rail { margin-inline: -12px; padding-inline: 12px; }
}
</style>

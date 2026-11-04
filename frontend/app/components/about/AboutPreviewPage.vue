<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'
import { isPublicCatalogPayload, type PublicCatalogPayload } from '#shared/contracts/public-catalog'

const props = defineProps<{ about: PublicAboutPayload }>()
const { data: brandCatalogData } = await useAsyncData<PublicCatalogPayload | null>('about-brand-catalog-options', async () => {
  const payload: unknown = await $fetch('/_migration/catalog', { query: { per_page: '1' } })
  return isPublicCatalogPayload(payload) ? payload : null
}, { default: () => null })
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const { isHeaderHidden } = useSiteHeader()
const aboutNavRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(aboutNavRef)
const activeAboutSection = ref('ve-phuc-thinh')
let aboutSectionObserver: IntersectionObserver | null = null
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
const partnerBrands = [
  { name: 'AESOLAR', logo: 'aesolar.png' },
  { name: 'Blue Carbon', logo: 'bluecarbon.png' },
  { name: 'Canadian Solar', logo: 'canadiansolar.png' },
  { name: 'EPSIVO', logo: 'epsivo.png' },
  { name: 'Hober', logo: 'hober.png' },
  { name: 'Huawei', logo: 'huawei.png' },
  { name: 'Jindian', logo: 'jdjindian.png' },
  { name: 'LONGi', logo: 'longi.png' },
  { name: 'Oriana', logo: 'oriana.png' },
  { name: 'Sharp', logo: 'sharp.png' },
  { name: 'SolaX', logo: 'solax.png' },
  { name: 'Sungrow', logo: 'sungrow.png' },
]
function normalizeBrandName(value: string): string {
  return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '')
}
const partnerBrandsWithProducts = computed(() => partnerBrands.map((brand) => {
  const name = normalizeBrandName(brand.name)
  const aliases = brand.name === 'Jindian' ? [name, 'jdjindian'] : [name]
  const match = (brandCatalogData.value?.filters?.brands ?? []).find((option) => aliases.includes(normalizeBrandName(option.name)))
  return { ...brand, href: match ? `/san-pham/?brand=${match.id}` : null }
}))
const aboutSectionNavItems = computed(() => [
  { id: 've-phuc-thinh', label: props.about.labels.story },
  ...(props.about.history.items.length ? [{ id: 'lich-su-phat-trien', label: props.about.labels.history }] : []),
  { id: 'con-nguoi', label: props.about.labels.people },
  ...(props.about.trust.items.length ? [{ id: 'vi-sao-chon-chung-toi', label: props.about.trust.title }] : []),
  { id: 'thuong-hieu-dong-hanh', label: 'Thương hiệu đồng hành' },
  ...(props.about.compass.items.length ? [{ id: 'tam-nhin-su-menh', label: props.about.labels.compass }] : []),
  ...(props.about.certificates.items.length ? [{ id: 'about-certificates', label: props.about.labels.certificates }] : []),
])

function setupAboutSectionObserver() {
  aboutSectionObserver?.disconnect()
  if (!import.meta.client) return
  const sections = aboutSectionNavItems.value
    .map((item) => document.getElementById(item.id))
    .filter((section): section is HTMLElement => Boolean(section))
  if (!sections.length) return
  aboutSectionObserver = new IntersectionObserver((entries) => {
    const current = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)[0]?.target as HTMLElement | undefined
    if (current?.id) activeAboutSection.value = current.id
  }, { rootMargin: '-150px 0px -58% 0px', threshold: [0, 0.01] })
  sections.forEach((section) => aboutSectionObserver?.observe(section))
}

function navigateAboutSection(id: string) {
  const target = document.getElementById(id)
  if (!target) return
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  activeAboutSection.value = id
  window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${id}`)
  target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' })
}

onMounted(() => nextTick(setupAboutSectionObserver))
watch(aboutSectionNavItems, () => nextTick(setupAboutSectionObserver), { deep: true })
onBeforeUnmount(() => aboutSectionObserver?.disconnect())

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
</script>

<template>
  <div class="overflow-x-clip text-navy-800">
    <!-- Chapter 1: Story / Bản sắc thương hiệu -->
    <section id="ve-phuc-thinh" class="scroll-mt-[160px] pts-container pt-6 pb-14 max-[800px]:scroll-mt-[116px] min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-story-title">
      <div class="grid min-h-[470px] grid-cols-[minmax(410px,.88fr)_minmax(0,1.12fr)] gap-0 overflow-hidden rounded-btn border border-navy-800/15 bg-[linear-gradient(135deg,#061735,#10366a)] shadow-md max-[800px]:min-h-0 max-[800px]:grid-cols-1">
        <div class="flex flex-col justify-center p-[clamp(38px,4vw,64px)] max-[800px]:block max-[800px]:px-6 max-[800px]:pb-[38px] max-[800px]:pt-[34px] max-[600px]:px-5 max-[600px]:pb-[34px] max-[600px]:pt-[30px]">
          <h1 id="about-story-title" class="m-0 max-w-[620px] text-[clamp(36px,3.6vw,54px)] font-black leading-[1.12] tracking-[-.035em] text-white max-[800px]:text-[34px] max-[600px]:text-[31px]">{{ about.story.title }}</h1>
          <p v-if="about.story.intro" class="mt-[17px] max-w-[690px] text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#e1ebf8]">{{ about.story.intro }}</p>
          <p v-if="about.story.body" class="mt-4 border-t border-white/15 pt-4 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#bfcfe3]">{{ about.story.body }}</p>
          <div class="mt-6 flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-[4px] bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
              @click="openConsultation('Giới thiệu: ' + about.story.title)"
            >
              <span>Nhận tư vấn hợp tác</span>
              <span aria-hidden="true">→</span>
            </button>
            <a
              v-if="about.certificates.items.length"
              href="#about-certificates"
              class="inline-flex min-h-11 items-center gap-2 rounded-[4px] border border-white/25 bg-white/5 px-4 py-2.5 text-sm font-bold text-white no-underline transition-colors hover:border-gold-400 hover:text-gold-300"
              @click.prevent="navigateAboutSection('about-certificates')"
            >
              <span>Xem chứng nhận năng lực</span>
              <span aria-hidden="true">↓</span>
            </a>
          </div>
        </div>
        <figure v-if="about.story.image" class="m-0 h-full min-h-0 overflow-hidden bg-[#102d58] max-[800px]:aspect-[16/10]">
          <img class="block h-full w-full object-cover" :src="'/assets/images/site-visuals/about-storefront-retouched-20261104.png'" :alt="about.story.title" width="1600" height="900" fetchpriority="high">
        </figure>
      </div>
    </section>

    <!-- Chapter Navigation: Thanh điều hướng chương trình tự động dính -->
    <nav class="sticky z-30 border-b border-slate-200 bg-white/95 backdrop-blur-md transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]" :class="isHeaderHidden ? 'top-0 shadow-sm' : 'top-[65px] min-[821px]:top-[88px] min-[1321px]:top-[104px] min-[1501px]:top-[120px] min-[1800px]:top-[136px]'" aria-label="Đi nhanh trong trang Giới thiệu">
      <div ref="aboutNavRef" class="pts-container flex gap-6 overflow-x-auto py-1 [scrollbar-width:none] max-[800px]:gap-[18px] [&::-webkit-scrollbar]:hidden" aria-label="Mục nội dung Giới thiệu">
        <a
          v-for="item in aboutSectionNavItems"
          :key="item.id"
          :data-about-section="item.id"
          class="inline-flex min-h-11 min-w-11 flex-none items-center justify-center border-b-2 px-2 text-[15px] font-extrabold no-underline transition-colors hover:border-gold-500 hover:text-gold-600 focus-visible:border-gold-500 focus-visible:text-gold-600"
          :class="activeAboutSection === item.id ? 'border-gold-500 text-gold-600' : 'border-transparent text-navy-800'"
          :href="`#${item.id}`"
          :aria-current="activeAboutSection === item.id ? 'location' : undefined"
          @click.prevent="navigateAboutSection(item.id)"
        >{{ item.label }}</a>
      </div>
    </nav>

    <!-- Chapter 2: History Timeline / Dòng thời gian phát triển -->
    <section v-if="about.history.items.length" id="lich-su-phat-trien" class="scroll-mt-[160px] bg-slate-50 py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-history-title">
      <div class="pts-container grid grid-cols-2 items-stretch gap-[clamp(30px,5.4vw,86px)] max-[800px]:grid-cols-1">
        <div>
          <h2 id="about-history-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.history.title || about.labels.history }}</h2>
          <ol class="mt-7 grid list-none gap-6 border-l-2 border-gold-500/45 pl-6 p-0" :aria-label="about.labels.timeline">
            <li v-for="(item, index) in about.history.items" :key="`${item.year}-${index}`" class="relative grid grid-cols-1 gap-x-4 border-t border-slate-200/80 pt-5 first:border-t-0 first:pt-0">
              <span class=" absolute -left-[31px] top-1.5 first:top-1.5 size-3 rounded-full border-2 border-gold-500 bg-white" :class="index > 0 ? 'top-6' : 'top-1.5'" aria-hidden="true" />
              <div>
                <strong class="inline-flex items-center rounded-[3px] bg-gold-500/15 px-2.5 py-0.5 text-[1.2rem] font-black leading-[1.2] text-gold-800">{{ item.year }}</strong>
              </div>
              <h3 v-if="item.title && about.history.items.length > 1" class="mt-2 text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] text-navy-800">{{ item.title }}</h3>
              <p v-if="item.description" class="m-0 mt-1.5 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ item.description }}</p>
            </li>
          </ol>
        </div>
        <figure class="m-0 min-h-[390px] overflow-hidden rounded-btn bg-navy-50 max-[800px]:max-h-[26rem]">
          <img class="block h-full w-full object-cover" :src="'/assets/images/site-visuals/about-showroom-solar-equipment-20261104.png'" :alt="`${about.history.title || about.labels.history} — không gian trưng bày thiết bị năng lượng mặt trời`" width="780" height="600" loading="lazy">
        </figure>
      </div>
    </section>

    <!-- Chapter 3: People & Core Principles / Con người & Nguyên tắc hành động -->
    <section id="con-nguoi" class="scroll-mt-[160px] bg-white py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-people-title">
      <div class="pts-container">
        <div class="grid grid-cols-2 items-center gap-[clamp(28px,5.4vw,86px)] max-[800px]:grid-cols-1">
          <figure v-if="about.people.image" class="m-0 aspect-[10/7] overflow-hidden rounded-btn bg-navy-50 max-[800px]:max-h-[26rem]">
            <img class="block h-full w-full object-cover" :src="about.people.image" :alt="about.people.title" width="780" height="620" loading="lazy">
          </figure>
          <div>
            <h2 id="about-people-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22]">{{ about.people.title }}</h2>
            <p v-if="about.people.intro" class="mt-[1.1rem] text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ about.people.intro }}</p>
            <p v-if="about.people.body" class="mt-[1.1rem] border-t border-slate-200 pt-4 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ about.people.body }}</p>
          </div>
        </div>

        <!-- Hợp nhất Card 1: Principles (Chuẩn Monograph Card System) -->
        <div v-if="about.people.principles.length" class="mt-14 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
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

    <!-- Chapter 4: Trust / Vì sao chọn chúng tôi (PHP 2-column parity) -->
    <section v-if="about.trust.items.length" id="vi-sao-chon-chung-toi" class="scroll-mt-[160px] bg-slate-50 py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-trust-title">
      <div class="pts-container grid grid-cols-[minmax(0,.7fr)_minmax(0,1.3fr)] items-start gap-16 max-[800px]:grid-cols-1 max-[800px]:gap-7">
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
                <img :src="`/assets/images/brand-logos/${brand.logo}`" :alt="brand.name" width="2172" height="724" loading="lazy" decoding="async">
              </NuxtLink>
              <img v-else :src="`/assets/images/brand-logos/${brand.logo}`" :alt="brand.name" width="2172" height="724" loading="lazy" decoding="async">
            </li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Chapter 5: Compass / Tầm nhìn - Sứ mệnh - Giá trị (Dark Stage Tone) -->
    <section v-if="about.compass.items.length" id="tam-nhin-su-menh" class="scroll-mt-[160px] bg-navy-950 py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-compass-title">
      <div class="pts-container">
        <h2 id="about-compass-title" class="m-0 text-[clamp(29px,2.6vw,38px)] font-bold leading-[1.22] text-white">{{ about.compass.title }}</h2>

        <!-- Hợp nhất Card 3: Compass (Đồng bộ ngôn ngữ với Dark Tone) -->
        <div class="mt-10 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article v-for="(item, index) in about.compass.items" :key="`${item.title}-${index}`" class="group border-t-2 border-white/20 pt-5 transition-colors duration-200 hover:border-gold-400">
            <header class="mb-3.5 flex items-center justify-end">
              <img v-if="item.image" class="size-9 object-contain filter invert opacity-90" :src="item.image" alt="" width="56" height="56" loading="lazy">
            </header>
            <h3 v-if="item.title" class="text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-white">{{ item.title }}</h3>
            <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#d1daea]">{{ item.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <!-- Chapter 6: Certificates & Proof / Năng lực & Chứng nhận đã kiểm chứng -->
    <section v-if="about.certificates.items.length" id="about-certificates" class="scroll-mt-[160px] bg-white py-16 max-[800px]:scroll-mt-[116px] max-[800px]:py-12 min-[1800px]:scroll-mt-[184px]" aria-labelledby="about-certificates-title">
      <div class="pts-container">
        <header class="grid grid-cols-1 items-end gap-5 min-[801px]:grid-cols-[minmax(0,1fr)_auto]">
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
        <div class="mt-10 grid grid-cols-3 gap-6 lg:gap-8 max-[800px]:grid-cols-1">
          <article
            v-for="item in visibleCertificates"
            :key="item.id"
            class="group flex flex-col justify-between border-t-2 border-slate-200 pt-5 transition-colors duration-200 hover:border-gold-500"
          >
            <div>
              <div v-if="item.image" class="mb-4 overflow-hidden rounded-sm bg-slate-50 p-4 border border-slate-100">
                <button
                  type="button"
                  class="block w-full h-48 focus:outline-none cursor-pointer"
                  :aria-label="`Mở chứng nhận ${item.name}`"
                  @click="openCertificate(item, $event)"
                >
                  <img class="block h-full w-full object-contain transition-transform duration-300 group-hover:scale-105" :src="item.image" :alt="item.name" width="260" height="360" loading="lazy">
                </button>
              </div>
              <span class="text-[15px] font-bold text-gold-700">{{ about.labels.certLabel }}</span>
              <h3 class="mt-1.5 text-[clamp(20px,1.6vw,24px)] font-extrabold leading-[1.25] tracking-[-.01em] text-navy-800">{{ item.name }}</h3>
              <p v-if="item.description" class="m-0 mt-2 text-[clamp(17px,1.32vw,20px)] leading-[1.7] text-[#52627c]">{{ item.description }}</p>
            </div>
            <div v-if="item.image" class="mt-4 pt-3 border-t border-slate-100">
              <button type="button" class="inline-flex items-center gap-1.5 text-[15px] font-bold text-navy-800 hover:text-gold-700 transition-colors cursor-pointer" @click="openCertificate(item, $event)">
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
  grid-template-columns: repeat(4, minmax(0, 1fr));
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
  min-height: clamp(76px, 8.2vw, 118px);
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
.about-certificate-dialog::backdrop { background: rgba(6,21,55,.86); backdrop-filter: blur(8px); }
.about-certificate-modal { display: flex; max-height: calc(100dvh - 24px); flex-direction: column; overflow: hidden; border: 0; border-radius: 4px; background: transparent; }
.about-certificate-modal-header, .about-certificate-modal-footer { display: flex; flex: none; align-items: center; justify-content: space-between; gap: 20px; padding: 16px 22px; }
.about-certificate-modal-header { border-bottom: 0; background: transparent; color: #fff; }
.about-certificate-modal-title { display: flex; min-width: 0; align-items: center; gap: 14px; }
.about-certificate-modal-title h2 { overflow: hidden; margin: 0; color: #fff; font-size: 18px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.about-certificate-modal-title span:not(.about-modal-mark) { display: inline-block; margin-top: 5px; color: rgba(255,255,255,.72); font-size: 12px; }
.about-modal-mark { display: grid; width: 40px; height: 40px; flex: none; place-items: center; border: 1px solid var(--color-gold-400); color: var(--color-gold-300); font-size: 11px; font-weight: 800; }
.about-certificate-close { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; border: 0; padding: 0 8px; background: transparent; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
.about-certificate-close span { color: var(--color-gold-300); font-size: 23px; }
.about-certificate-modal-image { position: relative; display: flex; min-height: 260px; flex: 1; align-items: center; justify-content: center; gap: 12px; overflow-y: auto; padding: clamp(16px, 4vw, 36px); background: transparent; }
.about-certificate-modal-image img { display: block; max-width: min(100%, 760px); max-height: min(66dvh, 680px); object-fit: contain; }
.about-certificate-modal-image > div { display: grid; min-height: 180px; place-items: center; padding: 24px; color: rgba(255,255,255,.9); text-align: center; }
.about-certificate-modal-image button { display: grid; width: 44px; height: 44px; flex: none; place-items: center; border: 1px solid rgba(255,255,255,.35); border-radius: 50%; background: rgba(6,21,55,.7); color: #fff; font-size: 20px; cursor: pointer; }
.about-certificate-modal-image button:disabled { cursor: not-allowed; opacity: .35; }
.about-certificate-modal-image button:focus-visible { outline: 2px solid var(--color-gold-700); outline-offset: 2px; }
.about-certificate-modal-footer { justify-content: center; border-top: 0; }
.about-certificate-modal-footer p { margin: 0; color: rgba(255,255,255,.9); font-size: 16px; line-height: 1.6; text-align: center; }
.about-certificate-modal-footer > span { flex: none; color: rgba(255,255,255,.72); font-size: 12px; }

@media (max-width: 560px) {
  .about-certificate-modal-header, .about-certificate-modal-footer { padding: 13px 14px; }
  .about-certificate-modal-image { min-height: 200px; gap: 8px; padding: 12px; }
  .about-certificate-modal-image button { width: 40px; height: 40px; }
  .about-certificate-modal-footer { align-items: flex-start; flex-direction: column; gap: 8px; }
}
</style>

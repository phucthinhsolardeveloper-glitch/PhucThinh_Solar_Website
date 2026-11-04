<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import { isPublicDealerPayload } from '#shared/contracts/public-dealer'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import DealerOverview from '~/components/dealer/DealerOverview.vue'
import DealerSections from '~/components/dealer/DealerSections.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { publicSeoTitle } from '#shared/public-seo'

const props = withDefaults(defineProps<{ preview?: boolean }>(), { preview: false })
const config = useRuntimeConfig()
const indexable = computed(() => !props.preview && config.public.dealerPublicRouteIndexable === true
  && !!dealer.value && !siteError.value && !error.value)
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData<unknown>('migration-dealer', () => $fetch('/_migration/dealer'))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const dealer = computed(() => isPublicDealerPayload(data.value) ? data.value : null)

if (import.meta.server && !props.preview && (siteError.value || !isPublicSitePayload(siteData.value) || error.value || !dealer.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

const selectedProduct = ref('')

function handleSelectProduct(title: string) {
  selectedProduct.value = title
  if (import.meta.client) {
    const el = document.getElementById('dang-ky-dai-ly')
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    el?.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'center' })
  }
}

const quickLinks = computed(() => {
  if (!dealer.value) return []
  const links = [{ label: 'Đăng ký hợp tác', href: '#dang-ky-dai-ly', icon: 'edit', badge: '' }]
  if (dealer.value.intro.html || dealer.value.overview.title || dealer.value.overview.descriptionContent.html || dealer.value.overview.description || dealer.value.overview.products.length) {
    links.push({
      label: 'Sản phẩm phân phối',
      href: '#nang-luc-phan-phoi',
      icon: 'solar',
      badge: dealer.value.overview.products.length ? String(dealer.value.overview.products.length) : '',
    })
  }
  if (dealer.value.support.title || dealer.value.support.descriptionContent.html || dealer.value.support.description || dealer.value.support.items.length) {
    links.push({
      label: 'Quyền lợi đại lý',
      href: '#ho-tro-dai-ly',
      icon: 'handshake',
      badge: dealer.value.support.items.length ? String(dealer.value.support.items.length) : '',
    })
  }
  return links
})
const links = useMigrationLinks()

// CMS stats count up once on load; SSR and reduced motion keep the final value.
const statShown = ref<string[]>([])
onMounted(() => {
  const stats = dealer.value?.stats ?? []
  const targets = stats.map((stat) => Number(stat.value.replace(/\D/g, '')))
  if (!targets.length || targets.some((n) => !Number.isFinite(n) || n <= 0)) return
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  const start = performance.now()
  const duration = 1400
  const step = (now: number) => {
    const t = Math.min(1, (now - start) / duration)
    const eased = 1 - Math.pow(1 - t, 3)
    statShown.value = targets.map((n) => new Intl.NumberFormat('vi-VN').format(Math.round(n * eased)))
    if (t < 1) requestAnimationFrame(step)
    else statShown.value = []
  }
  requestAnimationFrame(step)
})

const pageTitle = computed(() => props.preview
  ? 'Đại lý — internal preview'
  : publicSeoTitle(dealer.value?.seo?.title || dealer.value?.title, 'Đại lý'))
const pageDescription = computed(() => props.preview ? '' : dealer.value?.seo?.description || '')
const pageKeywords = computed(() => props.preview ? undefined : dealer.value?.seo?.keywords || undefined)
useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: () => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive',
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !props.preview && !!dealer.value && !error.value && !siteError.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/dai-ly/',
  image: computed(() => dealer.value?.seo?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Đại lý' }]" />
        </div>
      </div>

      <div v-if="error || !dealer" class="pts-container min-h-[45vh] py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ pageTitle }}</h1>
        <p class="text-slate-500">Nội dung chương trình đại lý tạm thời chưa tải được. Vui lòng thử lại sau ít phút.</p>
      </div>

      <template v-else>
        <!-- Hero full-bleed with CMS media; the CMS stats ride along its lower edge. -->
        <section class="dealer-hero" aria-labelledby="dealer-title">
          <img v-if="dealer.hero" :src="dealer.hero.image" :alt="dealer.hero.title" class="dealer-hero__image" fetchpriority="high">
          <span class="dealer-hero__shade" aria-hidden="true" />
          <div class="pts-container dealer-hero__inner">
            <h1 id="dealer-title" class="max-w-[680px] min-[1800px]:max-w-[760px]">{{ dealer.title }}</h1>
            <p v-if="dealer.hero?.title" class="dealer-hero__lede">{{ dealer.hero.title }}</p>
            <div class="dealer-hero__actions">
              <a class="dealer-hero__primary" href="#dang-ky-dai-ly">{{ dealer.form.title }} <span aria-hidden="true"><UIcon name="i-lucide-arrow-down" class="size-5" /></span></a>
              <a v-if="dealer.hotline" class="dealer-hero__call" :href="`tel:${dealer.hotline.tel}`">
                <UIcon name="i-lucide-phone-call" class="size-5 text-gold-300" aria-hidden="true" />
                <span><small>Hotline đại lý</small>{{ dealer.hotline.text }}</span>
              </a>
            </div>
          </div>
          <div v-if="dealer.stats.length" class="dealer-hero__stats">
            <dl class="pts-container" :aria-label="dealer.labels.stats">
              <div v-for="(stat, index) in dealer.stats" :key="stat.label">
                <dt>{{ stat.label }}</dt>
                <dd>{{ statShown[index] ?? stat.value }}+</dd>
              </div>
            </dl>
          </div>
        </section>

        <!-- Hàng neo nhảy mục, nền trắng, ngay dưới hero -->
        <nav class="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur-sm" aria-label="Điều hướng nhanh trong trang">
          <div class="pts-container flex min-h-[58px] flex-nowrap gap-0 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-[767px]:grid max-[767px]:grid-cols-3 min-[768px]:min-h-[66px]">
            <a v-for="link in quickLinks" :key="link.href" class="flex shrink-0 items-center gap-2 border-r border-gray-200 py-4 pl-4 pr-5 font-bold text-navy-800 no-underline transition-colors hover:bg-slate-50 hover:text-gold-700 first:pl-0 min-[992px]:px-[clamp(22px,3vw,42px)] first:min-[992px]:pl-0 max-[767px]:min-w-0 max-[767px]:min-h-[70px] max-[767px]:flex-col max-[767px]:justify-center max-[767px]:gap-1 max-[767px]:px-1 max-[767px]:py-2 max-[767px]:text-center max-[767px]:text-[12px] max-[767px]:leading-tight max-[767px]:first:pl-1" :href="links.generic(link.href)">
              <svg v-if="link.icon === 'edit'" class="h-[26px] w-[26px] flex-none text-gold-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
              <svg v-else-if="link.icon === 'solar'" class="h-[26px] w-[26px] flex-none text-gold-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 10 18 0-2 9H5l-2-9Zm3-5h12l3 5H3l3-5ZM8 10l1 9m6-9-1 9M2 22h20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" /></svg>
              <svg v-else class="h-[26px] w-[26px] flex-none text-gold-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3ZM8 11c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3Zm8 2c-2.21 0-6 1.11-6 3.33V19h12v-2.67C22 14.11 18.21 13 16 13ZM8 13c-2.67 0-7 1.34-7 4v2h5v-2.67c0-1.3.79-2.38 2.09-3.19L8 13Z" fill="currentColor" /></svg>
              <span>{{ link.label }}</span>
              <span v-if="link.badge" class="hidden rounded-[3px] bg-gold-500/15 px-1.5 py-0.5 text-xs font-extrabold tabular-nums text-gold-700 min-[768px]:inline-block">{{ link.badge }}</span>
            </a>
          </div>
        </nav>

        <div class="pts-container">
          <section
            id="nang-luc-phan-phoi"
            class="grid grid-cols-1 items-start gap-8 pb-12 pt-6 min-[992px]:grid-cols-[minmax(0,1fr)_minmax(360px,520px)] min-[992px]:gap-[clamp(44px,5vw,84px)] min-[992px]:pt-[clamp(24px,2.5vw,36px)] min-[1800px]:grid-cols-[minmax(0,1fr)_minmax(360px,560px)] nav:pb-16"
          >
            <div v-if="dealer.intro.html || dealer.overview.title || dealer.overview.description || dealer.overview.products.length" class="order-2 min-w-0 min-[992px]:order-none">
              <EditorialRichText v-if="dealer.intro.html" :content="dealer.intro" class="dealer-intro-rich" />
              <DealerOverview :dealer="dealer" :selected-product="selectedProduct" @select-product="handleSelectProduct" />
            </div>

            <aside
              id="dang-ky-dai-ly"
              class="order-first w-full max-w-[560px] rounded-[4px] border border-white/10 bg-[linear-gradient(145deg,var(--color-navy-950),#123c72)] p-[22px_18px] text-white shadow-[0_14px_34px_rgba(6,23,53,0.14)] min-[640px]:max-w-none min-[992px]:order-none min-[992px]:sticky min-[992px]:top-[104px] min-[1321px]:top-[120px] min-[1681px]:top-[136px] min-[1800px]:top-[152px] min-[992px]:ml-auto min-[992px]:max-w-[520px] min-[992px]:p-8 min-[1800px]:max-w-[560px]"
            >
              <h2 class="mb-2 text-2xl font-black leading-tight">{{ dealer.form.title }}</h2>
              <p class="mb-5 text-[15px] leading-relaxed text-blue-100">{{ dealer.form.note }}</p>
              <DealerLeadForm :selected-product="selectedProduct" :hotline="dealer.hotline" @clear-product="selectedProduct = ''" />
            </aside>
          </section>
        </div>

        <DealerSections :dealer="dealer" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

<style scoped>
.dealer-hero { --ease: cubic-bezier(0.22, 1, 0.36, 1); position: relative; isolation: isolate; display: flex; min-height: clamp(560px, 76vh, 760px); flex-direction: column; justify-content: flex-end; overflow: hidden; background: linear-gradient(115deg, #061735, #123c72); color: #fff; }
.dealer-hero__image { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; animation: dealer-in 1.2s var(--ease) both; }
.dealer-hero__shade { position: absolute; inset: 0; z-index: -1; background: linear-gradient(90deg, rgb(4 17 46 / 92%) 0%, rgb(4 17 46 / 72%) 42%, rgb(4 17 46 / 18%) 100%), linear-gradient(0deg, rgb(4 17 46 / 90%) 0%, transparent 40%); }
.dealer-hero__inner { width: 100%; padding-block: 48px 40px; }
.dealer-hero__lede { max-width: 56ch; margin: 18px 0 0; color: #d3dcea; font-size: clamp(17px, 1.3vw, 20px); line-height: 1.6; }
.dealer-hero h1 { margin: 0; color: #fff; font-size: clamp(36px, 4.2vw, 68px); font-weight: 850; line-height: 1.08; letter-spacing: -0.035em; text-wrap: balance; }
.dealer-hero__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 28px; margin-top: 32px; }
.dealer-hero__primary { display: inline-flex; min-height: 58px; align-items: center; gap: 16px; padding: 0 10px 0 24px; border-radius: 6px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 16.5px; font-weight: 850; text-decoration: none; transition: filter 0.25s ease; }
.dealer-hero__primary > span { display: grid; width: 40px; height: 40px; place-items: center; border-radius: 4px; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s var(--ease); }
.dealer-hero__primary:hover { filter: brightness(1.06); }
.dealer-hero__primary:hover > span { transform: translateY(3px); }
.dealer-hero__call { display: inline-flex; min-height: 52px; align-items: center; gap: 12px; color: #fff; font-size: 20px; font-weight: 850; text-decoration: none; font-variant-numeric: tabular-nums; }
.dealer-hero__call small { display: block; color: #9fb0c8; font-size: 13px; font-weight: 600; }
.dealer-hero a:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }
.dealer-hero__stats { border-top: 1px solid rgb(255 255 255 / 14%); background: rgb(4 17 46 / 55%); backdrop-filter: blur(10px); }
.dealer-hero__stats dl { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0; }
.dealer-hero__stats dl > div { display: flex; flex-direction: column-reverse; gap: 4px; padding: clamp(18px, 2.2vw, 28px) clamp(12px, 2vw, 32px); }
.dealer-hero__stats dl > div:first-child { padding-left: 0; }
.dealer-hero__stats dl > div + div { border-left: 1px solid rgb(255 255 255 / 12%); }
.dealer-hero__stats dt { color: #c3cfdf; font-size: clamp(14px, 1.05vw, 16px); font-weight: 600; }
.dealer-hero__stats dd { margin: 0; background-image: var(--pts-gold-gradient); background-clip: text; color: transparent; -webkit-text-fill-color: transparent; font-size: clamp(34px, 3.6vw, 58px); font-weight: 900; font-variant-numeric: tabular-nums; letter-spacing: -0.04em; line-height: 1; }
@keyframes dealer-in { from { opacity: 0; transform: scale(1.08); } }
@media (max-width: 640px) {
  .dealer-hero { min-height: 620px; }
  .dealer-hero__actions { align-items: stretch; flex-direction: column; }
  .dealer-hero__primary { justify-content: space-between; }
  .dealer-hero__stats dl > div { padding-inline: 10px; }
}
@media (prefers-reduced-motion: reduce) {
  .dealer-hero__image { animation: none; }
  .dealer-hero__primary > span { transition: none; }
}

.dealer-intro-rich :deep(p:first-child) {
  font-size: clamp(1.08rem, 1.3vw, 1.22rem);
  line-height: 1.65;
  color: var(--color-navy-900);
}
</style>

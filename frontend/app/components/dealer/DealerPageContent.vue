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
      <div class="border-b border-slate-200/80 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Đại lý' }]" />
        </div>
      </div>

      <div v-if="error || !dealer" class="pts-container min-h-[45vh] py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ pageTitle }}</h1>
        <p class="text-slate-500">Nội dung chương trình đại lý tạm thời chưa tải được. Vui lòng thử lại sau ít phút.</p>
      </div>

      <template v-else>
        <!-- Hero full-bleed with CMS media and the source page's legibility overlay. -->
        <section class="relative flex min-h-[210px] items-center overflow-hidden bg-[linear-gradient(115deg,#061735,#123c72)] min-[768px]:min-h-[230px] min-[992px]:min-h-[270px] min-[1800px]:min-h-[300px]">
          <img v-if="dealer.hero" :src="dealer.hero.image" :alt="dealer.hero.title" class="absolute inset-0 h-full w-full object-cover">
          <div
            v-if="dealer.hero"
            class="absolute inset-0 bg-[linear-gradient(90deg,rgba(4,17,46,0.86)_0%,rgba(4,17,46,0.68)_38%,rgba(4,17,46,0.18)_72%,rgba(4,17,46,0.02)_100%)] max-[767px]:bg-[linear-gradient(90deg,rgba(4,17,46,0.94),rgba(4,17,46,0.62))]"
            aria-hidden="true"
          />
          <div class="relative z-[1] pts-container">
            <h1 class="max-w-[680px] py-8 text-[clamp(34px,3.4vw,52px)] font-black leading-[1.13] text-white min-[1800px]:max-w-[760px] min-[1800px]:text-[48px]">
              {{ dealer.title }}
            </h1>
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
.dealer-intro-rich :deep(p:first-child) {
  font-size: clamp(1.08rem, 1.3vw, 1.22rem);
  line-height: 1.65;
  color: var(--color-navy-900);
}
</style>

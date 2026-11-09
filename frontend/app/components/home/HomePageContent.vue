<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'
import { isPublicHomePayload } from '#shared/contracts/public-home'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import HomeHero from '~/components/home/HomeHero.vue'
import HomeCatalog from '~/components/home/HomeCatalog.vue'
import HomeCampaigns from '~/components/home/HomeCampaigns.vue'
import HomeSolutions from '~/components/home/HomeSolutions.vue'
import HomeProcess from '~/components/home/HomeProcess.vue'
import HomeDealer from '~/components/home/HomeDealer.vue'
import HomeAbout from '~/components/home/HomeAbout.vue'
import { pageLayout, type LayoutDraftQuery } from '#shared/page-layout'
import HomeContact from '~/components/home/HomeContact.vue'
import HomePromoPopup from '~/components/home/HomePromoPopup.vue'
import EnergyStageSection from '~/components/experience-v3/EnergyStageSection.vue'

// A signed layout draft from Admin "Bố cục trang" replaces the published section order.
const props = defineProps<{ draft?: LayoutDraftQuery }>()
const config = useRuntimeConfig()
const indexable = computed(() => !props.draft && config.public.homePublicRouteIndexable === true
  && !!home.value && !siteError.value && !error.value)
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData, error: siteError } = await useFetch<unknown>('/_api/site', { default: () => fallbackSite })
const { data: homeData, error } = await useFetch<unknown>(props.draft ? '/_api/page-layout-draft' : '/_api/home', props.draft ? { query: props.draft } : {})
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const home = computed(() => isPublicHomePayload(homeData.value) ? homeData.value : null)
const layout = computed(() => pageLayout('home', (homeData.value as { layout?: unknown } | null)?.layout))
const fallbackTitle = computed(() => 'Phúc Thịnh Solar')

if (import.meta.server && (siteError.value || !isPublicSitePayload(siteData.value) || error.value || !home.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

const fallbackDescription = 'Phúc Thịnh Solar — thiết bị và giải pháp năng lượng mặt trời cho gia đình, doanh nghiệp và dự án.'
const pageKeywords = computed(() => home.value?.seo.keywords || '')
useSeoMeta({
  title: () => home.value?.seo.title || fallbackTitle.value,
  description: () => home.value?.seo.description || fallbackDescription,
  robots: () => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive',
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!home.value && !siteError.value && !error.value),
  title: computed(() => home.value?.seo.title || fallbackTitle.value),
  description: computed(() => home.value?.seo.description || fallbackDescription),
  canonicalPath: '/',
  // Without an SEO picture, links shared on Zalo/Facebook show the first banner rather than the logo.
  image: computed(() => home.value?.seo.image || home.value?.hero?.[0]?.image || undefined),
})
</script>

<template>
  <div>
    <main>
      <div v-if="error || !home" class="mx-auto min-h-[55vh] w-[min(calc(100%-32px),2200px)] py-[72px]" role="status">
        <h1 class="text-[clamp(28px,4vw,48px)] font-bold text-navy-950">{{ fallbackTitle }}</h1>
        <p class="mt-4 max-w-[68ch] leading-[1.7] text-slate-500">Không thể tải dữ liệu trang chủ hiện tại. Không hiển thị nội dung giả.</p>
      </div>
      <template v-else>
        <template v-for="block in layout" :key="block.type">
          <HomeHero v-if="block.type === 'hero'" :hero="home.hero" />
          <HomeCatalog v-else-if="block.type === 'catalog'" :categories="home.categories" :campaigns="home.campaigns" :energy-chain="home.energyChain" />
          <EnergyStageSection v-else-if="block.type === 'energy'" />
          <HomeCampaigns v-else-if="block.type === 'campaigns'" :campaigns="home.campaigns" :campaigns-configured="(home.summary.campaignsConfigured ?? 0) > 0" />
          <HomeSolutions v-else-if="block.type === 'solutions'" :data="home" />
          <HomeProcess v-else-if="block.type === 'process'" :steps="home.process" />
          <HomeDealer v-else-if="block.type === 'dealer'" :dealer="home.dealer" />
          <HomeAbout v-else-if="block.type === 'about'" :about="home.about" />
          <HomeContact v-else-if="block.type === 'contact'" :contact="home.contact" />
        </template>
        <HomePromoPopup v-if="home.popup" :popup="home.popup" />
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'
import { isPublicHomePayload } from '#shared/contracts/public-home'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import HomeCatalog from '~/components/home/HomeCatalog.vue'
import HomeCampaigns from '~/components/home/HomeCampaigns.vue'
import HomeContact from '~/components/home/HomeContact.vue'
import HomeProductGroups from '~/components/home/HomeProductGroups.vue'

const props = withDefaults(defineProps<{ preview?: boolean }>(), { preview: false })
const config = useRuntimeConfig()
const indexable = computed(() => !props.preview && isRuntimeFlagEnabled(config.public.homePublicRouteIndexable)
  && !!home.value && !siteError.value && !error.value)
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: homeData, error } = await useFetch<unknown>('/_migration/home')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const home = computed(() => isPublicHomePayload(homeData.value) ? homeData.value : null)
const fallbackTitle = computed(() => props.preview ? 'Phúc Thịnh Solar — homepage preview' : 'Phúc Thịnh Solar')

if (import.meta.server && !props.preview && (siteError.value || !isPublicSitePayload(siteData.value) || error.value || !home.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

useSeoMeta({
  title: () => home.value?.seo.title || fallbackTitle.value,
  description: () => home.value?.seo.description || '',
  robots: () => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive',
})
usePublicSocialMeta({
  enabled: computed(() => !props.preview && isRuntimeFlagEnabled(config.public.homePublicRouteEnabled) && !!home.value && !siteError.value && !error.value),
  title: computed(() => home.value?.seo.title || fallbackTitle.value),
  description: computed(() => home.value?.seo.description || ''),
  canonicalPath: '/',
})
</script>

<template>
  <div>
    <SiteHeader :site="site" />
    <main>
      <div v-if="error || !home" class="mx-auto min-h-[55vh] w-[min(calc(100%-32px),2200px)] py-[72px]" role="status">
        <h1 class="text-[clamp(28px,4vw,48px)] font-bold text-navy-950">{{ fallbackTitle }}</h1>
        <p class="mt-4 max-w-[68ch] leading-[1.7] text-slate-500">Không thể tải dữ liệu trang chủ hiện tại. Không hiển thị nội dung giả.</p>
      </div>
      <template v-else>
        <h1 v-if="!home.categories.length" id="home-catalog-title" class="mx-auto w-[min(calc(100%-32px),2200px)] pt-12 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950 md:pt-[clamp(64px,6vw,92px)]">
          Sản phẩm năng lượng mặt trời
        </h1>
        <HomeCatalog :categories="home.categories" />
        <HomeProductGroups :groups="home.productGroups" />
        <HomeCampaigns :campaigns="home.campaigns" :campaigns-configured="(home.summary.campaignsConfigured ?? 0) > 0" />
        <HomeContact :contact="home.contact" />
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

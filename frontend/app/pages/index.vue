<script setup lang="ts">
import { isPublicHomePayload, type PublicHomePayload } from '#shared/contracts/public-home'
import { isPublicSitePayload, type PublicSitePayload } from '#shared/contracts/public-site'
import HomeCatalog from '~/components/home/HomeCatalog.vue'
import HomeCampaigns from '~/components/home/HomeCampaigns.vue'
import HomeEditorial from '~/components/home/HomeEditorial.vue'
import HomeContact from '~/components/home/HomeContact.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/home')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const home = computed<PublicHomePayload | null>(() => isPublicHomePayload(data.value) ? data.value : null)

useSeoMeta({
  title: () => home.value?.seo.title || 'Phúc Thịnh Solar — Thiết bị và giải pháp năng lượng mặt trời',
  description: () => home.value?.seo.description || 'Thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng cho gia đình, doanh nghiệp và công trình.',
  robots: 'index, follow',
})
const requestUrl = useRequestURL()
useHead({ link: [{ rel: 'canonical', href: computed(() => new URL('/', requestUrl.origin).href) }] })
</script>

<template>
  <div class="flex min-h-screen flex-col text-navy-800">
    <SiteHeader :site="site" />
    <main class="min-w-0 flex-1">
      <div v-if="error || !home" class="mx-auto min-h-[55vh] w-[min(calc(100%-32px),2200px)] py-[72px]" role="status">
        <p class="text-slate-600">Không thể tải dữ liệu trang chủ. Vui lòng thử lại sau.</p>
      </div>
      <template v-else>
        <HomeHero v-if="home.hero.length" :hero="home.hero" />
        <h1 v-else class="sr-only">Thiết bị và giải pháp năng lượng mặt trời</h1>
        <HomeCatalog :categories="home.categories" :product-groups="home.productGroups" />
        <HomeCampaigns :campaigns="home.campaigns" />
        <HomeEditorial :data="home" />
        <HomeContact :contact="home.contact" />
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

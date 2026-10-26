<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import { isPublicDealerPayload } from '#shared/contracts/public-dealer'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import DealerSections from '~/components/dealer/DealerSections.vue'
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData<unknown>('migration-dealer', () => $fetch('/_migration/dealer'))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const dealer = computed(() => isPublicDealerPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Đại lý — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="dealer-preview"><SiteHeader :site="site" /><main><div class="pts-container"><SiteBreadcrumb :items="[{ label: 'Đại lý' }]" /><div v-if="error || !dealer" class="dealer-fallback" role="status"><h1>Đại lý — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung đại lý giả.</p></div><template v-else><section v-if="dealer.hero" class="dealer-hero"><img :src="dealer.hero.image" :alt="dealer.hero.title"><h1>{{ dealer.title }}</h1></section><h1 v-else>{{ dealer.title }}</h1><section class="dealer-intro"><div><h2>{{ dealer.overview.title }}</h2><p>{{ dealer.overview.description }}</p></div><aside id="dang-ky-dai-ly"><h2>{{ dealer.form.title }}</h2><p>{{ dealer.form.note }}</p><a class="dealer-cta" :href="dealer.form.action">Đăng ký ngay</a><small>Biểu mẫu và xác thực vẫn do PHP xử lý.</small></aside></section><DealerSections :dealer="dealer" /></template></div></main><SiteFooter :site="site" /></div>
</template>
<style>@import '~/assets/css/dealer-preview.css';</style>

<script setup lang="ts">
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'
import NotFoundExperience from '~/components/NotFoundExperience.vue'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicSitePayload } from '#shared/contracts/public-site'

defineProps<{ error: { statusCode?: number; statusMessage?: string } }>()
const fallback: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data } = await useFetch<unknown>('/_api/site', { default: () => fallback })
const site = computed(() => isPublicSitePayload(data.value) ? data.value : fallback)

</script>

<template>
  <div class="min-h-screen bg-[#f6f8fb] text-navy-800">
    <SiteHeader :site="site" />
    <main>
      <NotFoundExperience :status-code="error.statusCode || 500" :hotline="site.brand.hotline" />
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

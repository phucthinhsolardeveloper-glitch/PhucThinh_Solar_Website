<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import CatalogPage from '~/components/catalog/CatalogPage.vue'
import catalogPublicRoute from '~/middleware/catalog-public-route'

definePageMeta({
  layout: false,
  middleware: catalogPublicRoute,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.catalogPublicRouteIndexable))

useSeoMeta({
  title: 'Sản phẩm — Phúc Thịnh Solar',
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
</script>

<template>
  <CatalogPage />
</template>

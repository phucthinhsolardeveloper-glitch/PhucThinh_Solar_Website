<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import CatalogPage from '~/components/catalog/CatalogPage.vue'

definePageMeta({
  layout: false,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.catalogPublicRouteIndexable))
const requestUrl = useRequestURL()

useSeoMeta({
  title: 'Sản phẩm — Phúc Thịnh Solar',
  description: 'Khám phá thiết bị chiếu sáng, điện mặt trời, lưu trữ và bơm nước từ Phúc Thịnh Solar.',
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({ link: [{ rel: 'canonical', href: new URL('/san-pham/', requestUrl.origin).toString() }] }))
</script>

<template>
  <CatalogPage />
</template>

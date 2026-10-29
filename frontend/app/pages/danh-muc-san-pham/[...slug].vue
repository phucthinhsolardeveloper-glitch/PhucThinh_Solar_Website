<script setup lang="ts">
import { isRuntimeFlagEnabled, productCategoryPublicPath } from '#shared/public-route-gate'
import CatalogPage from '~/components/catalog/CatalogPage.vue'

definePageMeta({
  layout: false,
})

const route = useRoute()
const config = useRuntimeConfig()
const requestUrl = useRequestURL()
const segments = computed(() => {
  const value = route.params.slug
  const parts = Array.isArray(value) ? value : typeof value === 'string' ? value.split('/') : []
  return parts.filter((part) => part.length > 0)
})
const categoryPath = computed(() => productCategoryPublicPath(segments.value))
if (!segments.value.length) throw createError({ statusCode: 404, statusMessage: 'Category not found' })
const indexable = computed(() => isRuntimeFlagEnabled(config.public.catalogPublicRouteIndexable))

useSeoMeta({
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead({
  link: [{ rel: 'canonical', href: computed(() => new URL(categoryPath.value, requestUrl.origin).toString()) }],
})
</script>

<template>
  <CatalogPage />
</template>

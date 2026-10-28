<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import BrandDetailPage from '~/components/catalog/BrandDetailPage.vue'
import brandDetailPublicRoute from '~/middleware/brand-detail-public-route'

definePageMeta({
  layout: false,
  middleware: brandDetailPublicRoute,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.brandDetailPublicRouteIndexable))
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')

useSeoMeta({ robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive') })
</script>

<template>
  <BrandDetailPage :slug="slug" />
</template>

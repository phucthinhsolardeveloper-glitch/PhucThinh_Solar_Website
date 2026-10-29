<script setup lang="ts">
import BrandDetailPage from '~/components/catalog/BrandDetailPage.vue'
import brandDetailPublicRoute from '~/middleware/brand-detail-public-route'

definePageMeta({
  layout: false,
  middleware: brandDetailPublicRoute,
})

const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
</script>

<template>
  <BrandDetailPage :slug="slug" />
</template>

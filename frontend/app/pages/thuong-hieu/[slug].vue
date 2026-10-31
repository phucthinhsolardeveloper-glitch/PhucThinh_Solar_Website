<script setup lang="ts">
import BrandDetailPage from '~/components/catalog/BrandDetailPage.vue'
definePageMeta({
  layout: false,
  middleware: 'brand-detail-public-route',
})

const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
</script>

<template>
  <BrandDetailPage :slug="slug" />
</template>

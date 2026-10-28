<script setup lang="ts">
import BrandDetailPage from '~/components/catalog/BrandDetailPage.vue'

const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
useSeoMeta({ robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <BrandDetailPage :slug="slug" />
</template>

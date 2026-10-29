<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import SearchResultsPage from '~/components/catalog/SearchResultsPage.vue'

definePageMeta({
  layout: false,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.searchPublicRouteIndexable))

useSeoMeta({ robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive') })
</script>

<template>
  <SearchResultsPage />
</template>

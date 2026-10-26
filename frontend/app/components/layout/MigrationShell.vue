<script setup lang="ts">
import type { BreadcrumbItem } from '~/components/SiteBreadcrumb.vue'
import { usePublicSite } from '~/composables/usePublicSite'

const route = useRoute()
const { site, pending, hasError, errorMessage } = await usePublicSite()

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
  const value: unknown = route.meta.breadcrumb
  if (!Array.isArray(value)) return []

  return value.filter((item: unknown): item is BreadcrumbItem => {
    if (!item || typeof item !== 'object') return false
    const candidate = item as { label?: unknown; to?: unknown }
    return typeof candidate.label === 'string'
      && candidate.label.trim() !== ''
      && (candidate.to === undefined || typeof candidate.to === 'string')
  })
})

useSeoMeta({
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="migration-shell">
    <SiteHeader :site="site" />

    <div v-if="pending || hasError" class="migration-shell__status" role="status" aria-live="polite">
      <div class="pts-container">
        <p v-if="pending">Đang tải dữ liệu điều hướng…</p>
        <p v-else>{{ errorMessage }}</p>
      </div>
    </div>

    <div class="migration-shell__content">
      <div v-if="breadcrumbItems.length" class="pts-container">
        <SiteBreadcrumb :items="breadcrumbItems" />
      </div>
      <slot />
    </div>

    <SiteFooter :site="site" />
  </div>
</template>

<style>
@import '~/assets/css/layout-migration.css';
</style>

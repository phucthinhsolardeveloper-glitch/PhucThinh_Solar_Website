<script setup lang="ts">
import { usePublicSite } from '~/composables/usePublicSite'

const route = useRoute()
const { site, pending, hasError, errorMessage } = await usePublicSite()

type ShellOptions = {
  header: boolean
  footer: boolean
  status: boolean
}

const shellOptions = computed<ShellOptions>(() => {
  const value: unknown = route.meta.shell
  if (!value || typeof value !== 'object') return { header: true, footer: true, status: true }

  const options = value as Partial<ShellOptions>
  return {
    header: options.header !== false,
    footer: options.footer !== false,
    status: options.status !== false,
  }
})

useSeoMeta({
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="migration-shell">
    <SiteHeader v-if="shellOptions.header" :site="site" />

    <div v-if="shellOptions.status && (pending || hasError)" class="migration-shell__status" role="status" aria-live="polite">
      <div class="pts-container">
        <p v-if="pending">Đang tải dữ liệu điều hướng…</p>
        <p v-else>{{ errorMessage }}</p>
      </div>
    </div>

    <div class="migration-shell__content">
      <slot />
    </div>

    <SiteFooter v-if="shellOptions.footer" :site="site" />
  </div>
</template>

<style>
@import '~/assets/css/layout-migration.css';
</style>

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
  <div class="flex min-h-screen flex-col bg-white">
    <SiteHeader v-if="shellOptions.header" :site="site" />

    <div v-if="shellOptions.status && (pending || hasError)" class="border-b border-gray-200 bg-slate-50 text-slate-500" role="status" aria-live="polite">
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <p v-if="pending" class="py-2.5 text-sm leading-relaxed">Đang tải dữ liệu điều hướng…</p>
        <p v-else class="py-2.5 text-sm leading-relaxed">{{ errorMessage }}</p>
      </div>
    </div>

    <div class="min-w-0 flex-1">
      <slot />
    </div>

    <SiteFooter v-if="shellOptions.footer" :site="site" />
  </div>
</template>

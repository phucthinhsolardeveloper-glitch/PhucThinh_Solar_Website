<script setup lang="ts">
import { isPublicAboutPayload } from '#shared/contracts/public-about'
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import AboutPreviewPage from '~/components/about/AboutPreviewPage.vue'
import aboutPublicRoute from '~/middleware/about-public-route'

definePageMeta({
  layout: false,
  middleware: aboutPublicRoute,
})

const config = useRuntimeConfig()
const { site, hasError: siteHasError, errorMessage: siteErrorMessage } = await usePublicSite()
const { data, error, pending } = await useAsyncData<unknown>('public-about', () => $fetch('/_migration/about'), { default: () => null })
const about = computed(() => isPublicAboutPayload(data.value) ? data.value : null)
const indexable = computed(() => isRuntimeFlagEnabled(config.public.aboutPublicRouteIndexable))

useSeoMeta({
  title: computed(() => about.value?.title || 'Giới thiệu — Phúc Thịnh Solar'),
  description: computed(() => about.value?.story.intro || ''),
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-white">
    <SiteHeader :site="site" />
    <div v-if="siteHasError" class="border-b border-gray-200 bg-slate-50 text-slate-500" role="status">
      <div class="mx-auto w-full max-w-[1766px] px-4"><p class="py-2.5 text-sm leading-relaxed">{{ siteErrorMessage }}</p></div>
    </div>
    <main class="min-w-0 flex-1">
      <div class="mx-auto w-[min(calc(100%-32px),2200px)]">
        <SiteBreadcrumb :items="[{ label: 'Giới thiệu' }]" />
      </div>
      <div v-if="pending" class="mx-auto min-h-[40vh] w-[min(calc(100%-32px),2200px)] py-16 text-center" role="status" aria-live="polite">
        <h1 class="text-3xl font-bold text-navy-800">Giới thiệu</h1>
        <p class="mt-3 leading-[1.68] text-[#52627c]">Đang tải dữ liệu CMS…</p>
      </div>
      <div v-else-if="error || !about" class="mx-auto min-h-[40vh] w-[min(calc(100%-32px),2200px)] py-16 text-center" role="status">
        <h1 class="text-3xl font-bold text-navy-800">Giới thiệu</h1>
        <p class="mt-3 leading-[1.68] text-[#52627c]">Không thể tải dữ liệu giới thiệu trong môi trường hiện tại.</p>
      </div>
      <AboutPreviewPage v-else :about="about" />
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

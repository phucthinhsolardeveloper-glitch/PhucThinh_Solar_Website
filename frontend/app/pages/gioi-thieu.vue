<script setup lang="ts">
import { isPublicAboutPayload } from '#shared/contracts/public-about'
import { isPublicSeoIndexable } from '#shared/public-seo'
import AboutPreviewPage from '~/components/about/AboutPreviewPage.vue'
definePageMeta({
  layout: false,
  middleware: 'about-public-route',
})

const config = useRuntimeConfig()
const { site, hasError: siteHasError, errorMessage: siteErrorMessage } = await usePublicSite()
const { data, error, pending } = await useAsyncData<unknown>('public-about', () => $fetch('/_migration/about'), { default: () => null })
const about = computed(() => isPublicAboutPayload(data.value) ? data.value : null)
const indexable = computed(() => isPublicSeoIndexable(config.public.aboutPublicRouteIndexable, !siteHasError.value && !!about.value && !error.value)
  && !!about.value && !siteHasError.value && !error.value)
const apiStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && (siteHasError.value || error.value || !about.value)) {
  const status = apiStatus === 404 ? 404 : 502
  setResponseStatus(status, status === 404 ? 'Not Found' : 'Bad Gateway')
}

const pageTitle = computed(() => about.value?.seo?.title || about.value?.title || 'Giới thiệu — Phúc Thịnh Solar')
const pageDescription = computed(() => about.value?.seo?.description || about.value?.story.intro || '')
const pageKeywords = computed(() => about.value?.seo?.keywords || undefined)
useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!about.value && !siteHasError.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/gioi-thieu/',
  image: computed(() => about.value?.seo?.image || undefined),
})
</script>

<template>
  <div class="flex min-h-screen flex-col bg-white">
    <SiteHeader :site="site" />
    <div v-if="siteHasError" class="border-b border-gray-200 bg-slate-50 text-slate-500" role="status">
      <div class="mx-auto w-full max-w-[1766px] px-4"><p class="py-2.5 text-sm leading-relaxed">{{ siteErrorMessage }}</p></div>
    </div>
    <main class="min-w-0 flex-1">
      <div class="border-b border-slate-200/80 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Giới thiệu' }]" />
        </div>
      </div>
      <div v-if="pending" class="pts-container min-h-[40vh] py-16 text-center" role="status" aria-live="polite">
        <h1 class="text-3xl font-bold text-navy-800">Giới thiệu</h1>
        <p class="mt-3 leading-[1.68] text-[#52627c]">Đang tải dữ liệu CMS…</p>
      </div>
      <div v-else-if="error || !about" class="pts-container min-h-[40vh] py-16 text-center" role="status">
        <h1 class="text-3xl font-bold text-navy-800">Giới thiệu</h1>
        <p class="mt-3 leading-[1.68] text-[#52627c]">Không thể tải dữ liệu giới thiệu trong môi trường hiện tại.</p>
      </div>
      <AboutPreviewPage v-else :about="about" />
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

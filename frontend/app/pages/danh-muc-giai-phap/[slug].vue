<script setup lang="ts">
import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from '#shared/contracts/public-route-resolve'

definePageMeta({ layout: false })

const route = useRoute()
const slug = typeof route.params.slug === 'string' ? route.params.slug : ''
const validSlug = isPublicRouteResolveSlug(slug)
const { data, error } = await useAsyncData<unknown>(
  () => `legacy-solution-category-${slug}`,
  () => validSlug
    ? $fetch('/_migration/public-route-resolve', { query: { slug } })
    : Promise.resolve(null),
)
const resolution = isPublicRouteResolveResult(data.value) ? data.value : null
const statusCode = error.value
  ? ((error.value as { statusCode?: number }).statusCode === 404 ? 404 : 502)
  : 404

if (resolution?.slug === slug && resolution.kind === 'solution-category') {
  await navigateTo({ path: resolution.canonicalPath, query: route.query }, { redirectCode: 301 })
} else if (import.meta.server) {
  setResponseStatus(validSlug ? statusCode : 404, validSlug && statusCode === 502 ? 'Bad Gateway' : 'Not Found')
}

useSeoMeta({ robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <main class="mx-auto min-h-[45vh] max-w-4xl px-4 py-16 text-navy-800" :role="validSlug && error ? 'alert' : 'status'">
    <h1 class="mb-3 text-3xl font-black">{{ validSlug && error && statusCode !== 404 ? 'Tạm thời không thể tải trang' : 'Không tìm thấy danh mục giải pháp' }}</h1>
    <p class="text-slate-600">{{ validSlug && error && statusCode !== 404 ? 'Nguồn nội dung hiện không khả dụng.' : 'Danh mục này hiện không tồn tại hoặc đã được chuyển tới địa chỉ khác.' }}</p>
  </main>
</template>

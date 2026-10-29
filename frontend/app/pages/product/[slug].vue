<script setup lang="ts">
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'

const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { data, error } = await useFetch<unknown>('/_migration/product-detail', {
  key: () => `legacy-product-${slug.value}`,
  query: computed(() => ({ slug: slug.value })),
})

if (isPublicProductDetailPayload(data.value)) await navigateTo(`${data.value.product.url}/`, { redirectCode: 301 })
else if (error.value?.statusCode === 404 || !data.value) setResponseStatus(404)
else setResponseStatus(error.value?.statusCode || 502)

useSeoMeta({ title: 'Không tìm thấy sản phẩm — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <main class="mx-auto min-h-[55vh] max-w-[1180px] px-4 py-16 text-navy-800">
    <h1 class="text-3xl font-black">Không tìm thấy sản phẩm</h1>
    <NuxtLink to="/san-pham/" class="font-bold text-gold-700">Xem danh mục sản phẩm</NuxtLink>
  </main>
</template>

<script setup lang="ts">
import ProductComparePage from '~/components/compare/ProductComparePage.vue'
definePageMeta({ layout: false })

// Each visitor's own pick of products: never indexed.
useSeoMeta({
  title: 'So sánh sản phẩm | Phúc Thịnh Solar',
  description: 'Đặt thông số các sản phẩm năng lượng mặt trời cạnh nhau để chọn đúng loại cho công trình.',
  robots: 'noindex, follow',
})
</script>

<template>
  <ProductComparePage />
</template>

<script setup lang="ts">
import OrderThanksPage from '~/components/order/OrderThanksPage.vue'

definePageMeta({
  layout: false,
})

// A token-bound order confirmation must never be searchable or indexable.
useSeoMeta({
  title: 'Đặt hàng thành công — Phúc Thịnh Solar',
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <OrderThanksPage />
</template>

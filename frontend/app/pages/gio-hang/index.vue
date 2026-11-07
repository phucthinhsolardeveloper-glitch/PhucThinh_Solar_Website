<script setup lang="ts">
import CartPage from '~/components/cart/CartPage.vue'
definePageMeta({
  layout: false,
  middleware: 'cart-public-route',
})

// Cart contents depend on the session and must never be indexed.
useSeoMeta({
  title: 'Giỏ hàng | Phúc Thịnh Solar',
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <CartPage />
</template>

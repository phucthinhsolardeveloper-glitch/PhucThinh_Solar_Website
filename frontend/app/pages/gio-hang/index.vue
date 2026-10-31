<script setup lang="ts">
import CartPage from '~/components/cart/CartPage.vue'
import cartPublicRoute from '~/middleware/cart-public-route'

definePageMeta({
  layout: false,
  middleware: cartPublicRoute,
})

// Cart contents depend on the PHP session and must never be indexed.
useSeoMeta({
  title: 'Giỏ hàng | Phúc Thịnh Solar',
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <CartPage />
</template>

<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import CartPage from '~/components/cart/CartPage.vue'

definePageMeta({
  layout: false,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.cartPublicRouteIndexable))

useSeoMeta({
  title: 'Giỏ hàng — Phúc Thịnh Solar',
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
</script>

<template>
  <CartPage />
</template>

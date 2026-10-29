<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import OrderThanksPage from '~/components/order/OrderThanksPage.vue'

definePageMeta({
  layout: false,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.orderSuccessPublicRouteIndexable))

useSeoMeta({
  title: 'Đặt hàng thành công — Phúc Thịnh Solar',
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
</script>

<template>
  <OrderThanksPage />
</template>

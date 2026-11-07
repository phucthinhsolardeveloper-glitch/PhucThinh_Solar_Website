<script setup lang="ts">
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'

const { site } = await usePublicSite()
const links = useMigrationLinks()
const { data, error, pending } = await useFetch<unknown>('/_api/cart', {
  key: 'migration-checkout-cart',
  default: () => null,
})
const cart = computed<PublicCartPayload | null>(() => isPublicCartPayload(data.value) ? data.value : null)

function formatMoney(value: number | null): string {
  return value === null ? 'Chờ xác nhận' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

useSeoMeta({ title: 'Thanh toán — Nuxt preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <main class="pts-container py-16 max-ph:py-10">
      <SiteBreadcrumb :items="[{ label: 'Giỏ hàng', to: links.generic('/gio-hang/') }, { label: 'Thanh toán' }]" />
      <h1 class="mb-6 text-[clamp(30px,5vw,46px)] font-black tracking-tight">Thanh toán</h1>

      <section class="mb-8 rounded-btn border border-amber-200 bg-amber-50 p-6 text-amber-950" aria-labelledby="checkout-preview-title">
        <h2 id="checkout-preview-title" class="mb-2 text-xl font-black">Preview chỉ đọc</h2>
        <p class="mb-0 leading-relaxed">Trang preview không thu thập thông tin giao hàng, không gọi cổng thanh toán và không tạo đơn hàng. Luồng checkout thật chưa được mở.</p>
      </section>

      <div v-if="pending" class="rounded-btn border border-gray-200 p-6 text-slate-500" role="status">Đang tải nội dung giỏ hàng…</div>
      <div v-else-if="error || !cart" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        Không thể tải session giỏ hàng từ PHP local.
      </div>
      <section v-else class="rounded-btn border border-gray-200 p-6" aria-labelledby="checkout-cart-title">
        <h2 id="checkout-cart-title" class="mb-5 text-xl font-black">Tóm tắt giỏ hàng</h2>
        <p v-if="!cart.items.length" class="mb-0 text-slate-600">Giỏ hàng đang trống.</p>
        <ul v-else class="m-0 divide-y divide-gray-200 p-0">
          <li v-for="item in cart.items" :key="item.code" class="flex justify-between gap-4 py-3 first:pt-0 last:pb-0">
            <span class="min-w-0 leading-relaxed"><strong>{{ item.quantity }}×</strong> {{ item.name }}</span>
            <strong class="shrink-0 text-red-600">{{ formatMoney(item.lineTotal) }}</strong>
          </li>
        </ul>
        <div class="mt-5 flex justify-between border-t border-gray-200 pt-4 text-lg">
          <strong>Tạm tính</strong>
          <strong class="text-red-600">{{ formatMoney(cart.subtotal) }}</strong>
        </div>
        <p v-if="!cart.canCheckout" class="mb-0 mt-4 text-sm leading-relaxed text-slate-600">Giỏ hàng có sản phẩm cần báo giá hoặc chưa đủ điều kiện checkout.</p>
      </section>

      <NuxtLink :to="links.generic('/gio-hang/')" class="mt-6 inline-flex min-h-11 items-center rounded-chip border border-navy-800 px-5 font-bold text-navy-800 no-underline hover:bg-slate-50">
        Quay lại giỏ hàng
      </NuxtLink>
    </main>
  </div>
</template>

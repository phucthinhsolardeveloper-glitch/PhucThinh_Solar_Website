<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'

const { site } = await usePublicSite()
const route = useRoute()
const token = computed(() => typeof route.query.token === 'string' ? route.query.token : '')
const { data, error, pending } = await useFetch<unknown>('/_migration/order-thanks', { query: computed(() => ({ token: token.value })), default: () => null })
const payload = computed<PublicOrderThanksPayload | null>(() => isPublicOrderThanksPayload(data.value) ? data.value : null)

function formatMoney(value: number): string {
  return new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[900px] px-4 py-16 text-center max-ph:py-10">
      <div v-if="pending" class="text-slate-500" role="status">Đang xác nhận đơn hàng…</div>
      <div v-else-if="error || !payload" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-left text-amber-900">
        <h1 class="mb-2 text-2xl font-black">Không tìm thấy phiên xác nhận đơn hàng</h1>
        <p class="mb-4">Liên kết có thể đã hết hạn hoặc không thuộc phiên trình duyệt hiện tại.</p>
        <NuxtLink to="/" class="font-bold underline">Về trang chủ</NuxtLink>
      </div>
      <div v-else class="rounded-btn border border-emerald-200 bg-emerald-50/40 px-6 py-12">
        <div class="mx-auto mb-5 grid h-16 w-16 place-items-center rounded-full bg-emerald-600 text-3xl font-black text-white" aria-hidden="true">✓</div>
        <p class="mb-2 text-sm font-extrabold uppercase tracking-[.14em] text-emerald-700">Đặt hàng thành công</p>
        <h1 class="mb-4 text-[clamp(30px,5vw,46px)] font-black tracking-tight">Cảm ơn {{ payload.order.customerName }}</h1>
        <p class="mx-auto max-w-[620px] leading-relaxed text-slate-600">Phúc Thịnh Solar đã nhận đơn hàng <strong class="text-navy-800">{{ payload.order.code }}</strong>. Đội ngũ sẽ liên hệ theo thông tin anh/chị đã cung cấp.</p>
        <div class="mx-auto mt-6 max-w-[420px] rounded-chip border border-emerald-200 bg-white p-4">
          <span class="block text-sm text-slate-500">Tổng giá trị đơn</span>
          <strong class="mt-1 block text-2xl text-red-600">{{ formatMoney(payload.order.totalPrice) }}</strong>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
          <NuxtLink to="/" class="inline-flex min-h-11 items-center rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 no-underline">Về trang chủ</NuxtLink>
          <NuxtLink to="/san-pham/" class="inline-flex min-h-11 items-center rounded-chip border border-navy-800 px-6 font-bold text-navy-800 no-underline">Xem thêm sản phẩm</NuxtLink>
        </div>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

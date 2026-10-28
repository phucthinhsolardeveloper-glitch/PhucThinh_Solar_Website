<script setup lang="ts">
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'

const { site } = await usePublicSite()
const { cart, loading, error, refresh, mutate } = useMigrationCart()
const { data: initialCart } = await useFetch<unknown>('/_migration/cart', { key: 'migration-cart-page', default: () => null })
if (isPublicCartPayload(initialCart.value)) cart.value = initialCart.value

const quantityDraft = reactive<Record<string, number>>({})

watchEffect(() => {
  for (const item of cart.value?.items ?? []) {
    if (!(item.code in quantityDraft)) quantityDraft[item.code] = item.quantity
  }
})

function formatMoney(value: number | null): string {
  return value === null ? 'Liên hệ' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

async function updateItem(code: string, productId: number) {
  const quantity = Math.max(1, Math.min(999, Number(quantityDraft[code] ?? 1)))
  quantityDraft[code] = quantity
  await mutate({ action: 'update', code, productId, quantity })
}

async function removeItem(code: string) {
  await mutate({ action: 'delete', code })
  delete quantityDraft[code]
}

const payload = computed<PublicCartPayload | null>(() => cart.value)
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1320px] px-4 py-10 max-ph:py-7">
      <SiteBreadcrumb :items="[{ label: 'Giỏ hàng' }]" />

      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <p class="mb-2 text-xs font-extrabold uppercase tracking-[.12em] text-gold-700">Commerce preview</p>
          <h1 class="m-0 text-[clamp(30px,5vw,48px)] font-black tracking-tight">Giỏ hàng của bạn</h1>
        </div>
        <NuxtLink to="/migration-catalog-preview" class="font-bold text-navy-800 underline-offset-4 hover:underline">Tiếp tục xem sản phẩm</NuxtLink>
      </div>

      <div v-if="!payload && loading" class="rounded-btn border border-gray-200 p-8 text-slate-500" role="status">Đang tải giỏ hàng…</div>
      <div v-else-if="!payload" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        <strong>Không thể tải session giỏ hàng.</strong>
        <p class="mb-0 mt-2">{{ error || 'PHP cart API local chưa khả dụng.' }}</p>
        <button type="button" class="mt-4 rounded-chip border border-red-300 bg-white px-4 py-2 font-bold" @click="refresh()">Thử lại</button>
      </div>

      <template v-else>
        <div v-if="!payload.items.length" class="rounded-btn border border-gray-200 px-6 py-14 text-center">
          <h2 class="mb-2 text-2xl font-black">Giỏ hàng đang trống</h2>
          <p class="mb-6 text-slate-500">Chọn sản phẩm có giá công khai để thử luồng giỏ hàng Nuxt.</p>
          <NuxtLink to="/migration-catalog-preview" class="inline-flex min-h-11 items-center rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 no-underline">Xem sản phẩm</NuxtLink>
        </div>

        <div v-else class="grid gap-8 nav:grid-cols-[minmax(0,1fr)_340px]">
          <section aria-label="Sản phẩm trong giỏ" class="divide-y divide-gray-200 border-y border-gray-200">
            <article v-for="item in payload.items" :key="item.code" class="grid grid-cols-[96px_minmax(0,1fr)] gap-4 py-5 tb:grid-cols-[112px_minmax(0,1fr)_150px_120px] tb:items-center">
              <NuxtLink :to="{ path: '/migration-product-preview', query: { slug: item.slug } }" class="grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-contain" loading="lazy">
                <span v-else class="px-2 text-center text-xs text-slate-400">Chưa có ảnh</span>
              </NuxtLink>

              <div class="min-w-0">
                <NuxtLink :to="{ path: '/migration-product-preview', query: { slug: item.slug } }" class="font-extrabold leading-snug text-navy-800 no-underline hover:underline">{{ item.name }}</NuxtLink>
                <div v-if="item.color || item.size" class="mt-1 text-sm text-slate-500">
                  <span v-if="item.color">Màu: {{ item.color }}</span><span v-if="item.color && item.size"> · </span><span v-if="item.size">Size: {{ item.size }}</span>
                </div>
                <p class="mb-0 mt-2 text-sm font-bold text-red-600 tb:hidden">{{ formatMoney(item.lineTotal) }}</p>
              </div>

              <div class="col-start-2 flex items-center gap-2 tb:col-start-auto">
                <input
                  v-model.number="quantityDraft[item.code]"
                  type="number"
                  min="1"
                  max="999"
                  class="h-10 w-20 rounded-chip border border-gray-300 px-2 text-center outline-none focus:border-gold-600"
                  aria-label="Số lượng"
                  @change="updateItem(item.code, item.productId)"
                >
                <button type="button" class="text-sm font-bold text-red-700 hover:underline disabled:opacity-50" :disabled="loading" @click="removeItem(item.code)">Xóa</button>
              </div>

              <div class="hidden text-right tb:block">
                <strong class="block text-red-600">{{ formatMoney(item.lineTotal) }}</strong>
                <small class="text-slate-400">{{ formatMoney(item.unitPrice) }} / sản phẩm</small>
              </div>
            </article>
          </section>

          <aside class="h-fit rounded-btn border border-gray-200 p-5 nav:sticky nav:top-5">
            <h2 class="mb-5 text-xl font-black">Tóm tắt đơn hàng</h2>
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
              <span class="text-slate-500">{{ payload.quantityCount }} sản phẩm</span>
              <strong>{{ formatMoney(payload.subtotal) }}</strong>
            </div>
            <div class="flex items-center justify-between py-4 text-lg">
              <strong>Tạm tính</strong>
              <strong class="text-red-600">{{ formatMoney(payload.subtotal) }}</strong>
            </div>
            <p v-if="!payload.canCheckout" class="rounded-chip bg-amber-50 p-3 text-sm leading-relaxed text-amber-800">
              Giỏ có sản phẩm cần báo giá; hãy liên hệ tư vấn trước khi đặt hàng.
            </p>
            <div v-else class="rounded-chip bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">
              Cart session đã chạy qua PHP JSON API. Checkout Nuxt dùng một adapter riêng để giữ đúng CSRF, idempotency, shipping và payment validation.
            </div>
            <NuxtLink
              v-if="payload.canCheckout"
              to="/migration-checkout-preview"
              class="mt-4 flex min-h-12 w-full items-center justify-center rounded-chip bg-gold-500 px-5 text-center font-extrabold text-navy-950 no-underline hover:bg-gold-600"
            >
              Tiếp tục thanh toán
            </NuxtLink>
            <NuxtLink
              v-else
              to="/migration-contact-preview#yeu-cau-tu-van"
              class="mt-4 flex min-h-12 w-full items-center justify-center rounded-chip bg-gold-500 px-5 text-center font-extrabold text-navy-950 no-underline hover:bg-gold-600"
            >
              Liên hệ tư vấn
            </NuxtLink>
          </aside>
        </div>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

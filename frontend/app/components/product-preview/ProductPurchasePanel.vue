<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'

const props = defineProps<{ productId: number; pricing: PublicCatalogPricing; consultationAvailable: boolean }>()
const quantity = ref(1)
const added = ref(false)
const { loading, error, mutate } = useMigrationCart()
const links = useMigrationLinks()
const { openDealer, openConsultation } = useStorefrontModal()

async function add(goToCart = false) {
  added.value = false
  quantity.value = normalizeCommerceQuantity(quantity.value)
  try {
    await mutate({ action: 'add', productId: props.productId, quantity: quantity.value })
  } catch {
    return
  }
  added.value = true
  if (goToCart) await navigateTo(links.generic('/gio-hang/'))
}
</script>

<template>
  <section class="mt-4" aria-label="Mua sản phẩm">
    <QuantityStepper v-if="pricing.mode === 'price'" v-model="quantity" label="Số lượng" inline-label :disabled="loading" />
    <slot name="product-info" />
    <div v-if="pricing.mode === 'price'" class="mt-5 grid gap-[9px]">
      <button
        type="button"
        class="min-h-12 w-full rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 transition-all duration-150 hover:bg-gold-600 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer shadow-xs"
        :disabled="loading"
        @click="add(true)"
      >
        Mua ngay
      </button>
      <button
        type="button"
        class="min-h-12 w-full rounded-[4px] border border-navy-800 bg-white px-5 font-bold text-navy-800 transition-all duration-150 hover:bg-slate-50 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer"
        :disabled="loading"
        @click="add(false)"
      >
        {{ loading ? 'Đang thêm…' : 'Thêm vào giỏ' }}
      </button>
    </div>
    <p v-if="added && !error" class="mb-0 mt-3 text-sm font-semibold text-emerald-700" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
    <p v-if="error" class="mb-0 mt-3 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
    <a
      v-if="pricing.mode === 'quote' && consultationAvailable"
      href="#pts-pdp-consultation"
      class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-all duration-150 hover:bg-gold-600 active:scale-[0.98] cursor-pointer shadow-xs"
      @click.prevent="openConsultation()"
    >
      Liên hệ nhận báo giá
    </a>
    <p v-else-if="pricing.mode === 'quote'" class="mb-0 text-sm text-slate-500" role="status">
      Thông tin tư vấn sản phẩm hiện chưa tải được.
    </p>

    <!-- Link đại lý: mở popup modal đại lý theo chuẩn web PHP cũ -->
    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 font-bold text-navy-800 transition-colors hover:text-gold-700 cursor-pointer"
        @click="openDealer()"
      >
        <span>Bạn là đại lý? Đăng ký hợp tác</span>
        <span aria-hidden="true" class="text-gold-600 font-black">→</span>
      </button>
    </div>

    <!-- 4 cam kết dịch vụ cốt lõi (tái hiện từ PHP cũ: 100% Chính hãng, Bảo hành, Giao hàng, Kỹ thuật 24/7) -->
    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4" aria-label="Cam kết dịch vụ">
      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">100% Chính Hãng</span>
          <span class="text-slate-500 text-[11px]">Đầy đủ CO, CQ</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Giao Hàng Nhanh</span>
          <span class="text-slate-500 text-[11px]">Toàn quốc an toàn</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Bảo Hành Chuẩn</span>
          <span class="text-slate-500 text-[11px]">Theo nhà sản xuất</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Hỗ Trợ Kỹ Thuật</span>
          <span class="text-slate-500 text-[11px]">Đội ngũ 24/7</span>
        </div>
      </div>
    </div>
  </section>
</template>

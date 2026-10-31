<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'

const props = defineProps<{ productId: number; pricing: PublicCatalogPricing; consultationAvailable: boolean }>()
const quantity = ref(1)
const added = ref(false)
const loadingAction = ref<'buy' | 'cart' | null>(null)
const { loading, error, mutate } = useMigrationCart()
const links = useMigrationLinks()
const { openDealer, openConsultation } = useStorefrontModal()

async function add(goToCart = false) {
  added.value = false
  quantity.value = normalizeCommerceQuantity(quantity.value)
  loadingAction.value = goToCart ? 'buy' : 'cart'
  try {
    await mutate({ action: 'add', productId: props.productId, quantity: quantity.value })
  } catch {
    return
  } finally {
    loadingAction.value = null
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
      <UButton
        type="button"
        color="primary"
        size="lg"
        :loading="loading && loadingAction === 'buy'"
        class="min-h-12 w-full justify-center rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 transition-all duration-150 hover:bg-gold-600 hover:shadow-sm active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer shadow-xs"
        :disabled="loading"
        @click="add(true)"
      >
        {{ loadingAction === 'buy' ? 'Đang xử lý…' : 'Mua ngay' }}
      </UButton>
      <UButton
        type="button"
        color="secondary"
        variant="outline"
        size="lg"
        :loading="loading && loadingAction === 'cart'"
        class="public-neutral-button public-navy-outline-button min-h-12 w-full justify-center rounded-[4px] px-5 font-bold text-navy-800 transition-all duration-150 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer"
        :disabled="loading"
        @click="add(false)"
      >
        {{ loading ? 'Đang thêm…' : 'Thêm vào giỏ' }}
      </UButton>
    </div>
    <p v-if="added && !error" class="mb-0 mt-3 text-sm font-semibold text-emerald-700" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
    <p v-if="error" class="mb-0 mt-3 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
    <UButton
      v-if="pricing.mode === 'quote' && consultationAvailable"
      href="#pts-pdp-consultation"
      color="primary"
      size="lg"
      class="mt-5 min-h-12 w-full justify-center rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-all duration-150 hover:bg-gold-600 active:scale-[0.98] cursor-pointer shadow-xs"
      @click.prevent="openConsultation()"
    >
      Liên hệ nhận báo giá
    </UButton>
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

    <!-- Keep product trust copy aligned with the PHP catalog's published promises. -->
    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4" aria-label="Cam kết dịch vụ">
      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M8 14a6 6 0 1 1 8 0c-.8.7-1 1.4-1 2H9c0-.6-.2-1.3-1-2Z"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Tư vấn lựa chọn</span>
          <span class="text-slate-500 text-[11px]">Đúng nhu cầu, hiệu quả</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.7 6.3 3 3M3 21l6.5-6.5M14 4l2-2 6 6-2 2M9.5 14.5 5 10l4-4 4.5 4.5"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Hỗ trợ kỹ thuật</span>
          <span class="text-slate-500 text-[11px]">Trong suốt quá trình sử dụng</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Chính sách rõ ràng</span>
          <span class="text-slate-500 text-[11px]">Minh bạch, dễ hiểu</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v11H3zM14 10h4l3 4v4h-7z"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="17.5" cy="18.5" r="1.5"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Giao hàng toàn quốc</span>
          <span class="text-slate-500 text-[11px]">Nhanh chóng, an toàn</span>
        </div>
      </div>
    </div>
  </section>
</template>

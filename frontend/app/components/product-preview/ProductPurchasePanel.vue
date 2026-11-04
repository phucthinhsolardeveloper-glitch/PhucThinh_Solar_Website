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
  if (goToCart) { await navigateTo(links.generic('/gio-hang/')); return }
  justAdded.value = true
  clearTimeout(justAddedTimer)
  justAddedTimer = setTimeout(() => { justAdded.value = false }, 2000)
}
// Brief confirmation on the button itself; the status line below has a reserved slot.
const justAdded = ref(false)
let justAddedTimer: ReturnType<typeof setTimeout> | undefined
onBeforeUnmount(() => clearTimeout(justAddedTimer))
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
        <template v-if="loadingAction === 'cart'">Đang thêm…</template>
        <template v-else-if="justAdded"><UIcon name="i-lucide-check" class="size-5" aria-hidden="true" /> Đã thêm vào giỏ</template>
        <template v-else>Thêm vào giỏ</template>
      </UButton>
    </div>
    <!-- Reserved slot: confirmation/error fade in without pushing the rest of the panel. -->
    <div v-if="pricing.mode === 'price'" class="purchase-status mt-3 min-h-6" aria-live="polite">
      <Transition name="purchase-status" mode="out-in">
        <p v-if="error" key="error" class="m-0 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
        <p v-else-if="added" key="added" class="m-0 flex flex-wrap items-center gap-2 text-sm font-semibold text-emerald-700" role="status">
          <span>Đã thêm sản phẩm vào giỏ hàng.</span>
          <NuxtLink :to="links.generic('/gio-hang/')" class="font-bold text-navy-900 underline hover:text-gold-700">Xem giỏ hàng →</NuxtLink>
        </p>
      </Transition>
    </div>
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

    <div class="fixed inset-x-0 bottom-0 z-40 flex gap-2 border-t border-slate-200 bg-white/95 p-3 pb-[max(12px,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(6,21,55,0.12)] backdrop-blur-md nav:hidden">
      <UButton
        v-if="pricing.mode === 'price'"
        type="button"
        color="secondary"
        variant="outline"
        class="public-neutral-button min-h-12 flex-1 rounded-[4px] px-3 font-bold text-navy-800"
        :loading="loading && loadingAction === 'cart'"
        :disabled="loading"
        @click="add(false)"
      >Thêm vào giỏ</UButton>
      <UButton
        type="button"
        color="primary"
        class="min-h-12 flex-1 rounded-[4px] bg-gold-500 px-3 font-extrabold text-navy-950"
        :disabled="pricing.mode === 'price' ? loading : !consultationAvailable"
        @click="pricing.mode === 'price' ? add(true) : openConsultation()"
      >{{ pricing.mode === 'price' ? 'Mua ngay' : 'Nhận báo giá' }}</UButton>
    </div>
  </section>
</template>

<style scoped>
.purchase-status-enter-active, .purchase-status-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.purchase-status-enter-from, .purchase-status-leave-to { opacity: 0; transform: translateY(-3px); }
@media (prefers-reduced-motion: reduce) { .purchase-status-enter-active, .purchase-status-leave-active { transition: none; } }
</style>

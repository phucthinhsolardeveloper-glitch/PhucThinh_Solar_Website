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
        class="min-h-12 w-full justify-center rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 hover:bg-gold-600"
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
        class="min-h-12 w-full justify-center rounded-[4px] border border-navy-800 bg-white px-5 font-bold text-navy-800 hover:bg-slate-50"
        :disabled="loading"
        @click="add(false)"
      >
        {{ loading ? 'Đang thêm…' : 'Thêm vào giỏ' }}
      </UButton>
    </div>
    <p v-if="added && !error" class="mb-0 mt-3 text-sm font-semibold text-emerald-700" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
    <p v-if="error" class="mb-0 mt-3 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
    <UButton v-if="pricing.mode === 'quote' && consultationAvailable"
      href="#pts-pdp-consultation"
      color="primary"
      size="lg"
      class="mt-5 min-h-12 w-full justify-center rounded-[4px] bg-gold-500 px-6 font-extrabold text-navy-950 no-underline hover:bg-gold-600"
    >
      Liên hệ nhận báo giá
    </UButton>
    <p v-else-if="pricing.mode === 'quote'" class="mb-0 text-sm text-slate-500" role="status">
      Thông tin tư vấn sản phẩm hiện chưa tải được.
    </p>
  </section>
</template>

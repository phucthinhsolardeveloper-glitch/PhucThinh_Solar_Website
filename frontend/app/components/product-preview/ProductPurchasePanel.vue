<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'

const props = defineProps<{ productId: number; pricing: PublicCatalogPricing; consultationAvailable: boolean }>()
const quantity = ref(1)
const added = ref(false)
const { loading, error, mutate } = useMigrationCart()
const links = useMigrationLinks()

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
  <section class="mt-6 border-t border-gray-200 pt-5" aria-label="Mua sản phẩm">
    <template v-if="pricing.mode === 'price'">
      <div class="flex flex-wrap items-end gap-3">
        <QuantityStepper v-model="quantity" label="Số lượng" :disabled="loading" />
        <button
          type="button"
          class="min-h-11 rounded-chip border border-navy-800 bg-white px-5 font-bold text-navy-800 transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60"
          :disabled="loading"
          @click="add(false)"
        >
          {{ loading ? 'Đang thêm…' : 'Thêm vào giỏ' }}
        </button>
        <button
          type="button"
          class="min-h-11 rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 transition hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60"
          :disabled="loading"
          @click="add(true)"
        >
          Mua ngay
        </button>
      </div>
      <p v-if="added && !error" class="mb-0 mt-3 text-sm font-semibold text-emerald-700" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
      <p v-if="error" class="mb-0 mt-3 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
    </template>
    <a
      v-else-if="consultationAvailable"
      href="#pts-pdp-consultation"
      class="inline-flex min-h-11 items-center rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 no-underline hover:bg-gold-600"
    >
      Liên hệ nhận báo giá
    </a>
    <p v-else class="mb-0 text-sm text-slate-500" role="status">
      Thông tin tư vấn sản phẩm hiện chưa tải được.
    </p>
  </section>
</template>

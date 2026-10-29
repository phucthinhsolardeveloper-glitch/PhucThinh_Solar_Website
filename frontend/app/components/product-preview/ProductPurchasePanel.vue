<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'

const props = defineProps<{ productId: number; pricing: PublicCatalogPricing }>()
const quantity = ref(1)
const added = ref(false)
const { loading, error, mutate } = useMigrationCart()

async function add(goToCart = false) {
  added.value = false
  await mutate({ action: 'add', productId: props.productId, quantity: quantity.value })
  added.value = true
  if (goToCart) await navigateTo('/gio-hang/')
}
</script>

<template>
  <section class="mt-6 border-t border-gray-200 pt-5" aria-label="Mua sản phẩm">
    <template v-if="pricing.mode === 'price'">
      <div class="flex flex-wrap items-end gap-3">
        <label class="grid gap-1.5 text-sm font-bold text-navy-800">
          Số lượng
          <input
            v-model.number="quantity"
            type="number"
            min="1"
            max="999"
            step="1"
            inputmode="numeric"
            class="h-11 w-24 rounded-chip border border-gray-300 px-3 text-base outline-none focus:border-gold-600"
          >
        </label>
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
      v-else
      href="#pts-pdp-consultation"
      class="inline-flex min-h-11 items-center rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 no-underline hover:bg-gold-600"
    >
      Liên hệ nhận báo giá
    </a>
  </section>
</template>

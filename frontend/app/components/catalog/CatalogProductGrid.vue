<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

defineProps<{ products: PublicCatalogProduct[] }>()
const links = useMigrationLinks()
const failedImages = ref<Set<number>>(new Set())

function markImageFailed(productId: number) {
  failedImages.value = new Set(failedImages.value).add(productId)
}

// Per-card add-to-cart state, kept local to this grid (not the shared
// useMigrationCart `loading`/`error` state, which is a single global flag -
// reusing it here would make every card show "Đang thêm…" at once).
const { mutate } = useMigrationCart()
const addingIds = ref<Set<number>>(new Set())
const addedIds = ref<Set<number>>(new Set())
const cartErrors = ref<Map<number, string>>(new Map())

async function addToCart(product: PublicCatalogProduct) {
  addingIds.value = new Set(addingIds.value).add(product.id)
  const nextErrors = new Map(cartErrors.value)
  nextErrors.delete(product.id)
  cartErrors.value = nextErrors
  try {
    await mutate({ action: 'add', productId: product.id, quantity: 1 })
    addedIds.value = new Set(addedIds.value).add(product.id)
  } catch (reason) {
    const message = reason instanceof Error ? reason.message : 'Không thể thêm vào giỏ hàng.'
    const errors = new Map(cartErrors.value)
    errors.set(product.id, message)
    cartErrors.value = errors
  } finally {
    const next = new Set(addingIds.value)
    next.delete(product.id)
    addingIds.value = next
  }
}

/**
 * Định dạng tiền khớp đúng PHP: libraries/class/class.Functions.php::formatMoney()
 * dùng number_format($price, 0, ',', '.') + hậu tố "đ" — dấu CHẤM phân cách
 * nghìn, không có phần thập phân. 'vi-VN' của Intl cho đúng kết quả đó vì
 * locale này dùng dấu chấm làm thousands separator.
 */
function formatMoney(value: number): string {
  return `${new Intl.NumberFormat('vi-VN').format(value)}đ`
}

/** PHP hiển thị công suất dạng "650W" — bỏ số 0 thừa, không phần thập phân khi là số nguyên. */
function formatPower(watts: number): string {
  return `${Number.isInteger(watts) ? watts : watts.toFixed(1)}W`
}

function productHref(product: PublicCatalogProduct) {
  return links.product(product.slug)
}
</script>

<template>
  <!--
    Lưới đo từ ảnh production 2026-10-26 (danh mục tấm pin, 1920px): 4 cột rộng
    đúng 337px, mép trái 447/800/1153/1506 -> khoảng cách ngang 16px, tổng lưới
    1396px. Nút trong thẻ cao 40px.

    DTO đã mở rộng (merge feature/pts-nuxt-php-dto-20261026): powerWatts +
    pricing giờ có thật, lấy từ publicProductPricesVisible() và status
    'baogia' — cùng logic site PHP đang dùng ở PDP. Khi pricing.mode = 'quote'
    (tắt hiển thị giá công khai, hoặc sản phẩm cố ý ẩn giá) thì hiện "Liên hệ
    để nhận báo giá" như trước — không suy luận hay bịa giá.
  -->
  <div
    v-if="products.length"
    class="grid grid-cols-2 gap-x-4 gap-y-8 max-ph:gap-x-3 max-ph:gap-y-7 tb:grid-cols-3 nav:grid-cols-4"
  >
    <article v-for="product in products" :key="product.id" class="min-w-0">
      <NuxtLink
        :external="!links.active"
        :to="productHref(product)"
        class="relative grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50 no-underline"
      >
        <img
          v-if="product.media[0] && !failedImages.has(product.id)"
          :src="product.media[0]"
          :alt="product.name"
          class="h-full w-full object-contain"
          loading="lazy"
          width="640"
          height="640"
          sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
          @error="markImageFailed(product.id)"
        >
        <span v-else class="p-4 text-center text-sm text-slate-500">Chưa có hình ảnh</span>

        <span
          v-if="product.powerWatts"
          class="absolute left-2 top-2 rounded-chip bg-navy-800 px-2 py-1 text-xs font-bold text-white"
        >
          {{ formatPower(product.powerWatts) }}
        </span>
      </NuxtLink>

      <h3 class="mt-3 mb-1.5 text-[17px] leading-[1.4] max-ph:text-base">
        <NuxtLink
          :external="!links.active"
          :to="productHref(product)"
          class="text-inherit no-underline hover:underline hover:underline-offset-[3px] focus-visible:underline"
        >
          {{ product.name }}
        </NuxtLink>
      </h3>

      <p v-if="product.pricing?.mode === 'price'" class="m-0 flex items-baseline gap-2 text-base font-bold text-red-600">
        <template v-if="product.pricing.salePrice && product.pricing.regularPrice && product.pricing.salePrice < product.pricing.regularPrice">
          {{ formatMoney(product.pricing.salePrice) }}
          <span class="text-xs font-normal text-slate-400 line-through">{{ formatMoney(product.pricing.regularPrice) }}</span>
        </template>
        <template v-else>
          {{ formatMoney(product.pricing.salePrice ?? product.pricing.regularPrice ?? 0) }}
        </template>
      </p>
      <p v-else class="m-0 text-sm text-slate-500">Liên hệ để nhận báo giá</p>

      <div class="mt-3 grid gap-2">
        <button
          v-if="product.pricing?.mode === 'price'"
          type="button"
          class="min-h-10 rounded-chip bg-gold-500 px-3 text-sm font-extrabold text-navy-950 transition hover:bg-gold-600 disabled:cursor-wait disabled:opacity-60"
          :disabled="addingIds.has(product.id)"
          @click="addToCart(product)"
        >
          {{ addingIds.has(product.id) ? 'Đang thêm…' : 'Thêm giỏ hàng' }}
        </button>
        <NuxtLink
          :external="!links.active"
          :to="productHref(product)"
          class="inline-grid min-h-10 place-items-center rounded-chip border border-navy-800 px-3 text-sm font-bold text-navy-800 no-underline transition hover:bg-slate-50"
        >
          Xem chi tiết
        </NuxtLink>
        <p v-if="addedIds.has(product.id) && !cartErrors.has(product.id)" class="m-0 text-xs font-semibold text-emerald-700" role="status">Đã thêm vào giỏ hàng.</p>
        <p v-if="cartErrors.has(product.id)" class="m-0 text-xs font-semibold text-red-700" role="alert">{{ cartErrors.get(product.id) }}</p>
      </div>
    </article>
  </div>

  <p v-else class="py-8 text-slate-500" role="status">Chưa có sản phẩm trong danh mục này.</p>
</template>

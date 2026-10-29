<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

const props = withDefaults(defineProps<{ products: PublicCatalogProduct[]; layout?: 'grid' | 'rail' }>(), { layout: 'grid' })
const links = useMigrationLinks()
const isMigrationPreview = links.active
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

function quickSpecText(product: PublicCatalogProduct): string {
  if (product.quickSpecs?.length) return product.quickSpecs.join(' · ')
  return product.powerWatts ? formatPower(product.powerWatts) : ''
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
    :class="props.layout === 'rail' ? 'catalog-product-grid-rail' : 'grid grid-cols-2 gap-4 max-ph:gap-3 tb:grid-cols-3 nav:grid-cols-4'"
  >
    <article
      v-for="product in products"
      :key="product.id"
      class="group flex min-w-0 flex-col"
      :class="props.layout === 'rail' ? 'snap-start' : ''"
    >
      <NuxtLink
        :external="!isMigrationPreview"
        :to="productHref(product)"
        class="relative grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50/80 no-underline"
      >
        <img
          v-if="product.media[0] && !failedImages.has(product.id)"
          :src="product.media[0]"
          :alt="product.name"
          class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.03]"
          loading="lazy"
          width="640"
          height="640"
          sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
          @error="markImageFailed(product.id)"
        >
        <span v-else class="p-4 text-center text-sm text-slate-500">Chưa có hình ảnh</span>

      </NuxtLink>

      <h3 class="mt-3 mb-1.5 min-h-[42px] line-clamp-2 text-[15px] font-bold leading-[1.4] text-navy-800 max-ph:text-sm">
        <NuxtLink
          :external="!isMigrationPreview"
          :to="productHref(product)"
          class="text-inherit no-underline transition-colors group-hover:text-gold-700 focus-visible:underline"
        >
          {{ product.name }}
        </NuxtLink>
      </h3>
      <p class="mb-1 min-h-5 text-xs leading-5 text-slate-500">{{ quickSpecText(product) }}</p>

      <p v-if="product.pricing?.mode === 'price'" class="m-0 flex flex-wrap items-baseline gap-2 text-base font-extrabold text-red-600">
        <template v-if="product.pricing.salePrice && product.pricing.regularPrice && product.pricing.salePrice < product.pricing.regularPrice">
          {{ formatMoney(product.pricing.salePrice) }}
          <span class="text-xs font-normal text-slate-400 line-through">{{ formatMoney(product.pricing.regularPrice) }}</span>
        </template>
        <template v-else>
          {{ formatMoney(product.pricing.salePrice ?? product.pricing.regularPrice ?? 0) }}
        </template>
      </p>
      <p v-else class="m-0 text-[13px] font-medium text-slate-500">Liên hệ để nhận báo giá</p>

      <div class="mt-auto grid gap-2 pt-3">
        <button
          v-if="product.pricing?.mode === 'price'"
          type="button"
          class="min-h-10 rounded-chip bg-hdr-gold px-3 text-[13px] font-extrabold text-navy-950 transition-colors hover:bg-hdr-gold-hover disabled:cursor-wait disabled:opacity-60"
          :disabled="addingIds.has(product.id)"
          @click="addToCart(product)"
        >
          {{ addingIds.has(product.id) ? 'Đang thêm…' : 'Thêm giỏ hàng' }}
        </button>
        <NuxtLink
          :external="!isMigrationPreview"
          :to="productHref(product)"
          class="inline-grid min-h-10 place-items-center rounded-chip border border-navy-800/80 px-3 text-[13px] font-bold text-navy-800 no-underline transition-colors hover:border-navy-800 hover:bg-navy-800 hover:text-white"
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

<style scoped>
.catalog-product-grid-rail {
  display: grid;
  grid-auto-columns: calc((100% - 16px) / 2);
  grid-auto-flow: column;
  grid-template-rows: minmax(0, 1fr);
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 12px;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
}

@media (min-width: 821px) {
  .catalog-product-grid-rail {
    grid-auto-columns: calc((100% - 32px) / 3);
  }
}

@media (min-width: 1321px) {
  .catalog-product-grid-rail {
    grid-auto-columns: calc((100% - 48px) / 4);
  }
}
</style>

<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

const props = withDefaults(defineProps<{ products: PublicCatalogProduct[]; layout?: 'grid' | 'rail' }>(), { layout: 'grid' })
const links = useMigrationLinks()
const isMigrationPreview = links.active
const railRef = ref<HTMLElement | null>(null)
const failedImages = ref<Set<number>>(new Set())
const failedThumbnailImages = ref<Set<number>>(new Set())
const oversizedProductImages: Record<string, string> = {
  '/upload/product/t-bat-tsys-d53-2-4295.png': '/assets/images/site-visuals/media-fallbacks/brand-tbat-tsys-d53.webp',
}

function productImage(src: string): string {
  return oversizedProductImages[src] || src
}

function productImageSrc(product: PublicCatalogProduct): string {
  const original = productImage(product.media[0] || '')
  if (!original.startsWith('/upload/product/') || failedThumbnailImages.value.has(product.id)) return original
  return phpThumb(original, 640, 640)
}

function productImageSrcSet(product: PublicCatalogProduct): string | undefined {
  const original = productImage(product.media[0] || '')
  if (!original.startsWith('/upload/product/') || failedThumbnailImages.value.has(product.id)) return undefined
  return [320, 480, 640].map((width) => `${phpThumb(original, width, width)} ${width}w`).join(', ')
}

function scrollRail(direction: -1 | 1) {
  const rail = railRef.value
  if (!rail) return
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  rail.scrollBy({ left: direction * Math.max(rail.clientWidth * 0.82, 240), behavior: reducedMotion ? 'auto' : 'smooth' })
}

function markImageFailed(productId: number) {
  const next = new Set(failedImages.value)
  next.add(productId)
  failedImages.value = next
}

function markImageError(product: PublicCatalogProduct) {
  if (!failedThumbnailImages.value.has(product.id) && product.media[0]?.startsWith('/upload/product/')) {
    failedThumbnailImages.value = new Set(failedThumbnailImages.value).add(product.id)
    return
  }
  markImageFailed(product.id)
}

// Per-card add-to-cart state, kept local to this grid (not the shared
// useMigrationCart `loading`/`error` state, which is a single global flag -
// reusing it here would make every card show "Đang thêm…" at once).
const { mutate } = useMigrationCart()
const { celebrate } = useCartFeedback()
const { openConsultation } = useStorefrontModal()
const addingIds = ref<Set<number>>(new Set())
const addedIds = ref<Set<number>>(new Set())
const cartErrors = ref<Map<number, string>>(new Map())

const addedTimers = new Map<number, ReturnType<typeof setTimeout>>()
onBeforeUnmount(() => { for (const timer of addedTimers.values()) clearTimeout(timer) })

async function addToCart(product: PublicCatalogProduct, trigger?: EventTarget | null) {
  const card = trigger instanceof HTMLElement ? trigger.closest('article') : null
  addingIds.value = new Set(addingIds.value).add(product.id)
  const nextErrors = new Map(cartErrors.value)
  nextErrors.delete(product.id)
  cartErrors.value = nextErrors
  try {
    await mutate({ action: 'add', productId: product.id, quantity: 1 })
    addedIds.value = new Set(addedIds.value).add(product.id)
    celebrate({ name: product.name, image: productImageSrc(product), quantity: 1, from: card?.querySelector('img') })
    // The button confirms for 2s, then returns to its normal label.
    clearTimeout(addedTimers.get(product.id))
    addedTimers.set(product.id, setTimeout(() => {
      const next = new Set(addedIds.value)
      next.delete(product.id)
      addedIds.value = next
    }, 2000))
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

function discountPercent(product: PublicCatalogProduct): number | null {
  const pricing = product.pricing
  if (pricing?.mode === 'price' && pricing.regularPrice && pricing.salePrice && pricing.salePrice < pricing.regularPrice) {
    return Math.round(((pricing.regularPrice - pricing.salePrice) / pricing.regularPrice) * 100)
  }
  return null
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
  <div v-if="products.length" class="relative min-w-0">
    <div v-if="props.layout === 'rail' && products.length > 2" class="mb-3 flex justify-end gap-2" role="group" aria-label="Điều hướng sản phẩm liên quan">
      <UButton
        type="button"
        color="neutral"
        variant="outline"
        size="lg"
        square
        icon="i-lucide-chevron-left"
        aria-label="Sản phẩm trước"
        class="public-neutral-button size-11 justify-center rounded-[3px] text-navy-800"
        @click="scrollRail(-1)"
      />
      <UButton
        type="button"
        color="neutral"
        variant="outline"
        size="lg"
        square
        icon="i-lucide-chevron-right"
        aria-label="Sản phẩm tiếp theo"
        class="public-neutral-button size-11 justify-center rounded-[3px] text-navy-800"
        @click="scrollRail(1)"
      />
    </div>
    <div
      ref="railRef"
      tabindex="0"
      :aria-label="props.layout === 'rail' ? 'Danh sách sản phẩm liên quan, có thể cuộn ngang' : undefined"
      :class="props.layout === 'rail' ? 'catalog-product-grid-rail' : 'grid grid-cols-2 gap-4 max-ph:gap-3 tb:grid-cols-3 nav:grid-cols-4'"
    >
      <article
      v-for="(product, productIndex) in products"
      :key="product.id"
      class="group flex h-full min-w-0 flex-col"
      :class="props.layout === 'rail' ? 'snap-start' : ''"
    >
      <NuxtLink
        :external="!isMigrationPreview"
        :to="productHref(product)"
        class="pts-panel-sheen relative grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50/80 no-underline"
      >
        <span
          v-if="discountPercent(product)"
          class="absolute top-2.5 left-2.5 z-10 rounded-chip bg-red-600 px-2 py-0.5 text-xs font-black tracking-tight text-white shadow-xs"
        >
          -{{ discountPercent(product) }}%
        </span>
        <img
          v-if="product.media[0] && !failedImages.has(product.id)"
          :src="productImageSrc(product)"
          :srcset="productImageSrcSet(product)"
          :alt="product.name"
          class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none"
          :loading="productIndex < 4 ? 'eager' : 'lazy'"
          :fetchpriority="productIndex < 4 ? 'high' : 'auto'"
          decoding="async"
          width="640"
          height="640"
          sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
          @error="markImageError(product)"
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
      <p v-else-if="product.pricing?.mode !== 'quote'" class="m-0 text-[13px] font-medium text-slate-500">Liên hệ để nhận báo giá</p>

      <div class="mt-auto grid gap-1.5 pt-3">
        <UButton
          v-if="product.pricing?.mode === 'quote'"
          type="button"
          color="primary"
          size="md"
          class="min-h-11 w-full justify-center whitespace-nowrap rounded-[4px] bg-hdr-gold px-3 text-[13px] font-extrabold text-navy-950 shadow-xs transition-colors duration-150 hover:bg-hdr-gold-hover active:scale-[0.98]"
          :aria-label="`Liên hệ để nhận báo giá ${product.name}`"
          @click="openConsultation(product.name)"
        >
          <span class="max-[640px]:hidden">Liên hệ để nhận báo giá</span>
          <span class="hidden max-[640px]:inline">Liên hệ báo giá</span>
        </UButton>
        <UButton
          v-else-if="product.pricing?.mode === 'price'"
          type="button"
          color="primary"
          size="md"
          :loading="addingIds.has(product.id)"
          class="min-h-11 w-full justify-center gap-2 rounded-[4px] bg-hdr-gold px-3 text-[13px] font-extrabold text-navy-950 shadow-xs transition-colors duration-150 hover:bg-hdr-gold-hover active:scale-[0.98]"
          :disabled="addingIds.has(product.id)"
          @click="addToCart(product, $event.currentTarget)"
        >
          <template v-if="addingIds.has(product.id)">Đang thêm…</template>
          <span v-else-if="addedIds.has(product.id)" class="grid-added"><UIcon name="i-lucide-check" class="size-4" aria-hidden="true" />Đã thêm</span>
          <template v-else><UIcon name="i-lucide-shopping-bag" class="size-4" aria-hidden="true" />Thêm giỏ hàng</template>
        </UButton>
        <NuxtLink
          :external="!isMigrationPreview"
          :to="productHref(product)"
          class="inline-flex min-h-9 items-center justify-center gap-1.5 text-sm font-bold text-navy-800 no-underline transition-colors hover:text-gold-700 focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600"
        >
          Xem chi tiết <UIcon name="i-lucide-arrow-right" class="size-4" aria-hidden="true" />
        </NuxtLink>
        <div class="min-h-5 pt-0.5">
          <p v-if="cartErrors.has(product.id)" class="m-0 text-sm font-semibold leading-5 text-red-700" role="alert">{{ cartErrors.get(product.id) }}</p>
        </div>
      </div>
    </article>
    </div>
  </div>

  <div v-else class="flex flex-col items-start justify-between gap-4 pts-note p-6 sm:flex-row sm:items-center" role="status">
    <div>
      <p class="m-0 font-bold text-navy-900">Chưa có sản phẩm trong danh mục này.</p>
      <p class="m-0 mt-1 text-sm text-slate-600">Bạn có thể thử bỏ bớt tiêu chí lọc hoặc liên hệ kỹ thuật viên để được giới thiệu mã tương đương.</p>
    </div>
    <button
      type="button"
      class="inline-flex min-h-10 shrink-0 cursor-pointer items-center gap-1.5 rounded-[4px] bg-gold-500 px-4 py-2 text-xs font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
      @click="openConsultation('Tư vấn mã sản phẩm tương đương')"
    >
      <span>Nhận tư vấn mã tương đương</span>
      <span aria-hidden="true">→</span>
    </button>
  </div>
</template>

<style scoped>
.grid-added { display: inline-flex; align-items: center; gap: 6px; animation: grid-added-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes grid-added-in { from { opacity: 0; transform: translateY(5px) scale(0.95); } }
@media (prefers-reduced-motion: reduce) { .grid-added { animation: none; } }
.catalog-product-grid-rail {
  display: grid;
  grid-auto-columns: calc((100% - 16px) / 2);
  grid-auto-flow: column;
  grid-template-rows: minmax(0, 1fr);
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 12px;
  scroll-snap-type: x mandatory;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.catalog-product-grid-rail::-webkit-scrollbar {
  display: none;
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

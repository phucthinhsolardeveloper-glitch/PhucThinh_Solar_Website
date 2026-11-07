<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

const props = defineProps<{ dealer: PublicDealerPayload; selectedProduct?: string }>()
const emit = defineEmits<{ (e: 'selectProduct', title: string): void }>()
const links = usePublicLinks()
const productTrack = ref<HTMLElement | null>(null)
const canScrollPrevious = ref(false)
const canScrollNext = ref(false)
let productTrackObserver: ResizeObserver | null = null

const activeIndex = ref<number | null>(null)
const setDocumentScrollLock = useDocumentScrollLock()
const activeProduct = computed(() => activeIndex.value === null ? null : props.dealer.overview.products[activeIndex.value] ?? null)

function openProduct(index: number) {
  if (!props.dealer.overview.products[index]) return
  activeIndex.value = index
}

function closeProduct() {
  activeIndex.value = null
}

function moveProduct(delta: number) {
  const total = props.dealer.overview.products.length
  if (activeIndex.value === null || total < 2) return
  activeIndex.value = (activeIndex.value + delta + total) % total
}

function chooseProductLine(title: string) {
  closeProduct()
  emit('selectProduct', title)
}

function onKeydown(event: KeyboardEvent) {
  if (activeIndex.value === null) return
  if (event.key === 'Escape') closeProduct()
  else if (event.key === 'ArrowLeft') moveProduct(-1)
  else if (event.key === 'ArrowRight') moveProduct(1)
}

watch(activeIndex, (val) => setDocumentScrollLock(val !== null))

function syncProductControls() {
  const track = productTrack.value
  if (!track) return
  canScrollPrevious.value = track.scrollLeft > 2
  canScrollNext.value = track.scrollLeft + track.clientWidth < track.scrollWidth - 2
}

function scrollProducts(direction: -1 | 1) {
  const track = productTrack.value
  if (!track) return
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  track.scrollBy({ left: direction * Math.max(track.clientWidth * 0.82, 220), behavior: reducedMotion ? 'auto' : 'smooth' })
}

onMounted(() => {
  document.addEventListener('keydown', onKeydown)
  nextTick(() => {
    syncProductControls()
    if (productTrack.value && typeof ResizeObserver !== 'undefined') {
      productTrackObserver = new ResizeObserver(syncProductControls)
      productTrackObserver.observe(productTrack.value)
    }
  })
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  setDocumentScrollLock(false)
  productTrackObserver?.disconnect()
})
watch(() => props.dealer.overview.products.length, () => nextTick(syncProductControls))

function cardImage(src: string): string {
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? '/thumbs/480x300x1' + src
    : src
}
</script>

<template>
  <section
    v-if="dealer.overview.title || dealer.overview.descriptionContent.html || dealer.overview.description || dealer.overview.products.length"
    class="dealer-overview mt-10 min-[992px]:mt-[clamp(36px,4.2vw,56px)]"
  >
    <h2 v-if="dealer.overview.title" class="mb-4 text-[clamp(26px,3vw,40px)] font-black leading-tight tracking-tight text-navy-800">
      {{ dealer.overview.title || dealer.labels.overview }}
    </h2>

    <EditorialRichText v-if="dealer.overview.descriptionContent.html" :content="dealer.overview.descriptionContent" class="dealer-overview-rich mb-7 max-w-[70ch]" />
    <p v-else-if="dealer.overview.description" class="mb-7 max-w-[70ch] whitespace-pre-line leading-relaxed text-slate-600">{{ dealer.overview.description }}</p>

    <div v-if="dealer.overview.products.length > 1" class="dealer-products-nav mb-4 flex justify-end gap-2 min-[768px]:hidden" role="group" aria-label="Điều hướng sản phẩm phân phối">
      <button type="button" class="public-neutral-button grid size-11 place-items-center text-navy-800 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Sản phẩm trước" :disabled="!canScrollPrevious" @click="scrollProducts(-1)">
        <UIcon name="i-lucide-chevron-left" class="size-5" aria-hidden="true" />
      </button>
      <button type="button" class="public-neutral-button grid size-11 place-items-center text-navy-800 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Sản phẩm tiếp theo" :disabled="!canScrollNext" @click="scrollProducts(1)">
        <UIcon name="i-lucide-chevron-right" class="size-5" aria-hidden="true" />
      </button>
    </div>

    <div
      v-if="dealer.overview.products.length"
      ref="productTrack"
      tabindex="0"
      aria-label="Sản phẩm Phúc Thịnh Solar phân phối, có thể cuộn ngang"
      class="dealer-products-track flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3 pr-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 min-[768px]:mr-0 min-[768px]:grid min-[768px]:grid-cols-2 min-[768px]:gap-5 min-[768px]:overflow-visible min-[768px]:snap-none min-[768px]:pb-0 min-[768px]:pr-0 min-[992px]:grid-cols-3"
      @scroll.passive="syncProductControls"
    >
      <a
        v-for="(product, index) in dealer.overview.products"
        :key="product.title + product.image"
        class="dealer-product group w-[min(76vw,300px)] min-w-0 flex-none snap-start text-inherit no-underline cursor-pointer min-[768px]:w-auto"
        :href="product.href ? links.generic(product.href) : `#dang-ky-dai-ly`"
        :target="product.href ? '_blank' : undefined"
        :rel="product.href ? 'noopener noreferrer' : undefined"
        @click="product.href ? undefined : ($event.preventDefault(), openProduct(index))"
      >
        <div
          class="relative mb-2.5 overflow-hidden rounded-chip bg-slate-100 transition-shadow"
          :class="selectedProduct === product.title ? 'ring-2 ring-gold-500' : ''"
        >
          <img
            v-if="product.image"
            :src="cardImage(product.image)"
            :alt="product.title"
            class="block aspect-[8/5] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
            loading="lazy"
          >
          <span
            v-if="product.image && !product.href"
            class="dealer-spec-chip m-lg absolute bottom-2 right-2 inline-flex items-center gap-1 rounded-chip bg-navy-950/80 px-2 py-1 text-[11px] font-bold text-white opacity-90 backdrop-blur-xs transition-colors group-hover:bg-gold-500 group-hover:text-navy-950"
          >
            <UIcon name="i-lucide-zoom-in" class="size-3.5" aria-hidden="true" />
            <span>Thông số</span>
          </span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="dealer-product__name min-w-0 truncate font-bold text-navy-800 transition-colors group-hover:text-gold-700">{{ product.title }}</span>
          <button
            type="button"
            class="dealer-product__pick shrink-0 whitespace-nowrap rounded-chip px-2 py-1 text-xs font-extrabold transition-colors cursor-pointer"
            :class="selectedProduct === product.title
              ? 'bg-gold-500 text-navy-950'
              : 'bg-slate-100 text-navy-800 hover:bg-gold-500 hover:text-navy-950'"
            @click.stop.prevent="chooseProductLine(product.title)"
          >
            {{ selectedProduct === product.title ? 'Đã chọn' : '+ Chọn dòng' }}
          </button>
        </div>
      </a>
    </div>

    <Teleport to="body">
      <div
        v-if="activeProduct"
        class="fixed inset-0 z-[100] grid place-items-center photo-viewer p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="`Thông số dòng sản phẩm ${activeProduct.title}`"
        @click.self="closeProduct"
      >
        <button
          type="button"
          class="absolute right-4 top-4 flex size-11 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
          aria-label="Đóng bảng thông số"
          @click="closeProduct"
        >
          <UIcon name="i-lucide-x" class="size-6" aria-hidden="true" />
        </button>

        <button
          v-if="dealer.overview.products.length > 1"
          type="button"
          class="dealer-modal-arrow absolute left-4 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
          aria-label="Dòng sản phẩm trước"
          @click="moveProduct(-1)"
        >
          <UIcon name="i-lucide-chevron-left" class="size-6" aria-hidden="true" />
        </button>

        <div class="flex max-h-[90vh] w-[min(980px,92vw)] flex-col items-center gap-4">
          <!-- The frame holds its size and shows the card's photo until the large one has loaded. -->
          <div class="photo-viewer__media dealer-viewer-frame" :style="{ backgroundImage: `url(${cardImage(activeProduct.image)})` }">
            <img
              :key="activeProduct.image"
              :src="viewerImage(activeProduct.image)"
              :alt="activeProduct.title"
              class="dealer-viewer-frame__image"
              @load="($event.target as HTMLElement).classList.add('is-loaded')"
            >
          </div>
          <div class="flex w-full max-w-[760px] flex-wrap items-center justify-between gap-3 photo-viewer__label px-4 py-3">
            <div>
              <span class="text-xs font-bold text-slate-600">Dòng sản phẩm ({{ Number(activeIndex) + 1 }} / {{ dealer.overview.products.length }})</span>
              <strong class="block text-lg font-black">{{ activeProduct.title }}</strong>
            </div>
            <button
              type="button"
              class="dealer-modal-register inline-flex min-h-11 items-center gap-2 rounded-btn bg-gold-500 px-4 py-2 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400 cursor-pointer"
              @click="chooseProductLine(activeProduct.title)"
            >
              <span>Đăng ký phân phối {{ activeProduct.title }}</span>
              <span aria-hidden="true">→</span>
            </button>
          </div>
        </div>

        <button
          v-if="dealer.overview.products.length > 1"
          type="button"
          class="dealer-modal-arrow absolute right-4 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
          aria-label="Dòng sản phẩm tiếp theo"
          @click="moveProduct(1)"
        >
          <UIcon name="i-lucide-chevron-right" class="size-6" aria-hidden="true" />
        </button>
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.dealer-viewer-frame { position: relative; width: min(74vh, 92vw, 760px); aspect-ratio: 1; overflow: hidden; background-color: #eef2f8; background-position: center; background-size: cover; }
.dealer-viewer-frame__image { position: absolute; inset: 0; width: 100%; height: 100%; background: #fff; object-fit: contain; opacity: 0; transition: opacity .25s ease; }
.dealer-viewer-frame__image.is-loaded { opacity: 1; }
@media (prefers-reduced-motion: reduce) { .dealer-viewer-frame__image { transition: none; } }
.dealer-products-track { scrollbar-width: none; }
.dealer-products-track::-webkit-scrollbar { display: none; }
.dealer-overview-rich :deep(p:empty) { display: none; }
.dealer-overview-rich :deep(p) { margin-block: 0.45rem; }
@media (prefers-reduced-motion: reduce) {
  .dealer-products-track { scroll-behavior: auto; }
}

/* Phones (<=820px). Desktop rules above stay untouched. */
@media (max-width: 820px) {
  .dealer-overview { margin-top: 28px; }
  .dealer-overview h2 { margin-bottom: 10px; font-size: 26px; font-weight: 800; line-height: 1.18; letter-spacing: -0.02em; text-wrap: balance; }
  .dealer-overview-rich { margin-bottom: 18px; }
  .dealer-overview-rich :deep(p) { margin-block: 0.4rem; font-size: 16px; line-height: 1.6; }
  .dealer-products-nav { margin-bottom: 8px; }
  .dealer-product__name { overflow: visible; line-height: 1.3; text-overflow: clip; white-space: normal; }
  .dealer-product__pick { min-height: 44px; padding-inline: 12px; border-radius: var(--radius-btn); font-size: 13px; }
  .dealer-spec-chip { min-height: 28px; padding-inline: 10px; border-radius: 999px; font-size: 12px; }
  .dealer-modal-register { width: 100%; justify-content: space-between; text-align: left; }
  .dealer-modal-arrow { width: 44px; height: 44px; }
}
/* The rail bleeds to the screen edge while the first card keeps the page gutter. */
@media (max-width: 767px) {
  .dealer-products-track { gap: 12px; margin-inline: -16px; padding-inline: 16px; padding-bottom: 4px; scroll-padding-inline: 16px; }
}
@media (max-width: 359px) {
  .dealer-products-track { margin-inline: -12px; padding-inline: 12px; scroll-padding-inline: 12px; }
}
</style>

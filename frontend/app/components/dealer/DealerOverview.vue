<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

const props = defineProps<{ dealer: PublicDealerPayload }>()
const links = useMigrationLinks()
const productTrack = ref<HTMLElement | null>(null)
const canScrollPrevious = ref(false)
const canScrollNext = ref(false)
let productTrackObserver: ResizeObserver | null = null

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

onMounted(() => nextTick(() => {
  syncProductControls()
  if (productTrack.value && typeof ResizeObserver !== 'undefined') {
    productTrackObserver = new ResizeObserver(syncProductControls)
    productTrackObserver.observe(productTrack.value)
  }
}))
onBeforeUnmount(() => productTrackObserver?.disconnect())
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
    class="mt-12 min-[992px]:mt-[clamp(42px,5vw,72px)]"
  >
    <h2 v-if="dealer.overview.title" class="mb-5 text-[clamp(26px,3vw,40px)] font-black leading-tight tracking-tight text-navy-800">
      {{ dealer.overview.title || dealer.labels.overview }}
    </h2>

    <EditorialRichText v-if="dealer.overview.descriptionContent.html" :content="dealer.overview.descriptionContent" class="mb-8 max-w-[70ch]" />
    <p v-else-if="dealer.overview.description" class="mb-8 max-w-[70ch] whitespace-pre-line leading-relaxed text-slate-600">{{ dealer.overview.description }}</p>

    <div v-if="dealer.overview.products.length > 1" class="mb-4 flex justify-end gap-2 min-[768px]:hidden" role="group" aria-label="Điều hướng sản phẩm phân phối">
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
      class="dealer-products-track -mr-4 flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3 pr-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500 min-[768px]:mr-0 min-[768px]:grid min-[768px]:grid-cols-2 min-[768px]:gap-5 min-[768px]:overflow-visible min-[768px]:snap-none min-[768px]:pb-0 min-[768px]:pr-0 min-[992px]:grid-cols-3"
      @scroll.passive="syncProductControls"
    >
      <a
        v-for="product in dealer.overview.products"
        :key="product.title + product.image"
        class="group w-[min(76vw,300px)] min-w-0 flex-none snap-start text-inherit no-underline min-[768px]:w-auto"
        :href="product.href ? links.generic(product.href) : undefined"
        :target="links.active ? undefined : '_blank'"
        :rel="links.active ? undefined : 'noopener noreferrer'"
      >
        <div class="mb-3 overflow-hidden rounded-chip bg-slate-100">
          <img
            v-if="product.image"
            :src="cardImage(product.image)"
            :alt="product.title"
            class="block aspect-[8/5] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
            loading="lazy"
          >
        </div>
        <span class="font-bold text-navy-800 transition-colors group-hover:text-gold-700">{{ product.title }}</span>
      </a>
    </div>
  </section>
</template>

<style scoped>
.dealer-products-track { scrollbar-width: none; }
.dealer-products-track::-webkit-scrollbar { display: none; }
@media (prefers-reduced-motion: reduce) {
  .dealer-products-track { scroll-behavior: auto; }
}
</style>

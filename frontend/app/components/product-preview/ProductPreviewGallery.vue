<script setup lang="ts">
import { AnimatePresence, motion, useReducedMotion } from 'motion-v'

const props = defineProps<{ name: string; media: string[] }>()
const reducedMotion = useReducedMotion()
const selectedIndex = ref(0)
const failedMedia = ref<Set<string>>(new Set())
const retryVersion = ref(0)
const lightboxOpen = ref(false)
const isZoomed = ref(false)
const zoomOrigin = ref('50% 50%')
const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)
const displayedImage = ref<HTMLImageElement | null>(null)
const setDocumentScrollLock = useDocumentScrollLock()

watch(() => props.media, () => {
  selectedIndex.value = props.media.findIndex((image) => Boolean(image.trim()))
  if (selectedIndex.value < 0) selectedIndex.value = 0
  failedMedia.value = new Set()
  retryVersion.value = 0
  lightboxOpen.value = false
  isZoomed.value = false
})

const configuredIndexes = computed(() => props.media.map((image, index) => Boolean(image.trim()) ? index : -1).filter((index) => index >= 0))
const selectedMedia = computed(() => props.media[selectedIndex.value] || props.media[configuredIndexes.value[0] ?? 0] || '')
const selectedMediaAvailable = computed(() => Boolean(selectedMedia.value.trim()) && !failedMedia.value.has(selectedMedia.value))
const availableIndexes = computed(() => configuredIndexes.value.filter((index) => !failedMedia.value.has(props.media[index] ?? '')))
const displayedMediaNumber = computed(() => availableIndexes.value.indexOf(selectedIndex.value) + 1)

function retryMedia() {
  failedMedia.value = new Set()
  selectedIndex.value = configuredIndexes.value[0] ?? 0
  retryVersion.value += 1
  isZoomed.value = false
  lightboxOpen.value = false
}

// Swap only once the next photo is decoded, so the frame never shows an empty stage.
let showToken = 0
function selectMedia(index: number) {
  const image = props.media[index]
  if (!image) return
  const token = ++showToken
  const probe = new Image()
  probe.src = optimizedImage(image, 960, 960)
  probe.decode().catch(() => {}).finally(() => {
    if (token === showToken) selectedIndex.value = index
  })
}
function markMediaFailed(image: string) {
  failedMedia.value = new Set(failedMedia.value).add(image)
  if (image !== selectedMedia.value) return
  const nextIndex = props.media.findIndex((candidate) => !failedMedia.value.has(candidate))
  if (nextIndex >= 0) selectedIndex.value = nextIndex
  else lightboxOpen.value = false
}
function markDisplayedMediaFailed(element: HTMLImageElement | null) {
  if (!element || !import.meta.client) return
  const source = element.currentSrc || element.src
  if (source === new URL(optimizedImage(selectedMedia.value, 960, 960), document.baseURI).href) markMediaFailed(selectedMedia.value)
}
function handleDisplayedMediaError(event: Event) {
  markDisplayedMediaFailed(event.currentTarget as HTMLImageElement)
}
function reconcileDisplayedImage() {
  const image = displayedImage.value
  if (image?.complete && image.naturalWidth === 0) markDisplayedMediaFailed(image)
}
function focusFirst(container: HTMLElement | null) {
  container?.querySelector<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')?.focus()
}

function trapFocus(event: KeyboardEvent) {
  const container = lightboxPanel.value
  if (!container) return
  const focusable = [...container.querySelectorAll<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')]
  if (!focusable.length) return
  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function openLightbox(event: MouseEvent) {
  if (!selectedMediaAvailable.value) return
  lastTrigger.value = event.currentTarget instanceof HTMLElement ? event.currentTarget : null
  lightboxOpen.value = true
}
function closeLightbox() {
  lightboxOpen.value = false
}
function finishLightboxClose() {
  if (lightboxOpen.value) return
  setDocumentScrollLock(false)
  lastTrigger.value?.focus()
}
function moveLightbox(direction: -1 | 1) {
  const indexes = availableIndexes.value
  if (indexes.length < 2) return
  const current = indexes.indexOf(selectedIndex.value)
  const next = (current + direction + indexes.length) % indexes.length
  if (indexes[next] !== undefined) selectMedia(indexes[next])
}
function handleKey(event: KeyboardEvent) {
  if (!lightboxOpen.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    closeLightbox()
  } else if (event.key === 'ArrowLeft') moveLightbox(-1)
  else if (event.key === 'ArrowRight') moveLightbox(1)
  else if (event.key === 'Tab') trapFocus(event)
}

function handleMouseMove(event: MouseEvent) {
  const target = event.currentTarget as HTMLElement
  if (!target) return
  const rect = target.getBoundingClientRect()
  const x = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width))
  const y = Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height))
  zoomOrigin.value = `${(x * 100).toFixed(1)}% ${(y * 100).toFixed(1)}%`
  isZoomed.value = true
}

function handleMouseLeave() {
  isZoomed.value = false
}

function prevImage(event?: Event) {
  event?.stopPropagation()
  moveLightbox(-1)
}

function nextImage(event?: Event) {
  event?.stopPropagation()
  moveLightbox(1)
}

watch(lightboxOpen, (open) => {
  if (!import.meta.client) return
  if (open) {
    setDocumentScrollLock(true)
    nextTick(() => focusFirst(lightboxPanel.value))
  }
})
onMounted(() => document.addEventListener('keydown', handleKey))
watch([selectedMedia, retryVersion], async () => {
  await nextTick()
  reconcileDisplayedImage()
}, { flush: 'post' })
onMounted(async () => {
  await nextTick()
  reconcileDisplayedImage()
})
onMounted(() => {
  // Fetch the other photos ahead of the first swipe.
  const preload = () => props.media.forEach((image) => { if (image.trim()) new Image().src = optimizedImage(image, 960, 960) })
  if ('requestIdleCallback' in window) window.requestIdleCallback(preload)
  else setTimeout(preload, 800)
})
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKey)
  setDocumentScrollLock(false)
})

// Phones swipe between photos; a swipe must not also open the zoom view.
let swipeStartX: number | null = null
let swiped = false
function onSwipeStart(event: TouchEvent) {
  swipeStartX = event.touches[0]?.clientX ?? null
  swiped = false
}
function onSwipeEnd(event: TouchEvent) {
  const endX = event.changedTouches[0]?.clientX
  if (swipeStartX === null || endX === undefined) return
  const distance = endX - swipeStartX
  swipeStartX = null
  if (Math.abs(distance) < 40) return
  swiped = true
  if (distance < 0) nextImage()
  else prevImage()
}
function onStageClick(event: MouseEvent) {
  if (swiped) {
    swiped = false
    return
  }
  openLightbox(event)
}
</script>

<template>
  <div>
    <!-- Khung hiển thị ảnh sản phẩm chính với tính năng Zoom rê chuột & phím chuyển ảnh -->
    <div
      v-if="selectedMediaAvailable"
      class="group relative grid aspect-square w-full place-items-center overflow-hidden rounded-btn bg-white"
      @mousemove="handleMouseMove"
      @mouseleave="handleMouseLeave"
    >
      <button
        type="button"
        class="pdp-studio-stage relative flex h-full w-full items-center justify-center overflow-hidden bg-slate-50"
        :class="isZoomed ? 'cursor-crosshair' : 'cursor-zoom-in'"
        :aria-label="'Phóng to ảnh ' + name"
        @click="onStageClick($event)"
        @touchstart.passive="onSwipeStart"
        @touchend.passive="onSwipeEnd"
      >
        <span class="pdp-studio-halo" aria-hidden="true" />
        <Transition name="pdp-fade">
          <img
            ref="displayedImage"
            :key="`${selectedMedia}-${retryVersion}`"
            data-cart-source
            :src="optimizedImage(selectedMedia, 960, 960)"
            :alt="name"
            loading="eager"
            fetchpriority="high"
            decoding="async"
            class="pdp-zoom-image absolute inset-0 h-full w-full object-contain p-0 transition-transform duration-200 ease-out"
            :style="{
              transform: isZoomed ? 'scale(2.2)' : 'scale(1)',
              transformOrigin: zoomOrigin,
            }"
            width="720"
            height="720"
            @error="handleDisplayedMediaError"
          >
        </Transition>
      </button>

      <!-- Hiệu ứng viền phản quang quang học khi zoom -->
        <div
        v-if="isZoomed"
        class="pointer-events-none absolute inset-0 z-10 border border-gold-500/25 shadow-[inset_0_0_28px_rgba(201,150,46,0.12)] transition-opacity duration-200"
        aria-hidden="true"
      />

      <!-- Nút chuyển ảnh Trước (<) -->
      <button
        v-if="availableIndexes.length > 1"
        type="button"
        class="max-tb:hidden public-neutral-button absolute left-3 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full text-navy-900 opacity-0 backdrop-blur-xs transition-all duration-200 hover:scale-105 active:scale-95 group-hover:opacity-100 max-ph:opacity-90 max-ph:h-9 max-ph:w-9 cursor-pointer"
        aria-label="Ảnh trước"
        @click="prevImage"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <!-- Nút chuyển ảnh Tiếp theo (>) -->
      <button
        v-if="availableIndexes.length > 1"
        type="button"
        class="max-tb:hidden public-neutral-button absolute right-3 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full text-navy-900 opacity-0 backdrop-blur-xs transition-all duration-200 hover:scale-105 active:scale-95 group-hover:opacity-100 max-ph:opacity-90 max-ph:h-9 max-ph:w-9 cursor-pointer"
        aria-label="Ảnh tiếp theo"
        @click="nextImage"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>

      <!-- Huy hiệu hướng dẫn zoom -->
      <div
        class="max-tb:hidden pointer-events-none absolute bottom-3 left-3 z-10 flex items-center gap-1.5 rounded-full bg-navy-950/70 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-xs transition-opacity duration-200"
        :class="isZoomed ? 'opacity-0' : 'opacity-85 group-hover:opacity-100'"
      >
        <svg class="h-3.5 w-3.5 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/>
        </svg>
        <span class="max-ph:hidden">Rê chuột phóng to</span>
      </div>

      <!-- Số thứ tự ảnh -->
      <div v-if="availableIndexes.length > 1" class="pointer-events-none absolute bottom-3 right-3 z-10 max-tb:bottom-8 rounded-full bg-navy-950/70 px-2.5 py-1 text-xs font-bold text-white backdrop-blur-xs">
        {{ displayedMediaNumber }} / {{ availableIndexes.length }}
      </div>
    </div>
    <div v-else class="flex aspect-square w-full flex-col items-center justify-center gap-3 px-6 text-center" role="status" aria-live="polite">
      <p v-if="configuredIndexes.length === 0" class="m-0 text-base text-slate-600">Sản phẩm chưa có hình ảnh trong dữ liệu.</p>
      <template v-else>
        <p class="m-0 text-base text-slate-600">Không tải được hình ảnh sản phẩm. Vui lòng kiểm tra kết nối rồi thử lại.</p>
        <UButton type="button" size="sm" variant="outline" class="public-neutral-button" @click="retryMedia">Thử tải lại ảnh</UButton>
      </template>
    </div>

    <!-- Dải thumbnail nhỏ có chuyển ảnh mượt -->
    <div v-if="media.length > 1" class="flex gap-2 overflow-x-auto pt-3 max-tb:hidden scroll-smooth [scrollbar-width:none]" aria-label="Thư viện ảnh sản phẩm">
      <button
        v-for="(image, index) in media"
        v-show="!failedMedia.has(image)"
        :key="`${image}-${retryVersion}`"
        type="button"
        class="aspect-square flex-none basis-[72px] overflow-hidden rounded-btn border bg-white cursor-pointer transition-all duration-200 hover:opacity-100 active:scale-95"
        :class="selectedIndex === index ? 'border-navy-800 ring-2 ring-gold-500/50 shadow-xs scale-[1.02]' : 'border-gray-200 hover:border-slate-300 opacity-70'"
        :aria-label="'Xem ảnh ' + (index + 1) + ' của ' + media.length"
        :aria-pressed="selectedIndex === index"
        @mouseenter="selectMedia(index)"
        @click="selectMedia(index)"
      >
        <img :src="optimizedImage(image, 224, 224)" :alt="name + ' — ảnh ' + (index + 1)" class="h-full w-full object-contain" width="112" height="112" loading="lazy" @error="markMediaFailed(image)">
      </button>
    </div>

    <!-- Modal Lightbox xem toàn màn hình kiểu kính mờ Apple -->
    <Teleport to="body">
      <Transition name="pdp-lightbox" @after-leave="finishLightboxClose">
        <div
          v-if="lightboxOpen && selectedMediaAvailable"
          ref="lightboxPanel"
          class="fixed inset-0 z-[200] flex items-center justify-center bg-navy-950/90 backdrop-blur-md p-4"
          role="dialog"
          aria-modal="true"
          :aria-label="'Ảnh phóng to ' + name"
          @click.self="closeLightbox"
        >
          <button
            type="button"
            class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/20 active:scale-95 cursor-pointer"
            aria-label="Đóng ảnh phóng to"
            @click="closeLightbox"
          >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>

          <button
            v-if="availableIndexes.length > 1"
            type="button"
            class="absolute left-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/25 active:scale-95 cursor-pointer backdrop-blur-xs"
            aria-label="Ảnh trước"
            @click="moveLightbox(-1)"
          >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
          </button>

          <AnimatePresence :initial="false">
            <motion.div
              :key="`${selectedMedia}-${retryVersion}`"
              :initial="reducedMotion ? false : { opacity: 0, scale: 0.985 }"
              :animate="{ opacity: 1, scale: 1 }"
              :exit="{ opacity: 0, scale: 0.985 }"
              :transition="{ duration: reducedMotion ? 0 : 0.16, ease: 'easeOut' }"
              class="pointer-events-none absolute inset-0 flex items-center justify-center p-4"
            >
              <img :src="selectedMedia" :alt="name + ' — ảnh phóng to'" class="pointer-events-auto max-h-[85vh] max-w-[88vw] object-contain drop-shadow-2xl rounded-chip" @error="handleDisplayedMediaError">
            </motion.div>
          </AnimatePresence>

          <button
            v-if="availableIndexes.length > 1"
            type="button"
            class="absolute right-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/25 active:scale-95 cursor-pointer backdrop-blur-xs"
            aria-label="Ảnh tiếp theo"
            @click="moveLightbox(1)"
          >
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
          </button>

          <div class="absolute bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-xs">
            {{ displayedMediaNumber }} / {{ availableIndexes.length }}
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.pdp-studio-stage {
  background: radial-gradient(circle at 50% 42%, #ffffff 0%, #f8fafc 58%, #eef2f7 100%);
}
.pdp-studio-halo {
  position: absolute;
  width: 68%;
  aspect-ratio: 1;
  border-radius: 50%;
  background: radial-gradient(circle, rgb(224 182 84 / 18%) 0%, rgb(224 182 84 / 0%) 70%);
  pointer-events: none;
}
.pdp-zoom-image {
  filter: drop-shadow(0 22px 26px rgb(6 21 55 / 14%));
}
/* The old photo stays fully visible under the new one, so the background never shows through. */
.pdp-fade-enter-active { z-index: 1; transition: opacity 0.14s ease-out; }
.pdp-fade-enter-from { opacity: 0; }
.pdp-fade-leave-active { transition: opacity 0s linear 0.14s; }
.pdp-fade-leave-to { opacity: 0; }
.pdp-lightbox-enter-active,
.pdp-lightbox-leave-active {
  transition: opacity 0.16s ease-out;
}
.pdp-lightbox-enter-from,
.pdp-lightbox-leave-to {
  opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
  .pdp-fade-enter-active,
  .pdp-fade-leave-active,
  .pdp-lightbox-enter-active,
  .pdp-lightbox-leave-active,
  .pdp-zoom-image {
    transition-duration: 0ms;
  }
}
</style>

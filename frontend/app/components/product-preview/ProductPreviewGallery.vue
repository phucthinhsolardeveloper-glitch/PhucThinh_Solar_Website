<script setup lang="ts">
import { AnimatePresence, motion, useReducedMotion } from 'motion-v'

const props = defineProps<{ name: string; media: string[] }>()
const reducedMotion = useReducedMotion()
const selectedIndex = ref(0)
const failedMedia = ref<Set<string>>(new Set())
const lightboxOpen = ref(false)
const isZoomed = ref(false)
const zoomOrigin = ref('50% 50%')
const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)
let bodyOverflowBeforeLock: string | null = null

watch(() => props.media, () => {
  selectedIndex.value = 0
  failedMedia.value = new Set()
  lightboxOpen.value = false
  isZoomed.value = false
})

const selectedMedia = computed(() => props.media[selectedIndex.value] ?? props.media[0] ?? '')
const selectedMediaAvailable = computed(() => Boolean(selectedMedia.value) && !failedMedia.value.has(selectedMedia.value))
const availableIndexes = computed(() => props.media.map((_, index) => index).filter((index) => !failedMedia.value.has(props.media[index] ?? '')))

function selectMedia(index: number) {
  selectedIndex.value = index
}
function markMediaFailed(image: string) {
  failedMedia.value = new Set(failedMedia.value).add(image)
  if (image !== selectedMedia.value) return
  const nextIndex = props.media.findIndex((candidate) => !failedMedia.value.has(candidate))
  if (nextIndex >= 0) selectedIndex.value = nextIndex
  else lightboxOpen.value = false
}
function markDisplayedMediaFailed(event: Event) {
  const source = (event.currentTarget as HTMLImageElement).src
  const image = props.media.find((candidate) => new URL(candidate, document.baseURI).href === source)
  if (image) markMediaFailed(image)
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
  if (bodyOverflowBeforeLock !== null) document.body.style.overflow = bodyOverflowBeforeLock
  bodyOverflowBeforeLock = null
  lastTrigger.value?.focus()
}
function moveLightbox(direction: -1 | 1) {
  const indexes = availableIndexes.value
  if (indexes.length < 2) return
  const current = indexes.indexOf(selectedIndex.value)
  const next = (current + direction + indexes.length) % indexes.length
  selectedIndex.value = indexes[next] ?? selectedIndex.value
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
    if (bodyOverflowBeforeLock === null) bodyOverflowBeforeLock = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    nextTick(() => focusFirst(lightboxPanel.value))
  }
})
onMounted(() => document.addEventListener('keydown', handleKey))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKey)
  if (import.meta.client && bodyOverflowBeforeLock !== null) document.body.style.overflow = bodyOverflowBeforeLock
})
</script>

<template>
  <div>
    <!-- Khung hiển thị ảnh sản phẩm chính với tính năng Zoom rê chuột & phím chuyển ảnh -->
    <div
      v-if="selectedMediaAvailable"
      class="group relative grid aspect-square w-full place-items-center overflow-hidden rounded-chip border border-slate-200/80 bg-white shadow-xs transition-shadow duration-300 hover:shadow-md"
      @mousemove="handleMouseMove"
      @mouseleave="handleMouseLeave"
    >
      <button
        type="button"
        class="relative flex h-full w-full items-center justify-center overflow-hidden bg-white"
        :class="isZoomed ? 'cursor-crosshair' : 'cursor-zoom-in'"
        :aria-label="'Phóng to ảnh ' + name"
        @click="openLightbox($event)"
      >
        <Transition name="pdp-fade">
          <img
            :key="selectedMedia"
            :src="selectedMedia"
            :alt="name"
            class="pdp-zoom-image absolute inset-0 h-full w-full object-contain p-4 transition-transform duration-200 ease-out"
            :style="{
              transform: isZoomed ? 'scale(2.2)' : 'scale(1)',
              transformOrigin: zoomOrigin,
            }"
            width="720"
            height="720"
            @error="markDisplayedMediaFailed"
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
        class="absolute left-3 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200/90 bg-white/90 text-navy-900 opacity-0 shadow-md backdrop-blur-xs transition-all duration-200 hover:bg-white hover:scale-105 active:scale-95 group-hover:opacity-100 max-ph:opacity-90 max-ph:h-9 max-ph:w-9 cursor-pointer"
        aria-label="Ảnh trước"
        @click="prevImage"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <!-- Nút chuyển ảnh Tiếp theo (>) -->
      <button
        v-if="availableIndexes.length > 1"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 z-10 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200/90 bg-white/90 text-navy-900 opacity-0 shadow-md backdrop-blur-xs transition-all duration-200 hover:bg-white hover:scale-105 active:scale-95 group-hover:opacity-100 max-ph:opacity-90 max-ph:h-9 max-ph:w-9 cursor-pointer"
        aria-label="Ảnh tiếp theo"
        @click="nextImage"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>

      <!-- Huy hiệu hướng dẫn zoom -->
      <div
        class="pointer-events-none absolute bottom-3 left-3 z-10 flex items-center gap-1.5 rounded-full bg-navy-950/70 px-2.5 py-1 text-[11px] font-semibold text-white backdrop-blur-xs transition-opacity duration-200"
        :class="isZoomed ? 'opacity-0' : 'opacity-85 group-hover:opacity-100'"
      >
        <svg class="h-3.5 w-3.5 text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/>
        </svg>
        <span class="max-ph:hidden">Rê chuột phóng to</span>
      </div>

      <!-- Số thứ tự ảnh -->
      <div v-if="availableIndexes.length > 1" class="pointer-events-none absolute bottom-3 right-3 z-10 rounded-full bg-navy-950/70 px-2.5 py-1 text-xs font-bold text-white backdrop-blur-xs">
        {{ selectedIndex + 1 }} / {{ availableIndexes.length }}
      </div>
    </div>
    <p v-else class="text-slate-500">CMS chưa có ảnh sản phẩm hợp lệ.</p>

    <!-- Dải thumbnail nhỏ có chuyển ảnh mượt -->
    <div v-if="media.length > 1" class="flex gap-2 overflow-x-auto pt-3 scroll-smooth [scrollbar-width:none]" aria-label="Thư viện ảnh sản phẩm">
      <button
        v-for="(image, index) in media"
        v-show="!failedMedia.has(image)"
        :key="image"
        type="button"
        class="aspect-square flex-none basis-[72px] overflow-hidden rounded-chip border bg-white cursor-pointer transition-all duration-200 hover:opacity-100 active:scale-95"
        :class="selectedIndex === index ? 'border-navy-800 ring-2 ring-gold-500/50 shadow-xs scale-[1.02]' : 'border-gray-200 hover:border-slate-300 opacity-70'"
        :aria-label="'Xem ảnh ' + (index + 1) + ' của ' + media.length"
        :aria-pressed="selectedIndex === index"
        @mouseenter="selectMedia(index)"
        @click="selectMedia(index)"
      >
        <img :src="image" :alt="name + ' — ảnh ' + (index + 1)" class="h-full w-full object-contain" width="112" height="112" loading="lazy" @error="markMediaFailed(image)">
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
              :key="selectedMedia"
              :initial="reducedMotion ? false : { opacity: 0, scale: 0.985 }"
              :animate="{ opacity: 1, scale: 1 }"
              :exit="{ opacity: 0, scale: 0.985 }"
              :transition="{ duration: reducedMotion ? 0 : 0.16, ease: 'easeOut' }"
              class="pointer-events-none absolute inset-0 flex items-center justify-center p-4"
            >
              <img :src="selectedMedia" :alt="name + ' — ảnh phóng to'" class="pointer-events-auto max-h-[85vh] max-w-[88vw] object-contain drop-shadow-2xl rounded-chip" @error="markDisplayedMediaFailed">
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
            {{ selectedIndex + 1 }} / {{ availableIndexes.length }}
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.pdp-fade-enter-active,
.pdp-fade-leave-active {
  transition: opacity 0.16s ease-out;
}
.pdp-fade-enter-from {
  opacity: 0;
}
.pdp-fade-leave-to {
  opacity: 0;
}
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

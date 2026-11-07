<script setup lang="ts">
const runtime = useRuntimeConfig()
const emit = defineEmits<{ atmosphere: [value: string] }>()
const stage = ref<HTMLElement | null>(null)
const frame = ref<HTMLIFrameElement | null>(null)
const visible = ref(false)
const near = ref(false)
// The poster owns the section until the viewer has painted its first real frame.
const shouldLoad = ref(false)
const viewerReady = ref(false)
const manualStart = ref(false)
const expanded = ref(false)
let observer: IntersectionObserver | null = null
let nearObserver: IntersectionObserver | null = null
let scrollTimer: ReturnType<typeof setTimeout> | undefined
let loadTimer: ReturnType<typeof setTimeout> | undefined
let scrolling = false

const baseURL = runtime.app.baseURL.endsWith('/') ? runtime.app.baseURL : `${runtime.app.baseURL}/`
const viewerURL = `${baseURL}pts-living-energy/index.html?embed=1&v=20261107-3`
// The poster is the real first frame, drawn by this viewer with the same camera, so nothing jumps at the swap.
const isNight = ref(false)
const posterURL = (device: 'desktop' | 'phone') => `${baseURL}pts-living-energy/assets/pts-model-${device}-${isNight.value ? 'night' : 'day'}.webp`
const posterGone = ref(false)
// The page keeps the wheel and the touch until someone actually reaches for the model.
const pointerEngaged = ref(false)
const scrollingNow = ref(false)
const viewerTakesPointer = computed(() => viewerReady.value && !scrollingNow.value && pointerEngaged.value)
const touchOnly = ref(false)
// Temporary comparison for the owner's own machine: ?scroll3d=live keeps the model running during scroll.
let liveDuringScroll = false
const SCROLL_IDLE_MS = 160

function postToViewer(message: Record<string, unknown>) {
  frame.value?.contentWindow?.postMessage(message, window.location.origin)
}

function notifyViewer() {
  postToViewer({ type: 'pts-living-energy:visibility', visible: visible.value })
}

function onViewerMessage(event: MessageEvent) {
  if (event.origin !== window.location.origin || event.source !== frame.value?.contentWindow) return
  if (event.data?.type === 'pts-living-energy:ready') {
    viewerReady.value = true
    return
  }
  if (event.data?.type === 'pts-living-energy:expand') {
    setExpanded(event.data.expanded === true)
    return
  }
  if (event.data?.type !== 'pts-living-energy:atmosphere') return
  if (['day', 'dusk', 'night'].includes(event.data.atmosphere)) emit('atmosphere', event.data.atmosphere)
}

// iPhone has no element fullscreen API, so "fullscreen" there means covering the viewport.
function setExpanded(next: boolean) {
  expanded.value = next
  document.documentElement.style.overflow = next ? 'hidden' : ''
}

// Weak devices and data-saver users keep the poster and opt in; loading a WebGL scene
// for them unasked is what makes the rest of the page stutter.
function isConstrainedDevice() {
  const nav = navigator as Navigator & { deviceMemory?: number, connection?: { saveData?: boolean } }
  return nav.connection?.saveData === true
    || (nav.deviceMemory !== undefined && nav.deviceMemory <= 2)
    || (navigator.hardwareConcurrency !== undefined && navigator.hardwareConcurrency <= 2)
}

// Parsing the model shares this page's main thread, so it never starts mid-scroll.
function scheduleViewerLoad() {
  clearTimeout(loadTimer)
  if (shouldLoad.value || manualStart.value || !near.value) return
  loadTimer = setTimeout(() => {
    if (scrolling || !near.value) return
    if ('requestIdleCallback' in window) window.requestIdleCallback(() => { shouldLoad.value = true }, { timeout: 800 })
    else shouldLoad.value = true
  }, SCROLL_IDLE_MS)
}

function onWindowScroll() {
  if (!scrolling) {
    scrolling = true
    scrollingNow.value = true
    if (!touchOnly.value) pointerEngaged.value = false
    if (!liveDuringScroll) postToViewer({ type: 'pts-living-energy:scrolling', scrolling: true })
  }
  clearTimeout(scrollTimer)
  scrollTimer = setTimeout(() => {
    scrolling = false
    scrollingNow.value = false
    if (!liveDuringScroll) postToViewer({ type: 'pts-living-energy:scrolling', scrolling: false })
    scheduleViewerLoad()
  }, SCROLL_IDLE_MS)
}

function onStagePointerMove() {
  if (!scrolling) pointerEngaged.value = true
}

function onViewerFaded(event: TransitionEvent) {
  if (event.propertyName === 'opacity' && viewerReady.value) posterGone.value = true
}

onMounted(() => {
  window.addEventListener('message', onViewerMessage)
  manualStart.value = isConstrainedDevice()
  const hour = new Date().getHours()
  isNight.value = hour >= 18 || hour < 6
  touchOnly.value = window.matchMedia('(hover: none)').matches
  if (touchOnly.value) pointerEngaged.value = true
  liveDuringScroll = new URLSearchParams(window.location.search).get('scroll3d') === 'live'
  if (!('IntersectionObserver' in window)) {
    visible.value = true
    near.value = true
    scheduleViewerLoad()
    return
  }

  window.addEventListener('scroll', onWindowScroll, { passive: true })
  observer = new IntersectionObserver(([entry]) => {
    visible.value = entry?.isIntersecting === true
    notifyViewer()
    scheduleViewerLoad()
  }, { rootMargin: '200px 0px', threshold: 0 })

  // Loading starts a good way ahead of the section, while the visitor is at rest.
  nearObserver = new IntersectionObserver(([entry]) => {
    near.value = entry?.isIntersecting === true
    scheduleViewerLoad()
  }, { rootMargin: '150% 0px', threshold: 0 })

  if (stage.value) {
    observer.observe(stage.value)
    nearObserver.observe(stage.value)
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  nearObserver?.disconnect()
  clearTimeout(scrollTimer)
  clearTimeout(loadTimer)
  setExpanded(false)
  window.removeEventListener('scroll', onWindowScroll)
  window.removeEventListener('message', onViewerMessage)
})
</script>

<template>
  <div ref="stage" class="energy-scene-frame" :class="{ 'is-expanded': expanded, 'is-night': isNight }" @pointermove.passive="onStagePointerMove">
    <picture v-if="!posterGone">
      <source media="(max-width: 519px)" :srcset="posterURL('phone')" width="824" height="1688">
      <img
        class="energy-scene-poster"
        :src="posterURL('desktop')"
        alt=""
        aria-hidden="true"
        width="2400"
        height="1350"
        loading="lazy"
        decoding="async"
      >
    </picture>
    <button
      v-if="manualStart && !shouldLoad"
      type="button"
      class="energy-scene-start"
      @click="shouldLoad = true"
    >
      Xem mô hình 3D
    </button>
    <iframe
      v-if="shouldLoad"
      ref="frame"
      :class="{ 'is-ready': viewerReady, 'takes-pointer': viewerTakesPointer }"
      :src="viewerURL"
      title="Mô hình 3D tương tác về hệ thống năng lượng mặt trời"
      referrerpolicy="strict-origin-when-cross-origin"
      allow="fullscreen"
      scrolling="no"
      @load="notifyViewer"
      @transitionend="onViewerFaded"
    />
  </div>
</template>

<style scoped>
.energy-scene-frame {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  container-type: size;
  /* Same stage as the viewer draws, so only the model and its controls change at the swap. */
  border-radius: 16px;
  background: radial-gradient(75% 78% at 59% 52%, rgb(250 253 251), rgb(237 243 241) 58%, rgb(219 232 232));
}

.energy-scene-frame::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgb(69 97 115 / 2.8%) 1px, transparent 1px), linear-gradient(90deg, rgb(69 97 115 / 2.8%) 1px, transparent 1px);
  background-size: 44px 44px;
  mask-image: linear-gradient(to bottom, transparent, black 28%, black 76%, transparent);
  pointer-events: none;
}

.energy-scene-frame.is-night {
  background: radial-gradient(78% 70% at 57% 46%, rgb(29 59 89), rgb(13 36 61) 61%, rgb(6 19 38));
}

.energy-scene-frame.is-night::before {
  background-image: linear-gradient(rgb(176 199 218 / 5.5%) 1px, transparent 1px), linear-gradient(90deg, rgb(176 199 218 / 5.5%) 1px, transparent 1px);
}

.energy-scene-frame.is-expanded {
  position: fixed;
  inset: 0;
  z-index: 2147483000;
  border-radius: 0;
  height: 100dvh;
}

.energy-scene-poster {
  /* The camera scales with height, so the poster does too; the lift matches the viewer's offset (34px at 900px high). */
  position: absolute;
  top: 0;
  left: 50%;
  width: auto;
  max-width: none;
  height: 100%;
  transform: translate(-50%, calc(34px - 3.78cqh));
  pointer-events: none;
}

@media (max-width: 860px) {
  .energy-scene-poster { transform: translate(-50%, calc(56px - 3.78cqh)); }
}

@media (max-width: 519px) {
  .energy-scene-poster { transform: translate(-50%, calc(56px - 6.64cqh)); }
}

.energy-scene-start {
  position: absolute;
  bottom: 96px;
  left: 50%;
  min-height: 48px;
  padding: 0 22px;
  transform: translateX(-50%);
  border: 1px solid #d5aa52;
  border-radius: 999px;
  background: var(--pts-gold-gradient, linear-gradient(110deg, #f4d488, #d49b39));
  color: #0a2251;
  font-weight: 700;
  cursor: pointer;
}

.energy-scene-start:focus-visible {
  outline: 3px solid #0a2251;
  outline-offset: 4px;
}

iframe {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  background: transparent;
  opacity: 0;
  pointer-events: none;
  transition: opacity .45s ease;
}

iframe.takes-pointer {
  pointer-events: auto;
}

iframe.is-ready {
  opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
  iframe {
    transition: none;
  }
}
</style>

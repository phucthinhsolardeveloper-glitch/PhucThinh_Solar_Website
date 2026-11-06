<script setup lang="ts">
const runtime = useRuntimeConfig()
const emit = defineEmits<{ atmosphere: [value: string] }>()
const stage = ref<HTMLElement | null>(null)
const frame = ref<HTMLIFrameElement | null>(null)
const visible = ref(false)
// The poster owns the section until the viewer has painted its first real frame.
const shouldLoad = ref(false)
const viewerReady = ref(false)
const manualStart = ref(false)
const expanded = ref(false)
let observer: IntersectionObserver | null = null
let scrollTimer: ReturnType<typeof setTimeout> | undefined
let loadTimer: ReturnType<typeof setTimeout> | undefined
let scrolling = false

const baseURL = runtime.app.baseURL.endsWith('/') ? runtime.app.baseURL : `${runtime.app.baseURL}/`
const viewerURL = `${baseURL}pts-living-energy/index.html?embed=1&v=20261106-22`
const posterURL = `${baseURL}pts-living-energy/assets/pts-v05-day.webp`
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
  if (shouldLoad.value || manualStart.value || !visible.value) return
  loadTimer = setTimeout(() => {
    if (scrolling || !visible.value) return
    if ('requestIdleCallback' in window) window.requestIdleCallback(() => { shouldLoad.value = true }, { timeout: 800 })
    else shouldLoad.value = true
  }, SCROLL_IDLE_MS)
}

function onWindowScroll() {
  if (!scrolling) {
    scrolling = true
    postToViewer({ type: 'pts-living-energy:scrolling', scrolling: true })
  }
  clearTimeout(scrollTimer)
  scrollTimer = setTimeout(() => {
    scrolling = false
    postToViewer({ type: 'pts-living-energy:scrolling', scrolling: false })
    scheduleViewerLoad()
  }, SCROLL_IDLE_MS)
}

onMounted(() => {
  window.addEventListener('message', onViewerMessage)
  manualStart.value = isConstrainedDevice()
  if (!('IntersectionObserver' in window)) {
    visible.value = true
    scheduleViewerLoad()
    return
  }

  window.addEventListener('scroll', onWindowScroll, { passive: true })
  observer = new IntersectionObserver(([entry]) => {
    visible.value = entry?.isIntersecting === true
    notifyViewer()
    scheduleViewerLoad()
  }, { rootMargin: '200px 0px', threshold: 0 })

  if (stage.value) observer.observe(stage.value)
})

onBeforeUnmount(() => {
  observer?.disconnect()
  clearTimeout(scrollTimer)
  clearTimeout(loadTimer)
  setExpanded(false)
  window.removeEventListener('scroll', onWindowScroll)
  window.removeEventListener('message', onViewerMessage)
})
</script>

<template>
  <div ref="stage" class="energy-scene-frame" :class="{ 'is-expanded': expanded }">
    <img
      v-if="!viewerReady"
      class="energy-scene-poster"
      :src="posterURL"
      alt=""
      aria-hidden="true"
      width="1600"
      height="1000"
      loading="lazy"
      decoding="async"
    >
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
      :class="{ 'is-ready': viewerReady }"
      :src="viewerURL"
      title="Mô hình 3D tương tác về hệ thống năng lượng mặt trời"
      referrerpolicy="strict-origin-when-cross-origin"
      allow="fullscreen"
      scrolling="no"
      @load="notifyViewer"
    />
  </div>
</template>

<style scoped>
.energy-scene-frame {
  position: relative;
  width: 100%;
  height: 100%;
  overflow: hidden;
  background: #f0f5f3;
}

.energy-scene-frame.is-expanded {
  position: fixed;
  inset: 0;
  z-index: 2147483000;
  height: 100dvh;
}

.energy-scene-poster {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
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
  transition: opacity .25s ease;
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

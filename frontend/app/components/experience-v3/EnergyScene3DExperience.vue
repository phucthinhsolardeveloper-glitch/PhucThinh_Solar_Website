<script setup lang="ts">
const runtime = useRuntimeConfig()
const props = defineProps<{ atmosphere?: string }>()
const emit = defineEmits<{ atmosphere: [value: string] }>()

// The story beside the model. Wording comes from the viewer's own scenarios, which only ever describe a simulation.
const CHAPTERS = [
  { id: 'day', title: 'Ban ngày', headline: 'Điện mặt trời cấp tải', flow: 'PV → Inverter → Phụ tải', copy: 'Điện mặt trời đi qua inverter để cấp các phụ tải trong nhà.' },
  { id: 'night_storage', title: 'Đêm dùng pin', headline: 'Pin lưu trữ cấp tải', flow: 'Pin lưu trữ → Inverter → Phụ tải', copy: 'Ban đêm, nếu có pin và thiết bị phù hợp, pin lưu trữ cấp điện qua inverter cho các phụ tải.' },
  { id: 'night_grid', title: 'Đêm dùng lưới', headline: 'Điện lưới cấp tải', flow: 'Cột điện → công tơ → phân phối AC / inverter → tải', copy: 'Nguồn điện lưới từ cột đi qua cáp cấp và công tơ vào hệ thống điện AC. Tủ phân phối và inverter liên kết theo thiết kế cụ thể.' },
  { id: 'backup', title: 'Mất điện', headline: 'Chỉ tải ưu tiên còn điện', flow: 'Pin → Inverter backup → Tải ưu tiên', copy: 'Mô phỏng thiết bị có ngõ backup tương thích: lưới bị cách ly, pin cấp điện qua inverter đến tải ưu tiên, không phải toàn bộ ngôi nhà.' },
] as const
const CHAPTER_MS = 7000
const IDLE_RETURN_MS = 20000
const chapterIndex = ref(0)
const chapter = computed(() => CHAPTERS[chapterIndex.value]!)
const engaged = ref(false)
const storyStarted = ref(false)
const darkStage = computed(() => props.atmosphere === 'night')
let chapterTimer: ReturnType<typeof setInterval> | undefined
let idleTimer: ReturnType<typeof setTimeout> | undefined
let startTimer: ReturnType<typeof setTimeout> | undefined
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
const viewerURL = `${baseURL}pts-living-energy/index.html?embed=1&v=20261107-5`
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

function showChapter(index: number) {
  chapterIndex.value = (index + CHAPTERS.length) % CHAPTERS.length
  postToViewer({ type: 'pts-living-energy:chapter', id: chapter.value.id })
}

function stopChapters() {
  clearInterval(chapterTimer)
  chapterTimer = undefined
}

// Chapters run only while someone is looking and not scrolling, and never for reduced-motion visitors.
function runChapters() {
  stopChapters()
  if (!viewerReady.value || !visible.value || engaged.value || scrolling || document.hidden) return
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  chapterTimer = setInterval(() => showChapter(chapterIndex.value + 1), CHAPTER_MS)
}

// The first move comes a moment after arrival: the overview settles, then the camera glides into the first chapter.
function beginStory() {
  if (storyStarted.value || !viewerReady.value || !visible.value) return
  storyStarted.value = true
  startTimer = setTimeout(() => {
    showChapter(chapterIndex.value)
    runChapters()
  }, 900)
}

function chooseChapter(index: number) {
  showChapter(index)
  runChapters()
}

function setEngaged(next: boolean) {
  if (engaged.value === next) return
  engaged.value = next
  postToViewer({ type: 'pts-living-energy:intro', on: !next })
  if (next) {
    stopChapters()
    return
  }
  showChapter(chapterIndex.value)
  runChapters()
}

function noteInteraction() {
  setEngaged(true)
  clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    if (!expanded.value) setEngaged(false)
  }, IDLE_RETURN_MS)
}

function backToStory() {
  clearTimeout(idleTimer)
  setEngaged(false)
}

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
    beginStory()
    return
  }
  if (event.data?.type === 'pts-living-energy:interaction') {
    noteInteraction()
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
    stopChapters()
  }
  clearTimeout(scrollTimer)
  scrollTimer = setTimeout(() => {
    scrolling = false
    scrollingNow.value = false
    if (!liveDuringScroll) postToViewer({ type: 'pts-living-energy:scrolling', scrolling: false })
    scheduleViewerLoad()
    if (storyStarted.value) runChapters()
    else beginStory()
  }, SCROLL_IDLE_MS)
}

function onStoryVisibility() {
  if (document.hidden) stopChapters()
  else if (storyStarted.value) runChapters()
}

function onStagePointerMove() {
  if (!scrolling) pointerEngaged.value = true
}

function onViewerFaded(event: TransitionEvent) {
  if (event.propertyName === 'opacity' && viewerReady.value) posterGone.value = true
}

// Phones, data saver and slow links fetch the model (≈2 MB) only when the visitor is about to reach it, so it does not
// compete with the first screen; wide screens on good links keep loading it a good way ahead.
function nearMargin(): string {
  const link = (navigator as Navigator & { connection?: { saveData?: boolean; effectiveType?: string } }).connection
  if (link?.saveData || /(?:^|-)(?:2g|3g)$/.test(link?.effectiveType ?? '')) return '10% 0px'
  return window.matchMedia('(max-width: 820px)').matches ? '35% 0px' : '150% 0px'
}

onMounted(() => {
  window.addEventListener('message', onViewerMessage)
  manualStart.value = isConstrainedDevice()
  const hour = new Date().getHours()
  isNight.value = hour >= 18 || hour < 6
  // After dark the story opens on the battery, which matches the night poster.
  if (isNight.value) chapterIndex.value = 1
  document.addEventListener('visibilitychange', onStoryVisibility)
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
    if (visible.value) {
      if (storyStarted.value) runChapters()
      else beginStory()
    } else stopChapters()
  }, { rootMargin: '200px 0px', threshold: 0 })

  // Loading starts a good way ahead of the section, while the visitor is at rest.
  nearObserver = new IntersectionObserver(([entry]) => {
    near.value = entry?.isIntersecting === true
    scheduleViewerLoad()
  }, { rootMargin: nearMargin(), threshold: 0 })

  if (stage.value) {
    observer.observe(stage.value)
    nearObserver.observe(stage.value)
  }
})

onBeforeUnmount(() => {
  observer?.disconnect()
  nearObserver?.disconnect()
  stopChapters()
  clearTimeout(idleTimer)
  clearTimeout(startTimer)
  document.removeEventListener('visibilitychange', onStoryVisibility)
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
    <!-- The story sits beside the model and steps aside as soon as someone starts using it. -->
    <div class="stage-story" :class="{ 'is-away': engaged, 'is-dark': darkStage }">
      <h2 id="energy-showcase-title" class="stage-story__title">Ngôi nhà đón nắng. <span>Năng lượng thành cuộc sống.</span></h2>
      <div class="stage-story__body">
        <div :key="chapter.id" class="stage-story__chapter">
          <strong>{{ chapter.headline }}</strong>
          <p class="stage-story__flow" data-no-justify>{{ chapter.flow }}</p>
          <p class="stage-story__copy" data-no-justify>{{ chapter.copy }}</p>
        </div>
        <div v-show="viewerReady" class="stage-story__rail" role="group" aria-label="Chọn tình huống năng lượng">
          <button
            v-for="(item, index) in CHAPTERS"
            :key="item.id"
            type="button"
            class="stage-story__tab"
            :aria-current="index === chapterIndex ? 'true' : undefined"
            @click="chooseChapter(index)"
          >{{ item.title }}</button>
        </div>
        <div class="stage-story__foot">
          <button v-if="viewerReady" type="button" class="stage-story__explore" @click="noteInteraction">
            <UIcon name="i-lucide-rotate-3d" class="size-[18px]" aria-hidden="true" />Tự xoay mô hình
          </button>
        </div>
      </div>
    </div>
    <button v-if="engaged && !expanded" type="button" class="stage-back" @click="backToStory">
      <UIcon name="i-lucide-arrow-left" class="size-4" aria-hidden="true" />Xem câu chuyện
    </button>

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
  transform: translate(calc(-50% + 15cqw), calc(34px - 3.78cqh));
  pointer-events: none;
}

@media (max-width: 860px) {
  .energy-scene-poster { transform: translate(-50%, calc(56px - 3.78cqh + 4cqh)); }
}

@media (max-width: 519px) {
  .energy-scene-poster { transform: translate(-50%, calc(56px - 6.64cqh + 4cqh)); }
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

.stage-story { --ink: var(--color-navy-950); --soft: #44546c; --accent: #a8741a; position: absolute; z-index: 3; top: 0; bottom: 0; left: max(34px, calc((100% - var(--container-page, 1372px)) / 2)); display: flex; width: min(31%, 470px); flex-direction: column; justify-content: center; gap: 22px; color: var(--ink); pointer-events: none; transition: opacity .35s ease, transform .35s ease, visibility .35s; }
.stage-story.is-dark { --ink: #fff; --soft: #c9d5e5; --accent: var(--color-gold-300); }
.stage-story.is-away { visibility: hidden; opacity: 0; transform: translateX(-16px); }
.stage-story__title { margin: 0; color: var(--ink); font-size: clamp(32px, 3.1vw, 54px); font-weight: 850; line-height: 1.1; letter-spacing: -.03em; transition: color .5s ease; }
.stage-story__title span { display: block; background-image: linear-gradient(105deg, #c9962e 0%, #a97c1f 55%, #8a6518 100%); -webkit-background-clip: text; background-clip: text; color: transparent; -webkit-text-fill-color: transparent; }
.stage-story.is-dark .stage-story__title span { background-image: linear-gradient(105deg, #f6dc8e 0%, #e0b654 48%, #c9962e 100%); }
.stage-story__body { display: grid; gap: 16px; }
.stage-story__chapter { display: grid; min-height: 132px; align-content: start; gap: 6px; animation: story-in .5s cubic-bezier(.22, 1, .36, 1) both; }
.stage-story__chapter strong { color: var(--ink); font-size: clamp(18px, 1.5vw, 23px); font-weight: 800; transition: color .5s ease; }
.stage-story__flow { margin: 0; color: var(--accent); font-size: 15px; font-weight: 700; line-height: 1.45; transition: color .5s ease; }
.stage-story__copy { margin: 0; color: var(--soft); font-size: 15.5px; line-height: 1.6; transition: color .5s ease; }
.stage-story__rail { display: flex; gap: 18px; overflow-x: auto; border-bottom: 1px solid color-mix(in srgb, var(--ink) 16%, transparent); pointer-events: auto; scrollbar-width: none; }
.stage-story__tab { position: relative; display: flex; min-height: 44px; flex: none; align-items: center; color: var(--soft); font-size: 14.5px; font-weight: 700; white-space: nowrap; cursor: pointer; transition: color .25s ease; }
.stage-story__tab::after { content: ""; position: absolute; inset: auto 0 -1px; height: 2px; background: var(--accent); transform: scaleX(0); transform-origin: left; transition: transform .35s cubic-bezier(.22, 1, .36, 1); }
.stage-story__tab:hover { color: var(--ink); }
.stage-story__tab[aria-current] { color: var(--accent); }
.stage-story__tab[aria-current]::after { transform: scaleX(1); }
.stage-story__rail::-webkit-scrollbar { display: none; }
.stage-story__foot { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; }
.stage-story__explore { display: inline-flex; min-height: 44px; align-items: center; gap: 8px; color: var(--ink); font-size: 14px; font-weight: 700; pointer-events: auto; cursor: pointer; transition: color .25s ease; }
.stage-story__explore :deep(svg), .stage-story__explore > span { color: var(--accent); }
.stage-story__explore:hover { color: var(--accent); }
.stage-back { position: absolute; z-index: 4; top: 16px; left: 16px; display: inline-flex; min-height: 44px; align-items: center; gap: 8px; padding: 0 14px; border: 1px solid rgb(255 255 255 / 80%); border-radius: var(--radius-btn); background: rgb(255 255 255 / 88%); box-shadow: 0 6px 18px rgb(6 21 55 / 16%); color: var(--color-navy-950); font-size: 14px; font-weight: 700; cursor: pointer; }
@keyframes story-in { from { opacity: 0; transform: translateY(8px); } }
@media (max-width: 860px) {
  .stage-story { inset: 16px 16px 84px; width: auto; justify-content: flex-start; gap: 10px; }
  .stage-story__title { font-size: clamp(26px, 7.4vw, 36px); }
  .stage-story__chapter { min-height: 124px; gap: 2px; }
  .stage-story__chapter strong { font-size: 17px; }
  .stage-story__flow { font-size: 14px; }
  .stage-story__copy { font-size: 14.5px; line-height: 1.5; }
  /* Text stays with the title and the controls go under the model, which keeps the middle clear. */
  .stage-story__body { display: contents; }
  .stage-story__rail { margin: auto -16px 0; padding-inline: 16px; }
  .stage-story__rail { gap: 14px; }
}
@media (prefers-reduced-motion: reduce) {
  .stage-story, .stage-story__chapter { animation: none; transition: none; }
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

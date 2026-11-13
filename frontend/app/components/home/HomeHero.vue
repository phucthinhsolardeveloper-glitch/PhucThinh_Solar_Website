<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ hero: PublicHomePayload['hero'] }>()
const active = ref(0)
const current = computed(() => props.hero[active.value] || null)
const failedImages = ref(new Set<string>())
const heroImageUnavailable = computed(() => {
  const image = current.value?.image?.trim() ?? ''
  return !image || failedImages.value.has(image)
})
const links = usePublicLinks()
const reducedMotion = ref(false)
let autoplayTimer: number | undefined
let touchStartX: number | null = null
let pointerInside = false
let focusInside = false
let motionPreference: MediaQueryList | undefined

const phpResizableHeroImage = /^\/(?:upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)|assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png))$/i

function heroImageCandidate(src: string, width: 960 | 1600): string {
  if (!phpResizableHeroImage.test(src)) return src
  const height = Math.round(width * 720 / 1600)
  return thumbUrl(src, width, height)
}

function heroImageSrcSet(src: string): string | undefined {
  if (!phpResizableHeroImage.test(src)) return undefined
  return `${heroImageCandidate(src, 960)} 960w, ${heroImageCandidate(src, 1600)} 1600w`
}

function showSlide(index: number) {
  if (!props.hero.length) return
  active.value = (index + props.hero.length) % props.hero.length
}

function markImageFailed(event: Event) {
  const image = (event.currentTarget as HTMLImageElement | null)?.getAttribute('src') ?? current.value?.image ?? ''
  const source = image.trim()
  if (!source) return
  failedImages.value = new Set(failedImages.value).add(source)
}

function stopAutoplay() {
  if (autoplayTimer !== undefined) window.clearInterval(autoplayTimer)
  autoplayTimer = undefined
}

function startAutoplay() {
  stopAutoplay()
  if (props.hero.length > 1 && !reducedMotion.value && !document.hidden && !pointerInside && !focusInside && touchStartX === null) {
    autoplayTimer = window.setInterval(() => showSlide(active.value + 1), 4200)
  }
}

// Hover only pauses on a device with a real mouse. Scrolling can report a mouse entering on touch screens.
function onPointerEnter(event: PointerEvent) {
  if (event.pointerType === 'touch' || !window.matchMedia('(hover: hover)').matches) return
  pointerInside = true
  stopAutoplay()
}

function onPointerLeave(event: PointerEvent) {
  if (event.pointerType === 'touch') return
  pointerInside = false
  startAutoplay()
}

// Only keyboard focus holds the slide. A tap also focuses what it touched, and that must not stop the banner for good.
function onFocusIn(event: FocusEvent) {
  if (!(event.target as Element | null)?.matches(':focus-visible')) return
  focusInside = true
  stopAutoplay()
}

function onFocusOut(event: FocusEvent) {
  if ((event.currentTarget as HTMLElement).contains(event.relatedTarget as Node | null)) return
  focusInside = false
  startAutoplay()
}

function onMotionPreferenceChange(event: MediaQueryListEvent) {
  reducedMotion.value = event.matches
  startAutoplay()
}

function move(step: number) {
  showSlide(active.value + step)
  startAutoplay()
}

function onTouchStart(event: TouchEvent) {
  touchStartX = event.touches[0]?.clientX ?? null
  stopAutoplay()
}

function onTouchEnd(event: TouchEvent) {
  const endX = event.changedTouches[0]?.clientX
  if (touchStartX !== null && endX !== undefined && Math.abs(endX - touchStartX) > 45) {
    showSlide(active.value + (endX < touchStartX ? 1 : -1))
  }
  touchStartX = null
  startAutoplay()
}

// The browser cancels the touch when it turns into a page scroll, and no touchend follows.
function onTouchCancel() {
  touchStartX = null
  startAutoplay()
}

function onVisibilityChange() {
  if (document.hidden) stopAutoplay()
  else startAutoplay()
}

watch(() => props.hero, () => {
  active.value = 0
  startAutoplay()
})

onMounted(() => {
  motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)')
  reducedMotion.value = motionPreference.matches
  motionPreference.addEventListener('change', onMotionPreferenceChange)
  document.addEventListener('visibilitychange', onVisibilityChange)
  startAutoplay()
})

onBeforeUnmount(() => {
  stopAutoplay()
  motionPreference?.removeEventListener('change', onMotionPreferenceChange)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>
<template>
  <section
    v-if="current"
    class="relative isolate z-0 -mt-16 flex min-h-screen min-h-[100svh] min-[821px]:-mt-[88px] min-[1321px]:-mt-[104px] min-[1681px]:-mt-[120px] min-[1800px]:-mt-[136px] max-[640px]:-mt-16 overflow-hidden text-navy-950"
    :class="heroImageUnavailable ? 'bg-slate-50' : 'bg-navy-950'"
    aria-labelledby="home-title"
    role="region"
    aria-roledescription="carousel"
    @pointerenter="onPointerEnter"
    @pointerleave="onPointerLeave"
    @focusin="onFocusIn"
    @focusout="onFocusOut"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
    @touchcancel.passive="onTouchCancel"
  >
    <!-- Background cinematic slides -->
    <div class="absolute inset-0 z-0 h-full w-full">
      <div
        v-for="(slide, index) in hero"
        :key="`${slide.key}-${index}`"
        :data-home-hero-slide="index"
        class="absolute inset-0 transition-opacity duration-[900ms] ease-[cubic-bezier(0.4,0,0.2,1)] motion-reduce:transition-none"
        :class="index === active ? 'opacity-100 z-[1] pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
        :aria-hidden="index !== active"
        :inert="index !== active"
      >
        <a class="absolute inset-0 block focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-white" :href="links.generic(slide.href)" :tabindex="index === active ? 0 : -1">
          <img
            v-if="slide.image && !failedImages.has(slide.image.trim())"
            class="hero-slide-image absolute inset-0 h-full w-full object-cover max-[640px]:object-[62%_center] motion-reduce:!animate-none"
            :class="index === active ? 'is-active' : ''"
            :style="{ '--hero-pan-x': index % 2 === 0 ? '-1.4%' : '1.4%' }"
            :src="heroImageCandidate(slide.image, 1600)"
            :srcset="heroImageSrcSet(slide.image)"
            sizes="100vw"
            :alt="slide.name"
            :fetchpriority="index === 0 ? 'high' : 'low'"
            :loading="index === 0 ? 'eager' : 'lazy'"
            decoding="async"
            width="1600"
            height="720"
            @error="markImageFailed"
          >
          <!-- Cinematic layered lighting overlay -->
          <span
            v-if="slide.image && !failedImages.has(slide.image.trim())"
            class="hero-wash absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,.68)_0%,rgba(255,255,255,.45)_36%,rgba(255,255,255,.12)_62%,transparent_82%),linear-gradient(180deg,rgba(6,21,55,.12)_0%,transparent_30%,rgba(6,21,55,.24)_100%)] max-[640px]:bg-[linear-gradient(180deg,rgba(255,255,255,.75)_0%,rgba(255,255,255,.50)_45%,rgba(6,21,55,.32)_100%)]"
            aria-hidden="true"
          />
        </a>
      </div>
      <a v-if="current" :href="links.generic(current.href)" class="hidden" aria-hidden="true" />
    </div>

    <!-- Centered editorial copy sits in the upper-left field of the image. -->
    <div class="hero-copy-layer relative z-[4] pts-container mx-auto flex min-h-[inherit] flex-col items-start justify-start px-0 pb-0 pt-[clamp(132px,22vh,190px)] max-[640px]:items-center max-[640px]:px-4 max-[640px]:pt-[clamp(76px,12vh,108px)]">
      <div class="hero-copy ml-[clamp(64px,6.2vw,112px)] flex w-full max-w-[820px] flex-col items-center text-center max-[1100px]:max-w-[760px] max-[640px]:ml-0 max-[640px]:max-w-[min(100%,520px)]">
        <h1 id="home-title" class="m-0 w-full text-center text-[clamp(42px,4.3vw,62px)] min-[1800px]:text-[clamp(56px,3.4vw,72px)] font-black uppercase leading-[1.1] tracking-[-.035em] text-navy-950 [text-shadow:0_2px_14px_rgba(255,255,255,.7)] max-[640px]:text-[clamp(29px,8.8vw,38px)] max-[640px]:leading-[1.08]">
          Thiết bị và giải pháp <br><span class="hero-accent">năng lượng mặt trời</span>
        </h1>

        <p class="mx-auto mb-[28px] mt-[22px] w-full max-w-[760px] text-center text-[clamp(16px,1.3vw,19px)] font-medium leading-[1.65] text-navy-900 [text-shadow:0_1px_8px_rgba(255,255,255,.75)] max-[640px]:mb-[22px] max-[640px]:mt-[16px] max-[640px]:text-[15.5px]">
          Phúc Thịnh Solar cung cấp thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng; tư vấn phương án phù hợp cho gia đình, doanh nghiệp và công trình.
        </p>

        <div class="flex w-full flex-wrap items-center justify-center gap-[16px]">
          <UButton
            href="#danh-muc-thiet-bi"
            color="primary"
            size="xl"
            class="min-h-[52px] rounded-btn border border-gold-500 bg-gold-400 px-[32px] text-[15.5px] font-black leading-[1.2] text-navy-950 no-underline shadow-sm transition-all duration-200 hover:bg-gold-500 hover:shadow-md active:scale-[0.98] cursor-pointer max-[640px]:min-h-[48px] max-[640px]:px-[22px] max-[640px]:text-sm"
          >
            Xem sản phẩm
            <span class="hero-cta-arrow" aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-[18px]" /></span>
          </UButton>
          <a
            href="#yeu-cau-tu-van"
            class="hero-cta-second m-lg inline-flex min-h-[52px] items-center justify-center gap-2 rounded-btn border border-slate-300/90 bg-white/90 px-[32px] text-[15.5px] font-extrabold leading-[1.2] text-navy-950 no-underline shadow-sm transition-all duration-200 hover:bg-white hover:shadow-md active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500 max-[640px]:min-h-[48px] max-[640px]:px-[22px] max-[640px]:text-sm"
          >
            Tư vấn lắp đặt <span class="hero-cta-second__arrow" aria-hidden="true">→</span>
          </a>
        </div>
      </div>
    </div>

    <div class="hero-caption absolute bottom-[clamp(112px,14vh,132px)] right-[clamp(24px,4vw,96px)] z-[2] flex max-w-[min(500px,calc(100vw-48px))] flex-col gap-1 text-right text-navy-950 [text-shadow:0_1px_6px_rgba(255,255,255,.98),0_2px_16px_rgba(255,255,255,.86)] max-[640px]:bottom-[92px] max-[640px]:right-4 max-[640px]:max-w-[calc(100vw-32px)]">
      <!-- Phones: one bar per photo, filling for as long as it shows. Tap a bar to jump to it. -->
      <div v-if="hero.length > 1" class="hero-steps">
        <button
          v-for="(slide, index) in hero"
          :key="slide.key"
          type="button"
          :aria-label="`Xem ảnh ${index + 1}: ${slide.name}`"
          :aria-current="index === active ? 'true' : undefined"
          @click="showSlide(index); startAutoplay()"
        ><span :key="index === active ? `on-${active}` : 'off'" :class="{ 'is-on': index === active, 'is-done': index < active }" /></button>
      </div>
      <strong class="text-[clamp(19px,1.7vw,26px)] font-extrabold leading-[1.2] max-[640px]:text-[16px]">{{ current.name }}</strong>
      <span class="text-[clamp(13px,1vw,15px)] font-semibold leading-[1.5] max-[640px]:text-[12px]">{{ current.description }}</span>
    </div>

    <!-- Solar Cinema Controller (Bottom-Left, clear of fixed contact actions) -->
    <div v-if="hero.length > 1" class="hero-nav m-lg-dark absolute bottom-[30px] left-[clamp(24px,4vw,96px)] z-[6] flex items-center gap-[2px] rounded-full bg-navy-950/55 p-[3px] text-white max-[640px]:bottom-[16px] max-[640px]:left-4">
      <button class="flex size-10 items-center justify-center rounded-full border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner trước" @click="move(-1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
      </button>
      <span class="flex h-10 min-w-[64px] items-center justify-center px-2 text-xs">
        <b class="mr-[4px] text-[15px] font-black text-gold-400">{{ String(active + 1).padStart(2, '0') }}</b> / {{ String(hero.length).padStart(2, '0') }}
      </span>
      <button class="flex size-10 items-center justify-center rounded-full border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner tiếp theo" @click="move(1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
      </button>
    </div>

  </section>
</template>

<style scoped>
.hero-cta-arrow, .hero-steps { display: none; }
@keyframes hero-rise { from { opacity: 0; transform: translateY(12px); } }
/* The title is the page's largest text (LCP): it slides in but is visible from the first frame, since an element at
   opacity 0 only counts as painted once the fade ends (measured 3.7 s LCP on a 4x-throttled phone). */
@keyframes hero-rise-visible { from { transform: translateY(12px); } }
@keyframes hero-step { to { transform: scaleX(1); } }
@media (prefers-reduced-motion: reduce) {
  .hero-copy h1, .hero-copy p, .hero-copy > div { animation: none !important; }
  .hero-steps span.is-on::after { animation: none; transform: scaleX(1); }
}
/* Slow continuous Ken Burns drift on the active slide: zoom + a gentle pan that
   alternates direction per slide so the sequence doesn't feel mechanical. */
.hero-slide-image {
  transform: scale(1.06) translate3d(0, 0, 0);
}
.hero-slide-image.is-active {
  animation: hero-ken-burns 4.6s cubic-bezier(0.25, 0.1, 0.25, 1) forwards;
}
@keyframes hero-ken-burns {
  from { transform: scale(1.06) translate3d(0, 0, 0); }
  to { transform: scale(1.12) translate3d(var(--hero-pan-x, -1.4%), -1%, 0); }
}
@media (max-width: 640px) {
  .hero-copy-layer { justify-content: flex-end !important; padding: 0 0 92px !important; }
  .hero-copy-layer::before { content: ''; position: absolute; inset: auto 0 0; z-index: -1; height: 72%; background: linear-gradient(180deg, transparent, rgb(6 21 55 / 78%) 42%, var(--color-navy-950)); pointer-events: none; }
  .hero-copy { align-items: flex-start !important; padding-inline: 16px; text-align: left !important; }
  .hero-copy h1 { color: #fff !important; font-size: 34px !important; line-height: 1.12 !important; text-align: left !important; text-transform: none !important; text-shadow: none !important; }
  .hero-copy h1 br { display: none; }
  .hero-copy p { margin: 10px 0 16px !important; color: #dbe4f0 !important; font-size: 15px !important; line-height: 1.5 !important; text-align: left !important; text-shadow: none !important; }
  .hero-copy > div { justify-content: flex-start !important; gap: 8px !important; }
}

@media (prefers-reduced-motion: reduce) {
  .hero-slide-image, .hero-slide-image.is-active {
    animation: none;
    transform: scale(1);
  }
}

/* Phones: the fixed dock covers the bottom band, so slide controls and caption sit above it,
   clear of the copy. Controls are 44px glass over the navy foot of the photo. */
@media (max-width: 820px) {
  .hero-nav { bottom: calc(88px + env(safe-area-inset-bottom, 0px)); }
  .hero-nav button { width: 44px; height: 44px; }
  .hero-nav > span { min-width: 56px; height: 44px; }
}
/* 641-820px keeps the desktop light wash on the left, where white-on-glass would wash out. */
@media (min-width: 641px) and (max-width: 820px) {
  .hero-nav { background: rgb(6 21 55 / 62%) !important; }
}
@media (max-width: 640px) {
  /* The photo keeps its colour at the top and melts into navy only behind the copy. */
  .hero-wash { background: linear-gradient(180deg, rgb(6 21 55 / 6%) 0%, rgb(6 21 55 / 0%) 34%, rgb(6 21 55 / 46%) 50%, var(--color-navy-950) 76%) !important; }
  .hero-copy-layer { padding-bottom: calc(236px + env(safe-area-inset-bottom, 0px)) !important; }
  .hero-copy-layer::before { display: none; }
  .hero-copy h1 { font-size: 33px !important; font-weight: 800 !important; letter-spacing: -.025em !important; filter: drop-shadow(0 2px 10px rgb(6 21 55 / 45%)); animation: hero-rise-visible .7s cubic-bezier(.22, 1, .36, 1) both; }
  .hero-accent { display: block; background-image: linear-gradient(105deg, #f6dc8e 0%, #e0b654 48%, #c9962e 100%); -webkit-background-clip: text; background-clip: text; color: transparent; -webkit-text-fill-color: transparent; }
  .hero-copy p { color: #e4eaf4 !important; animation: hero-rise .7s .08s cubic-bezier(.22, 1, .36, 1) both; }
  .hero-copy p.text-justified { text-align: justify !important; }
  .hero-copy > div { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); width: 100%; animation: hero-rise .7s .16s cubic-bezier(.22, 1, .36, 1) both; }
  .hero-copy > div > * { min-width: 0; padding-inline: 8px; }
  .hero-copy > div > :first-child { justify-content: space-between; padding: 0 7px 0 18px; }
  .hero-cta-arrow { display: grid; width: 38px; height: 38px; flex: none; place-items: center; border-radius: var(--radius-btn); background: var(--color-navy-950); color: var(--color-gold-300); }
  .hero-cta-second { justify-content: center; border-color: rgb(255 255 255 / 38%) !important; background: rgb(255 255 255 / 12%) !important; box-shadow: inset 0 1px 0 rgb(255 255 255 / 22%) !important; color: #fff !important; -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); }
  .hero-cta-second__arrow { display: none; }
  /* The slide's story sits on one glass card with its progress bars, clear of the dock. */
  .hero-nav { display: none !important; }
  .hero-caption { z-index: 5; bottom: calc(92px + env(safe-area-inset-bottom, 0px)); left: 16px; right: 16px; max-width: none; padding: 2px 14px 12px; border: 1px solid rgb(255 255 255 / 16%); border-radius: var(--radius-md); background: rgb(255 255 255 / 9%); color: #fff; text-align: left; text-shadow: none; -webkit-backdrop-filter: blur(14px) saturate(130%); backdrop-filter: blur(14px) saturate(130%); }
  .hero-caption > strong { font-size: 14.5px; line-height: 1.25; }
  .hero-caption > span { color: #dbe4f0; font-size: 12.5px; line-height: 1.4; text-wrap: pretty; }
  .hero-steps { display: flex; gap: 6px; }
  .hero-steps button { display: flex; min-height: 28px; flex: 1; align-items: center; padding: 0; border: 0; background: none; cursor: pointer; }
  .hero-steps span { position: relative; display: block; width: 100%; height: 3px; overflow: hidden; border-radius: 3px; background: rgb(255 255 255 / 28%); }
  .hero-steps span::after { content: ''; position: absolute; inset: 0; border-radius: inherit; background: var(--color-gold-400); transform: scaleX(0); transform-origin: left; }
  .hero-steps span.is-done::after { transform: scaleX(1); }
  .hero-steps span.is-on::after { animation: hero-step 4.2s linear forwards; }
}
</style>

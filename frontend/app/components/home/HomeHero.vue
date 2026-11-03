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
const links = useMigrationLinks()
const reducedMotion = ref(false)
let autoplayTimer: number | undefined
let touchStartX: number | null = null
let pointerInside = false
let focusInside = false
let motionPreference: MediaQueryList | undefined

const phpResizableHeroImage = /^\/(?:upload\/photo|assets\/images\/site-visuals)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)$/i

function heroImageCandidate(src: string, width: 960 | 1600): string {
  if (!phpResizableHeroImage.test(src)) return src
  const height = Math.round(width * 720 / 1600)
  return `/thumbs/${width}x${height}x1${src}`
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
  if (props.hero.length > 1 && !document.hidden && !pointerInside && !focusInside && touchStartX === null) {
    autoplayTimer = window.setInterval(() => showSlide(active.value + 1), 4200)
  }
}

function onPointerEnter(event: PointerEvent) {
  if (event.pointerType === 'touch') return
  pointerInside = true
  stopAutoplay()
}

function onPointerLeave(event: PointerEvent) {
  if (event.pointerType === 'touch') return
  pointerInside = false
  startAutoplay()
}

function onFocusIn() {
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
    class="relative isolate flex min-h-[clamp(580px,40vw,700px)] min-[1800px]:min-h-[max(700px,calc(100vh-174px-clamp(12px,1.2vw,24px)))] min-[1800px]:max-[2299px]:min-h-[max(700px,calc(100vh-clamp(148px,8vw,164px)-clamp(12px,1.2vw,24px)))] max-[640px]:min-h-[720px] overflow-hidden rounded-b-[6px] text-navy-950 mb-[clamp(16px,1.8vw,32px)] shadow-xs"
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
  >
    <!-- Background cinematic slides -->
    <div class="absolute inset-0 z-0 h-full w-full">
      <div
        v-for="(slide, index) in hero"
        :key="`${slide.key}-${index}`"
        :data-home-hero-slide="index"
        class="absolute inset-0 transition-opacity duration-700 ease-in-out motion-reduce:transition-none"
        :class="index === active ? 'opacity-100 z-[1] pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
        :aria-hidden="index !== active"
        :inert="index !== active"
      >
        <a class="absolute inset-0 block focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-white" :href="links.generic(slide.href)" :tabindex="index === active ? 0 : -1">
          <img
            v-if="slide.image && !failedImages.has(slide.image.trim())"
            class="absolute inset-0 h-full w-full object-cover max-[640px]:object-[62%_center] transition-transform duration-1000 ease-out"
            :class="index === active ? 'scale-100' : 'scale-105'"
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
            class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,.68)_0%,rgba(255,255,255,.45)_36%,rgba(255,255,255,.12)_62%,transparent_82%),linear-gradient(180deg,rgba(6,21,55,.12)_0%,transparent_30%,rgba(6,21,55,.24)_100%)] max-[640px]:bg-[linear-gradient(180deg,rgba(255,255,255,.75)_0%,rgba(255,255,255,.50)_45%,rgba(6,21,55,.32)_100%)]"
            aria-hidden="true"
          />
        </a>
      </div>
      <a v-if="current" :href="links.generic(current.href)" class="hidden" aria-hidden="true" />
    </div>

    <!-- Editorial Stage Content: asymmetric, left-anchored, no decorative badge -->
    <div class="relative z-[4] pts-container mx-auto flex min-h-[inherit] flex-col justify-end px-0 pb-[clamp(56px,8vh,96px)] pt-[clamp(76px,12vh,120px)] min-[1800px]:pt-[clamp(88px,13vh,136px)] max-[640px]:min-h-[720px] max-[640px]:justify-end max-[640px]:pb-[150px] max-[640px]:pt-[54px]">
      <div class="flex w-full max-w-[760px] flex-col items-start text-left">
        <h1 id="home-title" class="m-0 w-full text-left text-[clamp(40px,4.4vw,72px)] min-[1800px]:text-[clamp(52px,4vw,86px)] font-black uppercase leading-[.98] tracking-[-.035em] text-navy-950 [text-shadow:0_2px_14px_rgba(255,255,255,.7)] max-[640px]:text-[clamp(32px,10vw,42px)] max-[640px]:leading-[1.04]">
          Thiết bị &amp; giải pháp năng lượng mặt trời
        </h1>

        <p class="mx-0 mb-[30px] mt-[22px] w-full max-w-[560px] text-left text-[clamp(16px,1.12vw,19px)] font-medium leading-[1.68] text-navy-900 [text-shadow:0_1px_8px_rgba(255,255,255,.65)] max-[640px]:mb-[22px] max-[640px]:mt-[14px] max-[640px]:text-[15.5px]">
          Phúc Thịnh Solar cung cấp thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng; tư vấn phương án phù hợp cho gia đình, doanh nghiệp và công trình.
        </p>

        <!-- CTAs: asymmetric, lead action first -->
        <div class="flex w-full max-w-[560px] flex-wrap items-center gap-[18px]">
          <UButton
            href="#danh-muc-thiet-bi"
            color="primary"
            size="xl"
            class="min-h-[52px] rounded-[4px] border border-gold-500 bg-gold-400 px-[32px] text-[15.5px] font-black leading-[1.2] text-navy-950 no-underline shadow-sm transition-all duration-200 hover:bg-gold-500 hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98] cursor-pointer max-[640px]:min-h-[48px] max-[640px]:px-[22px] max-[640px]:text-sm"
          >
            Xem sản phẩm
          </UButton>
          <a
            href="#yeu-cau-tu-van"
            class="inline-flex items-center gap-2 border-b-2 border-navy-950/70 pb-1 text-[15.5px] font-extrabold leading-[1.2] text-navy-950 no-underline transition-colors hover:border-gold-500 hover:text-gold-700 [text-shadow:0_1px_8px_rgba(255,255,255,.65)]"
          >
            Tư vấn lắp đặt <span aria-hidden="true">→</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Solar Cinema Controller (Bottom-Right) -->
    <div v-if="hero.length > 1" class="absolute bottom-[30px] right-[clamp(24px,4vw,96px)] z-[6] flex items-center gap-[4px] rounded-[6px] border border-white/20 bg-navy-950/70 p-[5px] text-white backdrop-blur-[14px] shadow-xl min-[2300px]:right-[max(24px,calc((100vw-2200px)/2))] max-[640px]:bottom-[16px] max-[640px]:right-4">
      <button class="flex size-11 items-center justify-center rounded-[4px] border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner trước" @click="move(-1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
      </button>
      <span class="flex h-11 min-w-[70px] items-center justify-center border-x border-white/15 px-2.5 text-xs text-white/85">
        <b class="mr-[4px] text-[15px] font-black text-gold-400">{{ String(active + 1).padStart(2, '0') }}</b> / {{ String(hero.length).padStart(2, '0') }}
      </span>
      <button class="flex size-11 items-center justify-center rounded-[4px] border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner tiếp theo" @click="move(1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
      </button>
    </div>

    <!-- Active Slide Title Card (Bottom-Right overlay) -->
    <p class="absolute bottom-[clamp(120px,15vh,146px)] right-[clamp(24px,4vw,96px)] z-[2] m-0 flex max-w-[min(500px,calc(100vw-48px))] flex-col gap-1 rounded-[4px] border border-white/25 bg-white/30 backdrop-blur-md px-[20px] pb-[14px] pt-[15px] text-right text-navy-950 shadow-sm [text-shadow:0_1px_6px_rgba(255,255,255,.8)] transition-opacity duration-500 min-[2300px]:right-[max(24px,calc((100vw-2200px)/2))] max-[640px]:bottom-[92px] max-[640px]:right-4 max-[640px]:max-w-[calc(100vw-36px)] max-[640px]:px-[14px] max-[640px]:py-[10px]">
      <strong class="text-[clamp(20px,1.6vw,28px)] font-bold leading-[1.2] max-[640px]:text-[clamp(15px,4.5vw,20px)] max-[640px]:leading-snug line-clamp-2">{{ current.name }}</strong>
      <span class="text-sm font-medium leading-[1.5] text-navy-950 max-[640px]:hidden">{{ current.description }}</span>
    </p>
  </section>
</template>

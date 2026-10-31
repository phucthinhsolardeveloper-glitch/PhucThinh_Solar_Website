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
  if (props.hero.length > 1 && !document.hidden && !reducedMotion.value && !pointerInside && !focusInside && touchStartX === null) {
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
    class="relative isolate flex min-h-[clamp(570px,39vw,670px)] min-[1800px]:min-h-[max(670px,calc(100vh-174px-clamp(12px,1.2vw,24px)))] min-[1800px]:max-[2299px]:min-h-[max(670px,calc(100vh-clamp(148px,8vw,164px)-clamp(12px,1.2vw,24px)))] max-[640px]:min-h-[700px] overflow-hidden rounded-b-[4px] text-navy-950 mb-[clamp(12px,1.2vw,24px)]"
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
    <div class="absolute inset-0 z-0 h-full w-full">
      <div
        v-for="(slide, index) in hero"
        :key="`${slide.key}-${index}`"
        class="absolute inset-0 transition-opacity duration-700 ease-in-out motion-reduce:transition-none"
        :class="index === active ? 'opacity-100 z-[1] pointer-events-auto' : 'opacity-0 z-0 pointer-events-none'"
        :aria-hidden="index !== active"
        :inert="index !== active"
      >
        <a class="absolute inset-0 block focus-visible:outline-2 focus-visible:outline-offset-[-4px] focus-visible:outline-white" :href="links.generic(slide.href)" :tabindex="index === active ? 0 : -1">
          <img
            v-if="slide.image && !failedImages.has(slide.image.trim())"
            class="absolute inset-0 h-full w-full object-cover max-[640px]:object-[62%_center]"
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
          <span v-if="slide.image && !failedImages.has(slide.image.trim())" class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,.58),rgba(255,255,255,.30)_32%,rgba(255,255,255,.04)_58%,transparent_76%),linear-gradient(0deg,rgba(6,21,55,.16),rgba(6,21,55,.03)_58%,transparent)] max-[640px]:bg-[linear-gradient(90deg,rgba(255,255,255,.57),rgba(255,255,255,.31)_42%,rgba(255,255,255,.03)_75%,transparent),linear-gradient(0deg,rgba(6,21,55,.18),rgba(6,21,55,.04)_62%,transparent)]" aria-hidden="true" />
        </a>
      </div>
      <a v-if="current" :href="links.generic(current.href)" class="hidden" aria-hidden="true" />
    </div>
    <div class="relative z-[4] mx-auto flex min-h-[inherit] w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)] max-w-[820px] flex-col items-start justify-center px-0 py-16 text-center [transform:translateY(calc(-70px_-_clamp(0px,calc(25vw_-_400px),240px)))] min-[1800px]:[transform:translateY(calc(-10px_-_clamp(0px,calc(25vw_-_400px),240px)))] max-[640px]:min-h-[700px] max-[640px]:items-center max-[640px]:justify-start max-[640px]:pb-[150px] max-[640px]:pt-[58px] max-[640px]:[transform:translateY(-14px)]">
      <p class="mb-[17px] w-full max-w-[850px] whitespace-nowrap text-center text-[clamp(22px,1.7vw,30px)] min-[1501px]:text-[clamp(22px,1.7vw,30px)] font-black uppercase tracking-[.02em] text-navy-950 [text-shadow:0_1px_9px_rgba(255,255,255,.52)] max-[640px]:text-[12px] max-[640px]:tracking-[.04em]">Phân phối thiết bị · Tư vấn · Thi công lắp đặt</p>
      <h1 id="home-title" aria-label="Thiết bị và giải pháp năng lượng mặt trời" class="m-0 w-full max-w-[920px] text-center text-[clamp(46px,3.8vw,66px)] min-[1800px]:text-[72px] font-black uppercase leading-[1.08] tracking-[-.03em] text-navy-950 [text-shadow:0_2px_12px_rgba(255,255,255,.62)] max-[640px]:text-[clamp(28px,7.5vw,36px)]">
        <span class="block">Thiết bị và giải pháp</span>
        <span class="block text-[.82em]">năng lượng mặt trời</span>
      </h1>
      <p class="mx-0 mb-[34px] mt-[22px] w-full max-w-[820px] text-center text-[clamp(16px,1.15vw,20px)] font-medium leading-[1.68] text-navy-950 [text-shadow:0_1px_9px_rgba(255,255,255,.55)] max-[640px]:text-base">Phúc Thịnh Solar cung cấp thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng; tư vấn phương án phù hợp cho gia đình, doanh nghiệp và công trình.</p>
      <div class="flex w-full max-w-[820px] flex-wrap justify-center gap-[16px]">
        <UButton href="#danh-muc-thiet-bi" color="primary" size="xl" class="min-h-[52px] rounded-[4px] border border-hdr-gold bg-hdr-gold px-[30px] text-[15.5px] font-extrabold leading-[1.2] text-navy-950 no-underline shadow-xs transition-all duration-150 hover:bg-hdr-gold-hover hover:shadow-md active:scale-[0.98] cursor-pointer max-[640px]:min-h-[48px] max-[640px]:px-[20px] max-[640px]:text-sm">
          Xem sản phẩm
        </UButton>
        <UButton href="#yeu-cau-tu-van" color="secondary" variant="outline" size="xl" class="min-h-[52px] rounded-[4px] border border-navy-950/40 bg-white/75 px-[30px] text-[15.5px] font-extrabold leading-[1.2] text-navy-950 no-underline backdrop-blur-xs shadow-xs transition-all duration-150 hover:bg-white hover:border-navy-950 hover:shadow-md active:scale-[0.98] cursor-pointer max-[640px]:min-h-[48px] max-[640px]:px-[20px] max-[640px]:text-sm">
          Tư vấn lắp đặt
        </UButton>
      </div>
    </div>
    <div v-if="hero.length > 1" class="absolute bottom-[38px] right-[max(24px,calc((100vw-1560px)/2))] z-[6] flex items-center gap-[4px] rounded-[6px] border border-white/20 bg-navy-950/40 p-[5px] text-white backdrop-blur-[12px] shadow-lg max-[640px]:bottom-[22px] max-[640px]:right-5">
      <button class="flex size-11 items-center justify-center rounded-[4px] border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner trước" @click="move(-1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
      </button>
      <span class="flex h-11 min-w-[66px] items-center justify-center border-x border-white/15 px-2 text-xs text-white/80"><b class="mr-[3px] text-[15px] font-bold text-white">{{ String(active + 1).padStart(2, '0') }}</b> / {{ String(hero.length).padStart(2, '0') }}</span>
      <button class="flex size-11 items-center justify-center rounded-[4px] border border-transparent bg-transparent text-white transition-all duration-150 motion-reduce:transition-none hover:bg-white/20 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white active:scale-90 motion-reduce:active:scale-100 cursor-pointer" type="button" aria-label="Banner tiếp theo" @click="move(1)">
        <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
      </button>
    </div>
    <p class="absolute bottom-[102px] right-[max(24px,calc((100vw-1560px)/2))] z-[2] m-0 flex max-w-[min(500px,calc(100vw-48px))] flex-col gap-1 border-b border-white/20 bg-white/10 backdrop-blur-xs px-[18px] pb-[13px] pt-[14px] text-right text-navy-950 [text-shadow:0_2px_9px_rgba(255,255,255,.7)] transition-opacity duration-500 max-[640px]:bottom-[83px] max-[640px]:right-5 max-[640px]:max-w-[calc(100vw-40px)] max-[640px]:px-[14px] max-[640px]:py-[11px]">
      <strong class="text-[clamp(22px,1.7vw,30px)] font-bold leading-[1.18] max-[640px]:text-[clamp(16px,4.8vw,21px)] max-[640px]:leading-snug line-clamp-2">{{ current.name }}</strong>
      <span class="text-sm font-medium leading-[1.55] text-navy-950 max-[640px]:hidden">{{ current.description }}</span>
    </p>
  </section>
</template>

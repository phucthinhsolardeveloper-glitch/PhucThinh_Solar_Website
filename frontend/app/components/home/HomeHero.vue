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
let touchStartX: number | null = null

function showSlide(index: number) {
  if (!props.hero.length) return
  active.value = (index + props.hero.length) % props.hero.length
}

function move(step: number) {
  showSlide(active.value + step)
}

function markImageFailed() {
  const image = current.value?.image?.trim()
  if (!image) return
  failedImages.value = new Set(failedImages.value).add(image)
}

function onTouchStart(event: TouchEvent) {
  touchStartX = event.touches[0]?.clientX ?? null
}

function onTouchEnd(event: TouchEvent) {
  const endX = event.changedTouches[0]?.clientX
  if (touchStartX !== null && endX !== undefined && Math.abs(endX - touchStartX) > 45) {
    showSlide(active.value + (endX < touchStartX ? 1 : -1))
  }
  touchStartX = null
}

watch(() => props.hero, () => {
  active.value = 0
})
</script>
<template>
  <section v-if="current" class="relative isolate flex min-h-[clamp(570px,39vw,670px)] overflow-hidden rounded-b-[4px] text-navy-950 mb-[clamp(12px,1.2vw,24px)] max-[640px]:min-h-[700px]" :class="heroImageUnavailable ? 'bg-slate-50' : 'bg-navy-950'" role="region" aria-roledescription="carousel" aria-labelledby="home-title" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
    <div class="absolute inset-0 z-0 h-full w-full">
      <a class="absolute inset-0 block" :href="links.generic(current.href)">
        <img class="absolute inset-0 h-full w-full object-cover max-[640px]:object-[62%_center]" :src="current.image" :alt="current.name" fetchpriority="high" width="1600" height="720" @error="markImageFailed">
        <span v-if="!heroImageUnavailable" class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,.55),rgba(255,255,255,.27)_30%,rgba(255,255,255,.03)_55%,transparent_74%),linear-gradient(0deg,rgba(6,21,55,.16),rgba(6,21,55,.03)_58%,transparent)] max-[640px]:bg-[linear-gradient(90deg,rgba(255,255,255,.57),rgba(255,255,255,.31)_42%,rgba(255,255,255,.03)_75%,transparent),linear-gradient(0deg,rgba(6,21,55,.18),rgba(6,21,55,.04)_62%,transparent)]" aria-hidden="true" />
      </a>
    </div>
    <div :class="heroImageUnavailable ? 'home-hero-copy--fallback' : 'home-hero-copy--image'" class="relative z-[4] mx-auto flex min-h-[inherit] w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)] max-w-none flex-col items-start justify-center px-0 pb-[92px] pt-16 text-left [transform:translateY(calc(-70px-clamp(0px,calc(25vw-400px),240px)))] min-[1800px]:[transform:translateY(calc(-10px-clamp(0px,calc(25vw-400px),240px)))] max-[640px]:min-h-[700px] max-[640px]:items-center max-[640px]:justify-start max-[640px]:pb-[150px] max-[640px]:pt-[58px] max-[640px]:text-center max-[640px]:[transform:translateY(-14px)]">
      <p class="mb-[17px] w-full max-w-[820px] whitespace-nowrap text-center text-[clamp(22px,1.7vw,30px)] font-extrabold uppercase tracking-[.02em] text-navy-950 [text-shadow:0_1px_9px_rgba(255,255,255,.52)] max-[640px]:text-[11px]">Phân phối thiết bị · Tư vấn · Thi công lắp đặt</p>
      <h1 id="home-title" aria-label="Thiết bị và giải pháp năng lượng mặt trời" class="m-0 w-full max-w-[820px] text-center text-[clamp(50px,3vw,59px)] font-extrabold uppercase leading-[1.1] tracking-[-.025em] text-navy-950 [text-shadow:0_2px_10px_rgba(255,255,255,.58)] max-[640px]:text-[25px]">
        <span class="block">Thiết bị và giải pháp</span>
        <span class="block text-[.78em]">năng lượng mặt trời</span>
      </h1>
      <p class="mx-0 mb-[30px] mt-[22px] w-full max-w-[820px] text-center text-[clamp(17px,1.1vw,19px)] font-normal leading-[1.65] text-navy-950 [text-shadow:0_1px_9px_rgba(255,255,255,.52)] max-[640px]:text-base">Phúc Thịnh Solar cung cấp thiết bị chiếu sáng, điện mặt trời và lưu trữ năng lượng; tư vấn phương án phù hợp cho gia đình, doanh nghiệp và công trình.</p>
      <div class="flex w-full max-w-[820px] flex-wrap justify-center gap-[14px]">
        <UButton href="#danh-muc-thiet-bi" color="primary" size="xl" class="min-h-[50px] rounded-[4px] border border-hdr-gold bg-hdr-gold px-[25px] font-extrabold leading-[1.2] text-navy-950 no-underline hover:bg-hdr-gold-hover max-[640px]:min-h-[50px] max-[640px]:px-[18px] max-[640px]:text-sm">
          Xem sản phẩm
        </UButton>
        <UButton href="#yeu-cau-tu-van" color="secondary" variant="outline" size="xl" class="min-h-[50px] rounded-[4px] border border-navy-950 bg-white/55 px-[25px] font-extrabold leading-[1.2] text-navy-950 no-underline hover:bg-white/80 max-[640px]:min-h-[50px] max-[640px]:px-[18px] max-[640px]:text-sm">
          Tư vấn lắp đặt
        </UButton>
      </div>
    </div>
    <div v-if="hero.length > 1" class="absolute bottom-[38px] right-[max(24px,calc((100vw-1560px)/2))] z-[6] flex items-center gap-[3px] rounded-[6px] border border-white/20 bg-navy-950/20 p-[5px] text-white backdrop-blur-[8px] max-[640px]:bottom-[22px] max-[640px]:right-5">
      <UButton color="neutral" variant="ghost" square aria-label="Banner trước" class="h-11 w-11 min-w-11 rounded-[4px] p-0 text-xl text-white hover:bg-white/15 hover:text-white" @click="move(-1)">←</UButton>
      <span class="flex h-11 min-w-[66px] items-center justify-center border-x border-white/10 px-2 text-xs text-white/70" aria-live="polite" aria-atomic="true"><b class="mr-[3px] text-[15px] text-white">{{ String(active + 1).padStart(2, '0') }}</b> / {{ String(hero.length).padStart(2, '0') }}</span>
      <UButton color="neutral" variant="ghost" square aria-label="Banner tiếp theo" class="h-11 w-11 min-w-11 rounded-[4px] p-0 text-xl text-white hover:bg-white/15 hover:text-white" @click="move(1)">→</UButton>
    </div>
    <p class="absolute bottom-[102px] right-[max(24px,calc((100vw-1560px)/2))] z-[2] m-0 flex max-w-[min(500px,calc(100vw-48px))] flex-col gap-1 border-b border-white/20 bg-white/5 px-[18px] pb-[13px] pt-[14px] text-right text-navy-950 [text-shadow:0_2px_9px_rgba(255,255,255,.7)] max-[640px]:bottom-[83px] max-[640px]:right-5 max-[640px]:max-w-[calc(100vw-40px)] max-[640px]:px-[14px] max-[640px]:py-[11px]">
      <strong class="text-[clamp(22px,1.7vw,30px)] leading-[1.18] max-[640px]:text-[clamp(16px,4.8vw,21px)] max-[640px]:whitespace-nowrap">{{ current.name }}</strong>
      <span class="text-sm leading-[1.55] text-navy-950 max-[640px]:hidden">{{ current.description }}</span>
    </p>
  </section>
</template>

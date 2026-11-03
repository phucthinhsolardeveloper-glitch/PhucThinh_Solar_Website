<script setup lang="ts">
definePageMeta({ layout: false })

const { site } = await usePublicSite()
const stageRef = ref<HTMLElement | null>(null)
const videoRef = ref<HTMLVideoElement | null>(null)
const isPlaying = ref(false)
const visible = ref(false)
const reducedMotion = ref(false)
const mobile = ref(false)
const userChoice = ref<boolean | null>(null)

let stageObserver: IntersectionObserver | null = null
let motionQuery: MediaQueryList | null = null
let mobileQuery: MediaQueryList | null = null

useSeoMeta({
  title: 'Solar Cinema — bản thử nghiệm Phúc Thịnh Solar',
  description: 'Bản xem trước video minh họa giải pháp năng lượng mặt trời.',
  robots: 'noindex, nofollow, noarchive',
})

function reconcilePlayback() {
  const player = videoRef.value
  if (!player) return
  const automatic = !reducedMotion.value && !mobile.value
  const shouldPlay = visible.value && !document.hidden && (userChoice.value ?? automatic)
  if (!shouldPlay) {
    player.pause()
    return
  }
  void player.play().catch(() => { isPlaying.value = false })
}

function togglePlayback() {
  userChoice.value = !isPlaying.value
  reconcilePlayback()
}

function onMotionChange(event: MediaQueryListEvent) {
  reducedMotion.value = event.matches
  reconcilePlayback()
}

function onMobileChange(event: MediaQueryListEvent) {
  mobile.value = event.matches
  reconcilePlayback()
}

onMounted(() => {
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  mobileQuery = window.matchMedia('(max-width: 640px)')
  reducedMotion.value = motionQuery.matches
  mobile.value = mobileQuery.matches
  motionQuery.addEventListener('change', onMotionChange)
  mobileQuery.addEventListener('change', onMobileChange)
  stageObserver = new IntersectionObserver((entries) => {
    visible.value = entries[0]?.isIntersecting ?? false
    reconcilePlayback()
  }, { threshold: 0.25 })
  if (stageRef.value) stageObserver.observe(stageRef.value)
  document.addEventListener('visibilitychange', reconcilePlayback)
})

onBeforeUnmount(() => {
  videoRef.value?.pause()
  stageObserver?.disconnect()
  motionQuery?.removeEventListener('change', onMotionChange)
  mobileQuery?.removeEventListener('change', onMobileChange)
  document.removeEventListener('visibilitychange', reconcilePlayback)
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <div class="pts-container pt-4">
      <SiteBreadcrumb :items="[{ label: 'Solar Cinema — preview' }]" />
    </div>

    <main>
      <section class="pts-container grid items-center gap-[clamp(28px,4vw,72px)] py-[clamp(35px,6vw,85px)] min-[1040px]:grid-cols-[minmax(0,.85fr)_minmax(0,1.15fr)]" aria-labelledby="film-heading">
        <div class="max-w-[620px]">
          <h1 id="film-heading" class="m-0 text-[clamp(36px,4vw,62px)] font-extrabold leading-[1.12] tracking-[-.035em] text-navy-950">
            Giải pháp năng lượng mặt trời
          </h1>
          <p class="mt-6 max-w-[55ch] text-[clamp(16px,1.4vw,19px)] leading-[1.75] text-slate-600">
            Từ tấm pin quang điện đến thiết bị biến tần và lưu trữ, mỗi thành phần có một vai trò trong cấu hình hệ thống phù hợp với nhu cầu sử dụng.
          </p>
          <div class="mt-8 flex flex-wrap items-center gap-4">
            <NuxtLink to="/giai-phap/" class="inline-flex min-h-11 items-center rounded-[4px] bg-gold-500 px-6 py-3 font-bold text-navy-950 no-underline transition hover:bg-gold-400">
              Khám phá giải pháp <span class="ml-2" aria-hidden="true">→</span>
            </NuxtLink>
            <NuxtLink to="/san-pham/" class="inline-flex min-h-11 items-center font-semibold text-navy-800 underline-offset-4 hover:underline">
              Xem danh mục thiết bị
            </NuxtLink>
          </div>
        </div>

        <figure ref="stageRef" class="m-0 min-w-0">
          <div class="relative aspect-video overflow-hidden rounded-[4px] bg-navy-950">
            <video
              ref="videoRef"
              class="absolute inset-0 block h-full w-full object-cover"
              poster="/assets/video/pts-solar-cinema-poster.webp"
              muted
              playsinline
              loop
              preload="none"
              aria-hidden="true"
              @play="isPlaying = true"
              @pause="isPlaying = false"
            >
              <source src="/assets/video/pts-solar-cinema-preview.mp4" type="video/mp4">
            </video>
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#061537]/45 via-transparent to-transparent" />
            <span class="film-edge absolute bottom-0 left-0 right-0 z-10 h-[2px]" :class="{ 'film-edge-active': isPlaying }" aria-hidden="true" />
            <button
              type="button"
              class="absolute bottom-4 right-4 z-20 inline-flex min-h-11 items-center gap-2 rounded-[4px] bg-[#061537]/85 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#061537] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-400"
              :aria-label="isPlaying ? 'Tạm dừng video minh họa' : 'Phát video minh họa'"
              @click="togglePlayback"
            >
              <span aria-hidden="true">{{ isPlaying ? 'Ⅱ' : '▶' }}</span>
              {{ isPlaying ? 'Tạm dừng' : 'Phát video' }}
            </button>
          </div>
          <figcaption class="mt-3 text-[13px] leading-[1.5] text-slate-500">
            Video trình diễn chuyển động từ ảnh minh họa. Không phải tư liệu công trình đã thi công.
          </figcaption>
        </figure>
      </section>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

<style scoped>
.film-edge {
  opacity: 0;
  background: linear-gradient(90deg, transparent 0%, #C9962E 48%, #E0B654 52%, transparent 100%);
  background-size: 180% 100%;
  transform: translateX(-30%);
}
.film-edge-active {
  opacity: .75;
  animation: film-energy-trace 5s linear infinite;
}
@keyframes film-energy-trace {
  from { background-position: 180% 0; }
  to { background-position: -180% 0; }
}
@media (prefers-reduced-motion: reduce) {
  .film-edge-active { animation: none; opacity: 0; }
}
</style>

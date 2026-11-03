<script setup lang="ts">
import type { PublicHomeAboutPoint, PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ about: PublicHomePayload['about'] }>()
const links = useMigrationLinks()
const phpResizablePointImage = /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i

const fallbackPoints: PublicHomeAboutPoint[] = [
  { label: 'Sản phẩm chính hãng', description: 'Thông tin thương hiệu, thông số kỹ thuật và chính sách bảo hành rõ ràng.', image: '' },
  { label: 'Tư vấn và thi công', description: 'Đề xuất thiết bị và phương án lắp đặt phù hợp với điều kiện thực tế của từng công trình.', image: '' },
  { label: 'Hỗ trợ sau bàn giao', description: 'Hướng dẫn sử dụng, tiếp nhận yêu cầu bảo hành và hỗ trợ kỹ thuật trong quá trình vận hành.', image: '' },
  { label: 'Hệ thống kho ba khu vực', description: 'Kho tại TP.HCM, Đà Nẵng và Gia Lai, hỗ trợ giao hàng toàn quốc.', image: '' },
]
const points = computed(() => (props.about.points?.length ? props.about.points : fallbackPoints).filter((point) => point.label))

function pointImage(src: string): string {
  return phpResizablePointImage.test(src) ? `/thumbs/48x48x1${src}` : src
}

// Gentle parallax: the illustration drifts slower than the page while the band is on screen.
const bandRef = ref<HTMLElement | null>(null)
const shift = ref(0)
let ticking = false
function measure() {
  ticking = false
  const band = bandRef.value
  if (!band) return
  const rect = band.getBoundingClientRect()
  const progress = (window.innerHeight - rect.top) / (window.innerHeight + rect.height)
  shift.value = Math.max(0, Math.min(1, progress))
}
function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(measure)
}
onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  measure()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <section
    v-if="!about.configured || about.visible"
    ref="bandRef"
    class="origin relative isolate overflow-hidden bg-navy-950 text-white"
    aria-labelledby="home-about-title"
    :style="{ '--shift': shift }"
  >
    <img
      class="origin-image"
      src="/assets/images/site-visuals/about-energy-landscape-illustration-20261102.webp"
      alt=""
      width="1672"
      height="941"
      loading="lazy"
      decoding="async"
    >
    <span class="origin-veil" aria-hidden="true" />

    <div class="pts-container relative flex min-h-[clamp(560px,52vw,780px)] flex-col justify-between gap-12 py-[clamp(64px,7vw,112px)]">
      <div class="max-w-[640px]">
        <h2 id="home-about-title" class="m-0 text-[clamp(32px,3.6vw,60px)] font-black leading-[1.04] tracking-[-.035em] text-white [text-shadow:0_2px_24px_rgba(6,21,55,.45)]">
          {{ about.title }}
        </h2>
        <p class="mt-5 max-w-[52ch] text-[clamp(16px,1.15vw,19px)] leading-[1.7] text-white/90 [text-shadow:0_1px_12px_rgba(6,21,55,.5)]">
          {{ about.description }}
        </p>
        <a class="mt-8 inline-flex min-h-[52px] items-center gap-2 rounded-[4px] bg-white px-6 text-[15px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400" :href="links.generic(about.href)">
          {{ about.action }} <span aria-hidden="true">→</span>
        </a>
      </div>

      <ul class="m-0 grid list-none gap-x-[clamp(20px,2.4vw,40px)] gap-y-7 p-0 min-[641px]:grid-cols-2 min-[1101px]:grid-cols-4">
        <li v-for="point in points" :key="point.label" class="origin-point">
          <div class="flex items-start gap-3">
            <img v-if="point.image" :src="pointImage(point.image)" alt="" class="mt-0.5 size-9 flex-none object-contain" width="48" height="48" loading="lazy" decoding="async">
            <div>
              <strong class="block text-[clamp(16px,1.15vw,19px)] font-extrabold leading-[1.3]">{{ point.label }}</strong>
              <span v-if="point.description" class="mt-2 block text-[14.5px] leading-[1.6] text-white/80">{{ point.description }}</span>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
/* clip, not hidden: the oversized parallax image must not make the band a programmatic scroll container. */
.origin { overflow: clip; }
.origin-image {
  position: absolute;
  inset: -12% 0;
  z-index: -2;
  width: 100%;
  height: 124%;
  object-fit: cover;
  object-position: 30% center;
  transform: translate3d(0, calc((var(--shift, 0.5) - 0.5) * -10%), 0) scale(1.04);
  will-change: transform;
}
/* Sunrise stays visible top-left; copy sits on a navy wash, the point strip on a darker foot. */
.origin-veil {
  position: absolute;
  inset: 0;
  z-index: -1;
  background:
    linear-gradient(90deg, rgb(6 21 55 / 82%) 0%, rgb(6 21 55 / 48%) 42%, rgb(6 21 55 / 8%) 72%),
    linear-gradient(180deg, transparent 45%, rgb(6 21 55 / 92%) 100%);
}
.origin-point {
  padding-top: 18px;
  border-top: 2px solid rgb(224 182 84 / 85%);
}
@media (max-width: 640px) {
  .origin-image { object-position: 18% center; }
  .origin-veil { background: linear-gradient(180deg, rgb(6 21 55 / 72%) 0%, rgb(6 21 55 / 55%) 40%, rgb(6 21 55 / 94%) 100%); }
}
@media (prefers-reduced-motion: reduce) {
  .origin-image { transform: none; inset: 0; height: 100%; }
}
</style>

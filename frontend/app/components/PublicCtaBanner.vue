<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { publicPhoneHref } from '#shared/contact-links'
import { sentenceCase } from '~/utils/sentenceCase'

export interface CtaTrustItem { label: string; image?: string; icon?: string }
export interface CtaVideo { src: string; poster: string; posterSmall?: string }

const props = withDefaults(defineProps<{
  variant: 'corporate' | 'editorial' | 'catalog'
  title: string
  description?: string
  image?: string
  titleId: string
  trust?: CtaTrustItem[]
  hotline?: string
  hotlineLabel?: string
  zalo?: string
  buttonLabel?: string
  subject?: string
  fallbackHref?: string
  video?: CtaVideo | null
}>(), { video: null, description: '', image: '', trust: () => [], hotline: '', hotlineLabel: 'Gọi hotline', zalo: '', buttonLabel: 'Nhận tư vấn', subject: '', fallbackHref: '/lien-he/#yeu-cau-tu-van' })

const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()

const fallbackImages = {
  corporate: '/assets/images/site-visuals/cta-corporate-bg.webp',
  editorial: '/assets/images/site-visuals/cta-editorial-bg.webp',
  catalog: '/assets/images/site-visuals/cta-catalog-bg.webp',
} as const

const fallbackImage = computed(() => fallbackImages[props.variant])
const imageSource = ref('')
const imageFailed = ref(false)

watch(() => [props.image, props.variant], () => {
  imageSource.value = props.image || fallbackImage.value
  imageFailed.value = false
}, { immediate: true })

function handleImageError() {
  if (imageSource.value !== fallbackImage.value) {
    imageSource.value = fallbackImage.value
    return
  }
  imageFailed.value = true
}

// Ambient footage only plays on wide screens with motion allowed and data saving off;
// everywhere else the poster (the clip's first frame) stands in.
const videoEl = ref<HTMLVideoElement | null>(null)
const playVideo = ref(false)
const videoFading = ref(false)
let observer: IntersectionObserver | null = null
let wideQuery: MediaQueryList | null = null
let motionQuery: MediaQueryList | null = null

function videoAllowed() {
  const saveData = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection?.saveData
  return !!props.video && !!wideQuery?.matches && !motionQuery?.matches && !saveData
}
function syncVideo() {
  playVideo.value = videoAllowed()
  if (!playVideo.value) videoEl.value?.pause()
}
function handleVisibility([entry]: IntersectionObserverEntry[]) {
  const el = videoEl.value
  if (!el || !playVideo.value) return
  if (entry?.isIntersecting) el.play().catch(() => { playVideo.value = false })
  else el.pause()
}
function handleTimeUpdate() {
  const el = videoEl.value
  if (el && el.duration && el.duration - el.currentTime < 0.9) videoFading.value = true
}
function handleEnded() {
  const el = videoEl.value
  if (!el) return
  el.currentTime = 0
  el.play().catch(() => {})
  videoFading.value = false
}
function attachObserver(el: HTMLVideoElement | null) {
  observer?.disconnect()
  if (!el) return
  observer = new IntersectionObserver(handleVisibility, { rootMargin: '200px 0px' })
  observer.observe(el)
}
watch(videoEl, attachObserver)
onMounted(() => {
  if (!props.video) return
  wideQuery = window.matchMedia('(min-width: 901px)')
  motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
  wideQuery.addEventListener('change', syncVideo)
  motionQuery.addEventListener('change', syncVideo)
  syncVideo()
})
onBeforeUnmount(() => {
  observer?.disconnect()
  wideQuery?.removeEventListener('change', syncVideo)
  motionQuery?.removeEventListener('change', syncVideo)
})

const heading = computed(() => sentenceCase(props.title))
const action = computed(() => sentenceCase(props.buttonLabel) || 'Nhận tư vấn')
const phoneHref = computed(() => props.hotline ? publicPhoneHref(props.hotline) : '')
</script>

<template>
  <section class="cta-band" :class="`cta-band--${variant}`" :aria-labelledby="titleId">
    <template v-if="video">
      <picture>
        <source v-if="video.posterSmall" media="(max-width: 900px)" :srcset="video.posterSmall">
        <img class="cta-band__image" :src="video.poster" alt="" loading="lazy" decoding="async">
      </picture>
      <video
        v-if="playVideo"
        ref="videoEl"
        class="cta-band__image cta-band__video"
        :class="{ 'is-fading': videoFading }"
        :src="video.src"
        muted
        playsinline
        preload="none"
        disablepictureinpicture
        disableremoteplayback
        aria-hidden="true"
        tabindex="-1"
        @timeupdate="handleTimeUpdate"
        @ended="handleEnded"
      />
    </template>
    <img v-else-if="!imageFailed" class="cta-band__image" :src="imageSource" alt="" loading="lazy" decoding="async" @error="handleImageError">
    <span class="cta-band__shade" aria-hidden="true" />
    <span class="cta-band__sun" aria-hidden="true" />

    <div class="cta-band__copy">
      <h2 :id="titleId">{{ heading }}</h2>
      <p v-if="description">{{ description }}</p>
      <ul v-if="trust.length" class="cta-band__trust">
        <li v-for="item in trust" :key="item.label">
          <img v-if="item.image" :src="item.image" alt="" width="20" height="20" loading="lazy">
          <UIcon v-else :name="item.icon || 'i-lucide-shield-check'" class="size-5 flex-none" aria-hidden="true" />
          {{ item.label }}
        </li>
      </ul>
    </div>

    <div class="cta-band__actions">
      <a class="cta-band__primary" :href="links.generic(fallbackHref)" @click.prevent="openConsultation(subject || heading)">
        {{ action }}
        <span class="cta-band__primary-arrow" aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
      </a>
      <div v-if="phoneHref || zalo" class="cta-band__channels">
        <a v-if="phoneHref" class="cta-band__channel" :href="phoneHref">
          <span class="cta-band__channel-icon is-phone" aria-hidden="true"><UIcon name="i-lucide-phone-call" class="size-5" /></span>
          <span><small>{{ hotlineLabel }}</small><strong>{{ hotline }}</strong></span>
        </a>
        <a v-if="zalo" class="cta-band__channel" :href="zalo" target="_blank" rel="noopener noreferrer">
          <span class="cta-band__channel-icon is-zalo" aria-hidden="true">Zalo</span>
          <span><small>Nhắn tin</small><strong>Chat Zalo</strong></span>
        </a>
      </div>
    </div>
  </section>
</template>

<style scoped>
.cta-band {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  position: relative;
  isolation: isolate;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
  align-items: center;
  gap: clamp(28px, 5vw, 80px);
  overflow: hidden;
  padding: clamp(36px, 4.5vw, 64px) clamp(28px, 4.5vw, 64px);
  border-radius: var(--radius-md);
  background: #061537;
  color: #fff;
}
.cta-band__image, .cta-band__shade { position: absolute; z-index: -3; }
.cta-band__image { inset: 0 0 0 auto; width: 62%; height: 100%; object-fit: cover; -webkit-mask-image: linear-gradient(90deg, transparent, #000 40%); mask-image: linear-gradient(90deg, transparent, #000 40%); transition: transform 1.4s var(--ease); }
.cta-band:hover .cta-band__image { transform: scale(1.04); }
.cta-band picture { position: absolute; inset: 0; z-index: -3; }
.cta-band__video { object-position: 60% 55%; transition: transform 1.4s var(--ease), opacity 0.85s var(--ease); }
.cta-band__video.is-fading { opacity: 0; }
.cta-band__shade { inset: 0; z-index: -2; background: linear-gradient(90deg, #061537 34%, rgb(6 21 55 / 82%) 52%, rgb(6 21 55 / 45%) 78%, rgb(6 21 55 / 62%) 100%); }
/* Solar arc: the brand's sun rising out of the band's lower edge. */
.cta-band__sun { position: absolute; z-index: -1; left: -8%; bottom: -62%; width: clamp(320px, 42vw, 620px); aspect-ratio: 1; border: 1px solid rgb(228 168 73 / 35%); border-radius: 50%; background: radial-gradient(closest-side, rgb(228 168 73 / 26%), rgb(228 168 73 / 6%) 60%, transparent 72%); box-shadow: 0 0 0 28px rgb(228 168 73 / 4%), 0 0 0 64px rgb(228 168 73 / 3%); pointer-events: none; }

.cta-band__copy { min-width: 0; max-width: 680px; }
.cta-band__copy h2 { margin: 0; color: #fff; font-size: clamp(26px, 2.6vw, 40px); font-weight: 850; line-height: 1.14; letter-spacing: -0.03em; text-wrap: balance; }
.cta-band__copy > p { margin: 16px 0 0; max-width: 60ch; color: #c3cfdf; font-size: clamp(16px, 1.2vw, 18px); line-height: 1.68; }
.cta-band__trust { display: flex; flex-wrap: wrap; gap: 10px; margin: 24px 0 0; padding: 0; list-style: none; }
.cta-band__trust li { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px 8px 10px; border: 1px solid rgb(255 255 255 / 14%); border-radius: 999px; background: rgb(255 255 255 / 6%); color: #e6edf6; font-size: 14.5px; font-weight: 650; }
.cta-band__trust li :deep(.iconify), .cta-band__trust li > :first-child { color: var(--color-gold-300); }
.cta-band__trust img { width: 20px; height: 20px; object-fit: contain; }

.cta-band__actions { display: flex; flex-direction: column; gap: 12px; padding: 14px; border: 1px solid rgb(255 255 255 / 14%); border-radius: var(--radius-md); background: rgb(6 21 55 / 55%); backdrop-filter: blur(10px); }
.cta-band__primary { display: flex; min-height: 60px; align-items: center; justify-content: space-between; gap: 14px; padding: 0 10px 0 22px; border-radius: var(--radius-sm); background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 16.5px; font-weight: 850; text-decoration: none; transition: filter 0.25s ease; }
.cta-band__primary:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.cta-band__primary-arrow { display: grid; width: 40px; height: 40px; flex: none; place-items: center; border-radius: var(--radius-btn); background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s var(--ease); }
.cta-band__primary:hover .cta-band__primary-arrow { transform: translateX(4px); }
.cta-band__channels { display: grid; gap: 4px; }
.cta-band__channel { display: flex; min-height: 56px; align-items: center; gap: 14px; padding: 6px 10px; border-radius: var(--radius-sm); color: #fff; text-decoration: none; transition: background-color 0.25s ease; }
.cta-band__channel:hover { background: rgb(255 255 255 / 7%); }
.cta-band__channel small { display: block; color: #9fb0c8; font-size: 13px; font-weight: 600; }
.cta-band__channel strong { display: block; font-size: 20px; font-weight: 850; letter-spacing: -0.01em; line-height: 1.2; font-variant-numeric: tabular-nums; }
.cta-band__channel-icon { position: relative; display: grid; width: 44px; height: 44px; flex: none; place-items: center; border-radius: 50%; }
.cta-band__channel-icon.is-phone { background: rgb(228 168 73 / 18%); color: var(--color-gold-300); }
.cta-band__channel-icon.is-zalo { background: #0068ff; color: #fff; font-size: 12px; font-weight: 800; }
.cta-band a:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }

.cta-band--catalog { padding-block: clamp(28px, 3.5vw, 48px); }
.cta-band--catalog .cta-band__copy h2 { font-size: clamp(24px, 2.2vw, 34px); }

@media (max-width: 900px) {
  .cta-band { grid-template-columns: minmax(0, 1fr); }
  .cta-band__video { display: none; }
  .cta-band__image { width: 100%; opacity: 0.5; -webkit-mask-image: none; mask-image: none; }
  .cta-band__shade { background: linear-gradient(180deg, rgb(6 21 55 / 88%), rgb(6 21 55 / 72%) 60%, #061537); }
  .cta-band__sun { left: auto; right: -40%; bottom: auto; top: -30%; }
}
@media (max-width: 520px) {
  .cta-band { padding: 32px 20px 20px; border-radius: var(--radius-sm); }
  .cta-band__actions { padding: 10px; }
}
@media (prefers-reduced-motion: reduce) {
  .cta-band__image, .cta-band__primary-arrow { transition: none; }
  .cta-band__video { display: none; }
  .cta-band:hover .cta-band__image { transform: none; }
  .cta-band__channel-icon.is-phone::after { animation: none; }
}
</style>

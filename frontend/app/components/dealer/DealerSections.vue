<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { sentenceCase } from '~/utils/sentenceCase'

const props = defineProps<{ dealer: PublicDealerPayload }>()
const oversizedSupportImages: Record<string, string> = {
  '/upload/news/svgviewer-png-output-3-5470.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-media.webp',
  '/upload/news/svgviewer-png-output-2-4536.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-training.webp',
  '/upload/news/svgviewer-png-output-1-2932.png': '/assets/images/site-visuals/media-fallbacks/dealer-support-technical.webp',
}

// Dùng thumbnail cho ảnh CMS lớn; ba PNG vượt giới hạn proxy có WebP derivative đã kiểm tra.
function cardImage(src: string): string {
  if (oversizedSupportImages[src]) return oversizedSupportImages[src]
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? '/thumbs/480x300x1' + src
    : src
}

function cleanQuote(raw: string): string {
  return raw.trim().replace(/^[“"']+|[”"']+$/g, '').trim()
}

// Testimonial stage: one quote at a time; portraits act as the switcher. Auto-advances
// unless the visitor is interacting or prefers reduced motion.
const active = ref(0)
const paused = ref(false)
let timer: ReturnType<typeof setInterval> | undefined
function select(index: number) { active.value = index }
onMounted(() => {
  if (props.dealer.testimonials.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  timer = setInterval(() => {
    if (!paused.value) active.value = (active.value + 1) % props.dealer.testimonials.length
  }, 8000)
})
onBeforeUnmount(() => clearInterval(timer))
const current = computed(() => props.dealer.testimonials[active.value])
</script>

<template>
  <!--
    Dải hỗ trợ đại lý: nền navy đặc, số lượng thẻ phụ thuộc CMS (ảnh production
    có 4 thẻ một hàng ở 1920px). CSS preview cũ cố định 3 cột — với 4 phần tử
    sẽ đẩy thẻ thứ 4 xuống dòng riêng, lệch khỏi ảnh. Dùng 4 cột ở desktop.
  -->
  <section v-if="dealer.support.title || dealer.support.descriptionContent.html || dealer.support.description || dealer.support.items.length" id="ho-tro-dai-ly" class="dealer-support">
    <span class="dealer-support__glow" aria-hidden="true" />
    <div class="pts-container dealer-support__grid">
      <header class="dealer-support__head">
        <h2 v-if="dealer.support.title">{{ sentenceCase(dealer.support.title || dealer.labels.support) }}</h2>
        <EditorialRichText v-if="dealer.support.descriptionContent.html" :content="dealer.support.descriptionContent" class="cms-content-on-dark dealer-support__desc" />
        <p v-else-if="dealer.support.description" class="dealer-support__desc">{{ dealer.support.description }}</p>
        <a class="dealer-support__cta" href="#dang-ky-dai-ly">{{ dealer.form.title }} <span aria-hidden="true">→</span></a>
      </header>
      <div class="dealer-support__cards" :data-count="dealer.support.items.length">
        <article v-for="item in dealer.support.items" :key="item.title" class="dealer-benefit">
          <span v-if="item.image" class="dealer-benefit__icon flex h-14 w-14 items-center justify-center rounded-chip bg-gold-300 p-2">
            <img :src="cardImage(item.image)" :alt="item.title" class="h-full w-full object-contain" loading="lazy">
          </span>
          <h3>{{ item.title }}</h3>
          <EditorialRichText v-if="item.descriptionContent.html" :content="item.descriptionContent" class="cms-content-on-dark dealer-benefit__text" />
          <p v-else class="dealer-benefit__text">{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>

  <section v-if="dealer.testimonials.length && current" class="dealer-voices" aria-labelledby="dealer-testimonials-heading" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false">
    <div class="pts-container">
      <h2 id="dealer-testimonials-heading">{{ dealer.labels.testimonials }}</h2>
      <div class="dealer-voices__stage">
        <div class="dealer-voices__quote" aria-live="polite">
          <span class="dealer-voices__mark" aria-hidden="true">“</span>
          <Transition name="voice" mode="out-in">
            <figure :key="active">
              <blockquote>{{ cleanQuote(current.quote || current.description) }}</blockquote>
              <figcaption>
                <strong>{{ current.title }}</strong>
                <span v-if="current.description && current.quote">{{ current.description }}</span>
              </figcaption>
            </figure>
          </Transition>
        </div>
        <ul v-if="dealer.testimonials.length > 1" class="dealer-voices__people" aria-label="Chọn phản hồi">
          <li v-for="(item, index) in dealer.testimonials" :key="item.title">
            <button type="button" class="dealer-voices__person" :aria-pressed="index === active" @click="select(index)">
              <img v-if="item.image" :src="cardImage(item.image)" :alt="item.title" class="h-14 w-14 flex-none rounded-full object-cover" loading="lazy">
              <span v-else class="dealer-voices__initial" aria-hidden="true">{{ item.title.slice(0, 1) }}</span>
              <span class="min-w-0"><strong>{{ item.title }}</strong><small v-if="item.description">{{ item.description }}</small></span>
              <span class="dealer-voices__progress" aria-hidden="true" />
            </button>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<style scoped>
.dealer-support { --ease: cubic-bezier(0.22, 1, 0.36, 1); position: relative; overflow: hidden; padding: clamp(64px, 8vw, 120px) 0; background: var(--color-navy-950); color: #fff; }
.dealer-support__glow { position: absolute; top: -30%; left: -15%; width: 60vw; height: 60vw; border-radius: 50%; background: radial-gradient(closest-side, rgb(228 168 73 / 18%), transparent); pointer-events: none; }
.dealer-support__grid { position: relative; display: grid; grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr); gap: clamp(36px, 6vw, 96px); align-items: start; }
.dealer-support__head { position: sticky; top: 120px; }
.dealer-support__head h2 { margin: 0; font-size: clamp(30px, 3vw, 48px); font-weight: 850; line-height: 1.12; letter-spacing: -0.03em; text-wrap: balance; }
.dealer-support__desc { margin-top: 18px; color: #c3cfdf; font-size: 17px; line-height: 1.75; }
.dealer-support__cta { display: inline-flex; min-height: 54px; align-items: center; gap: 12px; margin-top: 28px; padding: 0 22px; border-radius: 6px; background: var(--color-gold-400); color: var(--color-navy-950); font-weight: 850; text-decoration: none; transition: background-color 0.25s ease; }
.dealer-support__cta:hover { background: var(--color-gold-300); }
.dealer-support__cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.dealer-support__cards[data-count="1"], .dealer-support__cards[data-count="3"] .dealer-benefit:last-child { grid-column: 1 / -1; }
.dealer-benefit { display: flex; flex-direction: column; gap: 14px; padding: clamp(24px, 2.4vw, 36px); border: 1px solid rgb(255 255 255 / 10%); border-radius: 8px; background: linear-gradient(160deg, rgb(255 255 255 / 8%), rgb(255 255 255 / 2%)); transition: transform 0.45s var(--ease), border-color 0.35s ease; }
.dealer-benefit:hover { transform: translateY(-6px); border-color: rgb(228 168 73 / 55%); }
.dealer-benefit h3 { margin: 0; color: #fff; font-size: clamp(20px, 1.6vw, 24px); font-weight: 800; }
.dealer-benefit__text { margin: 0; color: #c3cfdf; font-size: 15.5px; line-height: 1.7; }

.dealer-voices { --ease: cubic-bezier(0.22, 1, 0.36, 1); padding: clamp(64px, 8vw, 120px) 0; background: linear-gradient(180deg, #f5f7fb, #fff); }
.dealer-voices h2 { margin: 0 0 clamp(32px, 4vw, 56px); color: var(--color-navy-950); font-size: clamp(30px, 3vw, 48px); font-weight: 850; line-height: 1.12; letter-spacing: -0.03em; }
.dealer-voices__stage { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.6fr); gap: clamp(32px, 5vw, 80px); align-items: center; }
.dealer-voices__quote { position: relative; min-height: 300px; padding-left: clamp(0px, 4vw, 64px); }
.dealer-voices__mark { position: absolute; top: -40px; left: -8px; color: var(--color-gold-400); font-family: Georgia, serif; font-size: clamp(120px, 12vw, 180px); line-height: 1; opacity: 0.35; }
.dealer-voices__quote figure { position: relative; margin: 0; }
.dealer-voices__quote blockquote { margin: 0; color: var(--color-navy-950); font-size: clamp(22px, 2.1vw, 34px); font-weight: 700; line-height: 1.45; letter-spacing: -0.015em; }
.dealer-voices__quote figcaption { display: flex; flex-direction: column; gap: 2px; margin-top: 28px; padding-left: 20px; border-left: 3px solid var(--color-gold-500); }
.dealer-voices__quote figcaption strong { color: var(--color-navy-950); font-size: 18px; font-weight: 850; }
.dealer-voices__quote figcaption span { color: #52627a; font-size: 15px; font-weight: 600; }
.dealer-voices__people { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
.dealer-voices__person { position: relative; display: flex; width: 100%; min-height: 80px; align-items: center; gap: 14px; overflow: hidden; padding: 12px 16px; border: 1px solid rgb(10 34 81 / 10%); border-radius: 8px; background: #fff; color: var(--color-navy-900); text-align: left; cursor: pointer; transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.35s var(--ease); }
.dealer-voices__person strong { display: block; font-size: 15.5px; font-weight: 800; }
.dealer-voices__person small { display: block; color: #64748b; font-size: 13.5px; }
.dealer-voices__person:hover { transform: translateX(-4px); }
.dealer-voices__person[aria-pressed="true"] { border-color: var(--color-gold-500); box-shadow: 0 18px 36px -24px rgb(10 34 81 / 50%); }
.dealer-voices__person:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.dealer-voices__progress { position: absolute; inset: auto 0 0; height: 3px; background: var(--color-gold-500); transform: scaleX(0); transform-origin: left; }
.dealer-voices__person[aria-pressed="true"] .dealer-voices__progress { animation: voice-progress 8s linear forwards; }
.dealer-voices:hover .dealer-voices__progress, .dealer-voices:focus-within .dealer-voices__progress { animation-play-state: paused; }
.dealer-voices__initial { display: grid; width: 56px; height: 56px; flex: none; place-items: center; border-radius: 50%; background: var(--color-navy-950); color: var(--color-gold-300); font-weight: 800; }
.voice-enter-active { transition: opacity 0.5s ease, transform 0.6s var(--ease); }
.voice-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.voice-enter-from { opacity: 0; transform: translateY(18px); }
.voice-leave-to { opacity: 0; transform: translateY(-10px); }
@keyframes voice-progress { to { transform: scaleX(1); } }

@media (max-width: 991px) {
  .dealer-support__grid, .dealer-voices__stage { grid-template-columns: 1fr; }
  .dealer-support__head { position: static; }
  .dealer-voices__quote { min-height: 0; padding-left: 0; }
  .dealer-voices__mark { top: -60px; }
}
@media (max-width: 560px) {
  .dealer-support__cards { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .dealer-benefit, .dealer-voices__person, .voice-enter-active, .voice-leave-active { transition: none; }
  .dealer-benefit:hover, .dealer-voices__person:hover { transform: none; }
  .dealer-voices__person[aria-pressed="true"] .dealer-voices__progress { animation: none; }
}
</style>

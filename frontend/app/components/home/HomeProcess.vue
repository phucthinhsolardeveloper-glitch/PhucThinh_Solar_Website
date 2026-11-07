<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ steps: PublicHomePayload['process'] }>()

const listRef = ref<HTMLElement | null>(null)
const progress = ref(0)
const reached = ref(-1)
let ticking = false

function measure() {
  ticking = false
  const list = listRef.value
  if (!list) return
  const rect = list.getBoundingClientRect()
  const line = window.innerHeight * 0.62
  progress.value = Math.min(1, Math.max(0, (line - rect.top) / rect.height))
  const items = [...list.querySelectorAll<HTMLElement>('[data-step]')]
  reached.value = items.reduce((last, item, index) => (item.getBoundingClientRect().top + 28 <= line ? index : last), -1)
}

function onScroll() {
  if (ticking) return
  ticking = true
  requestAnimationFrame(measure)
}

onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    progress.value = 1
    reached.value = props.steps.length - 1
    return
  }
  measure()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll)
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
})
</script>

<template>
  <section v-if="steps.length" class="journey bg-hdr-surface py-[clamp(64px,7vw,120px)] max-tb:py-8" aria-labelledby="home-process-title">
    <div class="pts-container grid gap-12 min-[961px]:grid-cols-[minmax(0,.82fr)_minmax(0,1.18fr)] min-[961px]:gap-[clamp(48px,6vw,120px)]">
      <header class="min-[961px]:sticky min-[961px]:top-[clamp(120px,14vh,180px)] min-[961px]:self-start">
        <h2 id="home-process-title" class="m-0 max-w-[13ch] text-[clamp(32px,3.4vw,56px)] font-black leading-[1.04] tracking-[-.035em] text-navy-950">
          Đồng hành từ tư vấn đến vận hành
        </h2>
        <p class="mt-5 max-w-[42ch] text-[clamp(15px,1.05vw,18px)] leading-[1.7] text-slate-600">
          Phúc Thịnh Solar tư vấn giải pháp phù hợp, thi công chuyên nghiệp và tận tâm hỗ trợ sau bàn giao, giúp khách hàng an tâm sử dụng lâu dài.
        </p>
        <a href="#yeu-cau-tu-van" class="mt-8 inline-flex min-h-[52px] items-center gap-2 rounded-btn pts-gold-fill px-6 text-[15px] font-extrabold no-underline transition-colors hover:bg-gold-500 hover:text-navy-950">
          Bắt đầu với bước tư vấn <span aria-hidden="true">→</span>
        </a>
      </header>

      <ol ref="listRef" class="journey-list relative m-0 list-none p-0" :style="{ '--progress': progress }">
        <li
          v-for="(step, index) in steps"
          :key="step.number"
          data-step
          class="journey-step"
          :class="{ 'is-reached': index <= reached }"
        >
          <span class="journey-num" aria-hidden="true">{{ step.number }}</span>
          <div class="min-w-0">
            <h3 class="journey-title">{{ step.name }}</h3>
            <p class="journey-desc"><ChunkedText :text="step.description" /></p>
          </div>
        </li>
      </ol>
    </div>
  </section>
</template>

<style scoped>
.journey-list {
  --rail-x: clamp(34px, 4.4vw, 58px);
  --text-x: calc(var(--rail-x) * 2 + clamp(14px, 1.6vw, 24px));
  padding-left: var(--text-x);
}
.journey-list::before,
.journey-list::after {
  content: "";
  position: absolute;
  top: 10px;
  bottom: 10px;
  left: var(--rail-x);
  width: 2px;
  border-radius: 2px;
}
.journey-list::before { background: rgb(10 34 81 / 12%); }
.journey-list::after {
  background: linear-gradient(180deg, var(--color-gold-400), var(--color-gold-600));
  box-shadow: 0 0 12px rgb(224 182 84 / 55%);
  transform-origin: top;
  transform: scaleY(var(--progress, 0));
}

.journey-step {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  padding: clamp(18px, 2.2vw, 30px) 0;
  border-bottom: 1px solid rgb(10 34 81 / 8%);
}
.journey-step:last-child { border-bottom: 0; }

/* Large numeral sits on the rail; it fills with gold once the current reaches it. */
.journey-num {
  position: absolute;
  top: clamp(14px, 2vw, 26px);
  left: calc(var(--text-x) * -1);
  width: calc(var(--rail-x) * 2);
  text-align: center;
  font-size: clamp(30px, 3.2vw, 48px);
  font-weight: 900;
  line-height: 1;
  letter-spacing: -0.04em;
  color: var(--color-hdr-surface, #f4f7fb);
  -webkit-text-stroke: 1.5px rgb(10 34 81 / 30%);
  paint-order: stroke fill;
  background: var(--color-hdr-surface, #f4f7fb);
  padding-block: 4px;
  transition: color 0.45s ease, -webkit-text-stroke-color 0.45s ease;
}
.is-reached .journey-num {
  color: var(--color-gold-500);
  -webkit-text-stroke-color: var(--color-gold-600);
}

.journey-title {
  margin: 0;
  font-size: clamp(19px, 1.6vw, 26px);
  font-weight: 800;
  line-height: 1.25;
  letter-spacing: -0.02em;
  color: rgb(10 34 81 / 45%);
  transition: color 0.45s ease;
}
.journey-desc {
  margin: 8px 0 0;
  max-width: 54ch;
  font-size: clamp(15px, 1.02vw, 17px);
  line-height: 1.65;
  color: rgb(71 85 105 / 60%);
  transition: color 0.45s ease;
}
.is-reached .journey-title { color: var(--color-navy-950); }
.is-reached .journey-desc { color: rgb(71 85 105); }

@media (prefers-reduced-motion: reduce) {
  .journey-num, .journey-title, .journey-desc { transition: none; }
}

/* Phones: the five steps sit side by side and swipe, instead of a screen and a half of list. */
@media (max-width: 820px) {
  .journey > div { grid-template-columns: minmax(0, 1fr); gap: 20px; }
  .journey > div > * { min-width: 0; }
  .journey-list { display: flex; gap: 12px; margin-inline: -16px; padding-inline: 16px; overflow-x: auto; scrollbar-width: none; scroll-snap-type: x mandatory; }
  .journey-list::before, .journey-list::after { display: none; }
  .journey-num { top: 12px; right: 12px; left: auto; width: auto; font-size: 24px; }
  .journey-step { position: relative; flex: 0 0 78%; padding: 16px 52px 16px 16px; border: 1px solid rgb(10 34 81 / 12%); border-radius: var(--radius-md); background: #fff; opacity: 1 !important; scroll-snap-align: center; }
}

@media (max-width: 820px) {
  .journey > div { gap: 14px; }
  .journey header h2 { max-width: none; font-size: 28px; line-height: 1.12; }
  .journey header p { max-width: none; margin-top: 8px; font-size: 15px; line-height: 1.55; }
  .journey header a { display: flex; justify-content: center; width: 100%; margin-top: 12px; }
  /* The numeral's base background masks the desktop rail; on a white card it shows as a grey square. */
  .journey-num { background: transparent; }
  .journey-title { font-size: 17px; }
  .journey-desc { margin-top: 6px; font-size: 14.5px; line-height: 1.5; }
}
</style>

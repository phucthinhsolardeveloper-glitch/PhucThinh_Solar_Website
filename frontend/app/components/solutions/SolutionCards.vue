<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'

const props = withDefaults(defineProps<{ cards: PublicSolutionCard[]; choicesLabel: string; emptyLabel: string; consultLabel: string; highlight?: string[] }>(), { highlight: () => [] })
const links = usePublicLinks()

// Phones show the cards on a rail, where the finder's match could be off-screen: bring it into view.
const mosaicRef = ref<HTMLElement | null>(null)
watch(() => props.highlight, async (slugs) => {
  if (!slugs.length) return
  await nextTick()
  const rail = mosaicRef.value
  const match = rail?.querySelector<HTMLElement>('.is-match')
  if (!rail || !match || rail.scrollWidth <= rail.clientWidth) return
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const left = rail.scrollLeft + match.getBoundingClientRect().left - rail.getBoundingClientRect().left - (rail.clientWidth - match.offsetWidth) / 2
  rail.scrollTo({ left, behavior: reduced ? 'auto' : 'smooth' })
})
</script>

<template>
  <!--
    Trang giải pháp chỉ dùng $solutionUi['choices']
    làm aria-label trên <section>, không render heading chữ nào phía trên
    lưới card khi có dữ liệu — mỗi card tự có heading riêng là tiêu đề của nó.
    Chỉ hiện text này như tiêu đề thật khi danh sách rỗng.
  -->
  <section id="cac-giai-phap" class="pt-[42px] pb-[54px] max-[520px]:pt-[26px] max-[520px]:pb-[26px]" :aria-label="cards.length ? choicesLabel : undefined" :aria-labelledby="cards.length ? undefined : 'solutions-choices-title'">
    <h2 v-if="!cards.length" id="solutions-choices-title" class="m-0 mb-[22px] text-[clamp(24px,2.5vw,32px)] leading-[1.2] font-black tracking-tight text-navy-950">
      {{ emptyLabel }}
    </h2>

    <div v-if="cards.length" ref="mosaicRef" class="mosaic" :class="{ 'is-single': cards.length === 1 }">
      <a
        v-for="(card, index) in cards"
        :key="card.slug"
        class="tile group"
        :class="{ 'is-lead': index === 0 && cards.length > 2, 'is-match': highlight.includes(card.slug), 'is-dimmed': highlight.length > 0 && !highlight.includes(card.slug) }"
        :href="links.solution(card.slug)"
      >
        <img
          v-if="card.image"
          :src="optimizedImage(card.image, 900, 675)"
          alt=""
          class="tile-image"
          width="900"
          height="700"
          :loading="index === 0 ? 'eager' : 'lazy'"
          decoding="async"
        >
        <span class="tile-veil" aria-hidden="true" />
        <span v-if="highlight.includes(card.slug)" class="tile-match">Phù hợp với bạn</span>
        <span class="tile-body">
          <h3 class="tile-title">{{ card.title }}</h3>
          <span v-if="card.description" class="tile-desc">{{ card.description }}</span>
          <span class="tile-cta">
            {{ card.actionLabel }}
            <span class="tile-arrow" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
            </span>
          </span>
        </span>
      </a>
    </div>
    <a v-else class="inline-flex min-h-11 items-center font-extrabold text-navy-800 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ consultLabel }} →</a>
  </section>
</template>

<style scoped>
.mosaic {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  grid-auto-rows: clamp(220px, 17vw, 300px);
  grid-auto-flow: dense;
  gap: clamp(10px, 1vw, 16px);
}
.mosaic.is-single { grid-template-columns: 1fr; grid-auto-rows: clamp(320px, 32vw, 480px); }

.tile {
  position: relative;
  isolation: isolate;
  display: flex;
  min-width: 0;
  overflow: hidden;
  overflow: clip;
  border-radius: var(--radius-btn);
  background: var(--color-navy-950);
  color: #fff;
  text-decoration: none;
}
.tile.is-lead { grid-column: span 2; grid-row: span 2; }
.tile:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 3px; }

.tile-image {
  position: absolute;
  inset: 0;
  z-index: -2;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.9s cubic-bezier(0.22, 1, 0.36, 1);
}
.tile:hover .tile-image, .tile:focus-visible .tile-image { transform: scale(1.06); }
.tile-veil {
  position: absolute;
  inset: 0;
  z-index: -1;
  background: linear-gradient(180deg, rgb(6 21 55 / 0%) 30%, rgb(6 21 55 / 88%) 100%);
  transition: background-color 0.4s ease;
}
.tile:hover .tile-veil { background-color: rgb(6 21 55 / 25%); }

.tile-body {
  margin-top: auto;
  display: flex;
  width: 100%;
  flex-direction: column;
  gap: 8px;
  padding: clamp(16px, 1.8vw, 28px);
}
.tile-title {
  margin: 0;
  font-size: clamp(18px, 1.35vw, 22px);
  font-weight: 900;
  line-height: 1.2;
  letter-spacing: -0.02em;
  color: #fff;
}
.is-lead .tile-title, .is-single .tile-title { font-size: clamp(26px, 2.4vw, 40px); line-height: 1.08; }

/* Description is hidden on small tiles until hover/focus; always visible on the lead tile. */
.tile-desc {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  max-width: 52ch;
  max-height: 0;
  opacity: 0;
  font-size: 14.5px;
  line-height: 1.6;
  color: rgb(226 232 240);
  transition: max-height 0.5s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.35s ease;
}
.tile:hover .tile-desc, .tile:focus-visible .tile-desc, .is-lead .tile-desc, .is-single .tile-desc { max-height: 5em; opacity: 1; }
.is-lead .tile-desc { font-size: 16px; }

.tile-cta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 4px;
  font-size: 13.5px;
  font-weight: 800;
  color: var(--color-gold-300);
}
.tile-arrow {
  display: grid;
  width: 36px;
  height: 36px;
  flex: none;
  place-items: center;
  border-radius: 50%;
  background: var(--pts-gold-gradient);
  color: var(--color-navy-950);
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.tile-arrow svg { width: 15px; height: 15px; }
.tile:hover .tile-arrow { transform: translateX(4px); }

.tile { transition: opacity 0.4s ease, filter 0.4s ease, box-shadow 0.4s ease; }
.tile.is-dimmed { opacity: 0.55; filter: saturate(0.4); }
.tile.is-dimmed:hover, .tile.is-dimmed:focus-visible { opacity: 1; filter: none; }
.tile.is-match { box-shadow: 0 0 0 3px var(--color-gold-400), 0 24px 50px -24px rgb(6 21 55 / 55%); }
.tile-match {
  position: absolute;
  left: clamp(14px, 1.6vw, 24px);
  top: clamp(14px, 1.6vw, 24px);
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--pts-gold-gradient);
  color: var(--color-navy-950);
  font-size: 13px;
  font-weight: 800;
  animation: match-pop 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes match-pop { from { opacity: 0; transform: translateY(-6px) scale(0.92); } to { opacity: 1; transform: none; } }

@media (max-width: 1100px) {
  .mosaic { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: clamp(220px, 34vw, 300px); }
}
@media (max-width: 640px) {
  .mosaic { grid-template-columns: 1fr; grid-auto-rows: auto; }
  .tile { min-height: 240px; }
  .tile.is-lead { grid-column: auto; grid-row: auto; min-height: 340px; }
  .tile-desc { max-height: 5em; opacity: 1; -webkit-line-clamp: 2; }
}
@media (prefers-reduced-motion: reduce) {
  .tile, .tile-image, .tile-desc, .tile-arrow, .tile-veil { transition: none; }
  .tile-match { animation: none; }
  .tile:hover .tile-image { transform: none; }
}
/* Phones: peer solutions on a swipe rail. Photo on top, full text on solid navy, so nothing is cut or sits on a bright photo. */
@media (max-width: 820px) {
  #cac-giai-phap { padding-block: 16px 24px; scroll-margin-top: 76px; }
  .mosaic, .mosaic.is-single { grid-template-columns: minmax(0, 1fr); grid-auto-rows: auto; }
  .mosaic:not(.is-single) {
    display: flex;
    gap: 12px;
    margin-inline: calc(50% - 50vw);
    padding: 4px calc(50vw - 50%) 8px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    scroll-snap-type: x mandatory;
    scrollbar-width: none;
  }
  .mosaic::-webkit-scrollbar { display: none; }
  .tile, .tile.is-lead { flex: 0 0 min(84%, 330px); flex-direction: column; min-height: 0; border-radius: var(--radius-md); scroll-snap-align: center; }
  .tile-image { position: relative; inset: auto; flex: none; height: auto; aspect-ratio: 16 / 9; }
  .tile-veil { inset: 0 0 auto; height: auto; aspect-ratio: 16 / 9; background: linear-gradient(180deg, rgb(6 21 55 / 0%) 55%, var(--color-navy-950) 100%); }
  .tile-body { flex: 1; margin-top: 0; gap: 6px; padding: 10px 14px 14px; }
  .tile-title, .is-lead .tile-title, .is-single .tile-title { font-size: 18px; font-weight: 800; line-height: 1.22; }
  .tile-desc, .is-lead .tile-desc, .is-single .tile-desc { display: block; overflow: visible; max-width: none; max-height: none; opacity: 1; font-size: 14px; line-height: 1.5; -webkit-line-clamp: unset; }
  .tile-cta { margin-top: auto; padding-top: 6px; font-size: 14px; }
}
</style>

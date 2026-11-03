<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'

defineProps<{ cards: PublicSolutionCard[]; choicesLabel: string; emptyLabel: string; consultLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <!--
    PHP (templates/news/service_list_tpl.php) chỉ dùng $solutionUi['choices']
    làm aria-label trên <section>, không render heading chữ nào phía trên
    lưới card khi có dữ liệu — mỗi card tự có heading riêng là tiêu đề của nó.
    Chỉ hiện text này như tiêu đề thật khi danh sách rỗng.
  -->
  <section id="cac-giai-phap" class="pt-[42px] pb-[54px] max-[520px]:pt-[26px] max-[520px]:pb-[26px]" :aria-label="cards.length ? choicesLabel : undefined" :aria-labelledby="cards.length ? undefined : 'solutions-choices-title'">
    <h2 v-if="!cards.length" id="solutions-choices-title" class="m-0 mb-[22px] text-[clamp(24px,2.5vw,32px)] leading-[1.2] font-black tracking-tight text-navy-950">
      {{ emptyLabel }}
    </h2>

    <div v-if="cards.length" class="mosaic" :class="{ 'is-single': cards.length === 1 }">
      <a
        v-for="(card, index) in cards"
        :key="card.slug"
        class="tile group"
        :class="{ 'is-lead': index === 0 && cards.length > 2 }"
        :href="links.solution(card.slug)"
      >
        <img
          v-if="card.image"
          :src="card.image"
          alt=""
          class="tile-image"
          width="900"
          height="700"
          :loading="index === 0 ? 'eager' : 'lazy'"
          decoding="async"
        >
        <span class="tile-veil" aria-hidden="true" />
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
  border-radius: 4px;
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
  background: var(--color-gold-500);
  color: var(--color-navy-950);
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}
.tile-arrow svg { width: 15px; height: 15px; }
.tile:hover .tile-arrow { transform: translateX(4px); }

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
  .tile-image, .tile-desc, .tile-arrow, .tile-veil { transition: none; }
  .tile:hover .tile-image { transform: none; }
}
</style>

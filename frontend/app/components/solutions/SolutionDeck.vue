<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'

const props = defineProps<{ cards: PublicSolutionCard[] }>()
const links = useMigrationLinks()
const deck = computed(() => props.cards.filter((card) => card.image).slice(0, 5))
const front = ref(0)
let timer: number | undefined
let paused = false

// Position of each card relative to the current front (0 = top of the deck).
function slot(index: number) {
  const count = deck.value.length
  return (index - front.value + count) % count
}
function slotName(index: number) {
  const position = slot(index)
  if (position < 3) return String(position)
  return position === deck.value.length - 1 ? 'out' : 'back'
}
function next() { front.value = (front.value + 1) % deck.value.length }
function stop() { if (timer !== undefined) window.clearInterval(timer); timer = undefined }
function start() {
  stop()
  if (paused || deck.value.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  timer = window.setInterval(next, 3600)
}
function pause() { paused = true; stop() }
function resume() { paused = false; start() }
onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <div
    v-if="deck.length"
    class="deck"
    @mouseenter="pause"
    @mouseleave="resume"
    @focusin="pause"
    @focusout="resume"
  >
    <a
      v-for="(card, index) in deck"
      :key="card.slug"
      class="deck-card"
      :data-slot="slotName(index)"
      :tabindex="slot(index) === 0 ? 0 : -1"
      :aria-hidden="slot(index) !== 0"
      :href="links.solution(card.slug)"
    >
      <img :src="card.image" alt="" width="800" height="600" :loading="index === 0 ? 'eager' : 'lazy'" decoding="async">
      <span class="deck-label">
        <strong>{{ card.title }}</strong>
        <span aria-hidden="true">→</span>
      </span>
    </a>
    <button v-if="deck.length > 1" type="button" class="deck-next" aria-label="Xem giải pháp tiếp theo" @click="next">
      <UIcon name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
.deck {
  position: relative;
  width: 100%;
  max-width: 560px;
  aspect-ratio: 6 / 5;
  justify-self: end;
}
.deck-card {
  position: absolute;
  left: 4%;
  top: 14%;
  width: 82%;
  aspect-ratio: 4 / 3;
  overflow: clip;
  border-radius: 6px;
  background: var(--color-navy-950);
  box-shadow: 0 30px 60px -24px rgb(6 21 55 / 45%);
  color: #fff;
  text-decoration: none;
  transform-origin: 50% 100%;
  transition: transform 0.9s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.6s ease, filter 0.6s ease;
}
.deck-card img { display: block; width: 100%; height: 100%; object-fit: cover; }
.deck-card[data-slot="0"] { z-index: 4; transform: rotate(-3deg); }
.deck-card[data-slot="1"] { z-index: 3; transform: translate(9%, -7%) rotate(3deg) scale(0.95); filter: brightness(0.82); }
.deck-card[data-slot="2"] { z-index: 2; transform: translate(17%, -14%) rotate(8deg) scale(0.9); filter: brightness(0.65); }
/* "out" = the card that just left the top: slides out left and fades. "back" = waiting behind the stack. */
.deck-card[data-slot="out"] { z-index: 5; opacity: 0; transform: translate(-26%, 12%) rotate(-16deg) scale(0.9); pointer-events: none; }
.deck-card[data-slot="back"] { z-index: 1; opacity: 0; transform: translate(22%, -18%) rotate(11deg) scale(0.86); pointer-events: none; transition: none; }

.deck-label {
  position: absolute;
  inset: auto 0 0;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 12px;
  padding: 48px 20px 18px;
  background: linear-gradient(180deg, transparent, rgb(6 21 55 / 88%));
  font-size: clamp(17px, 1.4vw, 22px);
  opacity: 0;
  transition: opacity 0.4s ease;
}
.deck-label strong { font-weight: 900; line-height: 1.2; letter-spacing: -0.015em; }
.deck-label span { color: var(--color-gold-400); font-weight: 900; }
.deck-card[data-slot="0"] .deck-label { opacity: 1; transition-delay: 0.35s; }
.deck-card:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; }

.deck-next {
  position: absolute;
  right: 0;
  bottom: 2%;
  z-index: 5;
  display: grid;
  width: 52px;
  height: 52px;
  place-items: center;
  border: 0;
  border-radius: 50%;
  background: var(--color-gold-400);
  color: var(--color-navy-950);
  cursor: pointer;
  box-shadow: 0 12px 24px -10px rgb(6 21 55 / 50%);
  transition: transform 0.25s ease, background-color 0.2s ease;
}
.deck-next:hover { background: var(--color-gold-500); transform: translateX(3px); }
.deck-next:focus-visible { outline: 2px solid var(--color-navy-950); outline-offset: 3px; }

@media (max-width: 900px) {
  .deck { justify-self: center; max-width: 440px; }
}
/* On phones the mosaic right below shows the same solutions; skip the deck. */
@media (max-width: 640px) { .deck { display: none; } }
@media (prefers-reduced-motion: reduce) {
  .deck-card, .deck-label, .deck-next { transition: none; }
}
</style>

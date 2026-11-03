<script setup lang="ts">
export type DirectorStageItem = {
  key: string
  label: string
  title: string
  description?: string
  image: string
  href: string
  linkLabel?: string
}

const props = withDefaults(defineProps<{
  items: DirectorStageItem[]
  listLabel: string
  reverse?: boolean
  interval?: number
}>(), { reverse: false, interval: 6000 })

const active = defineModel<number>('active', { default: 0 })
const previous = ref(-1)
const current = computed(() => props.items[active.value] ?? props.items[0])
const links = useMigrationLinks()
let timer: number | undefined
let paused = false

function select(index: number) {
  if (index === active.value) return
  previous.value = active.value
  active.value = index
}
function stop() {
  if (timer !== undefined) window.clearInterval(timer)
  timer = undefined
}
function start() {
  stop()
  if (paused || props.items.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  timer = window.setInterval(() => select((active.value + 1) % props.items.length), props.interval)
}
function pause() { paused = true; stop() }
function resume() { paused = false; start() }

onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <div
    v-if="items.length && current"
    class="director grid gap-[clamp(28px,4vw,72px)] min-[961px]:items-stretch"
    :class="reverse ? 'min-[961px]:grid-cols-[minmax(320px,.85fr)_minmax(0,1.45fr)]' : 'min-[961px]:grid-cols-[minmax(0,1.45fr)_minmax(320px,.85fr)]'"
    :style="{ '--stage-interval': `${interval}ms` }"
    @mouseenter="pause"
    @mouseleave="resume"
    @focusin="pause"
    @focusout="resume"
  >
    <figure class="stage relative m-0 overflow-hidden rounded-[4px] bg-navy-950 max-[960px]:order-2" :class="reverse ? 'min-[961px]:order-2' : ''">
      <img
        v-for="(item, index) in items"
        :key="item.key"
        class="stage-layer"
        :class="{ 'is-active': index === active, 'is-previous': index === previous }"
        :src="item.image"
        alt=""
        width="1200"
        height="900"
        loading="lazy"
        decoding="async"
      >
      <span class="stage-veil" aria-hidden="true" />
      <figcaption :key="active" class="stage-caption">
        <strong>{{ current.title }}</strong>
        <span v-if="current.description" class="stage-desc">{{ current.description }}</span>
        <slot name="caption" :item="current" :index="active" />
        <a class="stage-link" :href="links.generic(current.href)">{{ current.linkLabel || 'Xem chi tiết' }} <span aria-hidden="true">→</span></a>
      </figcaption>
      <span :key="`t-${active}`" class="stage-timer" :class="{ 'is-running': items.length > 1 }" aria-hidden="true" />
    </figure>

    <div class="flex flex-col max-[960px]:order-1" :class="reverse ? 'min-[961px]:order-1' : ''">
      <slot name="head" />
      <ul class="scene-list m-0 mt-8 list-none p-0 max-[960px]:mt-6" :aria-label="listLabel">
        <li v-for="(item, index) in items" :key="item.key">
          <button
            type="button"
            class="scene-item"
            :class="{ 'is-active': index === active }"
            :aria-pressed="index === active"
            @click="select(index)"
            @mouseenter="select(index)"
          >
            {{ item.label }}
          </button>
        </li>
      </ul>
      <div class="mt-auto pt-8 max-[960px]:hidden">
        <slot name="foot" :item="current" />
      </div>
    </div>

    <div class="order-3 min-[961px]:hidden">
      <slot name="foot" :item="current" />
    </div>
  </div>
</template>

<style scoped>
.stage { min-height: clamp(360px, 38vw, 600px); isolation: isolate; overflow: clip; }
@media (max-width: 960px) {
  .stage { min-height: clamp(440px, 125vw, 560px); }
  .stage-desc { -webkit-line-clamp: 2; }
}
.stage-layer {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  clip-path: inset(0 100% 0 0);
  z-index: 0;
}
.stage-layer.is-previous { clip-path: inset(0 0 0 0); z-index: 1; }
.stage-layer.is-active {
  clip-path: inset(0 0 0 0);
  z-index: 2;
  transition: clip-path 1s cubic-bezier(0.77, 0, 0.18, 1);
  animation: stage-settle var(--stage-interval) ease-out forwards;
}
@keyframes stage-settle { from { transform: scale(1.06); } to { transform: scale(1); } }
.stage-veil {
  position: absolute;
  inset: 0;
  z-index: 3;
  background: linear-gradient(180deg, transparent 35%, rgb(6 21 55 / 90%) 100%);
  pointer-events: none;
}
.stage-caption {
  position: absolute;
  left: clamp(20px, 2.6vw, 40px);
  right: clamp(20px, 2.6vw, 40px);
  bottom: clamp(22px, 2.6vw, 40px);
  z-index: 4;
  display: flex;
  max-width: 600px;
  flex-direction: column;
  gap: 8px;
  color: #fff;
  animation: caption-in 0.7s 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes caption-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.stage-caption strong { font-size: clamp(22px, 2vw, 32px); font-weight: 900; line-height: 1.12; letter-spacing: -0.02em; }
.stage-desc {
  display: -webkit-box;
  overflow: hidden;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  font-size: 15.5px;
  line-height: 1.6;
  color: rgb(226 232 240);
}
.stage-link {
  margin-top: 6px;
  align-self: flex-start;
  border-bottom: 2px solid var(--color-gold-500);
  padding-bottom: 4px;
  font-size: 15px;
  font-weight: 800;
  color: var(--color-gold-300);
  text-decoration: none;
}
.stage-link:hover { color: #fff; }
.stage-link:focus-visible { outline: 2px solid #fff; outline-offset: 4px; }

.stage-timer {
  position: absolute;
  left: 0;
  top: 0;
  z-index: 5;
  height: 3px;
  width: 100%;
  background: var(--color-gold-400);
  transform-origin: left;
  transform: scaleX(0);
}
.stage-timer.is-running { animation: stage-timer var(--stage-interval) linear forwards; }
@keyframes stage-timer { to { transform: scaleX(1); } }
.director:hover .stage-timer, .director:focus-within .stage-timer { animation-play-state: paused; }

.scene-list { border-top: 1px solid rgb(10 34 81 / 12%); }
.scene-item {
  display: block;
  width: 100%;
  min-height: 64px;
  padding: 18px 0 18px 22px;
  border: 0;
  border-bottom: 1px solid rgb(10 34 81 / 12%);
  border-left: 3px solid transparent;
  background: transparent;
  text-align: left;
  font: inherit;
  font-size: clamp(17px, 1.35vw, 22px);
  font-weight: 800;
  line-height: 1.3;
  letter-spacing: -0.015em;
  color: rgb(10 34 81 / 42%);
  cursor: pointer;
  transition: color 0.3s ease, border-color 0.3s ease, padding-left 0.4s cubic-bezier(0.22, 1, 0.36, 1);
}
.scene-item:hover { color: rgb(10 34 81 / 75%); }
.scene-item.is-active { color: var(--color-navy-950); border-left-color: var(--color-gold-500); padding-left: 28px; }
.scene-item:focus-visible { outline: 2px solid var(--color-gold-600); outline-offset: 2px; }

@media (prefers-reduced-motion: reduce) {
  .stage-layer.is-active { transition: none; animation: none; }
  .stage-caption { animation: none; }
  .stage-timer { display: none; }
  .scene-item { transition: none; }
}
</style>

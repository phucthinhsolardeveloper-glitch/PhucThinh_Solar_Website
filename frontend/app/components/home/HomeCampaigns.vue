<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ campaigns: PublicHomePayload['campaigns']; campaignsConfigured: boolean }>()
const phpFallback: PublicHomePayload['campaigns'] = [
  { name: 'Chiếu sáng năng lượng mặt trời cho công trình', description: 'Thiết bị đồng bộ cho đường nội bộ, khuôn viên và hạ tầng thương mại.', image: '/assets/images/site-visuals/home-street-lighting-v2.png', href: '/san-pham/' },
  { name: 'Hệ điện mặt trời và lưu trữ', description: 'Tấm pin, inverter và lưu trữ được lựa chọn theo đúng quy mô sử dụng.', image: '/assets/images/site-visuals/home-solar-storage.jpg', href: '/giai-phap/' },
  { name: 'Chiếu sáng sân vườn và lối đi', description: 'Tạo không gian an toàn, chỉn chu và vận hành độc lập bằng năng lượng sạch.', image: '/assets/images/site-visuals/home-outdoor-lighting.jpg', href: '/san-pham/' },
]
const items = computed(() => props.campaigns.length ? props.campaigns.slice(0, 3) : props.campaignsConfigured ? [] : phpFallback)
const links = useMigrationLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')

const active = ref(0)
const previous = ref(-1)
const current = computed(() => items.value[active.value] ?? items.value[0])
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
  if (paused || items.value.length < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  timer = window.setInterval(() => select((active.value + 1) % items.value.length), 6000)
}
function pause() { paused = true; stop() }
function resume() { paused = false; start() }

onMounted(start)
onBeforeUnmount(stop)
</script>

<template>
  <section v-if="items.length && current" class="director bg-hdr-surface py-[clamp(64px,7vw,112px)]" aria-labelledby="home-campaign-title">
    <div class="pts-container grid gap-[clamp(28px,4vw,72px)] min-[961px]:grid-cols-[minmax(0,1.45fr)_minmax(320px,.85fr)] min-[961px]:items-stretch" @mouseenter="pause" @mouseleave="resume" @focusin="pause" @focusout="resume">
      <!-- Stage: layers wipe in from left to right over the previous image -->
      <figure class="stage relative m-0 overflow-hidden rounded-[4px] bg-navy-950 max-[960px]:order-2">
        <img
          v-for="(campaign, index) in items"
          :key="campaign.href + campaign.name"
          class="stage-layer"
          :class="{ 'is-active': index === active, 'is-previous': index === previous }"
          :src="campaign.image"
          alt=""
          width="1200"
          height="900"
          loading="lazy"
        >
        <span class="stage-veil" aria-hidden="true" />
        <figcaption :key="active" class="stage-caption">
          <strong>{{ current.name }}</strong>
          <span v-if="current.description">{{ current.description }}</span>
          <a :href="links.generic(current.href)">Xem chi tiết <span aria-hidden="true">→</span></a>
        </figcaption>
        <span class="stage-timer" :class="{ 'is-running': items.length > 1 }" :key="`t-${active}`" aria-hidden="true" />
      </figure>

      <div class="flex flex-col max-[960px]:order-1">
        <h2 id="home-campaign-title" class="m-0 max-w-[14ch] text-[clamp(30px,3vw,50px)] font-black leading-[1.06] tracking-[-.035em] text-navy-950">
          Chiếu sáng phù hợp từng không gian
        </h2>
        <p class="mt-4 max-w-[40ch] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-600">
          Lựa chọn thiết bị theo khu vực sử dụng và điều kiện lắp đặt thực tế.
        </p>

        <ul class="scene-list m-0 mt-8 list-none p-0 max-[960px]:mt-6" aria-label="Không gian ứng dụng">
          <li v-for="(campaign, index) in items" :key="campaign.href + campaign.name">
            <button
              type="button"
              class="scene-item"
              :class="{ 'is-active': index === active }"
              :aria-pressed="index === active"
              @click="select(index)"
              @mouseenter="select(index)"
            >
              {{ campaign.name }}
            </button>
          </li>
        </ul>

        <a
          class="mt-auto inline-flex min-h-[52px] items-center justify-center self-start rounded-[4px] border border-gold-500 bg-gold-400 px-6 text-[15px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-500 max-[960px]:hidden"
          href="#yeu-cau-tu-van"
          @click="leadIntent = 'Đèn năng lượng mặt trời'"
        >
          Tư vấn chọn đèn
        </a>
      </div>

      <a
        class="order-3 inline-flex min-h-[52px] items-center justify-center self-start justify-self-start rounded-[4px] border border-gold-500 bg-gold-400 px-6 text-[15px] font-extrabold text-navy-950 no-underline min-[961px]:hidden"
        href="#yeu-cau-tu-van"
        @click="leadIntent = 'Đèn năng lượng mặt trời'"
      >
        Tư vấn chọn đèn
      </a>
    </div>
  </section>
</template>

<style scoped>
.stage { min-height: clamp(360px, 38vw, 600px); isolation: isolate; }
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
  animation: stage-settle 6s ease-out forwards;
}
@keyframes stage-settle { from { transform: scale(1.06); } to { transform: scale(1); } }
.stage-veil {
  position: absolute;
  inset: 0;
  z-index: 3;
  background: linear-gradient(180deg, transparent 40%, rgb(6 21 55 / 88%) 100%);
  pointer-events: none;
}
.stage-caption {
  position: absolute;
  left: clamp(20px, 2.6vw, 40px);
  right: clamp(20px, 2.6vw, 40px);
  bottom: clamp(22px, 2.6vw, 40px);
  z-index: 4;
  display: flex;
  max-width: 560px;
  flex-direction: column;
  gap: 8px;
  color: #fff;
  animation: caption-in 0.7s 0.35s cubic-bezier(0.22, 1, 0.36, 1) both;
}
@keyframes caption-in { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
.stage-caption strong { font-size: clamp(22px, 2vw, 32px); font-weight: 900; line-height: 1.12; letter-spacing: -0.02em; }
.stage-caption span { font-size: 15.5px; line-height: 1.6; color: rgb(226 232 240); }
.stage-caption a {
  margin-top: 6px;
  align-self: flex-start;
  border-bottom: 2px solid var(--color-gold-500);
  padding-bottom: 4px;
  font-size: 15px;
  font-weight: 800;
  color: var(--color-gold-300);
  text-decoration: none;
}
.stage-caption a:hover { color: #fff; }
.stage-caption a:focus-visible { outline: 2px solid #fff; outline-offset: 4px; }

/* Thin gold progress line along the top edge, restarts per slide */
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
.stage-timer.is-running { animation: stage-timer 6s linear forwards; }
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

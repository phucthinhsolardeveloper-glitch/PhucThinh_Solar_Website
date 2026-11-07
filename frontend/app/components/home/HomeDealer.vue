<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

defineProps<{ dealer: PublicHomePayload['dealer'] }>()
const { openDealer } = useStorefrontModal()
const leadIntent = useState<string>('public-home-lead-intent', () => '')

// Ordered north -> south; a schematic, deliberately not a geographic map.
const hubs = [
  { city: 'Đà Nẵng', y: 70 },
  { city: 'Gia Lai', y: 210 },
  { city: 'TP.HCM', y: 350 },
]
const benefits = [
  'Danh mục sản phẩm phù hợp nhu cầu thị trường.',
  'Hệ thống kho tại TP.HCM, Đà Nẵng và Gia Lai.',
  'Hỗ trợ tư vấn sản phẩm, kỹ thuật và bảo hành theo chính sách.',
]
const branches = [-45, -15, 15, 45]
</script>

<template>
  <section class="dealer relative overflow-hidden bg-navy-950 py-[clamp(64px,7vw,112px)] max-tb:py-8 text-[#d8e1ea]" aria-labelledby="home-dealer-title">
    <div class="dealer-wrap pts-container grid items-center gap-[clamp(36px,5vw,96px)] min-[961px]:grid-cols-[minmax(0,1fr)_minmax(380px,.9fr)]">
      <div class="dealer-copy">
        <h2 id="home-dealer-title" class="m-0 max-w-[15ch] text-[clamp(32px,3.4vw,56px)] font-black leading-[1.04] tracking-[-.035em] text-white">
          {{ dealer.title }}
        </h2>
        <p class="mt-5 max-w-[52ch] text-[clamp(16px,1.1vw,19px)] leading-[1.7] text-slate-300">
          {{ dealer.description }}
        </p>
        <ul class="dealer-benefits m-0 mt-7 grid list-none gap-3 p-0">
          <li v-for="item in benefits" :key="item" class="flex items-start gap-3 text-[15.5px] leading-[1.55]">
            <span class="mt-[9px] h-px w-5 flex-none bg-gold-400" aria-hidden="true" />
            {{ item }}
          </li>
        </ul>
        <div class="dealer-cta mt-9 flex flex-wrap items-center gap-x-7 gap-y-4">
          <button type="button" class="inline-flex min-h-[52px] items-center gap-2 rounded-btn border border-gold-500 bg-gold-400 px-6 text-[15px] font-extrabold text-navy-950 transition-colors hover:bg-gold-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-300" @click="openDealer()">
            Đăng ký làm đại lý <span aria-hidden="true">→</span>
          </button>
          <a class="m-lg-dark inline-flex min-h-11 items-center gap-2 border-b-2 border-white/40 pb-1 text-[15px] font-extrabold text-white no-underline transition-colors hover:border-gold-400 hover:text-gold-300" href="#yeu-cau-tu-van" @click="leadIntent = 'Hợp tác đại lý'">
            Tư vấn chính sách đại lý
          </a>
        </div>
      </div>

      <figure class="dealer-map m-0" aria-labelledby="dealer-network-caption">
        <svg class="h-auto w-full" viewBox="0 0 440 420" role="img" aria-labelledby="dealer-network-title">
          <title id="dealer-network-title">Sơ đồ ba kho Đà Nẵng, Gia Lai, TP.HCM cấp hàng cho đại lý toàn quốc</title>
          <defs>
            <linearGradient id="spine" gradientUnits="userSpaceOnUse" x1="0" y1="70" x2="0" y2="350">
              <stop offset="0" stop-color="#e0b654" /><stop offset="1" stop-color="#c9962e" />
            </linearGradient>
            <filter id="hub-glow" x="-100%" y="-100%" width="300%" height="300%"><feGaussianBlur stdDeviation="6" /></filter>
          </defs>

          <!-- Spine between hubs -->
          <path d="M120 70 V350" stroke="url(#spine)" stroke-width="2.5" />
          <path class="spine-pulse" d="M120 70 V350" stroke="#fff3d6" stroke-width="3" stroke-linecap="round" />

          <!-- Branches fanning out to dealers -->
          <g v-for="hub in hubs" :key="`b-${hub.city}`">
            <path
              v-for="(offset, index) in branches"
              :key="offset"
              class="branch"
              :style="{ animationDelay: `${index * 0.35 + hub.y / 200}s` }"
              :d="`M120 ${hub.y} C 210 ${hub.y}, 260 ${hub.y + offset}, 388 ${hub.y + offset}`"
              fill="none"
              stroke="#e0b654"
              stroke-opacity=".38"
              stroke-width="1.2"
            />
            <circle v-for="offset in branches" :key="`d-${offset}`" cx="392" :cy="hub.y + offset" r="3.2" fill="#0a2251" stroke="#e0b654" stroke-width="1.2" />
          </g>

          <!-- Hubs -->
          <g v-for="hub in hubs" :key="hub.city">
            <circle cx="120" :cy="hub.y" r="16" fill="#e0b654" opacity=".45" filter="url(#hub-glow)" class="hub-glow" />
            <circle cx="120" :cy="hub.y" r="9" fill="#e0b654" />
            <circle cx="120" :cy="hub.y" r="15" fill="none" stroke="#e0b654" stroke-opacity=".5" />
            <text x="96" :y="hub.y - 6" text-anchor="end" class="hub-label">Kho</text>
            <text x="96" :y="hub.y + 14" text-anchor="end" class="hub-city">{{ hub.city }}</text>
          </g>
        </svg>
        <figcaption id="dealer-network-caption" class="mt-3 text-right text-[13.5px] font-semibold text-slate-400">
          Ba kho cấp hàng, giao đến đại lý trên toàn quốc
        </figcaption>
      </figure>
    </div>
  </section>
</template>

<style scoped>
.hub-label { fill: #94a3b8; font-size: 12px; font-weight: 700; }
.hub-city { fill: #fff; font-size: 17px; font-weight: 900; letter-spacing: -0.01em; }

.spine-pulse {
  stroke-dasharray: 18 262;
  animation: spine-run 3.2s cubic-bezier(0.45, 0, 0.55, 1) infinite;
}
@keyframes spine-run { from { stroke-dashoffset: 18; } to { stroke-dashoffset: -280; } }

.branch {
  stroke-dasharray: 4 7;
  animation: branch-flow 2.4s linear infinite;
}
@keyframes branch-flow { to { stroke-dashoffset: -44; } }

.hub-glow { animation: hub-breathe 3.2s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
@keyframes hub-breathe { 50% { opacity: 0.85; transform: scale(1.35); } }

@media (prefers-reduced-motion: reduce) {
  .spine-pulse { display: none; }
  .branch, .hub-glow { animation: none; }
}

/* Phones: the network diagram moves up beside the promise and stays fully visible; benefits and
   the two actions follow. SVG labels grow because the whole drawing scales down. */
@media (max-width: 820px) {
  .dealer-wrap { grid-template-columns: minmax(0, 1fr); gap: 14px; }
  .dealer-copy { display: contents; }
  .dealer-copy > h2 { max-width: none; font-size: 28px; line-height: 1.12; }
  .dealer-copy > p { max-width: none; margin-top: -6px; font-size: 15px; line-height: 1.55; }
  .dealer-map { order: 1; min-width: 0; }
  .dealer-map svg { display: block; max-width: 340px; margin: -8px auto -12px; }
  .dealer-map figcaption { margin-top: 0; text-align: center; font-size: 13px; line-height: 1.4; }
  .hub-label { font-size: 15px; }
  .hub-city { font-size: 21px; }
  .dealer-benefits { order: 2; margin-top: 0; gap: 8px; }
  .dealer-benefits > li { gap: 10px; font-size: 14.5px; line-height: 1.45; }
  .dealer-benefits > li > span { width: 14px; }
  .dealer-cta { order: 3; display: grid; grid-template-columns: minmax(0, 1fr); margin-top: 2px; gap: 8px; }
  .dealer-cta > * { justify-content: center; width: 100%; }
  .dealer-cta > a { min-height: 48px; padding-bottom: 0; border-radius: var(--radius-btn); }
}
</style>

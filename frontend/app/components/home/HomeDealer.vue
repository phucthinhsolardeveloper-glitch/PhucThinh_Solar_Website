<script setup lang="ts">
import DealerNetworkMap from './DealerNetworkMap.vue'
import type { PublicHomePayload } from '#shared/contracts/public-home'

defineProps<{ dealer: PublicHomePayload['dealer'] }>()
const { openDealer } = useStorefrontModal()
const leadIntent = useState<string>('public-home-lead-intent', () => '')

// Used until Admin has its own benefit lines.
const stockBenefits = [
  'Danh mục sản phẩm phù hợp nhu cầu thị trường.',
  'Hệ thống kho tại TP.HCM, Đà Nẵng và Gia Lai.',
  'Hỗ trợ tư vấn sản phẩm, kỹ thuật và bảo hành theo chính sách.',
]
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
          <li v-for="item in dealer.benefits ?? stockBenefits" :key="item" class="flex items-start gap-3 text-[15.5px] leading-[1.55]">
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

      <DealerNetworkMap />
    </div>
  </section>
</template>

<style scoped>
/* Phones: the network diagram moves up beside the promise and stays fully visible; benefits and
   the two actions follow. SVG labels grow because the whole drawing scales down. */
@media (max-width: 820px) {
  .dealer-wrap { grid-template-columns: minmax(0, 1fr); gap: 14px; }
  .dealer-copy { display: contents; }
  .dealer-copy > h2 { max-width: none; font-size: 28px; line-height: 1.12; }
  .dealer-copy > p { max-width: none; margin-top: -6px; font-size: 15px; line-height: 1.55; }
  .dealer-map { order: 1; min-width: 0; }
  .dealer-benefits { order: 2; margin-top: 0; gap: 8px; }
  .dealer-benefits > li { gap: 10px; font-size: 14.5px; line-height: 1.45; }
  .dealer-benefits > li > span { width: 14px; }
  .dealer-cta { order: 3; display: grid; grid-template-columns: minmax(0, 1fr); margin-top: 2px; gap: 8px; }
  .dealer-cta > * { justify-content: center; width: 100%; }
  .dealer-cta > a { min-height: 48px; padding-bottom: 0; border-radius: var(--radius-btn); }
}
</style>

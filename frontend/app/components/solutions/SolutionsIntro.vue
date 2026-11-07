<script setup lang="ts">
import type { PublicSolutionsIntro } from '../../../shared/contracts/public-solutions'

defineProps<{ intro: PublicSolutionsIntro }>()
const links = useMigrationLinks()
</script>

<template>
  <section aria-labelledby="solutions-title">
    <div v-if="intro.image" class="grid grid-cols-1 items-center gap-6 min-[801px]:grid-cols-2 min-[801px]:gap-12 max-[1100px]:min-[801px]:gap-[30px]">
      <figure class="order-2 m-0 aspect-[8/5] overflow-hidden rounded-chip min-[801px]:order-1">
        <img :src="optimizedImage(intro.image, 1200, 750)" :alt="intro.title" class="h-full w-full object-cover" width="1200" height="750" fetchpriority="high">
      </figure>
      <div class="order-1 min-w-0 min-[801px]:order-2">
        <h1 id="solutions-title" class="m-0 mb-[18px] text-[clamp(34px,3.2vw,48px)] font-black leading-[1.18] tracking-[-0.035em] text-[#071b46] [text-wrap:balance] max-[800px]:text-[32px] max-[800px]:leading-[40.12px] max-[520px]:text-[clamp(32px,8.5vw,39px)] max-[520px]:leading-[1.18]">{{ intro.title }}</h1>
        <p class="m-0 text-[18px] leading-[1.7] text-[#52627a] max-[520px]:text-[17px] max-[520px]:leading-[1.8]">{{ intro.description }}</p>
        <div class="mt-[27px] flex items-center gap-6 max-[520px]:flex-col max-[520px]:items-stretch max-[520px]:gap-0">
          <a class="solutions-intro-primary inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-gold-500 px-[22px] py-[13px] text-[15px] leading-[1.75] font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400 max-[520px]:leading-[1.66]" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="inline-flex min-h-11 items-center text-[15px] font-extrabold text-navy-800 no-underline hover:text-gold-700" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 items-start gap-0 min-[801px]:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)] min-[801px]:gap-x-[72px] min-[801px]:gap-y-0">
      <!-- The PHP hub uses this same title as its only visible H1. -->
      <h1 id="solutions-title" class="m-0 mb-[18px] max-w-[600px] text-[clamp(34px,3.2vw,48px)] font-black leading-[1.18] tracking-[-0.035em] text-[#071b46] [text-wrap:balance] max-[800px]:max-w-none max-[800px]:text-[32px] max-[800px]:leading-[40.12px] max-[520px]:text-[clamp(32px,8.5vw,39px)] max-[520px]:leading-[1.18] min-[801px]:row-span-2 min-[801px]:mb-0">{{ intro.title }}</h1>
      <p class="m-0 text-[18px] leading-[1.7] text-[#52627a] max-[520px]:text-[17px] max-[520px]:leading-[1.8] min-[801px]:col-start-2 min-[801px]:row-start-1">{{ intro.description }}</p>
      <div class="mt-[27px] flex items-center gap-6 max-[520px]:flex-col max-[520px]:items-stretch max-[520px]:gap-0 min-[801px]:col-start-2 min-[801px]:row-start-2">
        <a class="solutions-intro-primary inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-gold-500 px-[22px] py-[13px] text-[15px] leading-[1.75] font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400 max-[520px]:leading-[1.66]" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
          {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
        </a>
        <a class="inline-flex min-h-11 items-center text-[15px] font-extrabold text-navy-800 no-underline hover:text-gold-700" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
      </div>
    </div>
  </section>
</template>

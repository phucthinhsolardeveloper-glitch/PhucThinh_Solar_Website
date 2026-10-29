<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'

defineProps<{ cards: PublicSolutionCard[]; choicesLabel: string; emptyLabel: string; consultLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <!--
    PHP (templates/news/service_list_tpl.php) chỉ dùng $solutionUi['choices']
    làm aria-label trên <section>, không render heading chữ nào phía trên
    lưới card khi có dữ liệu — mỗi card tự có <h2> riêng là tiêu đề của nó.
    Chỉ hiện text này như tiêu đề thật khi danh sách rỗng (PHP lúc đó không
    có section này để so, nên giữ emptyLabel hiển thị cho preview không trống
    trơn).
  -->
  <section id="cac-giai-phap" class="pb-[54px]" :aria-label="cards.length ? choicesLabel : undefined" :aria-labelledby="cards.length ? undefined : 'solutions-choices-title'">
    <h2 v-if="!cards.length" id="solutions-choices-title" class="m-0 mb-[22px] text-[28px] leading-[1.25] font-black text-navy-800">
      {{ emptyLabel }}
    </h2>

    <div v-if="cards.length" class="flex gap-4 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden nav:grid nav:gap-5 nav:overflow-visible" :class="cards.length === 1 ? 'nav:grid-cols-1' : 'nav:grid-cols-5'">
      <a
        v-for="card in cards"
        :key="card.slug"
        class="flex min-w-0 flex-none basis-[min(78vw,286px)] flex-col text-navy-800 no-underline nav:basis-auto"
        :class="cards.length === 1 ? 'nav:w-full nav:flex-row nav:gap-6' : ''"
        :href="links.solution(card.slug)"
      >
        <div v-if="card.image" class="flex aspect-[8/5] items-center justify-center overflow-hidden rounded-chip" :class="cards.length === 1 ? 'nav:w-1/2 nav:max-w-[560px] nav:flex-none' : ''">
          <img :src="card.image" alt="" class="h-full w-full object-cover" width="600" height="375" loading="lazy" decoding="async">
        </div>
        <div class="flex flex-1 flex-col items-start pt-[17px]" :class="cards.length === 1 ? 'nav:justify-center nav:pt-0' : ''">
          <h3 class="m-0 mb-2.5 min-h-[47px] text-lg leading-[1.3]">{{ card.title }}</h3>
          <p v-if="card.description" class="m-0 mb-3 line-clamp-3 min-h-[69px] text-sm leading-[1.62] text-slate-500">{{ card.description }}</p>
          <span class="mt-auto flex w-full items-center justify-between gap-2.5 text-[13px] font-extrabold">
            {{ card.actionLabel }}
            <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-hdr-gold text-navy-950" aria-hidden="true">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
            </span>
          </span>
        </div>
      </a>
    </div>
    <a v-else class="inline-flex min-h-11 items-center font-extrabold text-navy-800 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ consultLabel }} →</a>
  </section>
</template>

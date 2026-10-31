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
  <section id="cac-giai-phap" class="pt-[42px] pb-[54px] max-[520px]:pt-[26px] max-[520px]:pb-[26px]" :aria-label="cards.length ? choicesLabel : undefined" :aria-labelledby="cards.length ? undefined : 'solutions-choices-title'">
    <h2 v-if="!cards.length" id="solutions-choices-title" class="m-0 mb-[22px] text-[28px] leading-[1.25] font-black text-navy-800">
      {{ emptyLabel }}
    </h2>

    <div v-if="cards.length" class="flex gap-4 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-[520px]:-mr-4 max-[520px]:pr-4 max-[359px]:-mr-3 max-[359px]:pr-3 min-[1101px]:grid min-[1101px]:gap-5 min-[1101px]:overflow-visible" :class="cards.length === 1 ? 'min-[1101px]:grid-cols-1' : 'min-[1101px]:grid-cols-5'">
      <a
        v-for="card in cards"
        :key="card.slug"
        class="group flex min-w-0 flex-none basis-[280px] flex-col overflow-hidden bg-transparent text-navy-800 no-underline min-[1101px]:basis-auto max-[520px]:basis-[min(78vw,286px)]"
        :class="cards.length === 1 ? 'min-[1101px]:w-full min-[1101px]:flex-row min-[1101px]:gap-6' : ''"
        :href="links.solution(card.slug)"
      >
        <div v-if="card.image" class="flex aspect-[8/5] items-center justify-center overflow-hidden rounded-[3px] bg-[#f3f6fa]" :class="cards.length === 1 ? 'min-[1101px]:w-1/2 min-[1101px]:max-w-[560px] min-[1101px]:flex-none' : ''">
        <img :src="card.image" alt="" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none" width="600" height="375" loading="lazy" decoding="async">
        </div>
        <div class="flex flex-1 flex-col items-start pt-[17px]" :class="cards.length === 1 ? 'min-[1101px]:justify-center min-[1101px]:pt-0' : ''">
          <h3 class="m-0 mb-[10px] min-h-[47px] text-[18px] font-extrabold leading-[1.3] tracking-[-0.015em] text-navy-950 max-[520px]:min-h-0">{{ card.title }}</h3>
          <p v-if="card.description" class="m-0 mb-3 line-clamp-3 min-h-[69px] text-sm leading-[1.62] text-slate-600 max-[520px]:min-h-0 max-[520px]:mb-[14px] max-[520px]:leading-[1.8]">{{ card.description }}</p>
          <span class="mt-auto flex w-full items-center justify-between gap-2.5 text-[13px] font-extrabold text-navy-950">
            {{ card.actionLabel }}
              <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-gold-500 text-navy-950 max-[520px]:h-[30px] max-[520px]:w-[30px]" aria-hidden="true">
              <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
            </span>
          </span>
        </div>
      </a>
    </div>
    <a v-else class="inline-flex min-h-11 items-center font-extrabold text-navy-800 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ consultLabel }} →</a>
  </section>
</template>

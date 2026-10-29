<script setup lang="ts">
import type { PublicSolutionsIntro } from '../../../shared/contracts/public-solutions'

defineProps<{ intro: PublicSolutionsIntro }>()
const links = useMigrationLinks()
</script>

<template>
  <section class="pb-[42px]" aria-labelledby="solutions-title">
    <div v-if="intro.image" class="grid grid-cols-1 items-center gap-8 tb:grid-cols-2 tb:gap-12">
      <figure class="m-0 aspect-[8/5] overflow-hidden rounded-chip">
        <img :src="intro.image" :alt="intro.title" class="h-full w-full object-cover" width="1200" height="750" fetchpriority="high">
      </figure>
      <div class="min-w-0">
        <h1 id="solutions-title" class="m-0 mb-5 text-[clamp(28px,2.9vw,42px)] font-black leading-[1.22] tracking-tight text-navy-800">{{ intro.title }}</h1>
        <p class="m-0 text-[17px] leading-relaxed text-slate-500">{{ intro.description }}</p>
        <div class="mt-[27px] flex flex-col items-stretch gap-[15px] ph:flex-row ph:items-center ph:gap-6">
          <a class="inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-hdr-gold px-[22px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="inline-flex min-h-11 items-center justify-center text-center font-extrabold text-navy-800 no-underline ph:justify-start ph:text-left" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 items-start gap-8 tb:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)] tb:gap-[72px]">
      <div>
        <!--
          PHP (sources/solution_hub.php) tính $solutionHeroEyebrow nhưng
          templates/news/service_list_tpl.php không bao giờ in nó ra - đây là
          field chết ở PHP. Không render để khớp bố cục thật, dù API vẫn trả
          giá trị (giữ nguyên contract, chỉ ẩn ở UI).
        -->
        <h1 id="solutions-title" class="m-0 text-[clamp(28px,2.9vw,42px)] font-black leading-[1.22] tracking-tight text-navy-800">{{ intro.title }}</h1>
      </div>

      <div class="min-w-0">
        <p class="m-0 text-[17px] leading-relaxed text-slate-500">{{ intro.description }}</p>
        <div class="mt-[27px] flex flex-col items-stretch gap-[15px] ph:flex-row ph:items-center ph:gap-6">
          <a class="inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-hdr-gold px-[22px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="inline-flex min-h-11 items-center justify-center text-center font-extrabold text-navy-800 no-underline ph:justify-start ph:text-left" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>
  </section>
</template>

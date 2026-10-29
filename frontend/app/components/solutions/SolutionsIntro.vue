<script setup lang="ts">
import type { PublicSolutionsIntro } from '../../../shared/contracts/public-solutions'

defineProps<{ intro: PublicSolutionsIntro }>()
const links = useMigrationLinks()
</script>

<template>
  <section class="pb-[42px]" aria-labelledby="solutions-title">
    <div v-if="intro.image" class="grid grid-cols-1 items-center gap-8 tb:grid-cols-2 tb:gap-10 rounded-btn border border-navy-800/15 bg-gradient-to-br from-navy-950 via-navy-800 to-navy-900 p-6 text-white shadow-md tb:p-10">
      <figure class="m-0 aspect-[8/5] overflow-hidden rounded-chip border border-white/10">
        <img :src="intro.image" :alt="intro.title" class="h-full w-full object-cover" width="1200" height="750" fetchpriority="high">
      </figure>
      <div class="min-w-0">
        <h1 id="solutions-title" class="m-0 mb-5 text-[clamp(28px,2.9vw,42px)] font-black leading-[1.2] tracking-tight text-white">{{ intro.title }}</h1>
        <p class="m-0 text-base leading-[1.72] text-blue-100/95">{{ intro.description }}</p>
        <div class="mt-6 flex flex-col items-stretch gap-3.5 border-t border-white/15 pt-5 ph:flex-row ph:items-center ph:gap-4">
          <a class="inline-flex min-h-12 items-center justify-center gap-4 rounded-btn bg-hdr-gold px-6 text-sm font-extrabold text-navy-950 no-underline shadow-sm transition-colors hover:bg-hdr-gold-hover" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="inline-flex min-h-11 items-center justify-center rounded-btn border border-white/30 bg-white/[.04] px-5 text-center text-sm font-extrabold text-white no-underline transition-colors hover:border-gold-400 hover:bg-white/10 ph:justify-start ph:text-left" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 items-start gap-8 tb:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)] tb:gap-12 rounded-btn border border-navy-800/15 bg-gradient-to-br from-navy-950 via-navy-800 to-navy-900 p-7 text-white shadow-md tb:p-10">
      <div>
        <!--
          PHP (sources/solution_hub.php) tính $solutionHeroEyebrow nhưng
          templates/news/service_list_tpl.php không bao giờ in nó ra - đây là
          field chết ở PHP. Không render để khớp bố cục thật, dù API vẫn trả
          giá trị (giữ nguyên contract, chỉ ẩn ở UI).
        -->
        <h1 id="solutions-title" class="m-0 text-[clamp(28px,2.9vw,42px)] font-black leading-[1.2] tracking-tight text-white">{{ intro.title }}</h1>
      </div>

      <div class="min-w-0">
        <p class="m-0 text-base leading-[1.72] text-blue-100/95">{{ intro.description }}</p>
        <div class="mt-6 flex flex-col items-stretch gap-3.5 border-t border-white/15 pt-5 ph:flex-row ph:items-center ph:gap-4">
          <a class="inline-flex min-h-12 items-center justify-center gap-4 rounded-btn bg-hdr-gold px-6 text-sm font-extrabold text-navy-950 no-underline shadow-sm transition-colors hover:bg-hdr-gold-hover" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="inline-flex min-h-11 items-center justify-center rounded-btn border border-white/30 bg-white/[.04] px-5 text-center text-sm font-extrabold text-white no-underline transition-colors hover:border-gold-400 hover:bg-white/10 ph:justify-start ph:text-left" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>
  </section>
</template>

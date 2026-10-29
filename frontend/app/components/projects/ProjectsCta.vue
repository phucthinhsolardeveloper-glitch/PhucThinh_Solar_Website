<script setup lang="ts">
import type { PublicProjectsCta } from '#shared/contracts/public-projects'

defineProps<{ cta: PublicProjectsCta; label: string }>()
const links = useMigrationLinks()
</script>

<template>
  <section class="mt-16 grid grid-cols-1 items-center gap-7 rounded-chip bg-navy-950 p-7 text-white" :class="cta.image ? 'nav:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]' : ''">
    <img v-if="cta.image" :src="cta.image" alt="" class="aspect-[16/5] w-full rounded-chip object-cover nav:aspect-auto nav:h-full" loading="lazy" decoding="async">
    <div>
      <h2 class="mt-0 mb-2 text-[clamp(22px,3vw,32px)] font-black leading-tight">{{ cta.title }}</h2>
      <p v-if="cta.description" class="mb-4 leading-relaxed text-blue-100">{{ cta.description }}</p>
      <a class="mb-2 mr-[18px] mt-3 inline-block font-extrabold text-gold-400 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ cta.buttonLabel || label }}</a>
      <a v-if="cta.hotline?.tel" class="mb-2 mt-3 inline-block font-extrabold text-white no-underline" :href="`tel:${cta.hotline.tel}`">{{ cta.hotline.text }}</a>
    </div>
  </section>
</template>

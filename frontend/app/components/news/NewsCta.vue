<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'

defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <section class="mt-[58px] grid grid-cols-1 items-center gap-7 rounded-chip bg-navy-800 p-7 text-white" :class="cta.image ? 'nav:grid-cols-[minmax(140px,28%)_minmax(0,1fr)]' : ''">
    <img v-if="cta.image" :src="cta.image" alt="" class="w-full max-h-[190px] rounded-chip object-cover" loading="lazy" decoding="async">
    <div>
      <h2 class="mb-2 text-[clamp(22px,3vw,32px)] font-black leading-tight">{{ cta.title }}</h2>
      <p v-if="cta.description" class="mb-4 max-w-[58ch] leading-relaxed text-blue-100">{{ cta.description }}</p>
      <a class="mb-2 mr-[18px] inline-block font-bold text-gold-400 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">
        {{ cta.buttonLabel || 'Nhận tư vấn ngay' }} <span aria-hidden="true">→</span>
      </a>
      <a v-if="cta.hotline?.tel" class="mb-2 inline-block font-bold text-white no-underline" :href="`tel:${cta.hotline.tel}`">
        {{ phoneLabel }}: {{ cta.hotline.text }}
      </a>
    </div>
  </section>
</template>

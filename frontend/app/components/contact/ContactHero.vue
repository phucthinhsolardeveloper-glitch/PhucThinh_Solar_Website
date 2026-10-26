<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
defineProps<{ contact: PublicContactPayload }>()
</script>
<template>
  <section class="grid overflow-hidden rounded-[3px] bg-navy-800 nav:grid-cols-[minmax(0,1.78fr)_minmax(360px,1fr)]" aria-labelledby="contact-preview-title">
    <div v-if="contact.visual" class="relative min-w-0 bg-navy-50 nav:aspect-[8/5]">
      <img class="block h-full w-full object-cover" :src="contact.visual.image" :alt="contact.visual.name" width="1400" height="875">
      <a v-if="contact.contact.hotline" class="absolute bottom-4 left-4 flex min-h-14 items-center gap-3 rounded-[3px] bg-white px-4 py-2 text-navy-800 shadow-lg" :href="`tel:${contact.contact.hotline.replace(/[^+\d]/g, '')}`">
        <span class="grid size-9 place-items-center rounded-full bg-gold-500 text-navy-950"><UIcon name="i-lucide-phone" class="size-4" /></span>
        <span class="grid text-[11px] font-semibold uppercase tracking-wide text-navy-900/55">Hotline hỗ trợ<strong class="text-base font-extrabold tracking-normal text-navy-800">{{ contact.contact.hotline }}</strong></span>
      </a>
    </div>
    <div class="flex flex-col justify-center bg-navy-800 px-6 py-10 text-white tb:px-10 nav:px-14">
      <h1 id="contact-preview-title" class="max-w-xl text-[clamp(2rem,3vw,3.5rem)] font-extrabold leading-[1.08] tracking-[-0.03em]">{{ contact.visual?.name || contact.title }}</h1>
      <p v-if="contact.visual?.description" class="mt-5 max-w-lg text-base leading-7 text-white/78">{{ contact.visual.description }}</p>
      <p v-if="contact.contact.company" class="mt-6 max-w-lg font-bold leading-7">{{ contact.contact.company }}</p>
      <dl class="mt-6 grid gap-4 text-sm leading-6 text-white/75">
        <div v-if="contact.contact.hotline"><dt class="inline">Hotline: </dt><dd class="inline"><a class="font-bold text-gold-400 hover:text-gold-300" :href="`tel:${contact.contact.hotline.replace(/[^+\d]/g, '')}`">{{ contact.contact.hotline }}</a></dd></div>
        <div v-if="contact.contact.email"><dt class="inline">Email: </dt><dd class="inline"><a class="font-bold text-gold-400 hover:text-gold-300" :href="`mailto:${contact.contact.email}`">{{ contact.contact.email }}</a></dd></div>
        <div v-if="contact.contact.workingHours"><dt class="inline">Thời gian làm việc: </dt><dd class="inline font-bold text-gold-400">{{ contact.contact.workingHours }}</dd></div>
      </dl>
      <a class="mt-7 inline-flex min-h-11 w-fit items-center gap-2 rounded-[3px] bg-gold-400 px-5 py-3 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-300" :href="contact.visual?.href || '#yeu-cau-tu-van'">Gửi yêu cầu tư vấn <UIcon name="i-lucide-arrow-down" class="size-4" /></a>
    </div>
  </section>
</template>

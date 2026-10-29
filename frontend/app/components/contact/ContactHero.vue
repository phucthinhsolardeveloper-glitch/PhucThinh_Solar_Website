<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'

defineProps<{ contact: PublicContactPayload }>()
const links = useMigrationLinks()
</script>

<template>
  <!-- Ảnh và panel cùng chiều cao, liền mép như dải hero trong contact-v3.css. -->
  <section class="mt-4 mb-10 grid grid-cols-1 overflow-hidden rounded-btn bg-navy-950 max-[767px]:-mx-4 max-[767px]:-mt-2 max-[767px]:rounded-none min-[1101px]:grid-cols-[minmax(0,1.28fr)_minmax(390px,.72fr)]" aria-labelledby="contact-preview-title">
    <div v-if="contact.visual" class="relative min-w-0 aspect-[8/5] min-[1101px]:aspect-auto min-[1101px]:min-h-[460px]">
      <img
        :src="contact.visual.image"
        :alt="contact.visual.name"
        class="absolute inset-0 block h-full w-full object-cover"
        width="1400"
        height="875"
      >
      <!--
        Badge hotline nổi góc dưới trái của ẢNH (không phải góc panel navy).
        Đo trên ảnh: nền trắng, icon tròn gold, không phải nền navy-deep như
        CSS preview cũ mô tả — đối chiếu lại contact-v3.css xác nhận đây đúng
        là thiết kế hiện hành, preview cũ lấy nhầm từ bản trước đó.
      -->
      <span
        v-if="contact.contact.hotline"
        class="absolute bottom-4 left-4 flex items-center gap-3 rounded-chip bg-white px-4 py-3 shadow-[0_10px_24px_rgba(6,21,55,.18)] min-[1101px]:bottom-6 min-[1101px]:left-6"
      >
        <!-- Đo pixel: vòng tròn gold-500 #c9962e, icon navy-950 #061537 (không phải trắng). -->
        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
        </span>
        <span class="flex flex-col leading-tight">
          <small class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Hotline hỗ trợ</small>
          <strong class="text-base font-bold text-navy-800">{{ contact.contact.hotline }}</strong>
        </span>
      </span>
    </div>

    <div class="flex min-w-0 flex-col justify-center bg-[linear-gradient(145deg,var(--color-navy-950),var(--color-navy-700))] px-4 py-8 text-white min-[768px]:px-8 min-[1101px]:p-[clamp(38px,4vw,70px)]">
      <h1 id="contact-preview-title" class="m-0 mb-5 text-[30px] font-black leading-[1.12] tracking-[-.035em] min-[768px]:text-[clamp(32px,3vw,50px)]">
        {{ contact.visual?.name || contact.title }}
      </h1>
      <p v-if="contact.visual?.description" class="m-0 mb-5 text-base leading-[1.72] text-blue-100">
        {{ contact.visual.description }}
      </p>
      <p v-if="contact.contact.company" class="m-0 mb-5 text-[15px] leading-[1.65] text-white">{{ contact.contact.company }}</p>
      <div class="mb-6 grid gap-2.5 text-sm leading-normal text-blue-100">
        <a v-if="contact.contact.hotline" class="text-inherit no-underline" :href="`tel:${contact.contact.hotline.replace(/[^+\d]/g, '')}`">Hotline: <strong class="text-gold-300">{{ contact.contact.hotline }}</strong></a>
        <a v-if="contact.contact.email" class="text-inherit no-underline" :href="`mailto:${contact.contact.email}`">Email: <strong class="text-gold-300">{{ contact.contact.email }}</strong></a>
        <span v-if="contact.contact.workingHours">Thời gian làm việc: <strong class="text-gold-300">{{ contact.contact.workingHours }}</strong></span>
      </div>
      <a
        v-if="contact.visual?.href"
        class="mt-2 inline-flex items-center gap-2.5 self-start rounded-chip bg-hdr-gold px-4 py-3 font-bold
               text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover"
        :href="links.generic(contact.visual.href)"
      >
        Gửi yêu cầu tư vấn <span aria-hidden="true">↓</span>
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

defineProps<{ contact: PublicHomePayload['contact'] }>()
</script>

<template>
  <section
    id="yeu-cau-tu-van"
    class="bg-hdr-surface py-[clamp(64px,6vw,92px)] pb-[clamp(88px,7vw,108px)]"
    aria-labelledby="home-contact-title"
  >
    <div class="mx-auto w-[min(calc(100%-32px),2200px)]">
      <header class="mb-10 max-w-[760px]">
        <h2 id="home-contact-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950">
          Bạn đang cần giải pháp nào?
        </h2>
        <p class="mt-[13px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">
          Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ trao đổi với bạn.
        </p>
      </header>

      <div class="grid grid-cols-[minmax(310px,.72fr)_minmax(0,1.28fr)] overflow-hidden rounded-[4px] bg-white max-md:grid-cols-1">
        <div class="bg-navy-950 p-[clamp(30px,3.2vw,48px)] text-[#d4dee8]">
          <h3 class="m-0 text-2xl font-bold text-white">Thông tin liên hệ</h3>
          <p class="mt-3 leading-[1.65] text-[#d4dee8]">Đội ngũ Phúc Thịnh Solar phản hồi trong giờ làm việc.</p>

          <a
            v-if="contact.hotline"
            class="my-[18px] flex flex-col gap-0.5 text-white no-underline"
            :href="`tel:${contact.hotline.replace(/[^+\d]/g, '')}`"
          >
            <small class="text-xs text-[#9fb0c1]">Hotline</small>
            <strong>{{ contact.hotline }}</strong>
          </a>
          <a v-if="contact.email" class="my-[18px] flex flex-col gap-0.5 text-white no-underline" :href="`mailto:${contact.email}`">
            <small class="text-xs text-[#9fb0c1]">Email</small>
            <strong>{{ contact.email }}</strong>
          </a>
          <p v-if="contact.address" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Địa chỉ</small>
            <strong>{{ contact.address }}</strong>
          </p>
          <p v-if="contact.workingHours" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Giờ làm việc</small>
            <strong>{{ contact.workingHours }}</strong>
          </p>
        </div>

        <form class="p-[clamp(30px,3.2vw,48px)]" @submit.prevent>
          <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
            Họ và tên <span>*</span>
            <input class="mt-2 block w-full rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit" type="text" autocomplete="name" required>
          </label>
          <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
            Số điện thoại <span>*</span>
            <input class="mt-2 block w-full rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit" type="tel" autocomplete="tel" required>
          </label>
          <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
            Nội dung cần trao đổi <span>*</span>
            <textarea class="mt-2 block w-full resize-y rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit" rows="4" required placeholder="Mô tả ngắn nhu cầu của bạn" />
          </label>
          <button class="inline-flex min-h-[50px] items-center justify-center rounded-[4px] border border-hdr-gold bg-hdr-gold px-[25px] font-bold text-navy-950" type="submit">
            Gửi yêu cầu tư vấn
          </button>
          <small class="mt-3 block text-slate-500">Thông tin chỉ được sử dụng để liên hệ tư vấn.</small>
        </form>
      </div>
    </div>
  </section>
</template>

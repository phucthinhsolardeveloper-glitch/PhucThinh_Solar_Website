<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'

defineProps<{ labels: PublicContactPayload['labels'] }>()
const interest = ref('')
const interestItems = ['Đèn năng lượng mặt trời', 'Điện mặt trời', 'Thiết bị (inverter, tấm pin, pin lưu trữ...)', 'Hợp tác đại lý', 'Khác']

function continueOnPhp() {
  if (import.meta.client) window.location.assign('/lien-he/#yeu-cau-tu-van')
}
</script>

<template>
  <section id="yeu-cau-tu-van" class="my-10 grid gap-8 rounded-[3px] bg-navy-50 px-5 py-10 tb:px-10 nav:grid-cols-[260px_minmax(0,1fr)] nav:gap-16 nav:px-16 nav:py-16" aria-labelledby="contact-form-title">
    <div>
      <h2 id="contact-form-title" class="max-w-[190px] text-4xl font-extrabold uppercase leading-[1.08] tracking-[-0.03em] text-navy-800">Đăng ký nhận tư vấn</h2>
      <p class="mt-5 leading-7 text-navy-900/60">Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ để tư vấn giải pháp phù hợp.</p>
    </div>
    <form class="grid gap-5 tb:grid-cols-2" @submit.prevent="continueOnPhp">
      <div><label class="mb-2 block text-sm font-bold text-navy-950" for="contact-fullname">Họ và tên <span class="text-red-700">*</span></label><UInput id="contact-fullname" class="w-full" size="xl" placeholder="Nguyễn Văn A" autocomplete="name" required /></div>
      <div><label class="mb-2 block text-sm font-bold text-navy-950" for="contact-phone">Số điện thoại <span class="text-red-700">*</span></label><UInput id="contact-phone" class="w-full" size="xl" type="tel" inputmode="tel" placeholder="090xxxxxxx" autocomplete="tel" required /></div>
      <div><label class="mb-2 block text-sm font-bold text-navy-950" for="contact-email">Email <small class="font-normal text-navy-900/45">(không bắt buộc)</small></label><UInput id="contact-email" class="w-full" size="xl" type="email" placeholder="abc@gmail.com" autocomplete="email" /></div>
      <div><label class="mb-2 block text-sm font-bold text-navy-950" for="contact-interest">Anh/chị quan tâm đến <small class="font-normal text-navy-900/45">(không bắt buộc)</small></label><USelect id="contact-interest" v-model="interest" class="w-full" size="xl" :items="interestItems" placeholder="-- Chọn nhu cầu --" /></div>
      <div class="tb:col-span-2"><label class="mb-2 block text-sm font-bold text-navy-950" for="contact-content">Nhu cầu cần tư vấn <span class="text-red-700">*</span></label><UTextarea id="contact-content" class="w-full" :rows="8" autoresize :maxrows="12" placeholder="Ví dụ: nhu cầu chiếu sáng sân vườn, điện mặt trời cho gia đình..." required /></div>
      <div class="tb:col-span-2"><UButton type="submit" class="min-h-12 min-w-64 justify-center" size="xl" color="primary">Gửi yêu cầu tư vấn</UButton><p class="mt-3 text-xs leading-5 text-navy-900/50">{{ labels.formNote }}</p></div>
    </form>
  </section>
</template>

<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'

defineProps<{ contact: PublicContactPayload }>()
</script>

<template>
  <!--
    Tỉ lệ 1.3fr/0.7fr đo khớp ảnh production (/lien-he/, 1920px): panel navy
    bắt đầu tại x=1210 trong vùng nội dung 76..1842 (rộng 1766), tức ảnh rộng
    1134 / panel rộng 632 ~= 1.79, khớp đúng 1.3:0.7 = 1.857 trong sai số đo.

    Panel phải KHÔNG phải navy đặc mà là gradient 145deg từ navy-950 tới
    navy-700 (contact-v3.css: linear-gradient(145deg,var(--contact-navy),
    var(--contact-blue)); --contact-blue:#123c72 ~ navy-700 #123a7a, lệch 2
    đơn vị hex — cùng một token, không phải màu khác).
  -->
  <section class="grid grid-cols-1 items-center gap-8 py-8 nav:grid-cols-[1.3fr_0.7fr] nav:gap-[clamp(24px,5vw,80px)] nav:py-16" aria-labelledby="contact-preview-title">
    <div v-if="contact.visual" class="relative min-w-0">
      <img
        :src="contact.visual.image"
        :alt="contact.visual.name"
        class="block aspect-[8/5] w-full rounded-chip object-cover"
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
        class="absolute bottom-4 left-4 flex flex-col gap-2 rounded-chip bg-white px-4 py-3 shadow-[0_10px_24px_rgba(6,21,55,.18)]"
      >
        <span class="flex items-center gap-3">
          <!-- Đo pixel: vòng tròn gold-500 #c9962e, icon navy-950 #061537 (không phải trắng). -->
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
          </span>
          <span class="flex flex-col leading-tight">
            <small class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Hotline hỗ trợ</small>
            <strong class="text-base font-bold text-navy-800">{{ contact.contact.hotline }}</strong>
          </span>
        </span>
        <!--
          Hàng thứ hai: technicalHotline cùng field CMS đã dùng ở SiteFooter.vue
          (brand.technicalHotline, nguồn options.hotline_technical). Chỉ hiện
          khi API thật sự trả số, giữ đúng nguyên tắc "không bịa" của badge
          thứ nhất. Vị trí xếp dưới trong CÙNG badge chưa đo lại trên ảnh
          production riêng cho trang /lien-he/ (chỉ có ảnh cũ với một số) —
          đây là suy luận có cơ sở (tái dùng đúng field + layout đã xác minh ở
          footer), không phải đo pixel trực tiếp như hàng đầu.
        -->
        <span v-if="contact.contact.technicalHotline" class="flex items-center gap-3 border-t border-gray-100 pt-2">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-gold-500 text-navy-950">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a2 2 0 0 0-2 2v2.17a2 2 0 0 1-.59 1.42L7.59 9.41A2 2 0 0 0 7 10.83V13a2 2 0 0 0 2 2h.17a2 2 0 0 1 1.42.59l1.82 1.82a2 2 0 0 0 1.42.59H16a2 2 0 0 0 2-2v-2.17a2 2 0 0 1 .59-1.42L20 10.41" /></svg>
          </span>
          <span class="flex flex-col leading-tight">
            <small class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Hỗ trợ kỹ thuật</small>
            <strong class="text-base font-bold text-navy-800">{{ contact.contact.technicalHotline }}</strong>
          </span>
        </span>
      </span>
    </div>

    <div class="flex min-w-0 flex-col rounded-chip bg-[linear-gradient(145deg,var(--color-navy-950),var(--color-navy-700))] p-8 text-white nav:p-12">
      <h1 id="contact-preview-title" class="m-0 mb-5 text-[clamp(32px,4vw,56px)] font-black leading-[1.1]">
        {{ contact.visual?.name || contact.title }}
      </h1>
      <p v-if="contact.visual?.description" class="m-0 mb-4 text-lg leading-relaxed text-blue-100">
        {{ contact.visual.description }}
      </p>
      <p v-if="contact.contact.company" class="m-0 mb-4 font-bold text-blue-100">{{ contact.contact.company }}</p>
      <a
        class="mt-2 inline-flex items-center gap-2.5 self-start rounded-chip bg-hdr-gold px-4 py-3 font-bold
               text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover"
        href="/lien-he/"
      >
        {{ contact.labels.formCta }} <span aria-hidden="true">→</span>
      </a>
    </div>
  </section>
</template>

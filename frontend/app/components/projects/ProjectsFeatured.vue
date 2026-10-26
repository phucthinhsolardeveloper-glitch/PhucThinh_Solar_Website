<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; label: string; viewLabel: string }>()

/**
 * Ba gạch đầu dòng bảo chứng trong panel navy KHÔNG có trong DTO
 * (PublicProject chỉ có {title,slug,url,description,address,image,category,
 * featured}) — đây là nội dung quảng cáo CỐ ĐỊNH áp dụng cho bất kỳ dự án nổi
 * bật nào, không phải dữ liệu riêng từng dự án. Tương tự cách quickLinks của
 * trang Đại lý xử lý khung trang cố định khi DTO không có trường tương ứng.
 */
const trustBullets = [
  'Khảo sát và tư vấn thiết kế miễn phí',
  'Thiết bị chính hãng, thi công đúng kỹ thuật',
  'Bảo hành rõ ràng, hỗ trợ vận hành lâu dài',
]
</script>

<template>
  <!--
    Panel phải nền navy-800 đặc (không phải navy-deep/950 như CSS preview cũ
    khai — đo pixel bên trong panel cho giá trị gần đúng #0a2251). Số "01" góc
    trên phải là trang trí tĩnh: DTO chỉ có một `featured` duy nhất (không phải
    mảng), không phải số thứ tự động từ dữ liệu.
  -->
  <article class="mb-12 mt-2.5 grid grid-cols-1 bg-slate-50 nav:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.9fr)]">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#edf1f5] nav:aspect-auto" :class="{ 'grid place-items-center text-slate-500': !project.image }">
      <img v-if="project.image" :src="project.image" :alt="project.title" class="h-full w-full object-cover" loading="lazy" decoding="async">
      <span v-else aria-hidden="true">—</span>

      <span v-if="project.category" class="absolute left-3 top-3 flex items-center gap-1.5 rounded-chip bg-navy-800 px-2.5 py-1.5 text-[11px] font-bold text-white">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
        {{ project.category.name }}
      </span>
      <span class="absolute bottom-3 left-3 rounded-chip bg-gold-500 px-3 py-1.5 text-[11px] font-bold text-navy-950">
        {{ label }}
      </span>
    </div>

    <div class="relative bg-navy-800 p-6 text-white nav:p-[clamp(24px,5vw,72px)]">
      <span class="absolute right-4 top-4 text-xs font-bold text-white/40" aria-hidden="true">01</span>
      <h2 class="mb-3 text-2xl font-black leading-tight">{{ project.title }}</h2>
      <p v-if="project.address" class="mb-1 flex items-center gap-1.5 text-sm text-blue-100">
        <svg class="h-3.5 w-3.5 flex-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
        {{ project.address }}
      </p>

      <ul class="my-4 list-none space-y-2 p-0">
        <li v-for="bullet in trustBullets" :key="bullet" class="flex items-start gap-2 text-sm text-blue-100">
          <svg class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="9" fill-opacity=".25" /><path d="M9 12.5 11 14.5 15.5 9.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
          {{ bullet }}
        </li>
      </ul>

      <a
        class="inline-flex items-center gap-2 rounded-chip bg-hdr-gold px-5 py-3 font-bold text-navy-950 no-underline
               transition-colors hover:bg-hdr-gold-hover"
        :href="project.url"
      >
        {{ viewLabel }} <span aria-hidden="true">→</span>
      </a>
    </div>
  </article>
</template>

<script setup lang="ts">
import type { PublicContactLocation } from '#shared/contracts/public-contact'

defineProps<{ locations: PublicContactLocation[]; title: string }>()
</script>

<template>
  <!--
    PHÁT HIỆN KHI ĐỐI CHIẾU ẢNH: component gốc dựng 3 card (tên/địa chỉ/"Xem
    bản đồ") dạng lưới 3 cột. Ảnh production (/lien-he/, 1920px) không có lưới
    đó — cấu trúc thật là DANH SÁCH dọc bên trái (icon nhà + tên đậm, icon ghim
    + địa chỉ, link "Xem bản đồ", chia bằng đường kẻ mảnh) cùng với BẢN ĐỒ
    Google Maps nhúng chiếm toàn bộ cột phải. Dựng lại đúng cấu trúc đó.
  -->
  <section class="py-12 nav:py-16" aria-labelledby="contact-locations-title">
    <h2 id="contact-locations-title" class="mb-6 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-800">
      {{ title }}
    </h2>

    <div class="grid grid-cols-1 gap-8 nav:grid-cols-[340px_minmax(0,1fr)]">
      <ul class="m-0 list-none p-0">
        <li v-for="location in locations" :key="location.id" class="border-b border-gray-200 py-5 first:pt-0 last:border-b-0">
          <p class="m-0 mb-1.5 flex items-center gap-2 font-bold text-navy-800">
            <svg class="h-4 w-4 flex-none text-gold-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
            {{ location.name }}
          </p>
          <p class="m-0 mb-2.5 flex items-start gap-2 text-slate-500">
            <svg class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
            {{ location.address }}
          </p>
          <a
            v-if="location.mapUrl"
            class="inline-flex items-center gap-1.5 text-sm font-bold text-navy-800 no-underline hover:text-gold-700"
            :href="location.mapUrl"
            target="_blank"
            rel="noopener noreferrer"
          >
            Xem bản đồ <span aria-hidden="true">→</span>
          </a>
        </li>
      </ul>

      <!--
        CÒN THIẾU: ảnh production nhúng Google Maps Embed thật (iframe JS API).
        DTO chỉ có `mapUrl` — một link mở Google Maps ở tab mới, không phải
        nguồn nhúng được. Nhúng bản đồ cần API key, quyết định CSP/consent và
        chi phí riêng — không tự quyết ở bước chuyển CSS này. Thay bằng khung
        giữ chỗ trung thực thay vì giả một bản đồ tĩnh trông như thật.
      -->
      <div class="flex min-h-[360px] items-center justify-center rounded-chip border border-dashed border-gray-300 bg-slate-50 p-8 text-center text-slate-500">
        Bản đồ nhúng chưa khả dụng trong preview. Dùng "Xem bản đồ" ở từng chi nhánh.
      </div>
    </div>
  </section>
</template>

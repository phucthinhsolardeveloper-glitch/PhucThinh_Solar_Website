<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

defineProps<{ project: PublicProjectDetail; content: PublicSanitizedRichText }>()
</script>

<template>
  <!--
    Bố cục đo từ ảnh production (/du-an-dien-nlmt-ho-anh-quan/, 1920px) khác
    hẳn CSS preview cũ: không phải 2 cột copy+ảnh bìa + dải facts 4 cột viền
    trên/dưới. Thật ra là một dải nền xám (bg-slate-50) chứa: trái = nhãn "DỰ
    ÁN" + H1 + hàng pill thông tin (Danh mục/Khách hàng/Địa chỉ, dạng viên
    thuốc nền trắng bo tròn hết cỡ); phải = khối "TỔNG QUAN CÔNG TRÌNH" viền
    trái vàng — cùng mô-típ khối giá đã dùng ở trang chi tiết sản phẩm.
    KHÔNG CÓ ảnh bìa lớn cố định — ảnh nằm trong lưới gallery bên dưới.
  -->
  <article>
    <header class="bg-slate-50 px-4 py-10 nav:px-8 nav:py-12">
      <div class="mx-auto grid w-full max-w-[1766px] grid-cols-1 gap-8 nav:grid-cols-[0.85fr_1.15fr]">
        <div class="min-w-0">
          <p class="mb-3 font-bold text-gold-700">Dự án</p>
          <h1 class="m-0 mb-5 text-[clamp(34px,5vw,56px)] font-black leading-[1.05] tracking-tight text-navy-950">{{ project.title }}</h1>

          <div class="flex flex-wrap gap-3">
            <span v-if="project.category" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2.5 text-sm">
              <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Danh mục</span>
              <strong class="font-bold text-navy-800">{{ project.category.name }}</strong>
            </span>
            <span v-if="project.client" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2.5 text-sm">
              <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Khách hàng</span>
              <strong class="font-bold text-navy-800">{{ project.client }}</strong>
            </span>
            <span v-if="project.address" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2.5 text-sm">
              <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Địa chỉ</span>
              <strong class="font-bold text-navy-800">{{ project.address }}</strong>
            </span>
            <span v-if="project.startDate || project.endDate" class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2.5 text-sm">
              <span class="text-xs font-bold uppercase tracking-wide text-slate-400">Thời gian thực hiện</span>
              <strong class="font-bold text-navy-800">{{ project.startDate || 'Đang cập nhật' }}<template v-if="project.endDate"> – {{ project.endDate }}</template></strong>
            </span>
          </div>
        </div>

        <div v-if="project.summary" class="border-l-[3px] border-gold-500 bg-white py-1 pl-5">
          <p class="mb-2 text-xs font-bold uppercase tracking-wide text-gold-700">Tổng quan công trình</p>
          <p class="m-0 leading-relaxed text-navy-800">{{ project.summary }}</p>
        </div>
      </div>
    </header>

    <div class="mx-auto w-full max-w-[1766px] px-4 py-10 nav:px-8">
      <!--
        Tích hợp sanitizer: content giờ là envelope sanitized-html
        (pts_public_richtext.php sanitize contentvi của bảng #_news ở PHP).
        EditorialRichText.vue chỉ v-html trên html đã qua allowlist.
      -->
      <EditorialRichText class="mb-10" :content="content" />

      <ProjectDetailGallery :items="project.gallery" />
      <ProjectDetailEquipment :items="project.relatedEquipment" />
    </div>
  </article>
</template>

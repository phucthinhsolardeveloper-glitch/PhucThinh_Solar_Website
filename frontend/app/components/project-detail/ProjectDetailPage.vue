<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

const props = defineProps<{ project: PublicProjectDetail; content: PublicSanitizedRichText }>()
const links = useMigrationLinks()
const hotlineHref = computed(() => `tel:${props.project.cta?.hotline.replace(/[^+\d]/g, '') ?? ''}`)
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

      <section v-if="project.relatedProjects.length" class="mt-16" aria-labelledby="related-projects-title">
        <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
          <h2 id="related-projects-title" class="m-0 text-3xl font-black text-navy-950">Dự án khác</h2>
          <a class="font-bold text-navy-800 no-underline" href="/du-an-da-thuc-hien/">Xem tất cả dự án →</a>
        </header>
        <div class="grid grid-cols-1 gap-7 tb:grid-cols-2 nav:grid-cols-3">
          <a v-for="item in project.relatedProjects" :key="item.id" :href="links.generic(item.url)" class="group min-w-0 text-navy-800 no-underline">
            <img v-if="item.image" :src="item.image" :alt="item.title" class="aspect-[8/5] w-full object-cover" loading="lazy" decoding="async">
            <div class="pt-4">
              <h3 class="m-0 text-xl font-extrabold leading-snug text-navy-950 group-hover:text-gold-700">{{ item.title }}</h3>
              <p v-if="item.address" class="mb-0 mt-2 text-sm text-slate-500">{{ item.address }}</p>
              <span class="mt-3 inline-block font-bold text-navy-800">Xem dự án →</span>
            </div>
          </a>
        </div>
      </section>

      <section v-if="project.cta" class="relative isolate mt-16 grid grid-cols-1 items-center gap-7 overflow-hidden bg-navy-950 p-7 text-white nav:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)] nav:p-10" aria-labelledby="project-detail-cta-title">
        <img v-if="project.cta.image" :src="project.cta.image" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-15" loading="lazy" decoding="async">
        <div class="relative">
          <h2 id="project-detail-cta-title" class="mb-2 text-[clamp(22px,3vw,32px)] font-black leading-tight">{{ project.cta.title }}</h2>
          <p v-if="project.cta.description" class="mb-4 leading-relaxed text-blue-100">{{ project.cta.description }}</p>
          <p v-if="project.cta.eyebrow" class="mb-0 font-bold text-gold-400">{{ project.cta.eyebrow }}</p>
        </div>
        <div class="relative flex flex-wrap items-center gap-5 nav:justify-end">
          <a v-if="project.cta.hotline" class="font-extrabold text-white no-underline" :href="hotlineHref">{{ project.cta.hotline }}</a>
          <a class="inline-flex min-h-12 items-center rounded-btn bg-gold-500 px-5 font-extrabold text-navy-950 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ project.cta.buttonLabel }} →</a>
        </div>
      </section>
    </div>
  </article>
</template>

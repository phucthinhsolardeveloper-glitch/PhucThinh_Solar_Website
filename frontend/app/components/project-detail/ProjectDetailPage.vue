<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { formatProjectDate } from '#shared/project-date'

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
    <header class="py-10 nav:py-12">
      <div class="mx-auto grid w-full max-w-[1766px] grid-cols-1 gap-8 px-4" :class="{ 'nav:grid-cols-[0.85fr_1.15fr]': project.summary }">
        <div class="min-w-0">
          <h1 class="m-0 mb-5 text-[clamp(34px,5vw,56px)] font-black leading-[1.05] tracking-tight text-navy-950">{{ project.title }}</h1>

          <dl class="mt-6 flex flex-wrap gap-x-7 gap-y-3 text-sm">
            <div v-if="project.category" class="flex max-w-full flex-wrap items-baseline gap-x-2">
              <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Danh mục</dt>
              <dd class="m-0 break-words font-bold text-navy-800">{{ project.category.name }}</dd>
            </div>
            <div v-if="project.client" class="flex max-w-full flex-wrap items-baseline gap-x-2">
              <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Khách hàng</dt>
              <dd class="m-0 break-words font-bold text-navy-800">{{ project.client }}</dd>
            </div>
            <div v-if="project.address" class="flex max-w-full flex-wrap items-baseline gap-x-2">
              <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Địa chỉ</dt>
              <dd class="m-0 break-words font-bold text-navy-800">{{ project.address }}</dd>
            </div>
            <div v-if="project.startDate || project.endDate" class="flex max-w-full flex-wrap items-baseline gap-x-2">
              <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Thời gian thực hiện</dt>
              <dd class="m-0 break-words font-bold text-navy-800">{{ project.startDate ? formatProjectDate(project.startDate) : 'Đang cập nhật' }}<template v-if="project.endDate"> – {{ formatProjectDate(project.endDate) }}</template></dd>
            </div>
          </dl>
        </div>

        <section v-if="project.summary" aria-labelledby="project-summary-title">
          <h2 id="project-summary-title" class="mb-2 text-xl font-bold text-navy-800">Tổng quan công trình</h2>
          <p class="m-0 leading-relaxed text-navy-800">{{ project.summary }}</p>
        </section>
      </div>
    </header>

    <div class="mx-auto w-full max-w-[1766px] px-4 py-10">
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
          <a class="font-bold text-navy-800 no-underline" :href="links.generic('/du-an-da-thuc-hien/')">Xem tất cả dự án →</a>
        </header>
        <div class="grid grid-cols-1 gap-7 tb:grid-cols-2 nav:grid-cols-3">
          <a v-for="item in project.relatedProjects" :key="item.id" :href="links.project(item.slug)" class="group min-w-0 text-navy-800 no-underline">
            <img v-if="item.image" :src="item.image" :alt="item.title" class="aspect-[4/3] w-full object-cover" loading="lazy" decoding="async">
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
          <ul v-if="project.cta.trust.length" class="mt-5 flex list-none flex-wrap gap-x-5 gap-y-3 p-0 text-sm text-blue-100">
            <li v-for="item in project.cta.trust" :key="item.label" class="flex items-center gap-2">
              <img v-if="item.icon" :src="item.icon" alt="" class="h-[18px] w-[18px] object-contain" loading="lazy">
              <i v-else class="fas text-gold-400" :class="item.faIcon" aria-hidden="true" />
              <span>{{ item.label }}</span>
            </li>
          </ul>
        </div>
        <div class="relative flex flex-wrap items-center gap-5 nav:justify-end">
          <a v-if="project.cta.hotline" class="font-extrabold text-white no-underline" :href="hotlineHref">{{ project.cta.hotline }}</a>
          <a class="inline-flex min-h-12 items-center rounded-btn bg-gold-500 px-5 font-extrabold text-navy-950 no-underline" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ project.cta.buttonLabel }} →</a>
        </div>
      </section>
    </div>
  </article>
</template>

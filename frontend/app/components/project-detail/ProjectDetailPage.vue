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
const summaryFallback = computed(() => props.content.html?.trim() ? '' : props.project.summary.trim())
const hasOverview = computed(() => Boolean(props.content.html?.trim() || summaryFallback.value))
const chapters = [
  { id: 'project-overview', label: 'Tổng quan' },
  { id: 'project-gallery', label: 'Tư liệu dự án' },
  { id: 'project-equipment', label: 'Điểm nhấn kỹ thuật' },
]
</script>

<template>
  <article class="project-story">
    <header class="project-story__hero grid bg-navy-950 text-white nav:grid-cols-[minmax(0,1.2fr)_minmax(380px,0.8fr)]">
      <div class="project-story__cover relative overflow-hidden bg-slate-200">
        <img v-if="project.coverImage" :src="project.coverImage" :alt="project.title" class="block aspect-[4/3] w-full object-cover nav:h-full nav:aspect-auto" loading="eager" decoding="async">
        <div v-else class="grid aspect-[4/3] place-items-center px-6 text-center text-sm font-semibold text-slate-500 nav:h-full nav:aspect-auto">Chưa có hình ảnh đại diện cho dự án</div>
      </div>

      <div class="project-story__intro flex min-w-0 flex-col justify-center px-5 py-8 tb:px-8 nav:px-[clamp(32px,5vw,76px)] nav:py-16">
        <p class="mb-4 flex items-center gap-2 text-xs font-extrabold uppercase tracking-[0.16em] text-gold-300">
          <span class="h-px w-8 bg-gold-400" aria-hidden="true" />{{ project.category?.name || 'Dự án' }}
        </p>
        <h1 class="m-0 text-[clamp(32px,4.5vw,58px)] font-black leading-[1.06] tracking-tight text-white">{{ project.title }}</h1>

        <dl class="mt-8 grid gap-x-6 gap-y-5 border-t border-white/20 pt-6 text-sm tb:grid-cols-2">
          <div v-if="project.client" class="min-w-0">
            <dt class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-100/70">Khách hàng</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.client }}</dd>
          </div>
          <div v-if="project.address" class="min-w-0">
            <dt class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-100/70">Địa điểm</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.address }}</dd>
          </div>
          <div v-if="project.startDate || project.endDate" class="min-w-0 tb:col-span-2">
            <dt class="mb-1 text-xs font-bold uppercase tracking-wide text-blue-100/70">Thời gian thực hiện</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.startDate ? formatProjectDate(project.startDate) : '' }}<template v-if="project.startDate && project.endDate"> – </template>{{ project.endDate ? formatProjectDate(project.endDate) : '' }}</dd>
          </div>
        </dl>
      </div>
    </header>

    <nav v-if="chapters.length" class="project-story__chapters sticky top-0 z-20 overflow-x-auto border-b border-slate-200 bg-white/95 backdrop-blur-sm" aria-label="Các phần trong hồ sơ dự án">
      <div class="pts-container flex min-w-max gap-6 py-4 tb:gap-9">
        <a v-for="chapter in chapters" :key="chapter.id" class="inline-flex min-h-7 items-center whitespace-nowrap text-sm font-bold text-navy-800 no-underline transition-colors hover:text-gold-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500" :href="`#${chapter.id}`">{{ chapter.label }}</a>
      </div>
    </nav>

    <div class="pts-container pb-10">
      <section id="project-overview" class="project-story__section scroll-mt-20 grid gap-6 py-12 tb:grid-cols-[minmax(160px,0.36fr)_minmax(0,1fr)] tb:gap-12 nav:py-16" aria-labelledby="project-summary-title">
        <h2 id="project-summary-title" class="m-0 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Tổng quan công trình</h2>
        <div class="min-w-0 max-w-[78ch]">
          <p v-if="summaryFallback" class="m-0 text-lg leading-relaxed text-slate-700">{{ summaryFallback }}</p>
          <EditorialRichText v-else-if="hasOverview" :content="content" />
          <p v-else class="m-0 text-base leading-relaxed text-slate-500">Thông tin mô tả chi tiết chưa được công bố cho dự án này.</p>
        </div>
      </section>

      <section id="project-gallery" class="project-story__section scroll-mt-20 py-12 nav:py-16" aria-labelledby="project-gallery-heading">
        <header class="mb-7 grid gap-4 tb:grid-cols-[minmax(200px,0.36fr)_minmax(0,1fr)] tb:gap-12">
          <h2 id="project-gallery-heading" class="m-0 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Tư liệu dự án</h2>
          <p class="m-0 max-w-[60ch] self-end text-base leading-relaxed text-slate-600">Hình ảnh và video được công bố cho công trình này.</p>
        </header>
        <ProjectDetailGallery :items="project.gallery" />
      </section>

      <ProjectDetailEquipment :items="project.relatedEquipment" />

      <section v-if="project.relatedProjects.length" class="mt-16" aria-labelledby="related-projects-title">
        <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
          <h2 id="related-projects-title" class="m-0 text-3xl font-black text-navy-950">Dự án khác</h2>
          <a class="font-bold text-navy-800 no-underline" :href="links.generic('/du-an-da-thuc-hien/')">Xem tất cả dự án →</a>
        </header>
        <div class="grid grid-cols-1 gap-7 tb:grid-cols-2 nav:grid-cols-3">
          <a v-for="item in project.relatedProjects" :key="item.id" :href="links.project(item.slug)" class="group min-w-0 text-navy-800 no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-600">
            <div class="overflow-hidden rounded-chip bg-slate-100">
              <img :src="item.image || '/assets/images/noimage.png'" :alt="item.title" class="aspect-[4/3] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none" loading="lazy" decoding="async">
            </div>
            <div class="pt-4">
              <h3 class="m-0 text-xl font-extrabold leading-snug text-navy-950 transition-colors duration-200 group-hover:text-gold-700">{{ item.title }}</h3>
              <p v-if="item.address" class="mb-0 mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                <svg class="size-3.5 shrink-0 text-gold-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ item.address }}</span>
              </p>
              <span class="mt-3 inline-flex items-center gap-1 font-bold text-navy-800 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-gold-700 motion-reduce:transform-none motion-reduce:transition-none">Xem dự án →</span>
            </div>
          </a>
        </div>
      </section>

      <PublicCtaBanner v-if="project.cta" class="project-detail-cta mt-16" variant="editorial" :image="project.cta.image" labelled-by="project-detail-cta-title">
        <div>
          <h2 id="project-detail-cta-title" class="mb-2 text-[clamp(22px,3vw,30px)] font-black leading-tight text-white">{{ project.cta.title }}</h2>
          <p v-if="project.cta.description" class="mb-4 leading-relaxed text-blue-100/95">{{ project.cta.description }}</p>
          <ul v-if="project.cta.trust.length" class="mt-5 flex list-none flex-wrap gap-x-5 gap-y-3 p-0 text-sm text-blue-100">
            <li v-for="item in project.cta.trust" :key="item.label" class="flex items-center gap-2">
              <img v-if="item.icon" :src="item.icon" alt="" class="h-[18px] w-[18px] object-contain" loading="lazy">
              <UIcon v-else-if="item.faIcon === 'fa-check'" name="i-lucide-check" class="h-[18px] w-[18px] flex-none text-gold-400" aria-hidden="true" />
              <UIcon v-else-if="item.faIcon === 'fa-headset'" name="i-lucide-headset" class="h-[18px] w-[18px] flex-none text-gold-400" aria-hidden="true" />
              <UIcon v-else-if="item.faIcon === 'fa-cogs'" name="i-lucide-settings" class="h-[18px] w-[18px] flex-none text-gold-400" aria-hidden="true" />
              <UIcon v-else name="i-lucide-shield-check" class="h-[18px] w-[18px] flex-none text-gold-400" aria-hidden="true" />
              <span>{{ item.label }}</span>
            </li>
          </ul>
        </div>
        <template #actions>
        <div class="flex w-full flex-col items-stretch gap-[10px] min-[761px]:w-auto min-[761px]:min-w-[270px]">
          <a v-if="project.cta.hotline" class="flex min-h-12 items-center justify-center gap-3 text-white no-underline" :href="hotlineHref">
            <span class="grid size-12 shrink-0 place-items-center rounded-btn bg-hdr-gold text-navy-950" aria-hidden="true"><UIcon name="i-lucide-phone" class="size-5" /></span>
            <span class="min-w-0"><span class="block text-[11px] uppercase text-blue-100">Liên hệ ngay</span><strong class="block text-[22px] leading-tight">{{ project.cta.hotline }}</strong></span>
          </a>
          <a class="inline-flex min-h-12 items-center justify-center gap-3 rounded-btn bg-hdr-gold px-[22px] py-3 text-sm font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic('/lien-he/#yeu-cau-tu-van')">{{ project.cta.buttonLabel }} <span aria-hidden="true">→</span></a>
        </div>
        </template>
      </PublicCtaBanner>
    </div>
  </article>
</template>

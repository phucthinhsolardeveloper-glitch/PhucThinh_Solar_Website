<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { formatProjectDate } from '#shared/project-date'

const props = defineProps<{ project: PublicProjectDetail; content: PublicSanitizedRichText }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const hotlineHref = computed(() => `tel:${props.project.cta?.hotline.replace(/[^+\d]/g, '') ?? ''}`)
const summaryFallback = computed(() => props.content.html?.trim() ? '' : props.project.summary.trim())
const hasOverview = computed(() => Boolean(props.content.html?.trim() || summaryFallback.value))
const chapters = computed(() => {
  // Only chapters that actually have published content; empty ones are not rendered either.
  const list = [{ id: 'project-overview', label: 'Tổng quan', badge: '' }]
  if (props.project.gallery.length) list.push({ id: 'project-gallery', label: 'Tư liệu dự án', badge: String(props.project.gallery.length) })
  if (props.project.relatedEquipment.length) list.push({ id: 'project-equipment', label: 'Điểm nhấn kỹ thuật', badge: String(props.project.relatedEquipment.length) })
  if (props.project.relatedProjects.length) {
    list.push({ id: 'related-projects-section', label: 'Công trình liên quan', badge: String(props.project.relatedProjects.length) })
  }
  return list
})
const heroRef = ref<HTMLElement | null>(null)
const heroShift = ref(0)
let heroTicking = false
function measureHero() {
  heroTicking = false
  const el = heroRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  heroShift.value = Math.max(0, Math.min(1, -rect.top / Math.max(1, rect.height)))
}
function onHeroScroll() { if (!heroTicking) { heroTicking = true; requestAnimationFrame(measureHero) } }
onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  window.addEventListener('scroll', onHeroScroll, { passive: true })
})
onBeforeUnmount(() => window.removeEventListener('scroll', onHeroScroll))
</script>

<template>
  <article class="project-story">
    <header ref="heroRef" class="project-story__hero grid bg-navy-950 text-white" :style="{ '--shift': heroShift }">
      <div class="project-story__cover relative overflow-hidden bg-slate-200">
        <img v-if="project.coverImage" :src="project.coverImage" :alt="project.title" class="project-story__cover-img block aspect-[4/3] w-full object-cover" loading="eager" decoding="async">
        <div v-else class="grid aspect-[4/3] place-items-center px-6 text-center text-sm font-semibold text-slate-500 nav:h-full nav:aspect-auto">Chưa có hình ảnh đại diện cho dự án</div>
      </div>

      <div class="project-story__intro pts-container flex min-w-0 flex-col justify-end">
        <div v-if="project.category?.name" class="mb-4 flex flex-wrap items-center gap-2">
          <span class="inline-flex items-center rounded-[3px] border border-gold-400/35 bg-white/10 px-3 py-1 text-xs font-bold text-gold-300">
            {{ project.category.name }}
          </span>
        </div>
        <h1 class="m-0 max-w-[18ch] text-[clamp(34px,5.2vw,76px)] font-black leading-[1.02] tracking-[-.035em] text-white">{{ project.title }}</h1>

        <dl class="mt-8 grid gap-x-10 gap-y-5 border-t border-white/25 pt-6 text-base tb:grid-cols-[repeat(auto-fit,minmax(180px,max-content))]">
          <div v-if="project.client" class="min-w-0">
            <dt class="mb-1 text-sm font-semibold text-blue-100/75">Khách hàng</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.client }}</dd>
          </div>
          <div v-if="project.address" class="min-w-0">
            <dt class="mb-1 text-sm font-semibold text-blue-100/75">Địa điểm</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.address }}</dd>
          </div>
          <div v-if="project.startDate || project.endDate" class="min-w-0">
            <dt class="mb-1 text-sm font-semibold text-blue-100/75">Thời gian thực hiện</dt>
            <dd class="m-0 break-words font-bold text-white">{{ project.startDate ? formatProjectDate(project.startDate) : '' }}<template v-if="project.startDate && project.endDate"> – </template>{{ project.endDate ? formatProjectDate(project.endDate) : '' }}</dd>
          </div>
          <div v-if="project.gallery.length" class="min-w-0">
            <dt class="mb-1 text-sm font-semibold text-blue-100/75">Tư liệu thực tế</dt>
            <dd class="m-0 font-bold text-gold-300">{{ project.gallery.length }} tư liệu hình ảnh &amp; video</dd>
          </div>
        </dl>

        <div class="mt-7 flex flex-wrap items-center gap-3">
          <a
            :href="links.generic('/lien-he/#yeu-cau-tu-van')"
            class="inline-flex min-h-11 items-center gap-2 rounded-[4px] bg-hdr-gold px-5 py-2.5 text-sm font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover cursor-pointer"
            @click.prevent="openConsultation('Dự án: ' + project.title)"
          >
            <span>Tư vấn công trình tương tự</span>
            <span aria-hidden="true">→</span>
          </a>
          <a
            v-if="project.gallery.length"
            href="#project-gallery"
            class="inline-flex min-h-11 items-center gap-2 rounded-[4px] border border-white/25 bg-white/[.05] px-4 py-2.5 text-sm font-bold text-white no-underline transition-colors hover:border-gold-400 hover:bg-white/10"
          >
            <span>Xem tư liệu thi công</span>
          </a>
        </div>
      </div>
    </header>

    <nav v-if="chapters.length" class="project-story__chapters sticky top-0 z-20 overflow-x-auto border-b border-slate-200 bg-white/95 backdrop-blur-sm" aria-label="Các phần trong hồ sơ dự án">
      <div class="pts-container flex min-w-max items-center justify-between gap-6 py-3 tb:gap-9">
        <div class="flex items-center gap-6 tb:gap-9">
          <a v-for="chapter in chapters" :key="chapter.id" class="inline-flex min-h-11 items-center whitespace-nowrap gap-1.5 text-sm font-bold text-navy-800 no-underline transition-colors hover:text-gold-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500" :href="`#${chapter.id}`">
            <span>{{ chapter.label }}</span>
            <span v-if="chapter.badge" class="rounded-[3px] bg-slate-100 px-1.5 py-0.5 text-xs font-extrabold tabular-nums text-navy-700">{{ chapter.badge }}</span>
          </a>
        </div>
        <a
          :href="links.generic('/lien-he/#yeu-cau-tu-van')"
          class="hidden min-h-10 items-center gap-1.5 rounded-[4px] bg-navy-900 px-3.5 py-1.5 text-xs font-extrabold text-gold-300 no-underline transition-colors hover:bg-navy-800 tb:inline-flex cursor-pointer"
          @click.prevent="openConsultation('Dự án: ' + project.title)"
        >
          <span>Nhận báo giá cấu hình này</span>
          <span aria-hidden="true">→</span>
        </a>
      </div>
    </nav>

    <div class="pts-container pb-10">
      <section id="project-overview" class="project-story__section scroll-mt-20 grid gap-6 py-12 tb:grid-cols-[minmax(160px,0.36fr)_minmax(0,1fr)] tb:gap-12 nav:py-16" aria-labelledby="project-summary-title">
        <h2 id="project-summary-title" class="m-0 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Tổng quan công trình</h2>
        <div class="project-story__lede min-w-0 max-w-[68ch]">
          <p v-if="summaryFallback" class="m-0 text-[clamp(19px,1.6vw,24px)] leading-[1.6] text-navy-900">{{ summaryFallback }}</p>
          <EditorialRichText v-else-if="hasOverview" :content="content" />
          <p v-else class="m-0 text-base leading-relaxed text-slate-500">Thông tin mô tả chi tiết chưa được công bố cho dự án này.</p>
        </div>
      </section>

      <section v-if="project.gallery.length" id="project-gallery" class="project-story__section scroll-mt-20 py-12 nav:py-16" aria-labelledby="project-gallery-heading">
        <header class="mb-7 grid gap-4 tb:grid-cols-[minmax(200px,0.36fr)_minmax(0,1fr)] tb:gap-12">
          <h2 id="project-gallery-heading" class="m-0 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Tư liệu dự án</h2>
          <p class="m-0 max-w-[60ch] self-end text-base leading-relaxed text-slate-600">Hình ảnh và video được công bố cho công trình này.</p>
        </header>
        <ProjectDetailGallery :items="project.gallery" />
      </section>

      <ProjectDetailEquipment :items="project.relatedEquipment" />

      <section v-if="project.relatedProjects.length" id="related-projects-section" class="mt-16 scroll-mt-20" aria-labelledby="related-projects-title">
        <header class="mb-7 flex flex-wrap items-end justify-between gap-4">
          <h2 id="related-projects-title" class="m-0 text-[clamp(28px,3vw,46px)] font-black tracking-[-.03em] text-navy-950">Dự án khác</h2>
          <a class="font-bold text-navy-800 no-underline" :href="links.generic('/du-an-da-thuc-hien/')">Xem tất cả dự án →</a>
        </header>
        <div class="project-strip">
          <a v-for="item in project.relatedProjects" :key="item.id" :href="links.project(item.slug)" class="project-strip__item group min-w-0 text-navy-800 no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-600">
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
          <a class="inline-flex min-h-12 items-center justify-center gap-3 rounded-btn bg-hdr-gold px-[22px] py-3 text-sm font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover cursor-pointer" :href="links.generic('/lien-he/#yeu-cau-tu-van')" @click.prevent="openConsultation('Dự án: ' + project.title)">{{ project.cta.buttonLabel }} <span aria-hidden="true">→</span></a>
        </div>
        </template>
      </PublicCtaBanner>
    </div>
  </article>
</template>

<style scoped>
/* Cinematic hero: the cover fills the frame, copy sits on a navy wash at the bottom. */
.project-story__hero {
  position: relative;
  isolation: isolate;
  min-height: clamp(560px, 82vh, 860px);
  overflow: clip;
  grid-template-columns: minmax(0, 1fr);
}
.project-story__cover { position: absolute; inset: 0; z-index: -2; background: var(--color-navy-950); }
.project-story__cover-img {
  height: 112%;
  aspect-ratio: auto;
  transform: translate3d(0, calc(var(--shift, 0) * 10%), 0) scale(1.03);
  will-change: transform;
}
.project-story__hero::after {
  content: "";
  position: absolute;
  inset: 0;
  z-index: -1;
  background:
    linear-gradient(180deg, rgb(6 21 55 / 15%) 0%, rgb(6 21 55 / 25%) 40%, rgb(6 21 55 / 94%) 100%),
    linear-gradient(90deg, rgb(6 21 55 / 78%) 0%, rgb(6 21 55 / 45%) 45%, transparent 72%);
}
.project-story__intro { padding-top: clamp(120px, 20vh, 220px); padding-bottom: clamp(36px, 5vw, 64px); }

.project-story__lede :deep(p:first-child) { font-size: clamp(19px, 1.6vw, 24px); line-height: 1.6; color: var(--color-navy-900); }
.project-story__lede :deep(p) { font-size: 18px; line-height: 1.75; }

/* Related projects: horizontal film strip, large frames, snap scrolling. */
.project-strip {
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(300px, 40%);
  gap: clamp(16px, 2vw, 28px);
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scrollbar-width: thin;
  padding-bottom: 12px;
}
.project-strip__item { scroll-snap-align: start; }
@media (max-width: 760px) { .project-strip { grid-auto-columns: 82%; } }
@media (prefers-reduced-motion: reduce) { .project-story__cover-img { transform: none; height: 100%; } }
</style>

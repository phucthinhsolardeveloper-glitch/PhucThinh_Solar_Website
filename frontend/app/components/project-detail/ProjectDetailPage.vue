<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import { formatProjectDate } from '#shared/project-date'
import { restructureLineBreaks } from '~/utils/lineBreakLists'

const props = defineProps<{ project: PublicProjectDetail; content: PublicSanitizedRichText }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const faIcons: Record<string, string> = { 'fa-check': 'i-lucide-check', 'fa-headset': 'i-lucide-headset', 'fa-cogs': 'i-lucide-settings-2', 'fa-shield-alt': 'i-lucide-shield-check' }
const ctaTrust = computed(() => (props.project.cta?.trust ?? []).map((item) => ({ label: item.label, image: item.icon, icon: faIcons[item.faIcon] })))
const overviewContent = computed(() => ({ ...props.content, html: restructureLineBreaks(props.content.html || '') }))
const summaryFallback = computed(() => props.content.html?.trim() ? '' : props.project.summary.trim())
const hasOverview = computed(() => Boolean(props.content.html?.trim() || summaryFallback.value))
const chapters = computed(() => {
  // Only chapters that actually have published content; empty ones are not rendered either.
  const list = [{ id: 'project-overview', label: 'Tổng quan' }]
  if (props.project.gallery.length) list.push({ id: 'project-gallery', label: 'Tư liệu dự án' })
  if (props.project.relatedEquipment.length) list.push({ id: 'project-equipment', label: 'Điểm nhấn kỹ thuật' })
  if (props.project.relatedProjects.length) list.push({ id: 'related-projects-section', label: 'Công trình liên quan' })
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
        </dl>

        <div class="project-story__actions">
          <a
            :href="links.generic('/lien-he/#yeu-cau-tu-van')"
            class="project-story__primary"
            @click.prevent="openConsultation('Dự án: ' + project.title)"
          >
            Tư vấn công trình tương tự
            <span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
          </a>
          <a v-if="project.gallery.length" href="#project-gallery" class="project-story__more">
            Xem tư liệu thi công <UIcon name="i-lucide-arrow-down" class="size-4" aria-hidden="true" />
          </a>
        </div>
      </div>
    </header>

    <nav v-if="chapters.length" class="project-story__chapters overflow-x-auto border-b border-slate-200 bg-white" aria-label="Các phần trong hồ sơ dự án">
      <div class="pts-container flex min-w-max items-center justify-between gap-6 py-3 tb:gap-9">
        <div class="flex items-center gap-6 tb:gap-9">
          <a v-for="chapter in chapters" :key="chapter.id" class="inline-flex min-h-11 items-center whitespace-nowrap gap-1.5 text-sm font-bold text-navy-800 no-underline transition-colors hover:text-gold-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500" :href="`#${chapter.id}`">
            <span>{{ chapter.label }}</span>
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
          <EditorialRichText v-else-if="hasOverview" :content="overviewContent" />
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
          <a v-for="item in project.relatedProjects" :key="item.id" :href="links.project(item.slug)" class="project-strip__item group">
            <span class="project-strip__media"><img :src="item.image || '/assets/images/noimage.png'" :alt="item.title" loading="lazy" decoding="async"></span>
            <h3>{{ item.title }}</h3>
            <span v-if="item.address" class="project-strip__place"><UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-600" aria-hidden="true" />{{ item.address }}</span>
            <span class="project-strip__go">Xem dự án <span aria-hidden="true">→</span></span>
          </a>
        </div>
      </section>

      <PublicCtaBanner
        v-if="project.cta"
        class="project-detail-cta mt-16"
        variant="editorial"
        :image="project.cta.image"
        title-id="project-detail-cta-title"
        :title="project.cta.title"
        :description="project.cta.description"
        :trust="ctaTrust"
        :hotline="project.cta.hotline"
        :button-label="project.cta.buttonLabel"
        :subject="'Dự án: ' + project.title"
      />
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
.project-story__lede :deep(p) { margin: 0 0 14px; font-size: 18px; line-height: 1.75; color: #3d4d66; }
.project-story__lede :deep(ul) { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; margin: 24px 0 0; padding: 22px 0 0; border-top: 1px solid rgb(10 34 81 / 12%); list-style: none; }
.project-story__lede :deep(li) { position: relative; padding-left: 30px; color: var(--color-navy-900); font-size: 16.5px; font-weight: 600; line-height: 1.5; }
.project-story__lede :deep(li)::before { content: ''; position: absolute; top: 3px; left: 0; width: 20px; height: 20px; border-radius: 50%; background: var(--color-navy-950) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23f0c76e' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6 9 17l-5-5'/%3E%3C/svg%3E") center / 12px no-repeat; }
@media (max-width: 640px) { .project-story__lede :deep(ul) { grid-template-columns: 1fr; } }

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
.project-strip__item { display: flex; flex-direction: column; gap: 6px; color: var(--color-navy-900); text-decoration: none; scroll-snap-align: start; }
.project-strip__item:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; border-radius: 6px; }
.project-strip__media { display: block; margin-bottom: 10px; overflow: hidden; border-radius: 6px; background: #edf1f5; aspect-ratio: 4 / 3; }
.project-strip__media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1); }
.project-strip__item:hover .project-strip__media img { transform: scale(1.04); }
.project-strip__item h3 { margin: 0; color: var(--color-navy-950); font-size: clamp(18px, 1.35vw, 21px); font-weight: 800; line-height: 1.32; transition: color 0.2s ease; }
.project-strip__item:hover h3 { color: var(--color-gold-700); }
.project-strip__place { display: inline-flex; align-items: center; gap: 6px; color: #64748b; font-size: 14.5px; }
.project-strip__go { color: var(--color-navy-800); font-size: 14.5px; font-weight: 800; }
.project-strip__go > span { display: inline-block; transition: transform 0.3s ease; }
.project-strip__item:hover .project-strip__go > span { transform: translateX(5px); }

.project-story__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 28px; margin-top: 28px; }
.project-story__primary { display: inline-flex; min-height: 58px; align-items: center; gap: 16px; padding: 0 10px 0 24px; border-radius: 6px; background: var(--color-gold-400); color: var(--color-navy-950); font-size: 16.5px; font-weight: 850; text-decoration: none; transition: background-color 0.25s ease; }
.project-story__primary > span { display: grid; width: 40px; height: 40px; place-items: center; border-radius: 4px; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.project-story__primary:hover { background: var(--color-gold-300); }
.project-story__primary:hover > span { transform: translateX(4px); }
.project-story__more { display: inline-flex; min-height: 44px; align-items: center; gap: 8px; color: #d3dcea; font-size: 15.5px; font-weight: 700; text-decoration: none; }
.project-story__more:hover { color: var(--color-gold-300); }
.project-story__actions a:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 3px; }
@media (max-width: 640px) {
  .project-story__actions { align-items: stretch; flex-direction: column; }
  .project-story__primary { justify-content: space-between; }
}
@media (max-width: 760px) { .project-strip { grid-auto-columns: 82%; } }
@media (prefers-reduced-motion: reduce) {
  .project-story__cover-img { transform: none; height: 100%; }
  .project-strip__media img, .project-strip__go > span, .project-story__primary > span { transition: none; }
  .project-strip__item:hover .project-strip__media img { transform: none; }
}
</style>

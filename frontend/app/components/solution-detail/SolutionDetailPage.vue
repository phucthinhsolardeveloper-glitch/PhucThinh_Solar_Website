<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
import { buildEditorialTocForFragments } from '#shared/editorial-toc'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import SolutionDetailGallery from './SolutionDetailGallery.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'

const props = defineProps<{ data: PublicSolutionDetailPayload; hotline?: string }>()
const { isHeaderHidden } = useSiteHeader()
const links = useMigrationLinks()
const editorial = computed(() => buildEditorialTocForFragments(
  [props.data.solution.content, props.data.solution.extraContent] as const,
  { levels: [2, 3], idPrefix: 'solution-section' },
))
const tocItems = computed(() => {
  let h2Order = 0
  return editorial.value.toc.map((item) => {
    const level = item.level === 3 ? 3 as const : 2 as const
    if (level === 2) h2Order += 1
    return {
      id: item.id,
      title: item.label,
      level,
      order: level === 2 ? h2Order : null,
    }
  })
})
const solutionContent = computed(() => editorial.value.contents[0] ?? props.data.solution.content)
const solutionExtraContent = computed(() => editorial.value.contents[1] ?? props.data.solution.extraContent)

const quickHotlineTel = computed(() => {
  if (props.data.campaignCta?.hotline?.tel) return props.data.campaignCta.hotline.tel
  const digits = (props.hotline ?? '').replace(/[^\d+]/g, '')
  return digits
})
const quickHotlineText = computed(() => props.data.campaignCta?.hotline?.text || (props.hotline ?? '').trim())

const tocContainerRef = ref<HTMLElement | null>(null)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '#solution-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})
</script>

<template>
  <div>
    <!--
      Đo trên ảnh production (/giai-phap/dien-mat-troi-doanh-nghiep/, 1920px):
      chip điều hướng ĐANG CHỌN là nền navy đặc + chữ sáng, các chip còn lại
      nền sáng có viền mảnh — giữ nhận diện navy/gold của website kết hợp bố cục
      split hero và sticky sidebar TOC rõ cấp bậc.
    -->
    <article>
      <div class="pts-container pt-5 pb-2">
        <header class="mx-auto grid w-full grid-cols-1 overflow-hidden rounded-btn border border-navy-800/15 shadow-md" :class="data.solution.image ? 'nav:grid-cols-2' : 'nav:grid-cols-1'">
          <div class="flex flex-col justify-between bg-gradient-to-br from-navy-950 via-navy-800 to-navy-900 p-7 text-white tb:p-10 nav:p-12" :class="data.solution.image ? '' : 'nav:grid nav:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)] nav:items-center nav:gap-[72px]'">
            <div :class="data.solution.image ? '' : 'contents'">
              <h1 class="m-0 mb-4 text-[clamp(28px,3.2vw,42px)] font-black leading-[1.16] tracking-tight text-white" :class="data.solution.image ? '' : 'nav:row-span-2 nav:mb-0 nav:max-w-[600px]'">{{ data.solution.title }}</h1>
              <p v-if="data.solution.summary" class="mb-6 text-[15px] leading-[1.72] text-blue-100/95 tb:text-base" :class="data.solution.image ? '' : 'nav:col-start-2 nav:row-start-1 nav:mb-0'">{{ data.solution.summary }}</p>
            </div>
            <div class="flex flex-col items-stretch gap-3.5 border-t border-white/15 pt-5 ph:flex-row ph:flex-wrap ph:items-center" :class="data.solution.image ? '' : 'nav:col-start-2 nav:row-start-2'">
              <a class="inline-flex min-h-11 items-center justify-center gap-3 rounded-btn bg-hdr-gold px-6 text-sm font-extrabold tracking-wide text-navy-950 no-underline shadow-sm transition-colors hover:bg-hdr-gold-hover" :href="links.generic(data.cta.href)">
                <span>{{ data.cta.label }}</span>
                <span aria-hidden="true">→</span>
              </a>
              <a v-if="data.solution.content.html" class="inline-flex min-h-11 items-center justify-center rounded-btn border border-white/30 bg-white/[.04] px-5 text-center text-sm font-bold text-white no-underline transition-colors hover:border-gold-400 hover:bg-white/10 ph:justify-start ph:text-left" href="#noi-dung-giai-phap">Tìm hiểu phương án</a>
            </div>
          </div>
          <figure v-if="data.solution.image" class="relative m-0 aspect-[8/5] min-w-0 overflow-hidden bg-navy-950 nav:aspect-auto nav:min-h-[360px]">
            <img :src="data.solution.image" :alt="data.solution.title" class="h-full w-full object-cover" width="1600" height="1000" fetchpriority="high">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-navy-950/35 via-transparent to-transparent" aria-hidden="true" />
          </figure>
        </header>
      </div>

      <div class="pts-container py-2">
        <nav class="flex w-full gap-2.5 overflow-x-auto py-1.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Các giải pháp khác">
          <a
            class="inline-flex min-h-11 flex-none items-center whitespace-nowrap rounded-chip border border-slate-200 bg-white px-4 text-[13px] font-bold text-navy-800 no-underline transition-colors hover:border-gold-500 hover:text-gold-700"
            :href="links.generic(data.navigation.allUrl)"
          >
            Tất cả giải pháp
          </a>
          <a
            v-for="item in data.navigation.items"
            :key="item.slug"
            class="inline-flex min-h-11 flex-none items-center whitespace-nowrap rounded-chip border px-4 text-[13px] font-bold no-underline transition-colors"
            :class="item.active ? 'border-navy-800 bg-navy-800 text-gold-300 shadow-xs' : 'border-slate-200 bg-white text-navy-800 hover:border-gold-500 hover:text-gold-700'"
            :href="links.generic(item.url)"
          >
            {{ item.title }}
          </a>
        </nav>
      </div>

      <!--
        Tích hợp sanitizer: content/extraContent giờ là HTML thật qua
        pts_public_richtext.php, không còn mảng paragraph tách theo dòng.
      -->
      <div id="noi-dung-giai-phap" class="pts-container grid grid-cols-1 gap-8 py-8 min-[901px]:grid-cols-[minmax(250px,300px)_minmax(0,1fr)] min-[901px]:gap-[clamp(32px,3.6vw,64px)] nav:py-11">
        <aside
          v-if="tocItems.length"
          ref="tocContainerRef"
          class="self-start rounded-btn border border-navy-700/70 bg-gradient-to-b from-navy-800 to-navy-950 p-5 shadow-sm min-[901px]:sticky min-[901px]:max-h-[calc(100vh-116px)] min-[901px]:overflow-y-auto transition-[top] duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
          :class="isHeaderHidden ? 'min-[901px]:top-6' : 'min-[901px]:top-[104px] min-[1501px]:top-[124px] min-[1800px]:top-[140px]'"
          aria-label="Mục lục nội dung"
        >
          <h2 class="m-0 mb-3 flex items-center gap-2 border-b border-white/12 pb-3 text-[13px] font-extrabold uppercase tracking-wider text-gold-300">
            <svg class="h-4 w-4 flex-none text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
            </svg>
            <span>Nội dung chính</span>
          </h2>
          <ol class="m-0 max-h-[220px] list-none space-y-1 overflow-y-auto p-0 overscroll-contain min-[901px]:max-h-none min-[901px]:overflow-visible">
            <li v-for="item in tocItems" :key="item.id" :class="item.level === 3 ? 'ml-3.5' : ''">
              <a
                :data-toc-id="item.id"
                class="flex min-h-11 items-center border-l-2 py-1.5 pl-3 pr-2 text-[13px] leading-[1.45] no-underline transition-all duration-200"
                :class="[
                  item.level === 2 ? 'font-bold' : 'font-medium',
                  activeHeadingId === item.id
                    ? 'border-[#e4a849] bg-white/10 text-white font-extrabold rounded-r'
                    : 'border-transparent text-[#c9d5e6] hover:border-gold-400 hover:text-white hover:bg-white/5',
                ]"
                :href="`#${item.id}`"
                :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                @click.prevent="scrollToHeading(item.id)"
              >
                <span v-if="item.order" class="mr-2 flex-none text-xs font-extrabold text-gold-400">{{ item.order }}.</span>
                <span v-else class="mr-2 flex-none text-gold-400" aria-hidden="true">•</span>
                <span>{{ item.title }}</span>
              </a>
            </li>
          </ol>

          <div class="mt-5 rounded-chip border border-white/12 bg-white/[.04] p-3.5 text-xs">
            <p class="m-0 mb-1.5 font-extrabold text-white">{{ data.cta.label }}</p>
            <p v-if="data.campaignCta?.description" class="m-0 mb-3 line-clamp-2 leading-relaxed text-blue-100/80">{{ data.campaignCta.description }}</p>
            <a
              v-if="quickHotlineTel"
              class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-chip bg-hdr-gold px-3 py-2 text-center text-xs font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover"
              :href="`tel:${quickHotlineTel}`"
            >
              <svg class="h-3.5 w-3.5 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z" />
              </svg>
              <span>{{ quickHotlineText }}</span>
            </a>
            <a
              v-else
              class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-chip bg-hdr-gold px-3 py-2 text-center text-xs font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover"
              :href="links.generic(data.cta.href)"
            >
              <span>{{ data.cta.label }}</span>
              <span aria-hidden="true">→</span>
            </a>
          </div>
        </aside>

        <article id="solution-content" class="min-w-0">
          <EditorialRichText :content="solutionContent" variant="solution" />
          <EditorialRichText class="mt-8" :content="solutionExtraContent" variant="solution" />
          <p v-if="!data.solution.content.html && !data.solution.extraContent.html" class="text-slate-500">Nội dung chi tiết đang được cập nhật.</p>
        </article>
      </div>

      <SolutionDetailGallery :images="data.solution.gallery" :title="data.solution.title" />

    </article>

    <section v-if="data.equipment.length" class="pts-container py-6 nav:py-8" aria-labelledby="solution-equipment-title">
      <header class="mb-5 flex flex-col items-start gap-4 min-[801px]:flex-row min-[801px]:items-end min-[801px]:justify-between">
        <h2 id="solution-equipment-title" class="m-0 text-[clamp(22px,2.2vw,28px)] font-black tracking-tight text-navy-800">Thiết bị cho một hệ thống hoàn chỉnh</h2>
        <a class="inline-flex min-h-11 items-center gap-1.5 text-sm font-extrabold text-gold-700 no-underline transition-colors hover:text-navy-800" :href="links.generic('/san-pham/')">
          <span>Xem tất cả sản phẩm</span>
          <span aria-hidden="true">→</span>
        </a>
      </header>
      <div class="grid grid-cols-1 gap-2 min-[521px]:grid-cols-2 min-[1101px]:grid-cols-4">
        <a
          v-for="item in data.equipment"
          :key="item.url"
          class="group flex min-w-0 items-center gap-3 px-3.5 py-2.5 text-navy-800 no-underline"
          :href="links.generic(item.url)"
        >
          <img v-if="item.image" :src="item.image" alt="" class="h-[54px] w-[54px] shrink-0 object-contain min-[521px]:h-[72px] min-[521px]:w-16" width="80" height="80" loading="lazy">
          <span class="min-w-0 flex-1">
            <strong class="block text-[15px] font-extrabold leading-snug text-navy-800 transition-colors group-hover:text-gold-700">{{ item.name }}</strong>
            <small v-if="item.description" class="mt-1 block text-[13px] leading-[1.5] text-slate-500">{{ item.description }}</small>
          </span>
          <span class="ml-auto shrink-0 text-[22px] text-navy-800 transition-transform group-hover:translate-x-0.5 group-hover:text-gold-700" aria-hidden="true">→</span>
        </a>
      </div>
    </section>

    <div class="pts-container pb-12">
      <SolutionsCta v-if="data.campaignCta" :cta="data.campaignCta" />
    </div>
  </div>
</template>

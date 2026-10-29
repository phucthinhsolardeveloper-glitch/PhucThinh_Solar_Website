<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
import { buildEditorialTocForFragments } from '#shared/editorial-toc'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import SolutionDetailGallery from './SolutionDetailGallery.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'

const props = defineProps<{ data: PublicSolutionDetailPayload }>()
const links = useMigrationLinks()
const editorial = computed(() => buildEditorialTocForFragments(
  [props.data.solution.content, props.data.solution.extraContent] as const,
  { levels: [2, 3], idPrefix: 'solution-section' },
))
const tocItems = computed(() => editorial.value.toc.map((item) => ({
  id: item.id,
  title: item.label,
  level: item.level === 3 ? 3 as const : 2 as const,
})))
const solutionContent = computed(() => editorial.value.contents[0] ?? props.data.solution.content)
const solutionExtraContent = computed(() => editorial.value.contents[1] ?? props.data.solution.extraContent)
</script>

<template>
  <div>
    <!--
      Đo trên ảnh production (/giai-phap/dien-mat-troi-doanh-nghiep/, 1920px):
      chip điều hướng ĐANG CHỌN là nền navy đặc + chữ trắng, các chip còn lại
      nền xám nhạt — cùng quy ước active đã xác nhận ở Tin tức/Dự án/Giải pháp
      (không phải chỉ đổi màu chữ như CSS preview cũ mô tả).
    -->
    <article>
      <!--
        Hero: panel navy-800 đặc bên trái chứa chữ (không phải chỉ nền trang),
        ảnh bên phải tràn hết chiều cao panel — khác CSS preview cũ (nền trang
        trắng, chữ navy thường). "Tìm hiểu phương án" là liên kết neo xuống
        phần nội dung bên dưới, không có trong DTO vì là khung trang cố định.
      -->
      <header class="mx-auto grid w-full max-w-[1766px] grid-cols-1 overflow-hidden rounded-btn px-4" :class="data.solution.image ? 'nav:grid-cols-2' : 'nav:grid-cols-1'">
        <div class="flex flex-col justify-center bg-navy-800 p-8 text-white nav:p-12" :class="data.solution.image ? '' : 'nav:grid nav:grid-cols-[minmax(0,1.05fr)_minmax(0,.95fr)] nav:items-center nav:gap-[72px]'">
          <h1 class="m-0 mb-4 text-[clamp(30px,4vw,44px)] font-black leading-[1.15]" :class="data.solution.image ? '' : 'nav:row-span-2 nav:mb-0 nav:max-w-[600px]'">{{ data.solution.title }}</h1>
          <p v-if="data.solution.summary" class="mb-5 text-lg leading-relaxed text-blue-100" :class="data.solution.image ? '' : 'nav:col-start-2 nav:row-start-1 nav:mb-0'">{{ data.solution.summary }}</p>
          <div class="flex flex-col items-stretch gap-4 ph:flex-row ph:items-center" :class="data.solution.image ? '' : 'nav:col-start-2 nav:row-start-2'">
            <a class="inline-flex min-h-11 items-center justify-center gap-4 rounded-btn bg-hdr-gold px-5 font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(data.cta.href)">
              {{ data.cta.label }} <span aria-hidden="true">→</span>
            </a>
            <a v-if="data.solution.content.html" class="inline-flex min-h-11 items-center justify-center text-center font-bold text-white no-underline ph:justify-start ph:text-left" href="#noi-dung-giai-phap">Tìm hiểu phương án</a>
          </div>
        </div>
        <figure v-if="data.solution.image" class="m-0 aspect-[8/5] min-w-0 overflow-hidden">
          <img :src="data.solution.image" :alt="data.solution.title" class="h-full w-full object-cover" width="1600" height="1000" fetchpriority="high">
        </figure>
      </header>

      <nav class="mx-auto flex w-full max-w-[1766px] gap-2 overflow-x-auto px-4 py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Các giải pháp khác">
        <a
          class="inline-flex min-h-11 flex-none items-center whitespace-nowrap rounded-chip bg-slate-50 px-4 text-sm font-bold text-navy-800 no-underline hover:bg-gray-200"
          :href="links.generic(data.navigation.allUrl)"
        >
          Tất cả giải pháp
        </a>
        <a
          v-for="item in data.navigation.items"
          :key="item.slug"
          class="inline-flex min-h-11 flex-none items-center whitespace-nowrap rounded-chip px-4 text-sm font-bold no-underline"
          :class="item.active ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
          :href="links.generic(item.url)"
        >
          {{ item.title }}
        </a>
      </nav>

      <!--
        Tích hợp sanitizer: content/extraContent giờ là HTML thật qua
        pts_public_richtext.php, không còn mảng paragraph tách theo dòng.
      -->
      <div id="noi-dung-giai-phap" class="mx-auto grid w-full max-w-[1766px] grid-cols-1 gap-8 px-4 py-12 min-[901px]:grid-cols-[minmax(220px,280px)_minmax(0,1fr)] min-[901px]:gap-[clamp(32px,4vw,76px)]">
        <aside
          v-if="tocItems.length"
          class="self-start rounded-[3px] bg-navy-800 p-6 min-[901px]:sticky min-[901px]:top-[124px] min-[901px]:max-h-[calc(100vh-150px)] min-[901px]:overflow-y-auto"
          aria-label="Mục lục nội dung"
        >
          <h2 class="mb-3.5 text-sm font-extrabold text-[#efc45b]">Nội dung chính</h2>
          <ol class="m-0 max-h-[220px] list-none space-y-2 overflow-y-auto p-0 overscroll-contain min-[901px]:max-h-none min-[901px]:overflow-visible">
            <li v-for="item in tocItems" :key="item.id" :class="item.level === 3 ? 'ml-3' : ''">
              <a class="flex min-h-11 items-center border-l-2 border-transparent py-1 pl-3 text-[13px] font-semibold leading-[1.45] text-[#c9d5e6] no-underline transition-colors hover:border-gold-400 hover:text-white" :href="`#${item.id}`">
                {{ item.title }}
              </a>
            </li>
          </ol>
        </aside>

        <article id="solution-content" class="min-w-0">
          <EditorialRichText :content="solutionContent" />
          <EditorialRichText class="mt-8" :content="solutionExtraContent" />
          <p v-if="!data.solution.content.html && !data.solution.extraContent.html" class="text-slate-500">Nội dung chi tiết đang được cập nhật.</p>
        </article>
      </div>

      <SolutionDetailGallery :images="data.solution.gallery" :title="data.solution.title" />

    </article>

      <section v-if="data.equipment.length" class="mx-auto mb-12 w-full max-w-[1766px] px-4" aria-labelledby="solution-equipment-title">
      <header class="mb-5 flex flex-col items-start gap-3 tb:flex-row tb:items-end tb:justify-between">
        <h2 id="solution-equipment-title" class="m-0 text-[28px] font-black text-navy-800">Thiết bị liên quan</h2>
        <a class="inline-flex min-h-11 items-center font-bold text-navy-800 no-underline" :href="links.generic('/san-pham/')">Xem tất cả sản phẩm <span aria-hidden="true">→</span></a>
      </header>
      <div class="grid grid-cols-1 gap-3.5 tb:grid-cols-4">
        <a v-for="item in data.equipment" :key="item.url" class="flex items-center gap-3 border-t border-gray-200 py-3.5 text-navy-800 no-underline" :href="links.generic(item.url)">
          <img v-if="item.image" :src="item.image" alt="" class="h-16 w-16 flex-none object-contain" width="80" height="80" loading="lazy">
          <span class="min-w-0">
            <strong class="block">{{ item.name }}</strong>
            <small v-if="item.description" class="mt-1 block leading-relaxed text-slate-500">{{ item.description }}</small>
          </span>
          <span class="ml-auto flex-none text-xl" aria-hidden="true">→</span>
        </a>
      </div>
    </section>

    <SolutionsCta v-if="data.campaignCta" :cta="data.campaignCta" />
  </div>
</template>

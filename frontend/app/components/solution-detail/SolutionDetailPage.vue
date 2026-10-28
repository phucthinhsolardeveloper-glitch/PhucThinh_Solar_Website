<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

defineProps<{ data: PublicSolutionDetailPayload }>()
const links = useMigrationLinks()
const tocItems = ref<{ id: string; title: string; level: 2 | 3 }[]>([])

function headingId(title: string, index: number): string {
  const slug = title
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
  return `solution-section-${slug || 'heading'}-${index + 1}`
}

onMounted(() => {
  const article = document.querySelector<HTMLElement>('#solution-content .cms-content')
  if (!article) return

  tocItems.value = Array.from(article.querySelectorAll<HTMLElement>('h2, h3'))
    .map((heading, index) => {
      const title = heading.textContent?.trim() ?? ''
      if (!title) return null
      const id = heading.id || headingId(title, index)
      heading.id = id
      return { id, title, level: heading.tagName === 'H3' ? 3 as const : 2 as const }
    })
    .filter((item): item is { id: string; title: string; level: 2 | 3 } => item !== null)
})
</script>

<template>
  <div>
    <!--
      Đo trên ảnh production (/giai-phap/dien-mat-troi-doanh-nghiep/, 1920px):
      chip điều hướng ĐANG CHỌN là nền navy đặc + chữ trắng, các chip còn lại
      nền xám nhạt — cùng quy ước active đã xác nhận ở Tin tức/Dự án/Giải pháp
      (không phải chỉ đổi màu chữ như CSS preview cũ mô tả).
    -->
    <nav class="flex gap-2 overflow-x-auto px-4 py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Các giải pháp khác">
      <a
        class="flex-none whitespace-nowrap rounded-chip bg-slate-50 px-4 py-2.5 text-sm font-bold text-navy-800 no-underline hover:bg-gray-200"
        :href="links.generic(data.navigation.allUrl)"
      >
        Tất cả giải pháp
      </a>
      <a
        v-for="item in data.navigation.items"
        :key="item.slug"
        class="flex-none whitespace-nowrap rounded-chip px-4 py-2.5 text-sm font-bold no-underline"
        :class="item.active ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
        :href="links.generic(item.url)"
      >
        {{ item.title }}
      </a>
    </nav>

    <article>
      <!--
        Hero: panel navy-800 đặc bên trái chứa chữ (không phải chỉ nền trang),
        ảnh bên phải tràn hết chiều cao panel — khác CSS preview cũ (nền trang
        trắng, chữ navy thường). "Tìm hiểu phương án" là liên kết neo xuống
        phần nội dung bên dưới, không có trong DTO vì là khung trang cố định.
      -->
      <header class="mx-4 grid grid-cols-1 overflow-hidden rounded-btn nav:grid-cols-2">
        <div class="flex flex-col justify-center bg-navy-800 p-8 text-white nav:p-12">
          <h1 class="m-0 mb-4 text-[clamp(30px,4vw,44px)] font-black leading-[1.15]">{{ data.solution.title }}</h1>
          <p v-if="data.solution.summary" class="mb-5 text-lg leading-relaxed text-blue-100">{{ data.solution.summary }}</p>
          <div class="flex flex-col items-stretch gap-4 ph:flex-row ph:items-center">
            <a class="inline-flex min-h-11 items-center justify-center gap-4 rounded-btn bg-hdr-gold px-5 font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(data.cta.href)">
              {{ data.cta.label }} <span aria-hidden="true">→</span>
            </a>
            <a class="text-center font-bold text-white no-underline ph:text-left" href="#noi-dung-giai-phap">Tìm hiểu phương án</a>
          </div>
        </div>
        <img v-if="data.solution.image" :src="data.solution.image" :alt="data.solution.title" class="h-full min-h-[280px] w-full object-cover" width="1600" height="1000" fetchpriority="high">
      </header>

      <!--
        Tích hợp sanitizer: content/extraContent giờ là HTML thật qua
        pts_public_richtext.php, không còn mảng paragraph tách theo dòng.
      -->
      <div id="noi-dung-giai-phap" class="mx-auto grid w-full max-w-[1440px] grid-cols-1 gap-8 px-4 py-12 min-[768px]:grid-cols-[minmax(220px,280px)_minmax(0,1fr)] min-[768px]:gap-[clamp(32px,4vw,76px)]">
        <aside
          v-if="tocItems.length"
          class="hidden self-start rounded-[3px] bg-navy-800 p-6 min-[768px]:sticky min-[768px]:top-[124px] min-[768px]:block min-[768px]:max-h-[calc(100vh-150px)] min-[768px]:overflow-y-auto"
          aria-label="Mục lục nội dung"
        >
          <h2 class="mb-3.5 text-sm font-extrabold text-[#efc45b]">Nội dung chính</h2>
          <ol class="m-0 list-none space-y-2 p-0">
            <li v-for="item in tocItems" :key="item.id" :class="item.level === 3 ? 'ml-3' : ''">
              <a class="block border-l-2 border-transparent py-1 pl-3 text-[13px] font-semibold leading-[1.45] text-[#c9d5e6] no-underline transition-colors hover:border-gold-400 hover:text-white" :href="`#${item.id}`">
                {{ item.title }}
              </a>
            </li>
          </ol>
        </aside>

        <article id="solution-content" class="min-w-0">
          <EditorialRichText :content="data.solution.content" />
          <EditorialRichText class="mt-8" :content="data.solution.extraContent" />
          <p v-if="!data.solution.content.html && !data.solution.extraContent.html" class="text-slate-500">Nội dung chi tiết đang được cập nhật.</p>
        </article>
      </div>

      <section v-if="data.solution.gallery.length" class="px-4 pb-12" aria-labelledby="solution-gallery-title">
        <h2 id="solution-gallery-title" class="mb-5 text-[28px] font-black text-navy-800">Hình ảnh giải pháp</h2>
        <div class="grid grid-cols-1 gap-4 tb:grid-cols-3">
          <a v-for="image in data.solution.gallery" :key="image" :href="image">
            <img :src="image" :alt="data.solution.title" class="block aspect-[8/5] w-full rounded-btn object-cover" width="960" height="600" loading="lazy" decoding="async">
          </a>
        </div>
      </section>

    </article>

    <section v-if="data.equipment.length" class="mx-4 mb-12" aria-labelledby="solution-equipment-title">
      <header class="mb-5 flex flex-col items-start gap-3 tb:flex-row tb:items-end tb:justify-between">
        <h2 id="solution-equipment-title" class="m-0 text-[28px] font-black text-navy-800">Thiết bị liên quan</h2>
        <a class="font-bold text-navy-800 no-underline" :href="links.generic('/san-pham/')">Xem tất cả sản phẩm <span aria-hidden="true">→</span></a>
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
  </div>
</template>

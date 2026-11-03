<script setup lang="ts">
import type { PublicHomePayload, PublicHomeCategory } from '#shared/contracts/public-home'

const props = defineProps<{
  categories: PublicHomePayload['categories']
  campaigns: PublicHomePayload['campaigns']
}>()

const slots = [
  {
    key: 'lighting',
    needles: ['den-nlmt'],
    name: 'Đèn năng lượng mặt trời',
    description: 'Đèn đường, đèn pha và đèn sân vườn cho nhiều không gian sử dụng.',
    action: 'Xem các dòng đèn',
    image: '/assets/images/site-visuals/home-street-lighting-v2.png',
    href: '/san-pham/',
  },
  {
    key: 'panel',
    needles: ['tam-pin'],
    name: 'Tấm pin điện mặt trời',
    description: 'Thiết bị tạo điện từ ánh nắng, sử dụng trong hệ thống điện mặt trời.',
    action: 'Xem tấm pin',
    image: '/assets/images/site-visuals/category-panels-user-2026.jpg',
    href: '/san-pham/',
  },
  {
    key: 'inverter',
    needles: ['bien-tan-inverter', 'inverter'],
    name: 'Biến tần (Inverter)',
    description: 'Thiết bị chuyển đổi và quản lý nguồn điện trong hệ thống điện mặt trời.',
    action: 'Xem biến tần',
    image: '/assets/images/site-visuals/category-inverter-user-2026.jpg',
    href: '/san-pham/',
  },
  {
    key: 'storage',
    needles: ['pin-luu-tru', 'luu-tru'],
    name: 'Pin lưu trữ',
    description: 'Lưu trữ điện để sử dụng theo nhu cầu và cấu hình hệ thống.',
    action: 'Xem pin lưu trữ',
    image: '/assets/images/site-visuals/category-storage-user-2026.jpg',
    href: '/san-pham/',
  },
  {
    key: 'pump',
    needles: ['bien-tan-bom', 'bom-nang-luong', 'bom'],
    name: 'Bơm nước năng lượng mặt trời',
    description: 'Thiết bị bơm nước phục vụ tưới tiêu và cấp nước bằng năng lượng mặt trời.',
    action: 'Xem bơm nước',
    image: '/assets/images/site-visuals/category-pump-user-2026.jpg',
    href: '/giai-phap/',
  },
] as const

type HomeCategorySlot = Omit<typeof slots[number], 'image'> & {
  category: PublicHomeCategory | undefined
  image: string
}

const categories = computed<HomeCategorySlot[]>(() => slots.map((slot) => {
  const category = props.categories.find((item) =>
    slot.needles.some((needle) => item.slug.toLowerCase().includes(needle)),
  )
  return {
    ...slot,
    category,
    // The PHP homepage uses a curated, position-specific visual system for
    // this showcase. CMS category media remains available on catalog pages,
    // but must not override these landing assets and break visual parity.
    image: slot.image,
  }
}))

const phpResizableVisual = /^\/assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._/-]*\.(?:jpe?g|png)$/i

function visualCandidate(src: string, width: number, height: number): string {
  if (!phpResizableVisual.test(src)) return src
  return `/thumbs/${width}x${height}x1${src}`
}

function visualSrcSet(src: string, kind: 'hero' | 'card'): string | undefined {
  if (!phpResizableVisual.test(src)) return undefined
  return kind === 'hero'
    ? `${visualCandidate(src, 960, 280)} 960w, ${visualCandidate(src, 1440, 420)} 1440w, ${visualCandidate(src, 1920, 560)} 1920w`
    : `${visualCandidate(src, 320, 250)} 320w, ${visualCandidate(src, 480, 375)} 480w, ${visualCandidate(src, 640, 500)} 640w`
}

const featured = computed(() => categories.value[0]!)
const categoryRail = computed(() => categories.value.slice(1))
const links = useMigrationLinks()
</script>

<template>
  <section id="danh-muc-thiet-bi" class="py-[clamp(56px,5vw,84px)]" aria-labelledby="home-catalog-title">
    <div class="pts-container">
      <header class="mb-8 max-w-[760px]">
        <div class="mb-2 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gold-600">
          <span class="inline-block size-1.5 rounded-full bg-gold-500" /> Danh mục sản phẩm
        </div>
        <h2 id="home-catalog-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] tracking-[-.025em] text-navy-950">
          Sản phẩm năng lượng mặt trời
        </h2>
        <p class="mt-2.5 max-w-[760px] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-500">
          Khám phá các nhóm thiết bị cho nhu cầu chiếu sáng, phát điện, lưu trữ và bơm nước.
        </p>
      </header>

      <!-- Featured Category Showcase -->
      <a
        class="group relative isolate flex min-h-[170px] items-center justify-between gap-[30px] overflow-hidden rounded-[6px] border border-navy-900/10 bg-navy-950 px-8 py-[28px] text-white no-underline shadow-xs transition-all duration-300 hover:shadow-lg min-[768px]:min-h-[240px] max-[640px]:min-h-[190px] max-[640px]:flex-col max-[640px]:items-start max-[640px]:justify-start max-[640px]:gap-3 max-[640px]:px-5 max-[640px]:py-5 min-[1800px]:min-h-[264px]"
        :href="links.generic(featured.category?.url || featured.href)"
      >
        <img
          class="absolute inset-0 z-0 h-full w-full object-cover object-[center_58%] transition-transform duration-500 group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none"
          :src="visualCandidate(featured.image, 960, 280)"
          :srcset="visualSrcSet(featured.image, 'hero')"
          sizes="100vw"
          :alt="featured.category?.name || featured.name"
          width="1440"
          height="420"
          loading="lazy"
        >
        <div class="absolute inset-0 z-[1] bg-gradient-to-r from-navy-950/90 via-navy-950/60 to-transparent max-[640px]:bg-gradient-to-t max-[640px]:from-navy-950/95 max-[640px]:via-navy-950/70" />

        <span class="relative z-10 grid w-full max-w-[850px] grid-cols-[56px_minmax(0,1fr)] items-center gap-5 border-l-2 border-gold-500 bg-navy-950/60 backdrop-blur-xs px-5 py-4 rounded-r-[4px] max-[640px]:w-full max-[640px]:grid-cols-[44px_minmax(0,1fr)] max-[640px]:gap-[14px] max-[640px]:px-3.5 max-[640px]:py-3">
          <span class="flex h-14 w-14 items-center justify-center rounded-[4px] bg-navy-900/80 border border-gold-500/30 text-gold-400 max-[640px]:h-11 max-[640px]:w-11" aria-hidden="true">
            <svg class="h-[22px] w-[22px] max-[640px]:h-[18px] max-[640px]:w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
          </span>
          <span class="flex min-w-0 flex-col gap-[4px]">
            <strong class="text-[20px] font-bold leading-[1.3] text-white max-[640px]:text-[16px]">{{ featured.category?.name || featured.name }}</strong>
            <small class="text-[14.5px] leading-[1.5] text-slate-200 max-[640px]:text-[13px] line-clamp-2">{{ featured.description }}</small>
          </span>
        </span>
        <span class="relative z-10 inline-flex flex-none items-center gap-2 rounded-[4px] border border-gold-400/40 bg-navy-900/80 px-4 py-2.5 text-sm font-bold text-gold-300 backdrop-blur-xs transition-colors group-hover:bg-gold-500 group-hover:text-navy-950 max-[640px]:self-end">
          {{ featured.action }} <span aria-hidden="true">→</span>
        </span>
      </a>

      <!-- 4 Category Rail (Product Atelier) -->
      <div
        class="mt-5 grid grid-cols-4 gap-4 overflow-x-auto overscroll-x-contain py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-[860px]:flex max-[860px]:overflow-x-auto"
        tabindex="0"
        aria-label="Bốn nhóm thiết bị nổi bật, có thể cuộn ngang"
      >
        <a
          v-for="slot in categoryRail"
          :key="slot.key"
          class="group relative isolate flex min-h-[310px] min-w-0 flex-col justify-end overflow-hidden rounded-[6px] border border-slate-200/90 bg-white text-white no-underline shadow-2xs transition-all duration-300 hover:border-gold-500/70 hover:shadow-md hover:-translate-y-1 max-[860px]:min-h-[310px] max-[860px]:w-[280px] max-[860px]:flex-none max-[640px]:min-h-[270px] max-[640px]:w-[250px] min-[1800px]:min-h-[340px]"
          :class="slot.key === 'panel' ? '[&>img]:object-top' : ''"
          :href="links.generic(slot.category?.url || slot.href)"
        >
          <img
            class="absolute inset-0 z-0 h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-[1.04] motion-reduce:transform-none motion-reduce:transition-none"
            :src="visualCandidate(slot.image, 320, 250)"
            :srcset="visualSrcSet(slot.image, 'card')"
            sizes="(min-width: 861px) 25vw, 280px"
            :alt="slot.category?.name || slot.name"
            width="640"
            height="500"
            loading="lazy"
          >
          <div class="absolute inset-0 z-[1] bg-gradient-to-t from-navy-950/95 via-navy-950/45 to-transparent transition-opacity group-hover:from-navy-950" />

          <span class="relative z-10 mt-auto flex flex-col gap-1.5 p-4 text-white">
            <strong class="line-clamp-2 text-[17px] font-bold leading-[1.3] text-white group-hover:text-gold-300 transition-colors">{{ slot.category?.name || slot.name }}</strong>
            <small class="line-clamp-2 min-h-[44px] text-[13px] leading-[1.5] text-slate-200">{{ slot.description }}</small>
            <span class="mt-1 inline-flex items-center gap-1.5 text-[13px] font-bold text-gold-400 group-hover:translate-x-1 transition-transform">
              {{ slot.action }} <span aria-hidden="true">→</span>
            </span>
          </span>
        </a>
      </div>
    </div>
  </section>
</template>

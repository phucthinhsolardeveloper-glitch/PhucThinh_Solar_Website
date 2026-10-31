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
    image: category?.image || (slot.key === 'lighting' ? props.campaigns[0]?.image || slot.image : slot.image),
  }
}))

const featured = computed(() => categories.value[0]!)
const categoryRail = computed(() => categories.value.slice(1))
const links = useMigrationLinks()
</script>

<template>
  <section id="danh-muc-thiet-bi" class="py-[clamp(64px,6vw,92px)]" aria-labelledby="home-catalog-title">
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <header class="mb-10 max-w-[760px]">
        <h2 id="home-catalog-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950">
          Sản phẩm năng lượng mặt trời
        </h2>
        <p class="mt-[13px] max-w-[760px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">
          Khám phá các nhóm thiết bị cho nhu cầu chiếu sáng, phát điện, lưu trữ và bơm nước.
        </p>
      </header>

      <a
        class="group relative isolate flex min-h-[160px] items-center justify-between gap-[30px] overflow-hidden rounded-[4px] bg-navy-950 px-10 py-[30px] text-white no-underline transition-colors hover:text-white min-[768px]:ml-[-15px] min-[768px]:min-h-[240px] min-[768px]:w-[calc(100%+30px)] max-[640px]:min-h-[190px] max-[640px]:flex-col max-[640px]:items-start max-[640px]:justify-start max-[640px]:gap-3 max-[640px]:px-[22px] max-[640px]:py-6 min-[1800px]:min-h-[262px]"
        :href="links.generic(featured.category?.url || featured.href)"
      >
        <img
          class="absolute inset-0 z-0 h-full w-full object-cover object-[center_58%] transition-transform duration-300 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
          :src="featured.image"
          :alt="featured.category?.name || featured.name"
          width="1440"
          height="420"
          loading="lazy"
        >
        <span class="relative z-10 grid w-full max-w-[850px] grid-cols-[56px_minmax(0,1fr)] items-center gap-5 border-t border-white/45 bg-[linear-gradient(180deg,rgba(6,21,55,.22),rgba(6,21,55,.68))] px-5 py-4 max-[640px]:w-full max-[640px]:grid-cols-[44px_minmax(0,1fr)] max-[640px]:gap-[14px] max-[640px]:px-[13px] max-[640px]:py-[13px]">
          <span class="flex h-14 w-14 items-center justify-center rounded-[4px] bg-navy-950 text-[#c69a45] max-[640px]:h-11 max-[640px]:w-11" aria-hidden="true">
            <svg class="h-[22px] w-[22px] max-[640px]:h-[18px] max-[640px]:w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
          </span>
          <span class="flex min-w-0 flex-col gap-[5px]">
            <strong class="text-[21px] leading-[1.4] text-white max-[640px]:text-[17px]">{{ featured.category?.name || featured.name }}</strong>
            <small class="text-[15px] leading-[1.6] text-[#e8eef6] max-[640px]:text-[13px]">{{ featured.description }}</small>
          </span>
        </span>
        <span class="relative z-10 inline-flex flex-none items-center gap-2 border-t border-white/45 bg-[linear-gradient(180deg,rgba(6,21,55,.4),rgba(6,21,55,.86))] px-4 pb-4 pt-3 text-sm font-bold text-white after:absolute after:bottom-0 after:right-4 after:h-px after:w-10 after:bg-[#c69a45] max-[640px]:self-end">
          {{ featured.action }} <span aria-hidden="true">→</span>
        </span>
      </a>

      <div
        class="mt-4 grid grid-cols-4 gap-4 overflow-x-auto overscroll-x-contain py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden min-[768px]:ml-[-15px] min-[768px]:w-[calc(100%+30px)] max-[860px]:mr-[-24px] max-[860px]:flex max-[860px]:overflow-x-auto max-[860px]:pr-6 max-[640px]:mr-[-20px] max-[640px]:pr-5 max-[360px]:mr-[-16px] max-[360px]:pr-4"
        tabindex="0"
        aria-label="Bốn nhóm thiết bị nổi bật, có thể cuộn ngang"
      >
        <a
          v-for="slot in categoryRail"
          :key="slot.key"
          class="group relative isolate flex min-h-[320px] min-w-0 flex-col justify-end overflow-hidden rounded-[4px] border border-[#e2e6eb] bg-[#e9edf1] text-white no-underline max-[860px]:min-h-[320px] max-[860px]:w-[280px] max-[860px]:flex-none max-[640px]:min-h-[280px] max-[640px]:w-[250px] min-[1800px]:min-h-[350px]"
          :class="slot.key === 'panel' ? '[&>img]:object-top' : ''"
          :href="links.generic(slot.category?.url || slot.href)"
        >
          <img
            class="absolute inset-0 z-0 h-full w-full object-cover object-center transition-transform duration-300 group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
            :src="slot.image"
            :alt="slot.category?.name || slot.name"
            width="640"
            height="500"
            loading="lazy"
          >
          <span class="relative z-10 mt-auto flex flex-col gap-[5px] border-t border-white/45 bg-[linear-gradient(180deg,rgba(6,21,55,.22),rgba(6,21,55,.68))] px-[18px] py-4 text-white max-[640px]:px-[15px]">
            <strong class="line-clamp-2 text-[18px] font-bold leading-[1.4] text-white">{{ slot.category?.name || slot.name }}</strong>
            <small class="line-clamp-3 min-h-[66px] text-sm leading-[1.6] text-[#e8eef6]">{{ slot.description }}</small>
            <span class="mt-[3px] inline-flex items-center gap-[7px] text-[13.5px] font-bold text-white after:h-px after:w-10 after:bg-[#c69a45]">
              {{ slot.action }} <span aria-hidden="true">→</span>
            </span>
          </span>
        </a>
      </div>
    </div>
  </section>
</template>

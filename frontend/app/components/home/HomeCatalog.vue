<script setup lang="ts">import type { PublicHomePayload, PublicHomeCategory } from '#shared/contracts/public-home'; const props = defineProps<{ categories: PublicHomePayload['categories']; productGroups: PublicHomePayload['productGroups'] }>(); const featured = computed(() => props.categories.find((category) => /den-nlmt|đèn/i.test(`${category.slug} ${category.name}`)) || props.categories[0]); const categoryRail = computed(() => { const preferred = ['tam-pin', 'bien-tan', 'pin-luu-tru', 'bom']; const picked = preferred.map((needle) => props.categories.find((category) => `${category.slug} ${category.name}`.toLowerCase().includes(needle))).filter((category): category is PublicHomeCategory => !!category); return picked.length >= 4 ? picked.slice(0, 4) : props.categories.filter((category) => category.id !== featured.value?.id).slice(0, 4); }); function summary(category: PublicHomeCategory) { const key = `${category.slug} ${category.name}`.toLowerCase(); if (key.includes('den')) return 'Đèn đường, đèn pha và đèn sân vườn cho nhiều không gian sử dụng.'; if (key.includes('tam-pin')) return 'Thiết bị tạo điện từ ánh nắng, sử dụng trong hệ thống điện mặt trời.'; if (key.includes('bien-tan') || key.includes('inverter')) return 'Thiết bị chuyển đổi và quản lý nguồn điện trong hệ thống điện mặt trời.'; if (key.includes('luu-tru')) return 'Lưu trữ điện để chủ động sử dụng theo nhu cầu và cấu hình hệ thống.'; if (key.includes('bom')) return 'Thiết bị bơm nước phục vụ tưới tiêu và cấp nước bằng năng lượng mặt trời.'; return 'Khám phá thiết bị và lựa chọn cấu hình phù hợp với nhu cầu sử dụng.' } function action(category: PublicHomeCategory) { const key = `${category.slug} ${category.name}`.toLowerCase(); if (key.includes('den')) return 'Xem các dòng đèn'; if (key.includes('tam-pin')) return 'Xem tấm pin'; if (key.includes('inverter') || key.includes('bien-tan')) return 'Xem biến tần'; if (key.includes('luu-tru')) return 'Xem pin lưu trữ'; if (key.includes('bom')) return 'Xem bơm nước'; return 'Xem danh mục' } const links = useMigrationLinks()</script>
<template>
  <section v-if="featured" id="danh-muc-thiet-bi" class="py-[clamp(64px,6vw,92px)]" aria-labelledby="home-catalog-title">
    <div class="mx-auto w-[min(calc(100%-32px),2200px)]">
      <header class="mb-10 max-w-[760px]"><h1 id="home-catalog-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950">Sản phẩm năng lượng mặt trời</h1><p class="mt-[13px] max-w-[760px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">Khám phá các nhóm thiết bị cho nhu cầu chiếu sáng, phát điện, lưu trữ và bơm nước.</p></header>
      <a class="relative isolate flex min-h-[240px] items-center justify-between gap-[30px] overflow-hidden rounded-[4px] bg-navy-950 px-10 py-[30px] text-white no-underline max-md:min-h-[220px] max-md:flex-col max-md:items-stretch max-md:justify-end max-md:gap-3 max-md:px-5 max-md:py-6" :href="links.generic(featured.url)">
        <img v-if="featured.image" class="absolute inset-0 -z-20 h-full w-full object-cover object-[center_58%]" :src="featured.image" :alt="featured.name" width="1440" height="420" loading="lazy">
        <span class="absolute inset-0 -z-10" aria-hidden="true" />
        <span class="relative z-10 flex w-full max-w-[850px] flex-col gap-[5px] border-t border-white/45 bg-navy-950/70 px-5 py-4"><strong class="text-[18px] leading-[1.4]">{{ featured.name }}</strong><small class="text-sm leading-[1.6] text-[#e8eef6]">{{ summary(featured) }}</small></span>
        <span class="relative z-10 flex-none self-end border-t border-white/45 bg-navy-950/75 px-4 pb-4 pt-3 text-sm font-bold">{{ action(featured) }} →</span>
      </a>
      <div class="grid grid-flow-col auto-cols-[80vw] snap-x snap-mandatory gap-4 overflow-x-auto py-1 [scrollbar-width:none] md:auto-cols-auto md:grid-flow-row md:grid-cols-4" tabindex="0" aria-label="Bốn nhóm thiết bị nổi bật, có thể cuộn ngang">
        <a v-for="category in categoryRail" :key="category.id" class="relative isolate flex min-h-[320px] min-w-0 snap-start flex-col justify-end overflow-hidden rounded-[4px] border border-hdr-line bg-[#e9edf1] text-white no-underline" :href="links.generic(category.url)">
          <img v-if="category.image" class="absolute inset-0 -z-20 h-full w-full object-cover" :src="category.image" :alt="category.name" width="640" height="500" loading="lazy">
          <span class="absolute inset-0 -z-10" aria-hidden="true" />
          <span class="relative z-10 mt-auto border-t border-white/45 bg-navy-950/70 px-[18px] py-4"><strong class="block text-[18px] leading-[1.4]">{{ category.name }}</strong><small class="mt-1 block text-sm leading-[1.6] text-[#e8eef6]">{{ summary(category) }}</small><span class="mt-[5px] block text-[13.5px] font-bold">{{ action(category) }} →</span></span>
        </a>
      </div>
      <!-- PHP index_tpl.php has no inline per-category product listing on the
           homepage (only the 4 category tiles above); productGroups stays in
           the DTO/contract for potential later use but is not rendered here,
           to match PHP home layout. -->

    </div>
  </section>
</template>

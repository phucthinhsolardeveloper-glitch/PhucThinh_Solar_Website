<script setup lang="ts">
import type { PublicCatalogHero } from '#shared/contracts/public-catalog'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'

defineProps<{
  heading: string
  description: string
  hero: PublicCatalogHero | null
}>()
</script>

<template>
  <div class="w-full border-b border-slate-200/80">
    <section class="relative isolate h-[250px] overflow-hidden bg-navy-950 text-white max-[769px]:h-[230px]" aria-labelledby="catalog-page-heading">
      <div class="relative flex h-full items-center">
        <img
          v-if="hero?.image"
          :src="hero.image"
          alt=""
          width="1800"
          height="600"
          sizes="100vw"
          class="absolute inset-0 -z-20 h-full w-full object-cover object-[center_30%]"
          fetchpriority="high"
        >
        <span class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/92 via-navy-900/78 to-navy-950/30" aria-hidden="true" />
        <div class="mx-auto flex h-full w-full max-w-[1200px] flex-col items-start justify-center py-8 pb-12 max-[769px]:h-[154px] max-[769px]:w-[calc(100%_-_32px)] max-[769px]:max-w-none max-[769px]:px-4 max-[769px]:py-0 max-[769px]:pb-[10px]">
          <h1 id="catalog-page-heading" class="mb-2 w-[min(520px,48vw)] max-w-[520px] text-[clamp(27px,2.15vw,36px)] font-extrabold uppercase leading-[1.08] tracking-[-0.02em] text-gold-400 max-[769px]:mb-[7px] max-[769px]:w-[min(100%,300px)] max-[769px]:max-w-[min(100%,300px)] max-[769px]:text-[clamp(24px,7vw,27px)] max-[769px]:leading-[1.12]">
            {{ heading }}
          </h1>
          <p class="m-0 max-w-[560px] text-[14px] leading-[1.45] text-blue-50/95 max-[769px]:max-w-[300px] max-[769px]:text-[13px] max-[481px]:hidden">{{ description }}</p>
        </div>
      </div>
    </section>

    <div class="bg-white" aria-label="Lợi ích khi chọn sản phẩm">
      <div class="mx-auto grid w-[min(1800px,calc(100vw_-_96px))] grid-cols-2 gap-x-8 gap-y-0 py-4 max-[1199px]:w-[calc(100vw_-_48px)] max-[1199px]:gap-x-8 max-[769px]:w-[calc(100vw_-_32px)] max-[769px]:gap-x-3 max-[769px]:gap-y-0 max-[769px]:py-2 min-[769px]:grid-cols-4">
        <div class="flex min-h-[60px] items-center justify-center gap-3.5 max-[769px]:min-h-12 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1.5">
          <span class="flex h-9 w-9 flex-none items-center justify-center text-gold-700 max-[769px]:h-5 max-[769px]:w-[22px]">
            <CatalogMenuIcon class="h-8 w-8 max-[769px]:h-5 max-[769px]:w-[22px]" name="light" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Tư vấn lựa chọn</strong><small class="block text-[12px] text-slate-600 max-[769px]:hidden">Đúng nhu cầu, hiệu quả</small></span>
        </div>
        <div class="flex min-h-[60px] items-center justify-center gap-3.5 max-[769px]:min-h-12 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1.5">
          <span class="flex h-9 w-9 flex-none items-center justify-center text-gold-700 max-[769px]:h-5 max-[769px]:w-[22px]">
            <CatalogMenuIcon class="h-8 w-8 max-[769px]:h-5 max-[769px]:w-[22px]" name="cog" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Hỗ trợ kỹ thuật</strong><small class="block text-[12px] text-slate-600 max-[769px]:hidden">Trong suốt quá trình sử dụng</small></span>
        </div>
        <div class="flex min-h-[60px] items-center justify-center gap-3.5 max-[769px]:min-h-12 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1.5">
          <span class="flex h-9 w-9 flex-none items-center justify-center text-gold-700 max-[769px]:h-5 max-[769px]:w-[22px]">
            <CatalogMenuIcon class="h-8 w-8 max-[769px]:h-5 max-[769px]:w-[22px]" name="shield" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Chính sách rõ ràng</strong><small class="block text-[12px] text-slate-600 max-[769px]:hidden">Minh bạch, dễ hiểu</small></span>
        </div>
        <div class="flex min-h-[60px] items-center justify-center gap-3.5 max-[769px]:min-h-12 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1.5">
          <span class="flex h-9 w-9 flex-none items-center justify-center text-gold-700 max-[769px]:h-5 max-[769px]:w-[22px]">
            <CatalogMenuIcon class="h-8 w-8 max-[769px]:h-5 max-[769px]:w-[22px]" name="truck" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Giao hàng toàn quốc</strong><small class="block text-[12px] text-slate-600 max-[769px]:hidden">Nhanh chóng, an toàn</small></span>
        </div>
      </div>
    </div>
  </div>
</template>

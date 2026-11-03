<script setup lang="ts">
import type { PublicCatalogHero } from '#shared/contracts/public-catalog'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'

defineProps<{
  heading: string
  description: string
  hero: PublicCatalogHero | null
}>()

const phpResizableCatalogImage = /^\/(?:upload\/(?:product|news|photo)|assets\/images\/site-visuals)\/[A-Za-z0-9][A-Za-z0-9._/-]*\.(?:jpe?g|png|webp)$/i

function catalogHeroCandidate(src: string, width: 960 | 1800): string {
  if (!phpResizableCatalogImage.test(src)) return src
  const height = Math.round(width / 3)
  return `/thumbs/${width}x${height}x1${src}`
}

function catalogHeroSrcSet(src: string): string | undefined {
  if (!phpResizableCatalogImage.test(src)) return undefined
  return `${catalogHeroCandidate(src, 960)} 960w, ${catalogHeroCandidate(src, 1800)} 1800w`
}
</script>

<template>
  <div class="w-full border-b border-slate-200/80">
    <section class="relative isolate h-[250px] overflow-hidden bg-navy-950 text-white max-[769px]:h-[230px]" aria-labelledby="catalog-page-heading">
      <div class="relative flex h-full items-center">
        <img
          v-if="hero?.image"
          :src="catalogHeroCandidate(hero.image, 960)"
          :srcset="catalogHeroSrcSet(hero.image)"
          alt=""
          width="1800"
          height="600"
          sizes="100vw"
          class="absolute inset-0 -z-20 h-full w-full object-cover object-[center_30%]"
          fetchpriority="high"
        >
        <span class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/95 via-navy-900/80 to-navy-950/40" aria-hidden="true" />
        <div class="mx-auto flex h-full w-full max-w-[1200px] flex-col items-start justify-center py-8 pb-12 max-[769px]:h-[154px] max-[769px]:w-[calc(100%_-_32px)] max-[769px]:max-w-none max-[769px]:px-4 max-[769px]:py-0 max-[769px]:pb-[10px]">
          <h1 id="catalog-page-heading" class="mb-2 w-[min(560px,50vw)] max-w-[560px] text-[clamp(30px,2.6vw,40px)] font-extrabold uppercase leading-[1.08] tracking-[-0.02em] text-gold-400 max-[769px]:mb-[7px] max-[769px]:w-[min(100%,300px)] max-[769px]:max-w-[min(100%,300px)] max-[769px]:text-[clamp(24px,7vw,27px)] max-[769px]:leading-[1.12]">
            {{ heading }}
          </h1>
          <p class="m-0 max-w-[580px] text-[15px] leading-[1.5] text-blue-50/95 max-[769px]:max-w-[300px] max-[769px]:text-[13px] max-[481px]:hidden">{{ description }}</p>
        </div>
      </div>
    </section>

    <!-- 4 Value Props Bar -->
    <div class="bg-white border-b border-slate-100" aria-label="Lợi ích khi chọn sản phẩm">
      <div class="pts-container grid grid-cols-2 gap-x-6 gap-y-0 py-3.5 max-[769px]:gap-x-3 max-[769px]:py-2 min-[769px]:grid-cols-4">
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="light" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Tư vấn lựa chọn</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Đúng nhu cầu, hiệu quả</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="cog" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Hỗ trợ kỹ thuật</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Trong suốt quá trình sử dụng</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="shield" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Chính sách rõ ràng</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Minh bạch, dễ hiểu</small></span>
        </div>
        <div class="flex min-h-[56px] items-center justify-center gap-3 max-[769px]:min-h-11 max-[769px]:justify-start max-[769px]:gap-2 max-[769px]:py-1">
          <span class="flex h-9 w-9 flex-none items-center justify-center rounded-[4px] bg-gold-50 text-gold-700 max-[769px]:h-6 max-[769px]:w-6">
            <CatalogMenuIcon class="h-6 w-6 max-[769px]:h-4 max-[769px]:w-4" name="truck" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950 max-[769px]:text-[12px]">Giao hàng toàn quốc</strong><small class="block text-[12px] text-slate-500 max-[769px]:hidden">Nhanh chóng, an toàn</small></span>
        </div>
      </div>
    </div>
  </div>
</template>

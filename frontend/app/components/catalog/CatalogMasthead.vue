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
    <section class="relative isolate overflow-hidden bg-navy-950 text-white" aria-labelledby="catalog-page-heading">
      <div class="relative flex min-h-[clamp(180px,18vw,252px)] items-center">
        <img
          v-if="hero?.image"
          :src="hero.image"
          alt=""
          width="1800"
          height="600"
          sizes="100vw"
          class="absolute inset-0 -z-20 h-full w-full object-cover"
          fetchpriority="high"
        >
        <span class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/92 via-navy-900/78 to-navy-950/30" aria-hidden="true" />
        <div class="pts-container py-8">
          <h1 id="catalog-page-heading" class="mb-2.5 max-w-[640px] text-[clamp(24px,2.4vw,36px)] font-extrabold uppercase leading-[1.15] tracking-[.015em] text-gold-400">
            {{ heading }}
          </h1>
          <p class="m-0 max-w-[64ch] text-[14px] leading-[1.58] text-blue-50/95 tb:text-[15px]">{{ description }}</p>
        </div>
      </div>
    </section>

    <div class="bg-white" aria-label="Lợi ích khi chọn sản phẩm">
      <div class="pts-container grid grid-cols-2 gap-x-4 gap-y-3 py-4 tb:grid-cols-4">
        <div class="flex items-center gap-2.5">
          <span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700">
            <CatalogMenuIcon class="h-5 w-5" name="light" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950">Tư vấn lựa chọn</strong><small class="block text-[12px] text-slate-600">Đúng nhu cầu, hiệu quả</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700">
            <CatalogMenuIcon class="h-5 w-5" name="cog" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950">Hỗ trợ kỹ thuật</strong><small class="block text-[12px] text-slate-600">Trong suốt quá trình sử dụng</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700">
            <CatalogMenuIcon class="h-5 w-5" name="shield" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950">Chính sách rõ ràng</strong><small class="block text-[12px] text-slate-600">Minh bạch, dễ hiểu</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700">
            <CatalogMenuIcon class="h-5 w-5" name="truck" />
          </span>
          <span><strong class="block text-[13.5px] font-bold text-navy-950">Giao hàng toàn quốc</strong><small class="block text-[12px] text-slate-600">Nhanh chóng, an toàn</small></span>
        </div>
      </div>
    </div>
  </div>
</template>

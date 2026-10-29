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
  <div class="relative left-1/2 col-span-full w-screen -translate-x-1/2">
    <section class="relative isolate overflow-hidden bg-navy-950 text-white" aria-labelledby="catalog-page-heading">
      <div class="relative flex min-h-[clamp(180px,18vw,260px)] items-center">
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
        <span class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/90 via-navy-950/70 to-navy-950/20" aria-hidden="true" />
        <div class="mx-auto w-full max-w-(--container-page) px-(--spacing-gutter) py-8">
          <h1 id="catalog-page-heading" class="mb-3 max-w-[620px] text-[clamp(25px,2.4vw,36px)] font-extrabold uppercase leading-[1.14] tracking-[.015em] text-gold-400">
            {{ heading }}
          </h1>
          <p class="m-0 max-w-[64ch] text-[14px] leading-[1.5] text-white">{{ description }}</p>
        </div>
      </div>
    </section>

    <div class="bg-white" aria-label="Lợi ích khi chọn sản phẩm">
      <div class="mx-auto grid w-full max-w-(--container-page) grid-cols-2 gap-x-4 gap-y-3 px-(--spacing-gutter) py-4 tb:grid-cols-4">
        <div class="flex items-center gap-2.5">
          <CatalogMenuIcon class="h-5 w-5 flex-none text-gold-600" name="light" />
          <span><strong class="block text-[14px] font-bold text-navy-950">Tư vấn lựa chọn</strong><small class="block text-[12px] text-slate-600">Đúng nhu cầu, hiệu quả</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <CatalogMenuIcon class="h-5 w-5 flex-none text-gold-600" name="cog" />
          <span><strong class="block text-[14px] font-bold text-navy-950">Hỗ trợ kỹ thuật</strong><small class="block text-[12px] text-slate-600">Trong suốt quá trình sử dụng</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <CatalogMenuIcon class="h-5 w-5 flex-none text-gold-600" name="shield" />
          <span><strong class="block text-[14px] font-bold text-navy-950">Chính sách rõ ràng</strong><small class="block text-[12px] text-slate-600">Minh bạch, dễ hiểu</small></span>
        </div>
        <div class="flex items-center gap-2.5">
          <CatalogMenuIcon class="h-5 w-5 flex-none text-gold-600" name="truck" />
          <span><strong class="block text-[14px] font-bold text-navy-950">Giao hàng toàn quốc</strong><small class="block text-[12px] text-slate-600">Nhanh chóng, an toàn</small></span>
        </div>
      </div>
    </div>
  </div>
</template>

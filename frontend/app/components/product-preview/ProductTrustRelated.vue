<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

defineProps<{ extras: PublicProductExtrasPayload }>()
</script>

<template>
  <section v-if="extras.commitments.length" id="ly-do-chon-phuc-thinh-solar" class="scroll-mt-24 border-t border-gray-200 py-10">
    <h2 class="mb-6 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Lý do chọn Phúc Thịnh Solar</h2>
    <div class="grid gap-px border border-gray-200 bg-gray-200 ph:grid-cols-2 nav:grid-cols-4">
      <article v-for="item in extras.commitments" :key="item.name" class="flex min-h-[120px] items-center gap-4 bg-white p-5">
        <img v-if="item.image" :src="item.image" alt="" class="h-12 w-12 shrink-0 object-contain" loading="lazy">
        <strong class="leading-snug">{{ item.name }}</strong>
      </article>
    </div>
  </section>

  <section v-if="extras.related.length" id="san-pham-lien-quan" class="scroll-mt-24 border-t border-gray-200 py-10">
    <h2 class="mb-6 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Sản phẩm liên quan</h2>
    <CatalogProductGrid :products="extras.related" />
  </section>
</template>

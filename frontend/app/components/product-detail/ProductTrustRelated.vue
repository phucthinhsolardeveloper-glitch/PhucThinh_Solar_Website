<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

defineProps<{ extras: PublicProductExtrasPayload }>()

function commitmentIcon(name: string): string {
  const value = name.toLocaleLowerCase('vi')
  if (value.includes('đổi') || value.includes('trả')) return 'i-lucide-refresh-ccw'
  if (value.includes('bảo hành')) return 'i-lucide-shield-check'
  if (value.includes('chính hãng') || value.includes('chất lượng')) return 'i-lucide-badge-check'
  return 'i-lucide-circle-check'
}
</script>

<template>
  <section v-if="extras.related.length" id="san-pham-lien-quan" class="scroll-mt-24 py-12 tb:py-16">
    <h2 class="mb-6 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Sản phẩm liên quan</h2>
    <CatalogProductGrid :products="extras.related" layout="rail" />
  </section>

  <section data-fold="300" v-if="extras.commitments.length" id="ly-do-chon-phuc-thinh-solar" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16">
    <h2 class="mb-6 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Lý do chọn Phúc Thịnh Solar</h2>
    <div class="grid grid-cols-1 gap-x-8 gap-y-5 ph:grid-cols-2 nav:grid-cols-3">
      <article v-for="item in extras.commitments" :key="item.name" class="flex min-w-0 items-center gap-4">
        <img v-if="item.image" :src="item.image" alt="" class="h-12 w-12 shrink-0 object-contain" loading="lazy">
        <span v-else class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <UIcon :name="commitmentIcon(item.name)" class="size-5" />
        </span>
        <strong class="leading-snug">{{ item.name }}</strong>
      </article>
    </div>
  </section>
</template>

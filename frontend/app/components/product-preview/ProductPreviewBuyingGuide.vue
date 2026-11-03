<script setup lang="ts">
import type { PublicProductBuyingGuide } from '#shared/contracts/public-product-detail'

defineProps<{ guides: PublicProductBuyingGuide[] }>()
</script>

<template>
  <section id="huong-dan-lua-chon" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-buying-guide-title">
    <h2 id="product-buying-guide-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      Thông tin cần biết trước khi lựa chọn
    </h2>
    <dl class="m-0 grid gap-x-8 gap-y-6 min-[768px]:grid-cols-2 min-[1200px]:grid-cols-3">
      <div
        v-for="guide in guides"
        :key="guide.key"
        class="min-w-0 border-t-2 border-gold-500 pt-4"
      >
        <dt class="mb-1.5 text-[13.5px] font-bold text-slate-500">{{ guide.label }}</dt>
        <dd class="m-0 whitespace-pre-line text-[clamp(18px,1.5vw,22px)] font-black leading-snug tracking-tight text-navy-900 tabular-nums">{{ guide.value }}</dd>
      </div>
    </dl>
  </section>
</template>

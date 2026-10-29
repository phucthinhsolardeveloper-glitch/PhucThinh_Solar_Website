<script setup lang="ts">
import type { PublicProductBuyingGuide } from '#shared/contracts/public-product-detail'

defineProps<{ guides: PublicProductBuyingGuide[] }>()
</script>

<template>
  <section id="huong-dan-lua-chon" class="scroll-mt-24 py-10" aria-labelledby="product-buying-guide-title">
    <h2 id="product-buying-guide-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      Thông tin cần biết trước khi lựa chọn
    </h2>
    <dl class="m-0 grid gap-x-8 gap-y-6 min-[768px]:grid-cols-2 min-[1200px]:grid-cols-3">
      <div v-for="guide in guides" :key="guide.key" class="min-w-0">
        <dt class="mb-1 text-sm font-bold text-slate-500">{{ guide.label }}</dt>
        <dd class="m-0 whitespace-pre-line text-base leading-7 text-navy-800">{{ guide.value }}</dd>
      </div>
    </dl>
  </section>
</template>

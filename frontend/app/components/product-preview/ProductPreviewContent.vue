<script setup lang="ts">
import type { PublicProductContent } from '#shared/contracts/public-product-detail'

defineProps<{ content: PublicProductContent }>()
</script>

<template>
  <section v-if="content.blocks.length || content.highlights?.length" id="tong-quan" class="scroll-mt-24 border-t border-gray-200 py-10" aria-labelledby="product-content-title">
    <h2 id="product-content-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      Mô tả sản phẩm
    </h2>

    <div v-if="content.highlights?.length" class="mb-6">
      <h3 class="mb-4 text-lg font-black text-navy-800">Điểm nổi bật</h3>
      <ul class="m-0 flex flex-wrap gap-2 p-0 [list-style:none]">
        <li v-for="(highlight, index) in content.highlights" :key="`${index}-${highlight}`" class="bg-slate-100 px-3 py-2 text-sm font-semibold text-navy-800">{{ highlight }}</li>
      </ul>
    </div>

    <details v-if="content.blocks.length" class="border-y border-gray-200">
      <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 py-3 font-bold text-navy-800 [&::-webkit-details-marker]:hidden">
        Xem mô tả chi tiết
        <span class="text-xl text-gold-700" aria-hidden="true">+</span>
      </summary>
      <div class="space-y-6 py-4">
        <template v-for="(block, index) in content.blocks" :key="`${block.type}-${index}`">
          <h3 v-if="block.type === 'heading'" class="m-0 pt-2 text-[clamp(20px,2vw,27px)] font-black leading-tight text-navy-800">
            {{ block.text }}
          </h3>

          <p v-else-if="block.type === 'text'" class="m-0 whitespace-pre-line text-base leading-7 text-slate-600">
            {{ block.text }}
          </p>

          <ol v-else-if="block.type === 'list' && block.ordered" class="m-0 list-decimal space-y-2 pl-6 text-base leading-7 text-slate-600">
            <li v-for="(item, itemIndex) in block.items" :key="itemIndex">{{ item }}</li>
          </ol>

          <ul v-else-if="block.type === 'list'" class="m-0 list-disc space-y-2 pl-6 text-base leading-7 text-slate-600">
            <li v-for="(item, itemIndex) in block.items" :key="itemIndex">{{ item }}</li>
          </ul>

          <figure v-else-if="block.type === 'image'" class="m-0 overflow-hidden rounded-btn bg-slate-50">
            <img :src="block.image" :alt="block.caption || ''" class="mx-auto max-h-[680px] w-full object-contain" loading="lazy" decoding="async">
            <figcaption v-if="block.caption" class="px-4 py-3 text-center text-sm leading-relaxed text-slate-500">{{ block.caption }}</figcaption>
          </figure>

          <div
            v-else-if="block.type === 'media'"
            class="grid items-center gap-6 rounded-btn bg-slate-50 p-5 nav:p-7"
            :class="[block.side === 'center' ? 'nav:grid-cols-1' : 'nav:grid-cols-2', { 'nav:[&>figure]:order-2': block.side === 'right' }]"
          >
            <figure v-if="block.image" class="m-0 overflow-hidden rounded-btn">
              <img :src="block.image" :alt="block.title || ''" class="mx-auto max-h-[620px] w-full object-contain" loading="lazy" decoding="async">
            </figure>
            <div :class="{ 'mx-auto max-w-[780px] text-center': block.side === 'center' }">
              <strong v-if="block.title" class="mb-3 block text-xl font-black text-navy-800">{{ block.title }}</strong>
              <p v-if="block.text" class="m-0 whitespace-pre-line text-base leading-7 text-slate-600">{{ block.text }}</p>
            </div>
          </div>
        </template>
      </div>
    </details>

  </section>
</template>

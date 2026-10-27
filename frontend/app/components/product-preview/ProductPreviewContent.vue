<script setup lang="ts">
import type { PublicProductContent } from '#shared/contracts/public-product-detail'

defineProps<{ content: PublicProductContent }>()
</script>

<template>
  <section v-if="content.blocks.length || content.faqs.length" class="border-t border-gray-200 py-10" aria-labelledby="product-content-title">
    <h2 id="product-content-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      Nội dung chi tiết
    </h2>

    <div v-if="content.blocks.length" class="space-y-6">
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

    <section v-if="content.faqs.length" class="mt-12" aria-labelledby="product-faq-title">
      <h3 id="product-faq-title" class="mb-5 text-[clamp(20px,2vw,28px)] font-black text-navy-800">Câu hỏi thường gặp</h3>
      <div class="divide-y divide-gray-200 border-y border-gray-200">
        <details v-for="(faq, index) in content.faqs" :key="index" class="group py-4">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-navy-800">
            {{ faq.question }}
            <span class="text-xl text-gold-700 transition-transform group-open:rotate-45" aria-hidden="true">+</span>
          </summary>
          <p class="mb-0 mt-3 whitespace-pre-line pr-8 leading-7 text-slate-600">{{ faq.answer }}</p>
        </details>
      </div>
    </section>
  </section>
</template>

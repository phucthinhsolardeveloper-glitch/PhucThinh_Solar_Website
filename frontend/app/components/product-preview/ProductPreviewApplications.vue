<script setup lang="ts">
import type { PublicProductApplications } from '#shared/contracts/public-product-detail'

defineProps<{ applications: PublicProductApplications }>()
</script>

<template>
  <section id="ung-dung-san-pham" class="scroll-mt-24 py-10" aria-labelledby="product-applications-title">
    <h2 id="product-applications-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      {{ applications.title }}
    </h2>
    <div class="grid gap-x-8 gap-y-7 min-[768px]:grid-cols-2 min-[1200px]:grid-cols-3">
      <article v-for="(item, index) in applications.items" :key="`${item.title}-${index}`" class="min-w-0">
        <img
          v-if="item.image"
          :src="item.image"
          :alt="item.title"
          class="mb-4 aspect-[8/5] w-full object-cover"
          loading="lazy"
          decoding="async"
        >
        <h3 class="mb-2 text-lg font-bold text-navy-800">{{ item.title }}</h3>
        <p v-if="item.text" class="mb-0 whitespace-pre-line leading-7 text-slate-600">{{ item.text }}</p>
      </article>
    </div>
  </section>
</template>

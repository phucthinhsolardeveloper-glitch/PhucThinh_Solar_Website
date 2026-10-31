<script setup lang="ts">
import type { PublicProductApplications } from '#shared/contracts/public-product-detail'

defineProps<{ applications: PublicProductApplications }>()
</script>

<template>
  <section id="ung-dung-san-pham" class="scroll-mt-24 py-12 tb:py-16" aria-labelledby="product-applications-title">
    <h2 id="product-applications-title" class="mb-7 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      {{ applications.title }}
    </h2>
    <div
      class="applications-rail grid gap-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700"
      tabindex="0"
      aria-label="Danh sách ứng dụng sản phẩm"
    >
      <article v-for="(item, index) in applications.items" :key="`${item.title}-${index}`" class="min-w-0">
        <img
          v-if="item.image"
          :src="item.image"
          :alt="item.title"
          class="mb-3 aspect-[8/5] w-full object-cover"
          loading="lazy"
          decoding="async"
        >
        <h3 class="mb-2 text-base font-bold leading-[1.4] text-navy-800">{{ item.title }}</h3>
        <p v-if="item.text" class="mb-0 line-clamp-2 whitespace-pre-line text-base leading-6 text-slate-600">{{ item.text }}</p>
      </article>
    </div>
  </section>
</template>

<style scoped>
.applications-rail {
  scrollbar-width: none;
}

.applications-rail::-webkit-scrollbar {
  display: none;
}

@media (max-width: 991px) {
  .applications-rail {
    grid-template-columns: repeat(3, minmax(230px, 1fr));
    gap: 22px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    padding-bottom: 8px;
    scroll-snap-type: x proximity;
  }

  .applications-rail > article {
    scroll-snap-align: start;
  }
}

@media (max-width: 640px) {
  .applications-rail {
    margin-right: -16px;
    padding-right: 16px;
  }
}

@media (max-width: 359px) {
  .applications-rail {
    margin-right: -12px;
    padding-right: 12px;
  }
}

@media (min-width: 992px) {
  .applications-rail {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
  }
}
</style>

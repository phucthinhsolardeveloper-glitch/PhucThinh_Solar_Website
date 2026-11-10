<script setup lang="ts">
import type { PublicProductApplications } from '#shared/contracts/public-product-detail'

defineProps<{ applications: PublicProductApplications }>()
const activeIndex = ref(0)
</script>

<template>
  <section id="ung-dung-san-pham" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-applications-title">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
      <h2 id="product-applications-title" class="m-0 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
        {{ applications.title }}
      </h2>
      <a href="#pts-pdp-consultation" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-extrabold text-gold-700 no-underline transition-colors hover:text-navy-800">
        <span>Tư vấn theo không gian lắp đặt</span>
        <span aria-hidden="true">→</span>
      </a>
    </div>

    <div
      class="applications-rail grid gap-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-700"
      tabindex="0"
      aria-label="Danh sách ứng dụng sản phẩm"
    >
      <article
        v-for="(item, index) in applications.items"
        :key="`${item.title}-${index}`"
        class="app-card min-w-0"
        :data-active="activeIndex === index"
        @mouseenter="activeIndex = index"
        @focusin="activeIndex = index"
      >
        <div v-if="item.image" class="app-card-media mb-4 overflow-hidden rounded-btn bg-navy-950">
          <img
            :src="optimizedImage(item.image, 720, 450)"
            :srcset="`${optimizedImage(item.image, 480, 300)} 480w, ${optimizedImage(item.image, 720, 450)} 720w, ${optimizedImage(item.image, 960, 600)} 960w`"
            sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 90vw"
            width="720"
            height="450"
            :alt="item.title"
            class="aspect-[8/5] w-full object-cover transition-transform duration-500 ease-out"
            loading="lazy"
            decoding="async"
          >
        </div>
        <div class="app-card-body">
          <h3 class="mb-2 text-[19px] font-extrabold leading-[1.3] tracking-tight text-navy-900">{{ item.title }}</h3>
          <p v-if="item.text" class="mb-0 whitespace-pre-line text-[15.5px] leading-[1.65] text-slate-600">{{ item.text }}</p>
        </div>
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

.app-card-body {
  padding-top: 2px;
  border-top: 2px solid #e2e8f0;
  transition: border-color 0.25s ease;
}

.app-card[data-active="true"] .app-card-body {
  border-top-color: var(--color-gold-500);
}

.app-card:hover .app-card-media img {
  transform: scale(1.03);
}

@media (max-width: 991px) {
  .applications-rail {
    grid-template-columns: repeat(3, minmax(250px, 1fr));
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
    gap: 28px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-card-media img,
  .app-card-body {
    transition: none;
  }
}
</style>

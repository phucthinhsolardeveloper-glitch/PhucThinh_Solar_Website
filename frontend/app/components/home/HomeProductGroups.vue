<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

defineProps<{ groups: PublicHomePayload['productGroups'] }>()

const links = useMigrationLinks()
const isMigrationPreview = links.active
const oversizedProductImages: Record<string, string> = {
  '/upload/product/x3-pro-30-2-9905.png': '/assets/images/site-visuals/media-fallbacks/product-x3-pro-30.webp',
  '/upload/product/x3-pro-20-2-2061.png': '/assets/images/site-visuals/media-fallbacks/product-x3-pro-20.webp',
}

function productImage(src: string): string {
  return oversizedProductImages[src] || src
}
</script>

<template>
  <section v-if="groups.length" class="py-12 md:py-[clamp(64px,6vw,92px)]" aria-label="Sản phẩm theo danh mục">
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <section v-for="group in groups" :key="group.categoryId" class="mb-12 last:mb-0">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
          <h2 class="m-0 text-[clamp(24px,2vw,34px)] font-bold leading-[1.2] text-navy-950">
            {{ group.categoryName }}
          </h2>
          <a class="inline-flex min-h-11 items-center gap-2 font-bold text-navy-800 no-underline transition-all duration-150 hover:text-gold-700 hover:translate-x-0.5" :href="links.generic(group.categoryUrl)">
            Xem danh mục <span aria-hidden="true" class="transition-transform duration-150 group-hover:translate-x-1">→</span>
          </a>
        </header>

        <ul class="m-0 grid list-none grid-cols-2 gap-x-4 gap-y-8 p-0 max-ph:gap-x-3 max-ph:gap-y-7 tb:grid-cols-3 nav:grid-cols-4">
          <li v-for="product in group.products" :key="product.id" class="min-w-0">
            <NuxtLink
              :external="!isMigrationPreview"
              :to="links.product(product.slug)"
              class="group block min-w-0 text-navy-950 no-underline transition-transform duration-150 active:scale-[0.98]"
            >
              <div class="overflow-hidden rounded-[4px] bg-white transition-all duration-300 group-hover:shadow-[0_8px_20px_rgba(10,34,81,0.08)]">
                <img
                  :src="productImage(product.image)"
                  :alt="product.name"
                  class="block aspect-square w-full object-contain transition-transform duration-300 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                  width="640"
                  height="640"
                  loading="lazy"
                >
              </div>
              <span class="mt-3 block text-[17px] font-semibold leading-[1.4] transition-colors duration-200 group-hover:text-gold-700 group-hover:underline group-hover:underline-offset-[3px] max-ph:text-base">
                {{ product.name }}
              </span>
            </NuxtLink>
          </li>
        </ul>
      </section>
    </div>
  </section>
</template>

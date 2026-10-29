<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

defineProps<{ groups: PublicHomePayload['productGroups'] }>()

const links = useMigrationLinks()
const isMigrationPreview = links.active
</script>

<template>
  <section v-if="groups.length" class="py-12 md:py-[clamp(64px,6vw,92px)]" aria-label="Sản phẩm theo danh mục">
    <div class="mx-auto w-[min(calc(100%-32px),2200px)] max-[359px]:w-[calc(100%-24px)]">
      <section v-for="group in groups" :key="group.categoryId" class="mb-12 last:mb-0">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
          <h2 class="m-0 text-[clamp(24px,2vw,34px)] font-bold leading-[1.2] text-navy-950">
            {{ group.categoryName }}
          </h2>
          <a class="inline-flex min-h-11 items-center gap-2 font-bold text-navy-800 no-underline hover:text-gold-700" :href="links.generic(group.categoryUrl)">
            Xem danh mục <span aria-hidden="true">→</span>
          </a>
        </header>

        <ul class="m-0 grid list-none grid-cols-2 gap-x-4 gap-y-8 p-0 max-ph:gap-x-3 max-ph:gap-y-7 tb:grid-cols-3 nav:grid-cols-4">
          <li v-for="product in group.products" :key="product.id" class="min-w-0">
            <NuxtLink
              :external="!isMigrationPreview"
              :to="links.product(product.slug)"
              class="group block min-w-0 text-navy-950 no-underline"
            >
              <img
                :src="product.image"
                :alt="product.name"
                class="block aspect-square w-full object-contain"
                width="640"
                height="640"
                loading="lazy"
              >
              <span class="mt-3 block text-[17px] leading-[1.4] group-hover:underline group-hover:underline-offset-[3px] max-ph:text-base">
                {{ product.name }}
              </span>
            </NuxtLink>
          </li>
        </ul>
      </section>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'

defineProps<{ dealer: PublicDealerPayload }>()
const links = useMigrationLinks()

function cardImage(src: string): string {
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? '/thumbs/480x300x1' + src
    : src
}
</script>

<template>
  <section
    v-if="dealer.heroDescription || dealer.overview.title || dealer.overview.description || dealer.overview.products.length"
    id="nang-luc-phan-phoi"
    class="min-w-0"
  >
    <p v-if="dealer.heroDescription" class="mb-10 max-w-[70ch] text-[17px] leading-[1.78] text-slate-600 nav:mb-16">
      {{ dealer.heroDescription }}
    </p>

    <h2 v-if="dealer.overview.title" class="mb-5 text-[clamp(26px,3vw,40px)] font-black leading-tight tracking-tight text-navy-800">
      {{ dealer.overview.title }}
    </h2>
    <p v-if="dealer.overview.description" class="mb-8 max-w-[70ch] whitespace-pre-line leading-relaxed text-slate-600">
      {{ dealer.overview.description }}
    </p>

    <div v-if="dealer.overview.products.length" class="grid grid-cols-2 gap-5 tb:grid-cols-3">
      <a
        v-for="product in dealer.overview.products"
        :key="product.title + product.image"
        class="min-w-0 text-inherit no-underline"
        :href="product.href ? links.generic(product.href) : undefined"
        :target="links.active ? undefined : '_blank'"
        :rel="links.active ? undefined : 'noopener noreferrer'"
      >
        <img v-if="product.image" :src="cardImage(product.image)" :alt="product.title" class="mb-3 block aspect-[8/5] w-full rounded-chip object-cover" loading="lazy">
        <span class="font-semibold text-navy-800">{{ product.title }}</span>
      </a>
    </div>
  </section>
</template>

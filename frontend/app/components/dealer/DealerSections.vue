<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
defineProps<{ dealer: PublicDealerPayload }>()

function supportThumbnail(image: string): string {
  return /^\/upload\/(?:news|photo)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(image)
    ? `/thumbs/480x300x1${image}`
    : image
}
</script>
<template>
  <section v-if="dealer.support.title || dealer.support.items.length" id="ho-tro-dai-ly" class="bg-navy-950 py-14 text-white tb:py-20">
    <div class="mx-auto w-full max-w-(--container-page) px-4 tb:px-6 ul:px-(--spacing-gutter)">
      <div class="grid gap-8 nav:grid-cols-[minmax(280px,.75fr)_minmax(0,1.25fr)] nav:items-start">
        <h2 class="text-3xl font-extrabold uppercase leading-tight tracking-[-0.03em] tb:text-4xl">{{ dealer.support.title || dealer.labels.support }}</h2>
        <p v-if="dealer.support.description" class="max-w-3xl leading-8 text-white/72">{{ dealer.support.description }}</p>
      </div>
      <div v-if="dealer.support.items.length" class="mt-10 grid gap-4 tb:grid-cols-2 nav:grid-cols-4">
        <article v-for="item in dealer.support.items" :key="item.title" class="border border-white/12 bg-white/5 p-5">
          <img v-if="item.image" class="mb-5 size-12 object-contain" :src="supportThumbnail(item.image)" :alt="item.title" loading="lazy">
          <h3 class="text-lg font-extrabold">{{ item.title }}</h3>
          <p class="mt-3 whitespace-pre-line text-sm leading-7 text-white/68">{{ item.description }}</p>
        </article>
      </div>
    </div>
  </section>
  <section v-if="dealer.testimonials.length" class="py-14 tb:py-20">
    <div class="mx-auto w-full max-w-(--container-page) px-4 tb:px-6 ul:px-(--spacing-gutter)">
      <h2 class="text-3xl font-extrabold tracking-[-0.025em] text-navy-800 tb:text-4xl">{{ dealer.labels.testimonials }}</h2>
      <div class="mt-8 grid gap-5 nav:grid-cols-3">
        <article v-for="item in dealer.testimonials" :key="item.title" class="bg-navy-50 p-6">
          <p class="leading-7 text-navy-900/68">“{{ item.quote || item.description }}”</p>
          <div class="mt-6 flex items-center gap-3">
            <img v-if="item.image" class="size-11 rounded-full object-cover" :src="item.image" :alt="item.title" loading="lazy">
            <span><strong class="block text-sm text-navy-800">{{ item.title }}</strong><small v-if="item.description" class="mt-1 block text-xs text-navy-900/50">{{ item.description }}</small></span>
          </div>
        </article>
      </div>
    </div>
  </section>
  <section v-if="dealer.stats.length" class="bg-navy-800 py-14 text-white" :aria-label="dealer.labels.stats">
    <div class="mx-auto w-full max-w-(--container-page) px-4 tb:px-6 ul:px-(--spacing-gutter)">
      <p class="text-center text-xs font-bold uppercase tracking-[.18em] text-white/65">{{ dealer.labels.stats }}</p>
      <div class="mt-9 grid grid-cols-3 gap-4 text-center">
        <div v-for="stat in dealer.stats" :key="stat.label"><strong class="block text-3xl font-extrabold tb:text-4xl">{{ stat.value }}+</strong><span class="mt-2 block text-xs text-white/65">{{ stat.label }}</span></div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import type { PublicContactLocation } from '#shared/contracts/public-contact'

const props = defineProps<{ locations: PublicContactLocation[]; title: string }>()
const selectedId = ref(props.locations[0]?.id ?? 0)
const selectedLocation = computed(() => props.locations.find((location) => location.id === selectedId.value) ?? props.locations[0] ?? null)
</script>

<template>
  <section id="he-thong-chi-nhanh" class="py-14 tb:py-20" aria-labelledby="contact-locations-title">
    <h2 id="contact-locations-title" class="text-3xl font-extrabold tracking-[-0.025em] text-navy-800 tb:text-4xl">{{ title }}</h2>
    <div v-if="locations.length" class="mt-8 grid min-w-0 gap-6 nav:grid-cols-[320px_minmax(0,1fr)]">
      <div class="divide-y divide-navy-100">
        <article v-for="location in locations" :key="location.id" class="py-5 first:pt-0">
          <button class="w-full text-left" type="button" :aria-pressed="selectedLocation?.id === location.id" @click="selectedId = location.id">
            <h3 class="font-extrabold text-navy-800">{{ location.name }}</h3>
            <p class="mt-2 text-sm leading-6 text-navy-900/65">{{ location.address }}</p>
          </button>
          <a v-if="location.mapUrl" class="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-navy-800 hover:text-gold-700" :href="location.mapUrl" target="_blank" rel="noopener noreferrer">Xem bản đồ <UIcon name="i-lucide-chevron-right" class="size-4" /></a>
        </article>
      </div>
      <div class="min-h-[360px] overflow-hidden rounded-[3px] bg-navy-50 nav:min-h-[620px]">
        <iframe v-if="selectedLocation?.mapUrl" class="h-full min-h-[360px] w-full border-0 nav:min-h-[620px]" :src="selectedLocation.mapUrl" :title="`Bản đồ ${selectedLocation.name}`" loading="lazy" referrerpolicy="no-referrer-when-downgrade" />
        <div v-else class="grid h-full min-h-[360px] place-items-center px-6 text-center text-navy-900/60"><p>CMS chưa cung cấp bản đồ cho địa điểm này.</p></div>
      </div>
    </div>
    <p v-else class="mt-6 text-navy-900/60" role="status">CMS chưa có địa điểm đang hiển thị.</p>
  </section>
</template>

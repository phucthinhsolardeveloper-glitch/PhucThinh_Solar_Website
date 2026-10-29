<script setup lang="ts">
import type { PublicContactLocation } from '#shared/contracts/public-contact'

const props = defineProps<{ locations: PublicContactLocation[]; title: string }>()
const selectedId = ref<number | null>(null)
const selected = computed(() =>
  props.locations.find((location) => location.id === selectedId.value) ?? props.locations[0] ?? null,
)

watch(() => props.locations, (locations) => {
  if (!locations.some((location) => location.id === selectedId.value)) {
    selectedId.value = locations[0]?.id ?? null
  }
}, { immediate: true })
</script>

<template>
  <section class="py-12 nav:py-16" aria-labelledby="contact-locations-title">
    <h2 id="contact-locations-title" class="mb-6 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-800">
      {{ title }}
    </h2>

    <div class="grid grid-cols-1 gap-8 nav:grid-cols-[340px_minmax(0,1fr)]">
      <ul class="m-0 list-none p-0">
        <li v-for="location in locations" :key="location.id" class="border-b border-gray-200 py-5 first:pt-0 last:border-b-0">
          <p class="m-0 mb-1.5 flex items-center gap-2 font-bold text-navy-800">
            <svg class="h-4 w-4 flex-none text-gold-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
            {{ location.name }}
          </p>
          <p class="m-0 mb-2.5 flex items-start gap-2 text-slate-500">
            <svg class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
            {{ location.address }}
          </p>
          <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 border-0 bg-transparent p-0 text-sm font-bold text-navy-800 hover:text-gold-700"
              :aria-pressed="selected?.id === location.id"
              @click="selectedId = location.id"
            >
              Xem trên bản đồ <span aria-hidden="true">→</span>
            </button>
            <a
              v-if="location.mapUrl"
              :href="location.mapUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="text-sm font-semibold text-slate-500 no-underline hover:text-gold-700"
            >
              Mở Google Maps ↗
            </a>
          </div>
        </li>
      </ul>

      <div v-if="selected?.mapUrl" class="min-h-[360px] overflow-hidden rounded-chip border border-gray-200 bg-slate-50">
        <iframe
          :key="selected.id"
          :src="selected.mapUrl"
          :title="'Bản đồ ' + selected.name"
          class="h-[420px] w-full border-0"
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          allowfullscreen
        />
      </div>
      <div v-else-if="selected" class="flex min-h-[360px] items-center justify-center rounded-chip border border-gray-200 bg-slate-50 p-8 text-center text-slate-500">
        Bản đồ cho địa điểm này chưa được cấu hình.
      </div>
      <div v-else class="flex min-h-[360px] items-center justify-center rounded-chip border border-dashed border-gray-300 bg-slate-50 p-8 text-center text-slate-500">
        Hệ thống chi nhánh đang được cập nhật.
      </div>
    </div>
  </section>
</template>

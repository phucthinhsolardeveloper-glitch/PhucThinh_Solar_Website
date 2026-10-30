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
  <section class="pb-14 nav:pb-20" aria-labelledby="contact-locations-title">
    <div class="mb-7 max-w-3xl">
      <h2 id="contact-locations-title" class="m-0 text-[clamp(28px,2.7vw,40px)] font-bold leading-tight tracking-tight text-navy-800">
        {{ title }}
      </h2>
      <p v-if="locations.length" class="mb-0 mt-3 text-base leading-7 text-slate-600">Chọn địa điểm để xem địa chỉ và bản đồ từ hệ thống chi nhánh.</p>
    </div>

    <div v-if="locations.length" class="grid min-w-0 gap-8 lg:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)] lg:gap-10">
      <ul class="m-0 grid list-none content-start gap-2 p-0">
        <li v-for="location in locations" :key="location.id" class="min-w-0">
          <button
            type="button"
            class="group flex min-h-24 w-full min-w-0 items-start gap-3 rounded-chip border-l-[3px] px-4 py-4 text-left transition-colors"
            :class="selected?.id === location.id
              ? 'border-gold-500 bg-navy-50 text-navy-800'
              : 'border-transparent text-navy-800 hover:bg-slate-50'"
            :aria-pressed="selected?.id === location.id"
            @click="selectedId = location.id"
          >
            <UIcon name="i-lucide-map-pin" class="mt-0.5 size-5 flex-none text-gold-700" aria-hidden="true" />
            <span class="min-w-0 flex-1">
              <strong class="block text-base leading-6">{{ location.name }}</strong>
              <span class="mt-1 block text-sm leading-6 text-slate-600">{{ location.address }}</span>
            </span>
            <UIcon name="i-lucide-chevron-right" class="mt-1 size-4 flex-none text-slate-400" aria-hidden="true" />
          </button>
        </li>
      </ul>

      <div class="min-w-0 lg:sticky lg:top-28 lg:self-start">
        <div v-if="selected" class="overflow-hidden rounded-chip bg-slate-50">
          <div class="flex flex-wrap items-start justify-between gap-4 px-5 py-5 tb:px-6">
            <div class="min-w-0 flex-1">
              <p class="m-0 text-sm font-semibold text-gold-700">Địa điểm đang xem</p>
              <h3 class="mb-0 mt-1 text-xl font-bold leading-7 text-navy-800">{{ selected.name }}</h3>
              <p class="mb-0 mt-1 text-sm leading-6 text-slate-600">{{ selected.address }}</p>
            </div>
            <UButton
              v-if="selected.mapUrl"
              :href="selected.mapUrl"
              target="_blank"
              rel="noopener noreferrer"
              color="neutral"
              variant="outline"
              size="md"
              trailing-icon="i-lucide-external-link"
              class="min-h-11 rounded-chip border-navy-800 px-3 text-navy-800 no-underline hover:bg-navy-50"
            >Mở bản đồ</UButton>
          </div>
          <iframe
            v-if="selected?.mapUrl"
            :key="selected.id"
            :src="selected.mapUrl"
            :title="'Bản đồ ' + selected.name"
            class="h-[330px] w-full border-0 tb:h-[420px]"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen
          />
          <div v-else class="flex min-h-36 items-center gap-3 px-5 pb-6 text-slate-600 tb:px-6">
            <UIcon name="i-lucide-map" class="size-6 flex-none text-gold-700" aria-hidden="true" />
            <p class="m-0 text-sm leading-6">Bản đồ cho địa điểm này đang được cập nhật. Vui lòng liên hệ qua hotline để được hướng dẫn.</p>
          </div>
        </div>
      </div>
    </div>

    <p v-else class="m-0 rounded-chip bg-slate-50 p-6 text-base text-slate-600" role="status">Hệ thống chi nhánh đang được cập nhật.</p>
  </section>
</template>

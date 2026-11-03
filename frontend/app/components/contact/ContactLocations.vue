<script setup lang="ts">
import type { PublicContactLocation } from '#shared/contracts/public-contact'

const props = defineProps<{ locations: PublicContactLocation[]; title: string }>()
const selectedId = ref<number | null>(null)
const copiedId = ref<number | null>(null)
const selected = computed(() =>
  props.locations.find((location) => location.id === selectedId.value) ?? props.locations[0] ?? null,
)

async function copyAddress(location: PublicContactLocation) {
  if (!import.meta.client || !navigator.clipboard) return
  try {
    await navigator.clipboard.writeText(`${location.name} — ${location.address}`)
    copiedId.value = location.id
    setTimeout(() => {
      if (copiedId.value === location.id) copiedId.value = null
    }, 2200)
  } catch {
    // Ignore clipboard permission errors
  }
}

watch(() => props.locations, (locations) => {
  if (!locations.some((location) => location.id === selectedId.value)) {
    selectedId.value = locations[0]?.id ?? null
  }
}, { immediate: true })
</script>

<template>
  <section class="pb-14 nav:pb-20" aria-labelledby="contact-locations-title">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
      <div class="max-w-3xl">
        <h2 id="contact-locations-title" class="m-0 text-[clamp(28px,2.7vw,40px)] font-black leading-tight tracking-tight text-navy-900">
          {{ title }}
        </h2>
        <p v-if="locations.length" class="mb-0 mt-2.5 text-base leading-7 text-slate-600">Chọn điểm giao dịch hoặc kho khu vực để xem địa chỉ, sao chép nhanh và định vị trên bản đồ.</p>
      </div>
      <span v-if="locations.length" class="rounded-[3px] bg-navy-900 px-3 py-1.5 text-xs font-extrabold text-gold-300">
        {{ locations.length }} điểm giao dịch & kho vận
      </span>
    </div>

    <div v-if="locations.length" class="grid min-w-0 items-stretch gap-8 lg:grid-cols-[minmax(0,.92fr)_minmax(0,1.08fr)] lg:gap-10">
      <ul class="m-0 grid list-none content-start gap-2.5 p-0 lg:auto-rows-fr lg:content-stretch">
        <li v-for="(location, index) in locations" :key="location.id" class="min-w-0">
          <button
            type="button"
            class="group flex min-h-24 w-full min-w-0 items-start gap-3.5 rounded-chip border-l-[4px] px-4 py-4 text-left transition-colors cursor-pointer lg:h-full lg:items-center"
            :class="selected?.id === location.id
              ? 'border-gold-500 bg-navy-950 text-white shadow-sm'
              : 'border-slate-200 bg-slate-50/80 text-navy-800 hover:border-gold-400 hover:bg-slate-100/80'"
            :aria-pressed="selected?.id === location.id"
            @click="selectedId = location.id"
          >
            <span
              class="mt-0.5 grid size-8 flex-none place-items-center rounded-[3px] font-mono text-xs font-extrabold lg:mt-0"
              :class="selected?.id === location.id ? 'bg-gold-500 text-navy-950' : 'bg-white text-gold-700'"
            >
              0{{ index + 1 }}
            </span>
            <span class="min-w-0 flex-1">
              <strong class="block text-base font-extrabold leading-6">{{ location.name }}</strong>
              <span class="mt-1 block text-sm leading-6" :class="selected?.id === location.id ? 'text-blue-100' : 'text-slate-600'">{{ location.address }}</span>
            </span>
            <UIcon name="i-lucide-chevron-right" class="mt-1 size-4 flex-none lg:mt-0" :class="selected?.id === location.id ? 'text-gold-400' : 'text-slate-400'" aria-hidden="true" />
          </button>
        </li>
      </ul>

      <div class="min-w-0 lg:sticky lg:top-28 lg:self-start">
        <div v-if="selected" class="overflow-hidden rounded-chip border-t-[3px] border-gold-500 bg-slate-50">
          <div class="flex flex-col gap-3.5 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:gap-4 tb:px-6">
            <div class="min-w-0 flex-1">
              <p class="m-0 text-sm font-semibold text-gold-700">Địa điểm đang xem</p>
              <h3 class="mb-0 mt-1 text-xl font-black leading-7 text-navy-900">{{ selected.name }}</h3>
              <p class="mb-0 mt-1 text-sm leading-6 text-slate-600">{{ selected.address }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
              <button
                type="button"
                class="public-neutral-button inline-flex min-h-11 items-center gap-1.5 rounded-chip px-3 text-sm font-bold text-navy-800 cursor-pointer"
                @click="copyAddress(selected)"
              >
                <UIcon :name="copiedId === selected.id ? 'i-lucide-check' : 'i-lucide-copy'" class="size-4 text-gold-700" aria-hidden="true" />
                <span>{{ copiedId === selected.id ? 'Đã chép địa chỉ' : 'Sao chép địa chỉ' }}</span>
              </button>
              <UButton
                v-if="selected.mapUrl"
                :href="selected.mapUrl"
                target="_blank"
                rel="noopener noreferrer"
                color="neutral"
                variant="outline"
                size="md"
                trailing-icon="i-lucide-external-link"
                class="public-neutral-button min-h-11 rounded-chip px-3 text-navy-800 no-underline"
              >Mở bản đồ</UButton>
            </div>
          </div>
          <iframe
            v-if="selected?.mapUrl"
            :key="selected.id"
            :src="selected.mapUrl"
            :title="'Bản đồ ' + selected.name"
            class="h-[340px] w-full border-0 bg-slate-200 tb:h-[420px]"
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

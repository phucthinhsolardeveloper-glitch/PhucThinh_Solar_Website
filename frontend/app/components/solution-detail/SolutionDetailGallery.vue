<script setup lang="ts">
const props = defineProps<{ images: string[]; title: string }>()

const selectedIndex = ref<number | null>(null)
const setDocumentScrollLock = useDocumentScrollLock()
const selectedImage = computed(() => selectedIndex.value === null ? null : props.images[selectedIndex.value] ?? null)
const dialog = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)

function focusFirst(container: HTMLElement | null) {
  container?.querySelector<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')?.focus()
}

function trapFocus(event: KeyboardEvent) {
  if (!dialog.value) return
  const focusable = [...dialog.value.querySelectorAll<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')]
  if (!focusable.length) return
  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function open(index: number, trigger: HTMLElement) {
  if (!props.images[index]) return
  lastTrigger.value = trigger
  selectedIndex.value = index
}

function close() {
  selectedIndex.value = null
  if (import.meta.client) nextTick(() => lastTrigger.value?.focus())
}

function move(delta: number) {
  if (selectedIndex.value === null || props.images.length < 2) return
  selectedIndex.value = (selectedIndex.value + delta + props.images.length) % props.images.length
}

function onKeydown(event: KeyboardEvent) {
  if (selectedIndex.value === null) return
  if (event.key === 'Escape') close()
  else if (event.key === 'ArrowLeft') move(-1)
  else if (event.key === 'ArrowRight') move(1)
  else if (event.key === 'Tab') trapFocus(event)
}

watch(selectedIndex, (value, oldValue) => {
  setDocumentScrollLock(value !== null)
  if (value !== null && oldValue === null) nextTick(() => focusFirst(dialog.value))
})

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  setDocumentScrollLock(false)
})
const viewerSrc = useProgressiveImage(() => selectedImage.value, (src) => optimizedImage(src, 900, 563))
</script>

<template>
  <section v-if="images.length" class="pts-container pb-10" aria-labelledby="solution-gallery-title">
    <h2 id="solution-gallery-title" class="mb-5 text-[clamp(22px,2.2vw,28px)] font-black tracking-tight text-navy-800 max-tb:mb-3">Hình ảnh giải pháp</h2>
    <div class="grid grid-cols-1 gap-4 min-[521px]:grid-cols-2 max-tb:grid-cols-2 max-tb:gap-2">
      <button
        v-for="(image, index) in images"
        :key="image"
        type="button"
        class="group block w-full overflow-hidden rounded-btn border border-slate-200/80 bg-white p-0 text-left transition-all hover:border-gold-500 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500 cursor-pointer"
        :aria-label="`Mở ảnh ${index + 1} trong thư viện ${title}`"
        @click="open(index, $event.currentTarget as HTMLElement)"
      >
        <div class="overflow-hidden bg-slate-100">
          <img :src="optimizedImage(image, 900, 563)" :alt="title" class="block aspect-[8/5] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none" width="960" height="600" loading="lazy" decoding="async">
        </div>
      </button>
    </div>
  </section>

  <Teleport to="body">
    <div
      v-if="selectedImage"
      ref="dialog"
      class="fixed inset-0 z-[100] grid place-items-center photo-viewer p-4 transition-opacity duration-200"
      role="dialog"
      aria-modal="true"
      :aria-label="`Ảnh ${Number(selectedIndex) + 1} trong thư viện ${title}`"
      @click.self="close"
    >
      <button
        type="button"
        class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
        aria-label="Đóng ảnh"
        @click="close"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>

      <button
        v-if="images.length > 1"
        type="button"
        class="photo-viewer__arrow absolute left-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
        aria-label="Ảnh trước"
        @click="move(-1)"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <img :src="viewerSrc" :alt="title" class="photo-viewer__media max-h-[84vh] max-w-[min(1200px,90vw)] object-contain">

      <button
        v-if="images.length > 1"
        type="button"
        class="photo-viewer__arrow absolute right-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full photo-viewer__btn cursor-pointer"
        aria-label="Ảnh sau"
        @click="move(1)"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>

      <div class="absolute bottom-5 left-1/2 -translate-x-1/2 photo-viewer__label px-4 py-1.5 text-xs font-bold">
        {{ Number(selectedIndex) + 1 }} / {{ images.length }}
      </div>
    </div>
  </Teleport>
</template>

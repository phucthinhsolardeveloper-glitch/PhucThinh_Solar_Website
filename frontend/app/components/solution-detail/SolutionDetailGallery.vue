<script setup lang="ts">
const props = defineProps<{ images: string[]; title: string }>()

const selectedIndex = ref<number | null>(null)
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
  if (import.meta.client) document.body.style.overflow = value === null ? '' : 'hidden'
  if (value !== null && oldValue === null) nextTick(() => focusFirst(dialog.value))
})

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <section v-if="images.length" class="mx-auto w-full max-w-[1766px] px-4 pb-12" aria-labelledby="solution-gallery-title">
    <h2 id="solution-gallery-title" class="mb-5 text-[28px] font-black text-navy-800">Hình ảnh giải pháp</h2>
    <div class="grid grid-cols-1 gap-4 min-[521px]:grid-cols-2">
      <button
        v-for="(image, index) in images"
        :key="image"
        type="button"
        class="group block w-full border-0 bg-transparent p-0 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500"
        :aria-label="`Mở ảnh ${index + 1} trong thư viện ${title}`"
        @click="open(index, $event.currentTarget as HTMLElement)"
      >
        <img :src="image" :alt="title" class="block aspect-[8/5] w-full rounded-btn object-cover" width="960" height="600" loading="lazy" decoding="async">
      </button>
    </div>
  </section>

  <Teleport to="body">
    <div
      v-if="selectedImage"
      ref="dialog"
      class="fixed inset-0 z-[100] grid place-items-center bg-black/90 p-4"
      role="dialog"
      aria-modal="true"
      :aria-label="`Ảnh ${Number(selectedIndex) + 1} trong thư viện ${title}`"
      @click.self="close"
    >
      <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Đóng ảnh" @click="close">×</button>
      <button v-if="images.length > 1" type="button" class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh trước" @click="move(-1)">‹</button>
      <img :src="selectedImage" :alt="title" class="max-h-[84vh] max-w-[min(1200px,90vw)] object-contain">
      <button v-if="images.length > 1" type="button" class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh sau" @click="move(1)">›</button>
    </div>
  </Teleport>
</template>

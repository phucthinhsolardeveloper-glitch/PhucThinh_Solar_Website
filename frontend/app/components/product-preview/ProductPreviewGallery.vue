<script setup lang="ts">
const props = defineProps<{ name: string; media: string[] }>()
const selectedIndex = ref(0)
const failedMedia = ref<Set<string>>(new Set())
const lightboxOpen = ref(false)

watch(() => props.media, () => {
  selectedIndex.value = 0
  failedMedia.value = new Set()
  lightboxOpen.value = false
})

const selectedMedia = computed(() => props.media[selectedIndex.value] ?? props.media[0] ?? '')
const selectedMediaAvailable = computed(() => Boolean(selectedMedia.value) && !failedMedia.value.has(selectedMedia.value))
const availableIndexes = computed(() => props.media.map((_, index) => index).filter((index) => !failedMedia.value.has(props.media[index] ?? '')))

function selectMedia(index: number) {
  selectedIndex.value = index
}
function markMediaFailed(image: string) {
  failedMedia.value = new Set(failedMedia.value).add(image)
  if (image !== selectedMedia.value) return
  const nextIndex = props.media.findIndex((candidate) => !failedMedia.value.has(candidate))
  if (nextIndex >= 0) selectedIndex.value = nextIndex
  else lightboxOpen.value = false
}
function openLightbox() {
  if (selectedMediaAvailable.value) lightboxOpen.value = true
}
function closeLightbox() {
  lightboxOpen.value = false
}
function moveLightbox(direction: -1 | 1) {
  const indexes = availableIndexes.value
  if (indexes.length < 2) return
  const current = indexes.indexOf(selectedIndex.value)
  const next = (current + direction + indexes.length) % indexes.length
  selectedIndex.value = indexes[next] ?? selectedIndex.value
}
function handleKey(event: KeyboardEvent) {
  if (!lightboxOpen.value) return
  if (event.key === 'Escape') closeLightbox()
  if (event.key === 'ArrowLeft') moveLightbox(-1)
  if (event.key === 'ArrowRight') moveLightbox(1)
}

watch(lightboxOpen, (open) => {
  if (!import.meta.client) return
  document.body.style.overflow = open ? 'hidden' : ''
})
onMounted(() => document.addEventListener('keydown', handleKey))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <div>
    <button
      v-if="selectedMediaAvailable"
      type="button"
      class="grid aspect-square w-full cursor-zoom-in place-items-center overflow-hidden rounded-chip bg-white shadow-xs transition-shadow duration-300 hover:shadow-md"
      :aria-label="'Phóng to ảnh ' + name"
      @click="openLightbox"
    >
      <img :src="selectedMedia" :alt="name" class="h-full w-full object-contain transition-transform duration-300 ease-out hover:scale-[1.02]" width="720" height="720" @error="markMediaFailed(selectedMedia)">
    </button>
    <p v-else class="text-slate-500">CMS chưa có ảnh sản phẩm hợp lệ.</p>

    <div v-if="media.length > 1" class="flex gap-2 overflow-x-auto pt-3" aria-label="Thư viện ảnh sản phẩm">
      <button
        v-for="(image, index) in media"
        v-show="!failedMedia.has(image)"
        :key="image"
        type="button"
        class="aspect-square flex-none basis-[72px] overflow-hidden rounded-chip border bg-white cursor-pointer transition-all duration-200 hover:opacity-90 active:scale-95"
        :class="selectedIndex === index ? 'border-navy-800 ring-2 ring-gold-500/40 shadow-xs' : 'border-gray-200 hover:border-slate-300'"
        :aria-label="'Xem ảnh ' + (index + 1) + ' của ' + media.length"
        :aria-pressed="selectedIndex === index"
        @click="selectMedia(index)"
      >
        <img :src="image" :alt="name + ' — ảnh ' + (index + 1)" class="h-full w-full object-contain" width="112" height="112" loading="lazy" @error="markMediaFailed(image)">
      </button>
    </div>

    <Teleport to="body">
      <div
        v-if="lightboxOpen && selectedMediaAvailable"
        class="fixed inset-0 z-[200] flex items-center justify-center bg-navy-950/85 backdrop-blur-md p-4 transition-opacity duration-200"
        role="dialog"
        aria-modal="true"
        :aria-label="'Ảnh phóng to ' + name"
        @click.self="closeLightbox"
      >
        <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white transition-colors hover:bg-white/20 active:scale-95 cursor-pointer" aria-label="Đóng ảnh phóng to" @click="closeLightbox">×</button>
        <button v-if="availableIndexes.length > 1" type="button" class="absolute left-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white transition-colors hover:bg-white/20 active:scale-95 cursor-pointer" aria-label="Ảnh trước" @click="moveLightbox(-1)">‹</button>
        <img :src="selectedMedia" :alt="name + ' — ảnh phóng to'" class="max-h-[90vh] max-w-[90vw] object-contain drop-shadow-2xl" @error="markMediaFailed(selectedMedia)">
        <button v-if="availableIndexes.length > 1" type="button" class="absolute right-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white transition-colors hover:bg-white/20 active:scale-95 cursor-pointer" aria-label="Ảnh tiếp theo" @click="moveLightbox(1)">›</button>
      </div>
    </Teleport>
  </div>
</template>

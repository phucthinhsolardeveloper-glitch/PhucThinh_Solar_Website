<script setup lang="ts">
const props = defineProps<{ name: string; media: string[] }>()
const selectedIndex = ref(0)
const failedMedia = ref<Set<string>>(new Set())

watch(() => props.media, () => {
  selectedIndex.value = 0
  failedMedia.value = new Set()
})

const selectedMedia = computed(() => props.media[selectedIndex.value] ?? props.media[0] ?? '')
const selectedMediaAvailable = computed(() => Boolean(selectedMedia.value) && !failedMedia.value.has(selectedMedia.value))

function selectMedia(index: number) {
  selectedIndex.value = index
}

function markMediaFailed(image: string) {
  failedMedia.value = new Set(failedMedia.value).add(image)
  if (image !== selectedMedia.value) return
  const nextIndex = props.media.findIndex((candidate) => !failedMedia.value.has(candidate))
  if (nextIndex >= 0) selectedIndex.value = nextIndex
}
</script>
<template>
  <div class="product-preview-gallery">
    <div v-if="selectedMediaAvailable" class="product-preview-gallery__main"><img :src="selectedMedia" :alt="name" width="720" height="720" @error="markMediaFailed(selectedMedia)"></div>
    <p v-else class="product-preview-gallery__empty">CMS chưa có ảnh sản phẩm hợp lệ.</p>
    <div v-if="media.length > 1" class="product-preview-gallery__thumbs" aria-label="Thư viện ảnh sản phẩm">
      <button v-for="(image, index) in media" v-show="!failedMedia.has(image)" :key="image" type="button" :class="{ 'is-active': selectedIndex === index }" :aria-label="`Xem ảnh ${index + 1} của ${media.length}`" :aria-pressed="selectedIndex === index" @click="selectMedia(index)">
        <img :src="image" :alt="`${name} — ảnh ${index + 1}`" width="112" height="112" loading="lazy" @error="markMediaFailed(image)">
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ name: string; media: string[] }>()
const selectedIndex = ref(0)

watch(() => props.media, () => {
  selectedIndex.value = 0
})

const selectedMedia = computed(() => props.media[selectedIndex.value] ?? props.media[0] ?? '')

function selectMedia(index: number) {
  selectedIndex.value = index
}
</script>
<template>
  <div class="product-preview-gallery">
    <div v-if="selectedMedia" class="product-preview-gallery__main"><img :src="selectedMedia" :alt="name" width="720" height="720"></div>
    <p v-else class="product-preview-gallery__empty">CMS chưa có ảnh sản phẩm hợp lệ.</p>
    <div v-if="media.length > 1" class="product-preview-gallery__thumbs" aria-label="Thư viện ảnh sản phẩm">
      <button v-for="(image, index) in media" :key="image" type="button" :class="{ 'is-active': selectedIndex === index }" :aria-label="`Xem ảnh ${index + 1} của ${media.length}`" :aria-pressed="selectedIndex === index" @click="selectMedia(index)">
        <img :src="image" :alt="`${name} — ảnh ${index + 1}`" width="112" height="112" loading="lazy">
      </button>
    </div>
  </div>
</template>

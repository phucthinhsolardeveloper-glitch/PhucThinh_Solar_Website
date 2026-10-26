<script setup lang="ts">
defineProps<{ name: string; media: string[] }>()
</script>
<template>
  <div class="product-preview-gallery">
    <div v-if="media.length" class="product-preview-gallery__main"><img :src="media[0]" :alt="name" width="720" height="720"></div>
    <p v-else class="product-preview-gallery__empty">CMS chưa có ảnh sản phẩm hợp lệ.</p>
    <div v-if="media.length > 1" class="product-preview-gallery__thumbs" aria-label="Thư viện ảnh sản phẩm">
      <img v-for="image in media.slice(1)" :key="image" :src="image" :alt="name" width="112" height="112" loading="lazy">
    </div>
  </div>
</template>

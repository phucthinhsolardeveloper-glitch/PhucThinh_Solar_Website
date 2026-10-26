<script setup lang="ts">
import type { PublicProjectGalleryItem } from '#shared/contracts/public-project-detail'
defineProps<{ items: PublicProjectGalleryItem[] }>()
</script>
<template>
  <section class="project-detail-gallery" aria-labelledby="project-gallery-title">
    <h2 id="project-gallery-title">Hình ảnh và media</h2>
    <div v-if="items.length" class="project-detail-gallery__grid">
      <figure v-for="(item, index) in items" :key="`${item.type}-${item.src}-${index}`" class="project-detail-gallery__item">
        <a v-if="item.type === 'video'" :href="item.href" target="_blank" rel="noopener noreferrer">
          <img v-if="item.src" :src="item.src" :alt="item.caption || 'Media dự án'" loading="lazy" decoding="async">
          <span class="project-detail-gallery__video">Mở video ↗</span>
        </a>
        <img v-else :src="item.src" :alt="item.caption || 'Hình ảnh dự án'" loading="lazy" decoding="async">
        <figcaption v-if="item.caption">{{ item.caption }}</figcaption>
      </figure>
    </div>
    <p v-else class="project-detail-empty">Chưa có media được công bố cho dự án này.</p>
  </section>
</template>

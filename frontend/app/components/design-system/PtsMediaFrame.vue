<script setup lang="ts">
withDefaults(defineProps<{
  src: string
  alt: string
  caption?: string
  ratio?: 'square' | 'wide' | 'project' | 'content' | 'campaign'
  fit?: 'contain' | 'cover'
  loading?: 'eager' | 'lazy'
  width?: number
  height?: number
}>(), {
  ratio: 'wide',
  fit: 'cover',
  loading: 'lazy',
  width: 1200,
  height: 675,
})
</script>

<template>
  <figure class="pts-media">
    <div class="pts-media__frame" :data-ratio="ratio" :data-fit="fit">
      <img :src="src" :alt="alt" :loading="loading" :width="width" :height="height" decoding="async">
    </div>
    <figcaption v-if="caption">{{ caption }}</figcaption>
  </figure>
</template>

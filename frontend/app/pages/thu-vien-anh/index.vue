<script setup lang="ts">
import GalleryPage from '~/components/views/GalleryView.vue'
definePageMeta({ layout: false, middleware: 'gallery-public-route' })
</script>

<template><GalleryPage /></template>

<script setup lang="ts">
import VideoPage from '~/components/views/VideoView.vue'
definePageMeta({ layout: false, middleware: 'video-public-route' })
</script>

<template><VideoPage /></template>

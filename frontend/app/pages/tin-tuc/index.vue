<script setup lang="ts">
import NewsPage from '~/components/views/NewsView.vue'
definePageMeta({ layout: false, middleware: 'news-public-route' })
</script>

<template><NewsPage /></template>

<script setup lang="ts">
import HomePageContent from '~/components/home/HomePageContent.vue'
definePageMeta({ layout: false, middleware: 'home-public-route' })
</script>

<template>
  <HomePageContent />
</template>

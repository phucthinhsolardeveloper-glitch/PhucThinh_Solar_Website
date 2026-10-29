<script setup lang="ts">
import HomePageContent from '~/components/home/HomePageContent.vue'
import homePublicRoute from '~/middleware/home-public-route'

definePageMeta({ layout: false, middleware: homePublicRoute })
</script>

<template>
  <HomePageContent />
</template>

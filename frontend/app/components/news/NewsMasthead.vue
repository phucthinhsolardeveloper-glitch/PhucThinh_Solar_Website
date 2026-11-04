<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <!-- Owner decision 2026-11-04: no visible masthead; the h1 stays for SEO and screen readers. -->
  <h1 class="sr-only">{{ title }}</h1>
</template>

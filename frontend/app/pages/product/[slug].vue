<script setup lang="ts">
// Old /product/{slug} links redirect to the canonical product page.
definePageMeta({ layout: false, middleware: 'product-old-path-redirect' })
</script>

<template><div /></template>

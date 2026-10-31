<script setup lang="ts">
// Reuse the canonical PDP redirect for PHP's historical /product/{slug} alias.
definePageMeta({ layout: false, middleware: 'product-legacy-alias-public-route' })
</script>

<template><div /></template>

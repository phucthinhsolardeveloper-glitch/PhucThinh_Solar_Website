<script setup lang="ts">
import productLegacyAliasPublicRoute from '~/middleware/product-legacy-alias-public-route'

// Reuse the flag-aware PDP redirect for PHP's historical /product/{slug} alias.
definePageMeta({ layout: false, middleware: productLegacyAliasPublicRoute })
</script>

<template><div /></template>

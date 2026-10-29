<script setup lang="ts">
import productRootAliasPublicRoute from '~/middleware/product-root-alias-public-route'

definePageMeta({ layout: false, middleware: productRootAliasPublicRoute })
</script>

<template><div /></template>

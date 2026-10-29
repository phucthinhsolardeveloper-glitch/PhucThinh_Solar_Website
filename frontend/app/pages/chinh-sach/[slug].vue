<script setup lang="ts">
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
await navigateTo(`/ho-tro/${slug.value}/`, { redirectCode: 301 })
</script>

<template />

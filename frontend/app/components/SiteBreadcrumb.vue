<script setup lang="ts">
export interface BreadcrumbItem {
  label: string
  to?: string
}

defineProps<{
  items: BreadcrumbItem[]
}>()
</script>

<template>
  <nav class="pts-breadcrumb" aria-label="Breadcrumb">
    <NuxtLink to="/">Trang chủ</NuxtLink>
    <template v-for="item in items" :key="`${item.label}-${item.to ?? 'current'}`">
      <span class="pts-breadcrumb__separator" aria-hidden="true">/</span>
      <NuxtLink v-if="item.to" :to="item.to">{{ item.label }}</NuxtLink>
      <span v-else aria-current="page">{{ item.label }}</span>
    </template>
  </nav>
</template>

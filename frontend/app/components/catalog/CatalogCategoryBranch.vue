<script setup lang="ts">
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'

defineOptions({ name: 'CatalogCategoryBranch' })
defineProps<{ node: PublicProductCategoryNode; selected: string }>()

function previewLink(url: string) {
  return { path: '/migration-catalog-preview', query: { category: url.replace('/danh-muc-san-pham/', '').replace(/\/$/, '') } }
}
</script>

<template>
  <li>
    <NuxtLink :class="{ 'is-selected': selected === node.url }" :aria-current="selected === node.url ? 'page' : undefined" :to="previewLink(node.url)">{{ node.name }}</NuxtLink>
    <ul v-if="node.children.length">
      <CatalogCategoryBranch v-for="child in node.children" :key="child.id" :node="child" :selected="selected" />
    </ul>
  </li>
</template>

<script setup lang="ts">
import { CATALOG_MENU_ICON_PATHS, type CatalogMenuIconName } from '#shared/catalog-menu-presentation'

const props = defineProps<{ name: CatalogMenuIconName }>()
const pathHtml = computed(() => CATALOG_MENU_ICON_PATHS[props.name] || '')
</script>

<template>
  <svg
    class="block"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.65"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
    v-html="pathHtml"
  />
</template>

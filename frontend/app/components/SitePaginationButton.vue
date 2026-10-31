<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

withDefaults(defineProps<{
  to?: RouteLocationRaw
  href?: string
  active?: boolean
  ariaLabel?: string
  rel?: string
}>(), {
  active: false,
})
</script>

<template>
  <UButton
    :as="active ? 'span' : undefined"
    :to="to"
    :href="href"
    :rel="rel"
    :aria-current="active ? 'page' : undefined"
    :aria-label="ariaLabel"
    color="primary"
    :variant="active ? 'solid' : 'outline'"
    :prefetch="false"
    class="min-h-11 min-w-11 justify-center rounded-[4px] px-3 py-2 text-sm font-semibold leading-5"
    :ui="active ? { base: '!text-navy-950' } : undefined"
  >
    <slot />
  </UButton>
</template>

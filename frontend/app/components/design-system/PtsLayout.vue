<script setup lang="ts">
withDefaults(defineProps<{
  as?: string
  width?: 'site' | 'wide' | 'content' | 'reading' | 'bleed'
}>(), {
  as: 'div',
  width: 'site',
})
</script>

<template>
  <component :is="as" class="pts-layout" :class="`pts-layout--${width}`">
    <slot />
  </component>
</template>

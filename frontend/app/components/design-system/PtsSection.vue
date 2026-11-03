<script setup lang="ts">
withDefaults(defineProps<{
  as?: string
  id?: string
  tone?: 'paper' | 'warm' | 'quiet' | 'stage' | 'cinema'
  spacing?: 'compact' | 'regular' | 'roomy'
  composition?: 'precision-paper' | 'spatial-energy-stage' | 'solar-cinema' | 'architectural-editorial' | 'technical-journal' | 'product-atelier' | 'evidence-narrative' | 'connected-journey'
}>(), {
  as: 'section',
  tone: 'paper',
  spacing: 'regular',
})
</script>

<template>
  <component
    :is="as"
    :id="id"
    class="pts-section pts-composition"
    :class="`pts-section--${spacing}`"
    :data-tone="tone"
    :data-composition="composition"
  >
    <slot />
  </component>
</template>

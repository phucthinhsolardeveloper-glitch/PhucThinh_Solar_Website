<script setup lang="ts">
/**
 * Precision-paper grid: a thin 32px technical rhythm behind light sections only.
 * Edge-faded via mask so it never reads as a hard-edged texture block.
 * Lab-only component — not a shared design-system primitive yet.
 */
withDefaults(defineProps<{ size?: number }>(), { size: 32 })
</script>

<template>
  <div class="grid-surface" :style="{ '--grid-size': `${size}px` }">
    <div class="grid-surface-layer" aria-hidden="true" />
    <div class="grid-surface-content"><slot /></div>
  </div>
</template>

<style scoped>
.grid-surface {
  position: relative;
  isolation: isolate;
}

.grid-surface-layer {
  position: absolute;
  inset: 0;
  z-index: 0;
  background-image:
    linear-gradient(90deg, rgba(10, 34, 81, 0.055) 1px, transparent 1px),
    linear-gradient(0deg, rgba(10, 34, 81, 0.035) 1px, transparent 1px);
  background-size: var(--grid-size) var(--grid-size);
  mask-image: radial-gradient(ellipse 85% 80% at 50% 38%, black 42%, transparent 92%);
  -webkit-mask-image: radial-gradient(ellipse 85% 80% at 50% 38%, black 42%, transparent 92%);
}

.grid-surface-content {
  position: relative;
  z-index: 1;
}
</style>

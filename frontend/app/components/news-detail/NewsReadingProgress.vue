<script setup lang="ts">
// Thin gold bar at the very top that fills as the article body is read.
const props = defineProps<{ target: string }>()
const progress = ref(0)
let ticking = false
function measure() {
  ticking = false
  const el = document.querySelector<HTMLElement>(props.target)
  if (!el) return
  const rect = el.getBoundingClientRect()
  const span = rect.height - window.innerHeight * 0.6
  progress.value = span > 0 ? Math.min(1, Math.max(0, -rect.top / span)) : 1
}
function onScroll() { if (!ticking) { ticking = true; requestAnimationFrame(measure) } }
onMounted(() => { measure(); window.addEventListener('scroll', onScroll, { passive: true }); window.addEventListener('resize', onScroll) })
onBeforeUnmount(() => { window.removeEventListener('scroll', onScroll); window.removeEventListener('resize', onScroll) })
</script>

<template>
  <div class="reading-progress" aria-hidden="true"><span :style="{ transform: `scaleX(${progress})` }" /></div>
</template>

<style scoped>
.reading-progress { position: fixed; inset: 0 0 auto; z-index: 1100; height: 3px; pointer-events: none; }
.reading-progress span { display: block; height: 100%; background: linear-gradient(90deg, var(--color-gold-500), var(--color-gold-300)); transform-origin: left; }
</style>

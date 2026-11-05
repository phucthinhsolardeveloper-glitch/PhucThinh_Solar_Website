<script setup lang="ts">
// Money that counts to its new value instead of snapping; SSR and reduced motion show the value directly.
const props = withDefaults(defineProps<{ value: number | null; empty?: string; animate?: boolean }>(), { empty: 'Liên hệ', animate: true })
const shown = ref(props.value ?? 0)
let frame = 0

watch(() => props.value, (next, prev) => {
  cancelAnimationFrame(frame)
  if (!props.animate || next === null || prev === null || prev === undefined || !import.meta.client || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    shown.value = next ?? 0
    return
  }
  const from = shown.value
  const start = performance.now()
  const step = (now: number) => {
    const t = Math.min(1, (now - start) / 450)
    shown.value = Math.round(from + (next - from) * (1 - Math.pow(1 - t, 3)))
    if (t < 1) frame = requestAnimationFrame(step)
  }
  frame = requestAnimationFrame(step)
})
onBeforeUnmount(() => cancelAnimationFrame(frame))
</script>

<template>
  <span class="tabular-nums">{{ value === null ? empty : `${new Intl.NumberFormat('vi-VN').format(shown)}đ` }}</span>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'

withDefaults(defineProps<{
  as?: string
  delay?: number
  distance?: number
}>(), {
  as: 'div',
  delay: 0,
  distance: 12,
})

const element = ref<HTMLElement>()
const visible = ref(false)
const ready = ref(false)
let observer: IntersectionObserver | undefined
let media: MediaQueryList | undefined

function syncMotionPreference() {
  const reduce = media?.matches ?? true
  ready.value = !reduce
  if (reduce) {
    visible.value = true
    observer?.disconnect()
    return
  }
  if (element.value && 'IntersectionObserver' in window) {
    observer?.disconnect()
    observer = new IntersectionObserver(([entry]) => {
      if (entry?.isIntersecting) {
        visible.value = true
        observer?.unobserve(entry.target)
      }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0 })
    observer.observe(element.value)
  } else {
    visible.value = true
  }
}

onMounted(() => {
  media = window.matchMedia('(prefers-reduced-motion: reduce)')
  media.addEventListener('change', syncMotionPreference)
  syncMotionPreference()
})
onBeforeUnmount(() => {
  observer?.disconnect()
  media?.removeEventListener('change', syncMotionPreference)
})
</script>

<template>
  <component
    :is="as"
    ref="element"
    class="pts-motion-reveal"
    :data-motion-ready="ready"
    :data-visible="visible"
    :style="{ '--pts-motion-delay': `${Math.max(0, Math.min(delay, 500))}ms`, '--pts-motion-distance': `${Math.max(0, Math.min(distance, 32))}px` }"
  >
    <slot />
  </component>
</template>

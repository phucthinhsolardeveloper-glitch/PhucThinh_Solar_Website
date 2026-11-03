<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

export interface PtsChapterItem {
  id: string
  label: string
}

const props = withDefaults(defineProps<{
  items: PtsChapterItem[]
  label?: string
  sticky?: boolean
}>(), {
  label: 'Mục trên trang',
  sticky: false,
})

const activeId = ref('')
let observer: IntersectionObserver | undefined

function observeTargets() {
  observer?.disconnect()
  if (typeof window === 'undefined' || !('IntersectionObserver' in window)) return
  observer = new IntersectionObserver((entries) => {
    const visible = entries
      .filter(entry => entry.isIntersecting)
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
    if (visible[0]?.target.id) activeId.value = visible[0].target.id
  }, { rootMargin: '-18% 0px -72% 0px', threshold: 0 })
  props.items.forEach(({ id }) => {
    const target = document.getElementById(id)
    if (target) observer?.observe(target)
  })
}

onMounted(observeTargets)
watch(() => props.items.map(item => item.id).join('|'), observeTargets)
onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <nav class="pts-chapter-nav" :class="{ 'pts-chapter-nav--sticky': sticky }" :aria-label="label">
    <div class="pts-layout pts-layout--site">
      <div class="pts-chapter-nav__track">
        <a
          v-for="item in items"
          :key="item.id"
          class="pts-chapter-nav__link"
          :href="`#${item.id}`"
          :aria-current="activeId === item.id ? 'location' : undefined"
          @click="activeId = item.id"
        >
          {{ item.label }}
        </a>
      </div>
    </div>
  </nav>
</template>

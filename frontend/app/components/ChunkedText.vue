<script setup lang="ts">
// Long copy reads as short steps on phones. The words never change and desktop stays one paragraph:
// each chunk is an inline span there and becomes its own block below 821px (see .m-chunk in main.css).
const props = withDefaults(defineProps<{ text: string, minWords?: number }>(), { minWords: 35 })

const CHUNK_WORDS = 25
const wordCount = (value: string) => value.trim().split(/\s+/).length

const chunks = computed(() => {
  const text = props.text.trim()
  if (wordCount(text) < props.minWords) return [text]
  // Copy written as a dash list splits at its dashes, anything else at sentence ends.
  const parts = /\s– /.test(text)
    ? text.split(/\s+(?=– )/)
    : text.split(/(?<=[.!?…])\s+(?=[\p{Lu}"“])/u)
  const result: string[] = []
  for (const part of parts) {
    const last = result[result.length - 1]
    if (last && wordCount(last) < CHUNK_WORDS && !part.startsWith('– ')) result[result.length - 1] = `${last} ${part}`
    else result.push(part)
  }
  return result
})
</script>

<template>
  <template v-if="chunks.length > 1"><span v-for="(chunk, index) in chunks" :key="index" class="m-chunk">{{ chunk }}{{ index < chunks.length - 1 ? ' ' : '' }}</span></template>
  <template v-else>{{ text }}</template>
</template>

<script setup lang="ts">
const props = defineProps<{ text: string, term: string }>()
// Splits the original text around the match, so accents stay as written and nothing goes through v-html.
const parts = computed(() => {
  const term = foldText(props.term.trim())
  if (!term) return [{ text: props.text, match: false }]
  const folded = foldText(props.text)
  const output: Array<{ text: string, match: boolean }> = []
  const characters = [...props.text]
  let cursor = 0
  let index = folded.indexOf(term)
  while (index !== -1) {
    if (index > cursor) output.push({ text: characters.slice(cursor, index).join(''), match: false })
    output.push({ text: characters.slice(index, index + term.length).join(''), match: true })
    cursor = index + term.length
    index = folded.indexOf(term, cursor)
  }
  if (cursor < characters.length) output.push({ text: characters.slice(cursor).join(''), match: false })
  return output
})
</script>

<template>
  <span><template v-for="(part, index) in parts" :key="index"><mark v-if="part.match" class="search-mark">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></span>
</template>

<style scoped>
.search-mark { border-radius: 3px; background: rgb(228 168 73 / 30%); color: inherit; }
</style>

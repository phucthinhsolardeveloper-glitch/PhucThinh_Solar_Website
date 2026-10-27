<script setup lang="ts">
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'

defineProps<{ content: PublicSanitizedRichText }>()
</script>

<template>
  <div v-if="content.html" class="editorial-rich-text" v-html="content.html" />
</template>

<style scoped>
.editorial-rich-text {
  min-width: 0;
  color: var(--pts-ink, #172033);
  font-size: 1rem;
  line-height: 1.75;
}

.editorial-rich-text :deep(h2),
.editorial-rich-text :deep(h3),
.editorial-rich-text :deep(h4),
.editorial-rich-text :deep(h5),
.editorial-rich-text :deep(h6) {
  color: var(--pts-navy, #0a2251);
  line-height: 1.25;
  margin: 2rem 0 0.75rem;
}

.editorial-rich-text :deep(h2:first-child),
.editorial-rich-text :deep(h3:first-child),
.editorial-rich-text :deep(h4:first-child) {
  margin-top: 0;
}

.editorial-rich-text :deep(p),
.editorial-rich-text :deep(ul),
.editorial-rich-text :deep(ol),
.editorial-rich-text :deep(blockquote),
.editorial-rich-text :deep(pre),
.editorial-rich-text :deep(figure),
.editorial-rich-text :deep(table) {
  margin: 0 0 1rem;
}

.editorial-rich-text :deep(ul),
.editorial-rich-text :deep(ol) {
  padding-inline-start: 1.5rem;
}

.editorial-rich-text :deep(a) {
  color: var(--pts-gold-dark, #9c7420);
  text-decoration: underline;
  text-underline-offset: 0.15em;
}

.editorial-rich-text :deep(img) {
  display: block;
  max-width: 100%;
  height: auto;
}

.editorial-rich-text :deep(figcaption) {
  color: var(--pts-muted, #64748b);
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.editorial-rich-text :deep(table) {
  border-collapse: collapse;
  max-width: 100%;
  width: 100%;
}

.editorial-rich-text :deep(th),
.editorial-rich-text :deep(td) {
  border: 1px solid var(--pts-line, #e5e7eb);
  padding: 0.5rem 0.75rem;
  text-align: left;
  vertical-align: top;
}

.editorial-rich-text :deep(blockquote) {
  border-inline-start: 3px solid var(--pts-gold, #c9962e);
  padding-inline-start: 1rem;
}

.editorial-rich-text :deep(pre) {
  overflow-x: auto;
  padding: 1rem;
  background: var(--pts-surface, #f8fafc);
}
</style>

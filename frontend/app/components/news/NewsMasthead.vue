<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <header class="news-masthead">
    <h1>{{ title }}</h1>
    <p v-if="description" class="news-masthead__side">{{ description }}</p>
  </header>
</template>
<style scoped>
.news-masthead { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 520px); align-items: end; gap: clamp(20px, 4vw, 72px); padding: clamp(32px, 5vw, 72px) 0 clamp(24px, 3vw, 40px); border-bottom: 2px solid var(--color-navy-950); }
.news-masthead h1 { margin: 0; color: var(--color-navy-950); font-size: clamp(48px, 7.2vw, 116px); font-weight: 850; line-height: 0.95; letter-spacing: -0.05em; }
.news-masthead__side { margin: 0 0 10px; color: #52627a; font-size: clamp(16px, 1.2vw, 18px); line-height: 1.65; }
@media (max-width: 900px) {
  .news-masthead { grid-template-columns: 1fr; }
}
</style>

<script setup lang="ts">
defineProps<{
  title: string
  description?: string
  align?: 'left' | 'center'
}>()
</script>

<template>
  <header class="pts-page-intro" :class="`pts-page-intro--${align || 'left'}`">
    <h1 class="pts-page-intro__title">{{ title }}</h1>
    <p v-if="description" class="pts-page-intro__description">{{ description }}</p>
    <div v-if="$slots.actions" class="pts-page-intro__actions"><slot name="actions" /></div>
  </header>
</template>

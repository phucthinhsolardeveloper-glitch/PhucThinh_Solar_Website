<script setup lang="ts">
// Optional fields stay folded behind this button, so a form opens with only what is required.
withDefaults(defineProps<{ label?: string }>(), { label: 'Thêm chi tiết' })
</script>

<template>
  <button type="button" class="form-more">
    <span class="form-more__icon" aria-hidden="true"><UIcon name="i-lucide-plus" class="size-3.5" /></span>
    {{ label }}
  </button>
</template>

<style scoped>
.form-more { display: flex; width: 100%; min-height: 44px; align-items: center; justify-content: center; gap: 8px; border: 1px dashed rgb(10 34 81 / 28%); border-radius: var(--radius-sm); background: transparent; color: var(--color-navy-800); font-size: 14px; font-weight: 700; cursor: pointer; transition: border-color .2s ease, background-color .2s ease; }
.form-more:hover { border-color: var(--color-gold-600); background: rgb(201 150 46 / 8%); }
.form-more__icon { display: inline-flex; width: 20px; height: 20px; flex: none; align-items: center; justify-content: center; border-radius: 999px; background: var(--color-navy-900); color: var(--color-gold-300); }
</style>

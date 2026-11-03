<script setup lang="ts">
withDefaults(defineProps<{
  label: string
  value?: string | number | null
  note?: string
}>(), {
  value: null,
})
</script>

<template>
  <div class="pts-metric">
    <div class="pts-metric__label">{{ label }}</div>
    <div class="pts-metric__value">{{ value ?? 'Chưa có dữ liệu' }}</div>
    <div v-if="note" class="pts-metric__note">{{ note }}</div>
  </div>
</template>

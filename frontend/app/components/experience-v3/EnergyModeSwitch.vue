<script setup lang="ts">
export type EnergyMode = 'day' | 'night' | 'backup'

const props = defineProps<{
  modelValue: EnergyMode
  withBattery: boolean
}>()
const emit = defineEmits<{ 'update:modelValue': [EnergyMode] }>()

const modes: { id: EnergyMode; label: string; icon: string; note: string }[] = [
  { id: 'day', label: 'Ban ngày', icon: 'i-lucide-sun', note: 'Nguồn mặt trời' },
  { id: 'night', label: 'Ban đêm', icon: 'i-lucide-moon', note: 'Nguồn thay đổi' },
  { id: 'backup', label: 'Mất điện', icon: 'i-lucide-shield-check', note: 'Tải ưu tiên' },
]

function select(id: EnergyMode) {
  if (id === 'backup' && !props.withBattery) return
  emit('update:modelValue', id)
}
</script>

<template>
  <div class="mode-switch" role="group" aria-label="Chọn trạng thái hệ thống">
    <button
      v-for="item in modes"
      :key="item.id"
      type="button"
      class="mode-switch-button"
      :class="{ 'is-active': modelValue === item.id }"
      :aria-pressed="modelValue === item.id"
      :disabled="item.id === 'backup' && !withBattery"
      :title="item.id === 'backup' && !withBattery ? 'Cần bật \'Có pin lưu trữ\' trước' : undefined"
      @click="select(item.id)"
    >
      <UIcon :name="item.icon" aria-hidden="true" />
      <span class="mode-switch-label">{{ item.label }}</span>
    </button>
  </div>
</template>

<style scoped>
.mode-switch {
  display: flex;
  width: fit-content;
  max-width: 100%;
  align-items: center;
  gap: 6px;
}

.mode-switch-button {
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 42px;
  padding: 0 12px;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: #c3cfde;
  font: inherit;
  text-align: center;
  cursor: pointer;
  transition: background-color 0.18s ease, color 0.18s ease;
}

.mode-switch-button :deep(svg) {
  width: 18px;
  height: 18px;
  flex: none;
  color: #d2b468;
}

.mode-switch-label {
  font-size: 12px;
  font-weight: 700;
}

.mode-switch-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.mode-switch-button.is-active {
  background: rgba(224, 182, 84, 0.16);
  color: #ffe3a0;
}

.mode-switch-button.is-active :deep(svg) {
  color: #e0b654;
}

.mode-switch-button:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.mode-switch-button:focus-visible {
  outline: 2px solid #f2d281;
  outline-offset: 2px;
}

@media (max-width: 520px) {
  .mode-switch { width: 100%; gap: 3px; }
  .mode-switch-button {
    flex: 1;
    gap: 6px;
    min-height: 40px;
    padding: 0 6px;
  }
  .mode-switch-button :deep(svg) { width: 16px; height: 16px; }
  .mode-switch-label { font-size: 11px; }
}

@media (max-width: 360px) {
  .mode-switch-button { gap: 4px; padding: 0 3px; }
  .mode-switch-label { font-size: 10.5px; }
}

@media (prefers-reduced-motion: reduce) {
  .mode-switch-button { transition: none; }
}
</style>

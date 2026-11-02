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
      <span class="mode-switch-note">{{ item.note }}</span>
    </button>
  </div>
</template>

<style scoped>
.mode-switch {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  border: 1px solid rgba(10, 34, 81, 0.12);
  border-radius: 4px;
  overflow: hidden;
  background: rgba(10, 34, 81, 0.03);
}

.mode-switch-button {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  min-height: 56px;
  padding: 10px 14px;
  border: 0;
  background: #fff;
  color: #0a2251;
  font: inherit;
  text-align: left;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease;
}

.mode-switch-button :deep(svg) {
  width: 16px;
  height: 16px;
  margin-bottom: 4px;
  color: #9c7420;
}

.mode-switch-label {
  font-size: 13px;
  font-weight: 700;
}

.mode-switch-note {
  font-size: 10.5px;
  color: #64748b;
}

.mode-switch-button:hover:not(:disabled) {
  background: rgba(201, 150, 46, 0.08);
}

.mode-switch-button.is-active {
  background: linear-gradient(180deg, #123a7a 0%, #0a2251 100%);
  color: #f4f0e4;
}

.mode-switch-button.is-active :deep(svg) {
  color: #e0b654;
}

.mode-switch-button.is-active .mode-switch-note {
  color: #c7d3e2;
}

.mode-switch-button:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}

.mode-switch-button:focus-visible {
  outline: 3px solid #9c7420;
  outline-offset: 2px;
}

@media (max-width: 480px) {
  .mode-switch-button {
    padding: 8px 8px;
    min-height: 60px;
  }
  .mode-switch-note {
    display: none;
  }
}
</style>

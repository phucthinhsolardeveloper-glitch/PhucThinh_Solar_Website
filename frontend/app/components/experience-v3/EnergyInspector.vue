<script setup lang="ts">
import type { EnergyMode } from './EnergyModeSwitch.vue'

export type DeviceId = 'pv' | 'inverter' | 'battery' | 'grid' | 'load'

export interface Device {
  id: DeviceId
  title: string
  icon: string
}

const props = defineProps<{
  mode: EnergyMode
  withBattery: boolean
  devices: Device[]
  selected: DeviceId
  scenario: { title: string; body: string; route: string; state: string }
  deviceCopy: Record<DeviceId, { role: string; note: string }>
}>()
const emit = defineEmits<{ select: [DeviceId] }>()

const modeIndex = computed(() => ({ day: 1, night: 2, backup: 3 })[props.mode])
const currentCopy = computed(() => props.deviceCopy[props.selected])
const currentDevice = computed(() => props.devices.find(d => d.id === props.selected)!)
</script>

<template>
  <aside class="inspector" aria-label="Diễn giải trạng thái hệ thống">
    <div class="inspector-head">
      <span class="inspector-axis"><b>02</b><i /> DIỄN GIẢI</span>
      <span class="inspector-index">0{{ modeIndex }} / 03</span>
    </div>

    <p class="inspector-state">{{ scenario.state }}</p>
    <h2 class="inspector-title">{{ scenario.title }}</h2>
    <p class="inspector-body">{{ scenario.body }}</p>

    <div class="inspector-route">
      <span>LUỒNG MINH HỌA</span>
      <p>{{ scenario.route }}</p>
    </div>

    <div class="inspector-devices">
      <p class="inspector-devices-label">CHỌN MỘT THÀNH PHẦN</p>
      <div class="inspector-device-grid">
        <button
          v-for="device in devices"
          :key="device.id"
          type="button"
          class="inspector-device-button"
          :class="{ 'is-selected': selected === device.id }"
          :disabled="device.id === 'battery' && !withBattery"
          :aria-pressed="selected === device.id"
          @click="emit('select', device.id)"
        >
          <UIcon :name="device.icon" aria-hidden="true" />
          <span>{{ device.title }}</span>
        </button>
      </div>
      <div class="inspector-device-detail" aria-live="polite">
        <h3>{{ currentDevice.title }} <span class="role-chip">{{ currentCopy.role }}</span></h3>
        <p>{{ currentCopy.note }}</p>
      </div>
    </div>

  </aside>
</template>

<style scoped>
.inspector {
  min-width: 0;
  padding: 22px 0 0;
  color: #cbd5e1;
}

.inspector-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 14px;
  border-bottom: 1px solid rgba(226, 234, 239, 0.16);
}

.inspector-axis {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #b8c6d2;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.14em;
}
.inspector-axis b { color: #d6b260; font-size: 11px; }
.inspector-axis i { width: 20px; height: 1px; background: rgba(216, 179, 99, 0.55); }

.inspector-index {
  color: #8494a7;
  font-family: ui-monospace, monospace;
  font-size: 10px;
  letter-spacing: 0.1em;
}

.inspector-state {
  margin: 20px 0 6px;
  color: #d3af60;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.inspector-title {
  margin: 0;
  max-width: 420px;
  color: #f0f0ea;
  font-size: clamp(22px, 2.3vw, 30px);
  font-weight: 600;
  line-height: 1.22;
  letter-spacing: -0.02em;
}

.inspector-body {
  margin: 12px 0 18px;
  color: #b7c3d0;
  font-size: 13px;
  line-height: 1.75;
  max-width: 440px;
}

.inspector-route {
  padding: 12px 0 13px;
  border-top: 1px solid rgba(225, 232, 237, 0.14);
  border-bottom: 1px solid rgba(225, 232, 237, 0.14);
}
.inspector-route span {
  display: block;
  color: #899aac;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.12em;
}
.inspector-route p {
  margin: 6px 0 0;
  color: #e1c276;
  font-size: 13px;
  font-weight: 700;
}

.inspector-devices { padding-top: 16px; }
.inspector-devices-label {
  margin: 0 0 8px;
  color: #899aac;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.12em;
}
.inspector-device-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px 12px;
}
.inspector-device-button {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 0 4px;
  border: 0;
  border-bottom: 1px solid transparent;
  background: transparent;
  color: #aebbc9;
  font: inherit;
  font-size: 12px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
}
.inspector-device-button :deep(svg) { width: 15px; height: 15px; flex: none; color: #9caebc; }
.inspector-device-button:hover:not(:disabled),
.inspector-device-button.is-selected {
  color: #f2e2b7;
  border-bottom-color: rgba(201, 150, 46, 0.6);
}
.inspector-device-button.is-selected :deep(svg) { color: #e0bc6e; }
.inspector-device-button:disabled { cursor: not-allowed; opacity: 0.35; }
.inspector-device-button:focus-visible { outline: 2px solid #e4bd67; outline-offset: 3px; }

.inspector-device-detail {
  margin-top: 12px;
  padding-left: 12px;
  border-left: 2px solid #b68b3c;
}
.inspector-device-detail h3 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #e5e9e7;
  font-size: 14px;
  font-weight: 700;
}
.role-chip {
  padding: 2px 7px;
  border: 1px solid rgba(224, 182, 84, 0.45);
  border-radius: 3px;
  color: #e0b654;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 0.04em;
}
.inspector-device-detail p {
  margin: 7px 0 0;
  color: #b8c4cf;
  font-size: 12px;
  line-height: 1.7;
}

.inspector-disclaimer {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  margin: 20px 0 24px;
  padding-top: 16px;
  border-top: 1px solid rgba(225, 232, 237, 0.14);
  color: #94a3b8;
  font-size: 10.5px;
  line-height: 1.7;
}
.inspector-disclaimer :deep(svg) { flex: none; width: 14px; height: 14px; color: #d5b460; margin-top: 1px; }

@media (max-width: 900px) {
  .inspector { padding-top: 24px; }
}
</style>

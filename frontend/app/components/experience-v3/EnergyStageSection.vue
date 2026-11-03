<script setup lang="ts">
import EnergyModeSwitch, { type EnergyMode } from './EnergyModeSwitch.vue'
import EnergySceneStage from './EnergySceneStage.vue'
import EnergyInspector, { type Device, type DeviceId } from './EnergyInspector.vue'

const mode = ref<EnergyMode>('day')
const withBattery = ref(true)
const selected = ref<DeviceId>('inverter')
const devices: Device[] = [
  { id: 'pv', title: 'Mái pin mặt trời', icon: 'i-lucide-sun' },
  { id: 'inverter', title: 'Inverter', icon: 'i-lucide-cpu' },
  { id: 'battery', title: 'Pin lưu trữ', icon: 'i-lucide-battery-charging' },
  { id: 'grid', title: 'Điện lưới', icon: 'i-lucide-utility-pole' },
  { id: 'load', title: 'Ngôi nhà & phụ tải', icon: 'i-lucide-house' },
]

function changeBattery(next: boolean) {
  withBattery.value = next
  if (!next && mode.value === 'backup') mode.value = 'night'
  if (!next && selected.value === 'battery') selected.value = 'inverter'
}

const scenario = computed(() => {
  if (mode.value === 'day') return {
    title: 'Ánh nắng đi vào hệ thống.',
    body: 'Tấm pin tạo ra điện một chiều (DC). Inverter chuyển đổi thành điện xoay chiều (AC) để cấp cho các thiết bị trong nhà.',
    route: 'Mái pin → Inverter → Ngôi nhà',
    state: 'Nắng trên mái',
  }
  if (mode.value === 'night') return withBattery.value ? {
    title: 'Khi trời tối, pin tiếp sức.',
    body: 'Pin lưu trữ cấp điện qua inverter để duy trì nguồn cho ngôi nhà khi không còn ánh nắng.',
    route: 'Pin lưu trữ → Inverter → Ngôi nhà',
    state: 'Nguồn lưu trữ',
  } : {
    title: 'Không có pin, lưới là nguồn chính.',
    body: 'Điện lưới đi qua inverter trước khi tới các thiết bị trong nhà.',
    route: 'Điện lưới → Inverter → Ngôi nhà',
    state: 'Nguồn điện lưới',
  }
  return {
    title: 'Mất điện, tải ưu tiên vẫn hoạt động.',
    body: 'Pin cấp điện qua ngõ backup của inverter tới các thiết bị được chọn là tải ưu tiên, trong khi phần còn lại của ngôi nhà tạm ngắt.',
    route: 'Pin lưu trữ → Inverter backup → Tải ưu tiên',
    state: 'Tải ưu tiên',
  }
})

const activePathIds = computed(() => {
  if (mode.value === 'day') return ['solar', 'home']
  if (mode.value === 'night' && !withBattery.value) return ['grid', 'home']
  if (mode.value === 'night') return ['discharge', 'home']
  return ['discharge', 'home']
})

const deviceCopy = computed<Record<DeviceId, { role: string; note: string }>>(() => {
  const currentMode = mode.value
  return {
    pv: currentMode === 'day'
      ? { role: 'Đang nhận nắng', note: 'Tấm pin chuyển ánh sáng mặt trời thành điện một chiều (DC).' }
      : { role: 'Không hoạt động', note: 'Không có ánh nắng để chuyển đổi, mái pin tạm nghỉ.' },
    inverter: { role: 'Trung tâm chuyển đổi', note: 'Inverter chuyển điện DC từ pin/tấm pin thành điện AC và điều phối nguồn cho cả hệ thống.' },
    battery: !withBattery.value
      ? { role: 'Không có trong cấu hình', note: 'Hệ thống này không lắp pin lưu trữ.' }
      : currentMode === 'day'
        ? { role: 'Sạc điện dư', note: 'Pin lưu lại phần điện dư từ tấm pin sau khi đã cấp đủ cho ngôi nhà.' }
        : currentMode === 'night'
          ? { role: 'Đang cấp điện', note: 'Pin cấp điện qua inverter để duy trì nguồn ban đêm.' }
          : { role: 'Cấp tải ưu tiên', note: 'Pin cấp điện qua ngõ backup tới các thiết bị ưu tiên, không phải toàn bộ nhà.' },
    grid: currentMode === 'night' && !withBattery.value
      ? { role: 'Nguồn chính', note: 'Lưới cấp điện qua inverter cho ngôi nhà.' }
      : currentMode === 'backup'
        ? { role: 'Bị cách ly', note: 'Hệ thống tự cách ly khỏi lưới khi mất điện để đảm bảo an toàn, không phát ngược lên lưới.' }
        : { role: 'Nguồn dự phòng', note: 'Nguồn khác đang cấp chính ở trạng thái này.' },
    load: currentMode === 'backup'
      ? { role: 'Chỉ tải ưu tiên', note: 'Khi mất lưới, chỉ các thiết bị được cấu hình ưu tiên mới tiếp tục có điện.' }
      : { role: 'Nhận điện bình thường', note: 'Các thiết bị trong nhà nhận điện qua hệ thống phân phối.' },
  }
})
</script>

<template>
  <section class="solution-energy-stage" aria-labelledby="energy-stage-title">
    <div class="solution-energy-stage-inner pts-container">
      <div class="solution-stage-heading">
        <div>
          <h2 id="energy-stage-title">Khám phá dòng năng lượng</h2>
          <p>Chuyển trạng thái để xem các nguồn và phụ tải thay đổi trong minh họa.</p>
        </div>
        <label class="solution-battery-toggle">
          <input type="checkbox" :checked="withBattery" @change="changeBattery(($event.target as HTMLInputElement).checked)">
          <span class="solution-toggle-track" aria-hidden="true"><i /></span>
          <span>Có pin lưu trữ</span>
        </label>
      </div>
      <EnergyModeSwitch v-model="mode" :with-battery="withBattery" />
      <div class="solution-stage-layout">
        <EnergySceneStage :mode="mode" :with-battery="withBattery" :selected="selected" :active-path-ids="activePathIds" @select="selected = $event" />
        <EnergyInspector :mode="mode" :with-battery="withBattery" :devices="devices" :selected="selected" :scenario="scenario" :device-copy="deviceCopy" @select="selected = $event" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.solution-energy-stage { background: linear-gradient(155deg, #0a2251 0%, #061537 100%); color: #f4f0e4; }
.solution-energy-stage-inner { padding-top: clamp(28px, 3.6vw, 44px); padding-bottom: clamp(32px, 4vw, 52px); }
.solution-stage-heading { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
.solution-stage-heading h2 { margin: 0; color: #f4f0e4; font-size: clamp(24px, 2.7vw, 34px); line-height: 1.15; letter-spacing: -.025em; }
.solution-stage-heading p { margin: 8px 0 0; color: #bac8da; line-height: 1.6; }
.solution-battery-toggle { display: inline-flex; min-height: 44px; flex: none; align-items: center; gap: 10px; color: #e8edf4; font-size: 13px; font-weight: 650; cursor: pointer; }
.solution-battery-toggle input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.solution-toggle-track { position: relative; width: 34px; height: 19px; border: 1px solid #8190a2; border-radius: 99px; background: #46566b; transition: background .18s ease; }
.solution-toggle-track i { position: absolute; top: 3px; left: 3px; width: 11px; height: 11px; border-radius: 50%; background: #e8edf4; transition: transform .18s ease; }
.solution-battery-toggle input:checked + .solution-toggle-track { border-color: #e0b654; background: #9c7420; }
.solution-battery-toggle input:checked + .solution-toggle-track i { transform: translateX(14px); }
.solution-battery-toggle input:focus-visible + .solution-toggle-track { outline: 2px solid #f2d281; outline-offset: 3px; }
.solution-stage-layout { display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(260px, .8fr); gap: 0 26px; margin-top: 16px; }
.solution-stage-layout :deep(.scene-stage) { border-radius: 3px; }
.solution-stage-layout :deep(.inspector) { padding-top: 0; }

@media (max-width: 860px) {
  .solution-stage-heading { align-items: start; flex-direction: column; }
  .solution-stage-layout { grid-template-columns: 1fr; gap: 22px; }
  .solution-stage-layout :deep(.inspector) { padding-top: 0; }
}

@media (max-width: 520px) {
  .solution-energy-stage-inner { padding-top: 20px; padding-bottom: 24px; }
  .solution-stage-layout { gap: 14px; }
}
</style>

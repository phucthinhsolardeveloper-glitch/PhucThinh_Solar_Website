<script setup lang="ts">
/**
 * Energy V3 lab — local R&D route only. Not linked from navigation/sitemap.
 * Demonstrates: (1) precision-paper grid on a light editorial intro, (2) a
 * spatial stage whose sky/ground/materials actually relight per mode instead
 * of recoloring only the sun + windows (see docs/design/energy-v3/
 * CLAUDE-ENERGY-V3-CRITIQUE.md). Educational simulation only — no telemetry,
 * no equipment sizing, no real customer data.
 */
import GridSurface from '~/components/experience-v3/GridSurface.vue'
import EnergyModeSwitch, { type EnergyMode } from '~/components/experience-v3/EnergyModeSwitch.vue'
import EnergySceneStage from '~/components/experience-v3/EnergySceneStage.vue'
import EnergyInspector, { type Device, type DeviceId } from '~/components/experience-v3/EnergyInspector.vue'

definePageMeta({ layout: false })
useSeoMeta({
  title: 'Energy System Explorer V3 — Phúc Thịnh Solar (lab)',
  description: 'Mô phỏng tương tác nguyên lý hệ thống điện mặt trời, chưa thẩm định kỹ thuật.',
  robots: 'noindex, nofollow, noarchive',
})

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
    body: 'PV tạo điện DC. Inverter chuyển đổi để cấp cho phụ tải theo cấu hình minh họa.',
    route: 'Mái pin → Inverter → Ngôi nhà',
    state: 'Nắng trên mái',
  }
  if (mode.value === 'night') return withBattery.value ? {
    title: 'Khi trời tối, pin có thể tiếp sức.',
    body: 'Trong kịch bản này, pin cấp điện qua inverter. Khả năng cấp điện thực tế phụ thuộc trạng thái pin và cấu hình hệ thống.',
    route: 'Pin lưu trữ → Inverter → Ngôi nhà',
    state: 'Nguồn lưu trữ',
  } : {
    title: 'Không có pin, lưới là nguồn minh họa.',
    body: 'Luồng điện lưới đi qua inverter trước khi tới phụ tải. Hệ thống thực tế có thể vận hành khác theo thiết kế.',
    route: 'Điện lưới → Inverter → Ngôi nhà',
    state: 'Nguồn điện lưới',
  }
  return {
    title: 'Nguồn dự phòng cần một cấu hình riêng.',
    body: 'Pin cấp điện qua inverter/ngõ backup phù hợp tới tải ưu tiên. Mất lưới không đồng nghĩa toàn bộ ngôi nhà luôn được cấp điện.',
    route: 'Pin lưu trữ → Inverter backup → Tải ưu tiên',
    state: 'Tải ưu tiên',
  }
})

const activePathIds = computed<string[]>(() => {
  if (mode.value === 'day') return ['solar', 'home']
  if (mode.value === 'night' && !withBattery.value) return ['grid', 'home']
  if (mode.value === 'night') return ['discharge', 'home']
  return ['discharge', 'home']
})

const deviceCopy = computed<Record<DeviceId, { role: string; note: string }>>(() => {
  const m = mode.value
  return {
    pv: m === 'day'
      ? { role: 'Đang nhận nắng', note: 'Các tấm PV chuyển ánh sáng thành điện một chiều (DC) khi có nắng.' }
      : { role: 'Không hoạt động', note: 'Không có ánh nắng để chuyển đổi; mái pin ở trạng thái nghỉ trong minh họa này.' },
    inverter: { role: 'Trung tâm chuyển đổi', note: 'Inverter điều phối và chuyển đổi điện theo chức năng của hệ thống minh họa.' },
    battery: !withBattery.value
      ? { role: 'Không có trong cấu hình', note: 'Minh họa này không có pin lưu trữ.' }
      : m === 'day'
        ? { role: 'Chờ hoặc sạc tùy kịch bản', note: 'Pin có thể lưu điện dư khi hệ thống minh họa cho phép.' }
        : m === 'night'
          ? { role: 'Đang cấp điện (minh họa)', note: 'Pin cấp điện qua inverter trong kịch bản ban đêm có pin.' }
          : { role: 'Cấp tải ưu tiên (minh họa)', note: 'Pin cấp điện qua ngõ backup tới tải ưu tiên, không phải toàn bộ nhà.' },
    grid: m === 'night' && !withBattery.value
      ? { role: 'Nguồn bổ sung (minh họa)', note: 'Lưới cấp cho phụ tải qua inverter trong kịch bản này.' }
      : m === 'backup'
        ? { role: 'Bị cách ly', note: 'Minh họa cách ly điện lưới khi mất điện; không biểu diễn phát ngược lên lưới.' }
        : { role: 'Không là nguồn chính', note: 'Nguồn khác đang được minh họa làm nguồn chính ở trạng thái này.' },
    load: m === 'backup'
      ? { role: 'Chỉ tải ưu tiên', note: 'Khi mất lưới, chỉ tải ưu tiên được thiết kế đúng mới có thể được cấp điện trong minh họa.' }
      : { role: 'Nhận điện bình thường', note: 'Thiết bị trong nhà nhận điện qua hệ thống phân phối minh họa.' },
  }
})
</script>

<template>
  <main class="v3-lab">
    <header class="v3-masthead pts-container">
      <NuxtLink to="/" class="v3-brand" aria-label="Phúc Thịnh Solar — Trang chủ">
        <span class="v3-brand-mark"><UIcon name="i-lucide-sun" aria-hidden="true" /></span>
        <span>PHÚC THỊNH <b>SOLAR</b></span>
      </NuxtLink>
      <span class="v3-masthead-tag">ENERGY LAB <i>03</i> · preview nội bộ, chưa phê duyệt phát hành</span>
    </header>

    <GridSurface :size="32">
      <section class="v3-intro pts-container" aria-labelledby="v3-title">
        <div class="v3-intro-copy">
          <p class="v3-axis"><span>01</span><i /> HỆ THỐNG</p>
          <h1 id="v3-title">Một ánh sáng,<br>ba trạng thái.</h1>
          <p class="v3-intro-body">
            Cùng một hệ thống PV – inverter – lưu trữ – lưới – phụ tải, nhìn dưới ba trạng thái vận
            hành minh họa: ban ngày, ban đêm, và khi mất điện. Chuyển trạng thái để xem bầu trời,
            ngôi nhà và đường năng lượng thay đổi theo.
          </p>
        </div>
        <div class="v3-intro-peek" aria-hidden="true">
          <div class="v3-peek-frame is-night">
            <div class="v3-peek-window" />
          </div>
        </div>
      </section>
    </GridSurface>

    <section class="v3-stage-section pts-container" aria-label="Khám phá dòng năng lượng theo trạng thái">
      <div class="v3-stage-head">
        <EnergyModeSwitch v-model="mode" :with-battery="withBattery" />
        <label class="v3-battery-toggle">
          <input type="checkbox" :checked="withBattery" @change="changeBattery(($event.target as HTMLInputElement).checked)">
          <span class="v3-toggle-track"><i /></span>
          <span>Có pin lưu trữ</span>
        </label>
      </div>

      <div class="v3-stage-layout">
        <EnergySceneStage
          :mode="mode"
          :with-battery="withBattery"
          :selected="selected"
          :active-path-ids="activePathIds"
          @select="selected = $event"
        />
        <EnergyInspector
          :mode="mode"
          :with-battery="withBattery"
          :devices="devices"
          :selected="selected"
          :scenario="scenario"
          :device-copy="deviceCopy"
          @select="selected = $event"
        />
      </div>
    </section>

    <footer class="v3-footer pts-container">
      <p><strong>Minh họa giáo dục, chưa thẩm định kỹ thuật.</strong> Không đại diện cho sản phẩm, sơ đồ đấu nối hay dữ liệu vận hành thực tế; không thể hiện công suất, mức sạc hoặc mức tiết kiệm.</p>
      <NuxtLink to="/giai-phap/">Tìm hiểu giải pháp <UIcon name="i-lucide-arrow-up-right" aria-hidden="true" /></NuxtLink>
    </footer>
  </main>
</template>

<style scoped>
.v3-lab {
  min-height: 100vh;
  background: #fff;
  color: #0a2251;
  font-family: var(--font-sans);
}

.v3-masthead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  height: 76px;
  border-bottom: 1px solid rgba(10, 34, 81, 0.1);
}
.v3-brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #0a2251;
  text-decoration: none;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
}
.v3-brand b { color: #9c7420; }
.v3-brand-mark {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border: 1px solid rgba(201, 150, 46, 0.5);
  color: #9c7420;
  border-radius: 3px;
}
.v3-masthead-tag {
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
  text-align: right;
}
.v3-masthead-tag i { margin-left: 6px; color: #9c7420; font-style: normal; }

.v3-intro {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(220px, 1fr);
  align-items: center;
  gap: 48px;
  padding: clamp(56px, 8vw, 104px) 0 clamp(48px, 6vw, 76px);
}
.v3-axis {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  color: #9c7420;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.14em;
}
.v3-axis span { font-size: 13px; }
.v3-axis i { width: 26px; height: 1px; background: #c9962e; }
.v3-intro-copy h1 {
  margin: 18px 0 0;
  color: #0a2251;
  font-size: clamp(38px, 5.4vw, 68px);
  line-height: 1.02;
  letter-spacing: -0.03em;
  font-weight: 700;
}
.v3-intro-body {
  max-width: 54ch;
  margin: 22px 0 0;
  color: #475569;
  font-size: 16px;
  line-height: 1.75;
}
.v3-intro-peek { display: flex; justify-content: center; }
.v3-peek-frame {
  width: 100%;
  max-width: 260px;
  aspect-ratio: 4 / 5;
  border-radius: var(--radius-sm);
  background: linear-gradient(160deg, #0a2251 0%, #061537 100%);
  position: relative;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(6, 21, 55, 0.22);
}
.v3-peek-window {
  position: absolute;
  right: 22%;
  top: 38%;
  width: 20%;
  height: 16%;
  background: linear-gradient(180deg, #ffe6a5, #d39b42);
  filter: drop-shadow(0 0 10px rgba(233, 185, 96, 0.6));
  border-radius: 2px;
}

.v3-stage-section { padding: 0 0 clamp(56px, 7vw, 96px); }
.v3-stage-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}
.v3-battery-toggle {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  color: #334155;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.v3-battery-toggle input { position: absolute; width: 1px; height: 1px; opacity: 0; }
.v3-toggle-track { position: relative; width: 31px; height: 17px; border: 1px solid #94a3b8; border-radius: 99px; background: #e2e8f0; transition: background 0.2s; }
.v3-toggle-track i { position: absolute; top: 3px; left: 3px; width: 9px; height: 9px; border-radius: 50%; background: #64748b; transition: transform 0.2s, background 0.2s; }
.v3-battery-toggle input:checked + .v3-toggle-track { border-color: #b58c3e; background: #e9d3a3; }
.v3-battery-toggle input:checked + .v3-toggle-track i { transform: translateX(14px); background: #9c7420; }
.v3-battery-toggle input:focus-visible + .v3-toggle-track { outline: 3px solid #9c7420; outline-offset: 2px; }

.v3-stage-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.75fr) minmax(300px, 0.78fr);
  gap: 0 28px;
  padding: 24px;
  border-radius: var(--radius-md);
  background: linear-gradient(180deg, #0a2251 0%, #061537 100%);
}

.v3-footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  padding: 24px 0 48px;
  border-top: 1px solid rgba(10, 34, 81, 0.1);
}
.v3-footer p { max-width: 760px; margin: 0; color: #64748b; font-size: 12px; line-height: 1.7; }
.v3-footer strong { color: #0a2251; }
.v3-footer a { display: inline-flex; align-items: center; gap: 6px; color: #9c7420; font-size: 13px; font-weight: 700; text-decoration: none; }

@media (max-width: 900px) {
  .v3-intro { grid-template-columns: 1fr; gap: 28px; padding: 44px 0 36px; }
  .v3-intro-peek { display: none; }
  .v3-stage-layout { grid-template-columns: 1fr; gap: 24px; padding: 16px; }
}

@media (max-width: 480px) {
  .v3-masthead { height: 64px; }
  .v3-masthead-tag { display: none; }
  .v3-intro-copy h1 { font-size: 40px; }
}
</style>

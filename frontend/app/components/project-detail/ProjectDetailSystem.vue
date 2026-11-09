<script setup lang="ts">
import type { PublicProjectSystem } from '#shared/contracts/public-project-detail'

// The installation in numbers, right under the project hero. Only what Admin filled in is shown.
const props = defineProps<{ system: PublicProjectSystem }>()
const number = (value: number) => value.toLocaleString('vi-VN', { maximumFractionDigits: 2 })
const facts = computed(() => [
  props.system.typeLabel ? { icon: 'i-lucide-cpu', label: 'Loại hệ thống', value: props.system.typeLabel, unit: '' } : null,
  props.system.kwp ? { icon: 'i-lucide-sun', label: 'Công suất pin', value: number(props.system.kwp), unit: 'kWp' } : null,
  props.system.storageKwh ? { icon: 'i-lucide-battery-charging', label: 'Lưu trữ', value: number(props.system.storageKwh), unit: 'kWh' } : null,
  props.system.outputKwhMonth ? { icon: 'i-lucide-zap', label: 'Sản lượng thực tế', value: number(props.system.outputKwhMonth), unit: 'kWh/tháng' } : null,
  props.system.savingMonth ? { icon: 'i-lucide-piggy-bank', label: 'Tiết kiệm', value: number(props.system.savingMonth) + 'đ', unit: '/tháng' } : null,
  props.system.buildDays ? { icon: 'i-lucide-calendar-check', label: 'Thi công', value: number(props.system.buildDays), unit: 'ngày' } : null,
].filter((fact) => fact !== null))
</script>

<template>
  <section v-if="facts.length" class="project-system" aria-labelledby="project-system-title">
    <div class="pts-container">
      <h2 id="project-system-title" class="sr-only">Thông số hệ thống</h2>
      <dl class="project-system__grid">
        <div v-for="fact in facts" :key="fact.label" class="project-system__fact">
          <dt><UIcon :name="fact.icon" class="size-4" aria-hidden="true" />{{ fact.label }}</dt>
          <dd>{{ fact.value }} <small v-if="fact.unit">{{ fact.unit }}</small></dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<style scoped>
.project-system { border-bottom: 1px solid rgb(10 34 81 / 8%); background: #fffbf2; }
.project-system__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1px; margin: 0; padding: 18px 0; }
@media (min-width: 768px) { .project-system__grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); padding: 22px 0; } }
.project-system__fact { padding: 8px 14px; border-left: 3px solid var(--color-gold-500); }
.project-system__fact dt { display: flex; align-items: center; gap: 6px; color: #475569; font-size: 13.5px; font-weight: 700; }
.project-system__fact dd { margin: 4px 0 0; color: var(--color-navy-950); font-size: clamp(19px, 2vw, 24px); font-weight: 900; font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
.project-system__fact dd small { color: #64748b; font-size: 13px; font-weight: 700; }
</style>

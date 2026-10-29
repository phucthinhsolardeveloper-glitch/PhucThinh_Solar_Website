<script setup lang="ts">
import type { PublicProductDecisionMetric } from '#shared/contracts/public-product-detail'
import { formatProductMetricValue } from '#shared/product-metric-display'

const props = defineProps<{ metrics: PublicProductDecisionMetric[] }>()
const availableMetrics = computed(() => props.metrics.filter((metric) => metric.value.trim() !== ''))

function displayValue(metric: PublicProductDecisionMetric): string {
  return formatProductMetricValue(metric.value, metric.unit)
}
</script>

<template>
  <div v-if="availableMetrics.length" class="pts-pdp-decision-metrics" aria-label="Thông số chính của sản phẩm">
    <div v-for="metric in availableMetrics" :key="metric.key" class="pts-pdp-decision-metric">
      <span class="pts-pdp-decision-icon" aria-hidden="true">
        <img
          v-if="metric.iconImage"
          :src="`/assets/images/icons/${metric.iconDir}/${metric.iconImage}`"
          alt=""
          width="44"
          height="44"
          class="h-full w-full object-contain"
          loading="lazy"
          decoding="async"
        >
        <svg v-else class="fill-none stroke-current stroke-[1.6] text-gold-700" viewBox="0 0 32 32">
          <template v-if="metric.icon === 'battery'">
            <rect x="6" y="7" width="19" height="18" rx="2" /><path d="M25 13h3v6h-3M10 12h11M10 16h11M10 20h11" />
          </template>
          <template v-else-if="metric.icon === 'height'">
            <path d="M16 4v24M12 8l4-4 4 4M12 24l4 4 4-4M8 9h7M8 13h5M8 19h5M8 23h7" />
          </template>
          <template v-else-if="metric.icon === 'shield'">
            <path d="M16 4l10 4v7c0 6-4.2 10.7-10 13-5.8-2.3-10-7-10-13V8l10-4zM11 16l3 3 7-7" />
          </template>
          <template v-else-if="metric.icon === 'sun'">
            <circle cx="16" cy="16" r="6" /><path d="M16 3v5M16 24v5M3 16h5M24 16h5M7 7l4 4M21 21l4 4M25 7l-4 4M11 21l-4 4" />
          </template>
          <path v-else d="M18 3L8 18h7l-1 11 10-15h-7l1-11z" />
        </svg>
      </span>
      <div class="min-w-0">
        <span class="block text-[10px] font-extrabold uppercase tracking-[0.055em] text-slate-500">{{ metric.label }}</span>
        <strong class="mt-0.5 block break-words text-[15px] font-extrabold leading-[1.35] text-navy-800">{{ displayValue(metric) }}</strong>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pts-pdp-decision-metrics {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin: 20px 0 0;
  border-top: 1px solid #dfe5ed;
  border-bottom: 1px solid #dfe5ed;
}

.pts-pdp-decision-metric {
  display: grid;
  grid-template-columns: 44px minmax(0, 1fr);
  gap: 12px;
  min-width: 0;
  padding: 16px 18px 16px 0;
}

.pts-pdp-decision-metric:nth-child(even) {
  padding-right: 0;
  padding-left: 18px;
  border-left: 1px solid #dfe5ed;
}

.pts-pdp-decision-metric:nth-child(n + 3) { border-top: 1px solid #dfe5ed; }
.pts-pdp-decision-icon { display: flex; width: 44px; height: 44px; align-items: center; justify-content: center; }
.pts-pdp-decision-icon img { display: block; width: 44px; height: 44px; object-fit: contain; }
.pts-pdp-decision-icon svg { display: block; width: 32px; height: 32px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.6; }

@media (max-width: 640px) {
  .pts-pdp-decision-metrics { margin-top: 16px; }
  .pts-pdp-decision-metric { grid-template-columns: 36px minmax(0, 1fr); gap: 9px; padding: 14px 10px 14px 0; }
  .pts-pdp-decision-metric:nth-child(even) { padding-right: 0; padding-left: 10px; }
  .pts-pdp-decision-icon { width: 36px; height: 36px; }
  .pts-pdp-decision-icon img { width: 36px; height: 36px; }
  .pts-pdp-decision-icon svg { width: 26px; height: 26px; }
  .pts-pdp-decision-metric strong { font-size: 13px; }
  .pts-pdp-decision-metric small { font-size: 10px; }
}

@media (max-width: 359px) {
  .pts-pdp-decision-metrics { grid-template-columns: 1fr; border-top: 0; border-bottom: 0; }
  .pts-pdp-decision-metric,
  .pts-pdp-decision-metric:nth-child(even) { padding: 12px 0; border-top: 1px solid #dfe5ed; border-left: 0; }
  .pts-pdp-decision-metric:first-child { border-top: 0; }
}
</style>

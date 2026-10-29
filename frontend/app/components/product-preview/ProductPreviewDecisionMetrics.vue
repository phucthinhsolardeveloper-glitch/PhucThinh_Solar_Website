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
  <div v-if="availableMetrics.length" class="mt-6 grid grid-cols-1 gap-x-4 gap-y-5 tb:grid-cols-2 tb:gap-x-6" aria-label="Thông số chính của sản phẩm">
    <div v-for="metric in availableMetrics" :key="metric.key" class="flex min-w-0 items-start gap-2.5">
      <img
        v-if="metric.iconImage"
        :src="`/assets/images/icons/${metric.iconDir}/${metric.iconImage}`"
        alt=""
        width="28"
        height="28"
        class="mt-0.5 h-7 w-7 flex-none object-contain"
        loading="lazy"
        decoding="async"
      >
      <svg v-else class="mt-0.5 h-7 w-7 flex-none fill-none stroke-current stroke-[1.6] text-gold-700" viewBox="0 0 32 32" aria-hidden="true">
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
      <div class="min-w-0">
        <span class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ metric.label }}</span>
        <strong class="mt-0.5 block break-words text-base leading-snug text-navy-800">{{ displayValue(metric) }}</strong>
      </div>
    </div>
  </div>
</template>

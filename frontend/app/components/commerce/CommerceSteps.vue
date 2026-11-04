<script setup lang="ts">
const props = defineProps<{ current: 'cart' | 'checkout' | 'done' }>()
const steps = [
  { id: 'cart', label: 'Giỏ hàng', icon: 'i-lucide-shopping-bag', to: '/gio-hang/' },
  { id: 'checkout', label: 'Thông tin giao hàng', icon: 'i-lucide-truck', to: '/thanh-toan/' },
  { id: 'done', label: 'Hoàn tất', icon: 'i-lucide-badge-check', to: '' },
] as const
const index = computed(() => steps.findIndex((step) => step.id === props.current))
</script>

<template>
  <nav class="commerce-steps" aria-label="Các bước đặt hàng">
    <ol :style="{ '--progress': index / (steps.length - 1) }">
      <li v-for="(step, i) in steps" :key="step.id" :class="{ 'is-done': i < index, 'is-current': i === index }">
        <NuxtLink v-if="i < index && step.to" :to="step.to" class="commerce-steps__item">
          <span class="commerce-steps__dot" aria-hidden="true"><UIcon name="i-lucide-check" class="size-4" /></span>
          <span>{{ step.label }}</span>
        </NuxtLink>
        <span v-else class="commerce-steps__item" :aria-current="i === index ? 'step' : undefined">
          <span class="commerce-steps__dot" aria-hidden="true"><UIcon :name="step.icon" class="size-4" /></span>
          <span>{{ step.label }}</span>
        </span>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.commerce-steps ol { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin: 0; padding: 0; list-style: none; }
.commerce-steps ol::before, .commerce-steps ol::after { content: ''; position: absolute; top: 19px; left: calc(100% / 6); height: 2px; border-radius: 2px; }
.commerce-steps ol::before { right: calc(100% / 6); background: #e2e8f0; }
.commerce-steps ol::after { width: calc((100% - 100% / 3) * var(--progress)); background: linear-gradient(90deg, var(--color-gold-500), var(--color-gold-300)); animation: steps-grow 0.9s cubic-bezier(0.22, 1, 0.36, 1) both; transform-origin: left; }
.commerce-steps li { display: flex; justify-content: center; }
.commerce-steps__item { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; color: #94a3b8; font-size: 14px; font-weight: 700; text-align: center; text-decoration: none; }
.commerce-steps__dot { display: grid; width: 40px; height: 40px; place-items: center; border: 2px solid #e2e8f0; border-radius: 50%; background: #fff; transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease; }
.is-done .commerce-steps__item { color: var(--color-navy-800); }
.is-done .commerce-steps__dot { border-color: var(--color-gold-500); background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.is-current .commerce-steps__item { color: var(--color-navy-950); }
.is-current .commerce-steps__dot { border-color: var(--color-navy-950); background: var(--color-navy-950); color: var(--color-gold-300); box-shadow: 0 0 0 6px rgb(10 34 81 / 8%); }
a.commerce-steps__item:hover { color: var(--color-gold-700); }
a.commerce-steps__item:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; border-radius: var(--radius-sm); }
@keyframes steps-grow { from { transform: scaleX(0); } }
@media (max-width: 520px) { .commerce-steps__item { font-size: 12.5px; } }
@media (prefers-reduced-motion: reduce) { .commerce-steps ol::after { animation: none; } }
</style>

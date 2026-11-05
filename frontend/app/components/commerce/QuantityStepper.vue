<script setup lang="ts">
import { MAX_COMMERCE_QUANTITY, MIN_COMMERCE_QUANTITY, normalizeCommerceQuantity } from '#shared/commerce-quantity'

const props = withDefaults(defineProps<{
  modelValue: number
  label?: string
  disabled?: boolean
  busy?: boolean
  inlineLabel?: boolean
}>(), {
  label: 'Số lượng',
  disabled: false,
  busy: false,
  inlineLabel: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: number]
  change: [value: number]
}>()
const quantity = computed(() => normalizeCommerceQuantity(props.modelValue))

function setQuantity(value: unknown, commit = false) {
  const next = normalizeCommerceQuantity(value)
  emit('update:modelValue', next)
  if (commit) emit('change', next)
}

function onInput(event: Event) {
  setQuantity((event.target as HTMLInputElement).valueAsNumber)
}

function onChange(event: Event) {
  setQuantity((event.target as HTMLInputElement).valueAsNumber, true)
}
</script>

<template>
  <div :class="inlineLabel ? 'flex items-center gap-3' : 'grid gap-1.5'">
    <span v-if="label" :class="inlineLabel ? 'text-[13px] font-bold text-slate-500' : 'text-sm font-bold text-navy-800'">
      {{ inlineLabel ? `${label}:` : label }}
    </span>
    <div role="group" :aria-label="label" :aria-busy="busy ? 'true' : undefined" class="quantity-stepper inline-flex w-fit items-stretch overflow-hidden rounded-chip border border-gray-300" :class="{ 'is-busy': busy }">
      <button
        type="button"
        class="grid h-11 w-11 flex-none place-items-center text-xl font-semibold text-navy-800 hover:bg-slate-50 focus-visible:z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-gold-600 disabled:cursor-not-allowed disabled:text-slate-300"
        :disabled="disabled || quantity <= MIN_COMMERCE_QUANTITY"
        :aria-label="`Giảm ${label.toLocaleLowerCase()}`"
        @click="setQuantity(quantity - 1, true)"
      >−</button>
      <input
        type="number"
        min="1"
        max="999"
        step="1"
        inputmode="numeric"
        :value="quantity"
        :aria-label="label"
        :disabled="disabled"
        class="h-11 w-[4.5rem] min-w-[4.5rem] border-x border-gray-300 bg-white px-2 text-center text-base text-navy-800 outline-none focus-visible:z-10 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-600 disabled:bg-slate-50"
        @input="onInput"
        @change="onChange"
      >
      <button
        type="button"
        class="grid h-11 w-11 flex-none place-items-center text-xl font-semibold text-navy-800 hover:bg-slate-50 focus-visible:z-10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-gold-600 disabled:cursor-not-allowed disabled:text-slate-300"
        :disabled="disabled || quantity >= MAX_COMMERCE_QUANTITY"
        :aria-label="`Tăng ${label.toLocaleLowerCase()}`"
        @click="setQuantity(quantity + 1, true)"
      >+</button>
    </div>
  </div>
</template>

<style scoped>
.quantity-stepper.is-busy button:disabled {
  background: transparent;
  color: var(--color-navy-800);
  opacity: 1;
}

.quantity-stepper.is-busy input:disabled {
  background: #fff;
  color: var(--color-navy-800);
  opacity: 1;
  -webkit-text-fill-color: var(--color-navy-800);
}
</style>

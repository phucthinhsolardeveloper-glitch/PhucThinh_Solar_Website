<script setup lang="ts">
export interface PtsJourneyStep {
  id: string
  title: string
  description?: string
  href?: string
}

withDefaults(defineProps<{
  steps: PtsJourneyStep[]
  currentId?: string
}>(), {
  currentId: '',
})
</script>

<template>
  <ol class="pts-journey">
    <li
      v-for="step in steps"
      :key="step.id"
      class="pts-journey__step"
      :aria-current="currentId === step.id ? 'step' : undefined"
    >
      <NuxtLink v-if="step.href" :to="step.href" class="pts-journey__content">
        <h3 class="m-0 text-lg font-bold text-navy-800">{{ step.title }}</h3>
        <p v-if="step.description" class="mt-2 mb-0 text-sm leading-6 text-slate-600">{{ step.description }}</p>
      </NuxtLink>
      <div v-else class="pts-journey__content">
        <h3 class="m-0 text-lg font-bold text-navy-800">{{ step.title }}</h3>
        <p v-if="step.description" class="mt-2 mb-0 text-sm leading-6 text-slate-600">{{ step.description }}</p>
      </div>
    </li>
  </ol>
</template>

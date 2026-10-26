<script setup lang="ts">
import type { PublicSolutionEquipment } from '../../../shared/contracts/public-solutions'

defineProps<{ equipment: PublicSolutionEquipment[]; title: string; allProductsUrl: string; allProductsLabel: string }>()
</script>

<template>
  <section v-if="equipment.length" class="rounded-btn bg-slate-50 px-[26px] py-[27px]" aria-labelledby="solutions-equipment-title">
    <header class="mb-[22px] flex flex-col items-start gap-4 tb:flex-row tb:items-end tb:justify-between tb:gap-6">
      <h2 id="solutions-equipment-title" class="m-0 text-[28px] font-black leading-[1.25] text-navy-800">{{ title }}</h2>
      <a class="font-extrabold text-navy-800 no-underline" :href="allProductsUrl">{{ allProductsLabel }} <span aria-hidden="true">→</span></a>
    </header>

    <div class="grid grid-cols-1 gap-2.5 tb:grid-cols-2 nav:grid-cols-4">
      <a
        v-for="item in equipment"
        :key="item.url"
        class="flex min-w-0 items-center gap-3 border border-gray-200 bg-white px-[13px] py-4 text-navy-800 no-underline"
        :href="item.url"
      >
        <img v-if="item.image" :src="item.image" alt="" class="h-[72px] w-16 flex-none object-contain" width="80" height="80" loading="lazy" decoding="async">
        <span class="min-w-0">
          <strong class="block leading-snug">{{ item.name }}</strong>
          <small v-if="item.description" class="mt-1 line-clamp-2 block text-sm leading-[1.5] text-slate-500">{{ item.description }}</small>
        </span>
        <span class="ml-auto flex-none text-xl" aria-hidden="true">→</span>
      </a>
    </div>
  </section>
</template>

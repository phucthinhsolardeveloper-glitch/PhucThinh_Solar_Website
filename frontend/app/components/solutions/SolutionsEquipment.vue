<script setup lang="ts">
import type { PublicSolutionEquipment } from '../../../shared/contracts/public-solutions'

defineProps<{ equipment: PublicSolutionEquipment[]; title: string; allProductsUrl: string; allProductsLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <section v-if="equipment.length" class="py-8" aria-labelledby="solutions-equipment-title">
    <header class="mb-[22px] flex flex-col items-start gap-4 tb:flex-row tb:items-end tb:justify-between tb:gap-6">
      <h2 id="solutions-equipment-title" class="m-0 text-[28px] font-black leading-[1.25] text-navy-800">{{ title }}</h2>
      <a class="inline-flex min-h-11 items-center font-extrabold text-navy-800 no-underline" :href="links.generic(allProductsUrl)">{{ allProductsLabel }} <span aria-hidden="true">→</span></a>
    </header>

    <div class="grid grid-cols-1 gap-x-6 gap-y-3 tb:grid-cols-2 nav:grid-cols-4">
      <a
        v-for="item in equipment"
        :key="item.url"
        class="group flex min-w-0 items-center gap-3 py-3 text-navy-800 no-underline hover:text-gold-700"
        :href="links.generic(item.url)"
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

<script setup lang="ts">
import type { PublicSolutionEquipment } from '../../../shared/contracts/public-solutions'
import { resolveEquipmentIcon } from '#shared/catalog-menu-presentation'

defineProps<{ equipment: PublicSolutionEquipment[]; title: string; allProductsUrl: string; allProductsLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <section v-if="equipment.length" class="py-8" aria-labelledby="solutions-equipment-title">
    <header class="mb-6 flex flex-col items-start gap-3 border-t border-slate-200/80 pt-7 tb:flex-row tb:items-end tb:justify-between tb:gap-6">
      <h2 id="solutions-equipment-title" class="m-0 text-[clamp(22px,2.2vw,28px)] font-black leading-[1.25] tracking-tight text-navy-800">{{ title }}</h2>
      <a class="inline-flex min-h-11 items-center gap-1.5 text-sm font-extrabold text-gold-700 no-underline transition-colors hover:text-navy-800" :href="links.generic(allProductsUrl)">{{ allProductsLabel }} <span aria-hidden="true">→</span></a>
    </header>

    <div class="grid grid-cols-1 gap-4 ph:grid-cols-2 nav:grid-cols-4">
      <a
        v-for="item in equipment"
        :key="item.url"
        class="group flex min-w-0 flex-col justify-between rounded-chip border border-slate-200/90 bg-white p-4 text-navy-800 no-underline transition-all hover:border-gold-500 hover:shadow-sm"
        :href="links.generic(item.url)"
      >
        <div>
          <div v-if="item.image" class="mb-3.5 flex aspect-square w-full items-center justify-center overflow-hidden rounded-chip bg-navy-800/[.03] p-4">
            <img :src="item.image" alt="" class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.03]" width="160" height="160" loading="lazy" decoding="async">
          </div>
          <div v-else class="mb-3.5 flex items-center justify-between">
            <span class="grid h-11 w-11 place-items-center rounded-chip bg-navy-800/[.05] text-gold-700 transition-colors group-hover:bg-navy-800 group-hover:text-gold-300">
              <CatalogMenuIcon class="h-5 w-5" :name="resolveEquipmentIcon(item.name, item.url)" />
            </span>
            <span class="text-base text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-gold-700" aria-hidden="true">→</span>
          </div>
          <strong class="block text-[15px] font-extrabold leading-snug text-navy-800 transition-colors group-hover:text-gold-700">{{ item.name }}</strong>
          <small v-if="item.description" class="mt-1.5 line-clamp-2 block text-[13px] leading-[1.58] text-slate-500">{{ item.description }}</small>
        </div>
        <span v-if="item.image" class="mt-3 inline-flex items-center gap-1 text-xs font-extrabold text-gold-700">
          <span>Xem danh mục</span>
          <span aria-hidden="true">→</span>
        </span>
      </a>
    </div>
  </section>
</template>

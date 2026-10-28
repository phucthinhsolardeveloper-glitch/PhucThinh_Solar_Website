<script setup lang="ts">
import type { PublicProjectEquipment } from '#shared/contracts/public-project-detail'

defineProps<{ items: PublicProjectEquipment[] }>()
const links = useMigrationLinks()
</script>

<template>
  <section v-if="items.length" class="mt-16" aria-labelledby="project-equipment-title">
    <h2 id="project-equipment-title" class="mb-5 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Thiết bị liên quan</h2>
    <div class="grid grid-cols-2 gap-6 tb:grid-cols-4">
      <a v-for="item in items" :key="item.id" class="text-inherit no-underline" :href="links.generic(item.url)">
        <img v-if="item.image" :src="item.image" :alt="item.name" class="block aspect-square w-full rounded-chip bg-slate-50 object-contain" loading="lazy" decoding="async">
        <span class="mt-3 grid gap-1">
          <strong class="font-bold text-navy-800">{{ item.name }}</strong>
          <small v-if="item.code" class="text-slate-500">Mã: {{ item.code }}</small>
        </span>
      </a>
    </div>
  </section>
</template>

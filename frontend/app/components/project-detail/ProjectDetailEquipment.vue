<script setup lang="ts">
import type { PublicProjectEquipment } from '#shared/contracts/public-project-detail'

defineProps<{ items: PublicProjectEquipment[] }>()
const links = useMigrationLinks()
</script>

<template>
  <section v-if="items.length" class="mt-16" aria-labelledby="project-equipment-title">
    <div class="mb-6 max-w-[720px]">
      <h2 id="project-equipment-title" class="mb-2 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Thiết bị liên quan</h2>
      <p class="m-0 text-base leading-relaxed text-slate-600">Các thiết bị được CMS liên kết với công trình, giúp đối chiếu giải pháp và tìm hiểu thêm về cấu hình sử dụng.</p>
    </div>
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

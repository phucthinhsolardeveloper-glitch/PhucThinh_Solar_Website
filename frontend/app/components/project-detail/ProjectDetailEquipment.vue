<script setup lang="ts">
import type { PublicProjectEquipment } from '#shared/contracts/public-project-detail'

defineProps<{ items: PublicProjectEquipment[] }>()
const links = usePublicLinks()

</script>

<template>
  <section v-if="items.length" id="project-equipment" class="mt-10 scroll-mt-20 grid gap-7 border-t-[3px] border-navy-800 pt-7 tb:grid-cols-[minmax(190px,0.7fr)_minmax(0,1.3fr)] tb:gap-12 nav:mt-16" aria-labelledby="project-equipment-title">
    <div class="max-w-[560px]">
      <h2 id="project-equipment-title" class="m-0 mb-2 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Điểm nhấn kỹ thuật</h2>
      <p class="m-0 text-base leading-relaxed text-slate-600">Thiết bị và nhóm vật tư chính cấu thành hệ thống năng lượng mặt trời.</p>
    </div>
    <div class="grid grid-cols-2 gap-6 tb:grid-cols-4">
      <a v-for="item in items" :key="item.id" class="group text-inherit no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-600" :href="links.generic(item.url)">
        <img v-if="item.image" :src="optimizedImage(item.image, 480, 480)" :alt="item.name" class="block aspect-square w-full rounded-chip bg-slate-50 p-2 object-contain transition-transform duration-300 group-hover:scale-105 motion-reduce:transform-none" loading="lazy" decoding="async">
        <span class="mt-3 grid gap-1">
          <strong class="font-bold text-navy-800 transition-colors group-hover:text-gold-700">{{ item.name }}</strong>
          <small v-if="item.code" class="text-slate-500">Mã: {{ item.code }}</small>
        </span>
      </a>
    </div>
  </section>
</template>

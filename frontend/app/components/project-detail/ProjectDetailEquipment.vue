<script setup lang="ts">
import type { PublicProjectEquipment } from '#shared/contracts/public-project-detail'

defineProps<{ items: PublicProjectEquipment[] }>()
const links = useMigrationLinks()

const coreCategories = [
  { name: 'Tấm pin năng lượng mặt trời', desc: 'Mono Half-cell hiệu suất cao cho hệ áp mái', url: '/tam-pin-nang-luong-mat-troi' },
  { name: 'Biến tần - Inverter', desc: 'Hòa lưới bám tải & Hybrid lưu trữ thông minh', url: '/bien-tan-inverter' },
  { name: 'Pin lưu trữ Lithium', desc: 'An toàn, tuổi thọ chu kỳ sạc xả dài hạn', url: '/pin-luu-tru-lithium' },
  { name: 'Đèn năng lượng mặt trời', desc: 'Chiếu sáng sân vườn, đường nội bộ & công trình', url: '/den-nang-luong-mat-troi' },
]
</script>

<template>
  <section id="project-equipment" class="mt-10 scroll-mt-20 grid gap-7 border-t-[3px] border-navy-800 pt-7 tb:grid-cols-[minmax(190px,0.7fr)_minmax(0,1.3fr)] tb:gap-12 nav:mt-16" aria-labelledby="project-equipment-title">
    <div class="max-w-[560px]">
      <h2 id="project-equipment-title" class="m-0 mb-2 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Điểm nhấn kỹ thuật</h2>
      <p class="m-0 text-base leading-relaxed text-slate-600">Thiết bị và nhóm vật tư chính cấu thành hệ thống năng lượng mặt trời.</p>
    </div>
    <div v-if="items.length" class="grid grid-cols-2 gap-6 tb:grid-cols-4">
      <a v-for="item in items" :key="item.id" class="group text-inherit no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-600" :href="links.generic(item.url)">
        <img v-if="item.image" :src="item.image" :alt="item.name" class="block aspect-square w-full rounded-chip bg-slate-50 p-2 object-contain transition-transform duration-300 group-hover:scale-105 motion-reduce:transform-none" loading="lazy" decoding="async">
        <span class="mt-3 grid gap-1">
          <strong class="font-bold text-navy-800 transition-colors group-hover:text-gold-700">{{ item.name }}</strong>
          <small v-if="item.code" class="text-slate-500">Mã: {{ item.code }}</small>
        </span>
      </a>
    </div>
    <div v-else class="grid gap-4">
      <p class="m-0 max-w-[60ch] text-sm leading-relaxed text-slate-500">Chưa có thiết bị hoặc sản phẩm liên quan được công bố cho hồ sơ này. Tham khảo các nhóm thiết bị cốt lõi thường dùng trong hệ thống:</p>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <a
          v-for="cat in coreCategories"
          :key="cat.url"
          :href="links.generic(cat.url)"
          class="group flex items-start justify-between gap-3 rounded-[4px] border-l-[3px] border-gold-500 bg-slate-50 p-4 text-navy-900 no-underline transition-colors hover:bg-slate-100"
        >
          <span class="min-w-0">
            <strong class="block text-sm font-extrabold transition-colors group-hover:text-gold-700">{{ cat.name }}</strong>
            <small class="mt-1 block text-xs leading-relaxed text-slate-600">{{ cat.desc }}</small>
          </span>
          <span class="font-bold text-gold-700 transition-transform group-hover:translate-x-1 motion-reduce:transform-none" aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  </section>
</template>

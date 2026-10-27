<script setup lang="ts">
import type { PublicProjectGalleryItem } from '#shared/contracts/public-project-detail'

const props = defineProps<{ items: PublicProjectGalleryItem[] }>()

const hasVideo = computed(() => props.items.some((item) => item.type === 'video'))
const activeTab = ref<'image' | 'video'>('image')
const visibleItems = computed(() => props.items.filter((item) => item.type === activeTab.value))
</script>

<template>
  <section aria-labelledby="project-gallery-title">
    <h2 id="project-gallery-title" class="mb-5 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Hình ảnh và media</h2>

    <!--
      Tab "Hình ảnh / Video" lọc theo loại: đo trên ảnh production, hai tab
      cùng kiểu gạch chân vàng dưới mục đang chọn đã dùng ở trang chi tiết sản
      phẩm. Chỉ hiện tab Video khi thật sự có mục video — tránh một tab trống
      không bấm được gì.
    -->
    <div v-if="hasVideo" class="mb-6 flex gap-6 border-b border-gray-200">
      <button
        class="relative border-0 bg-transparent px-1 py-3 font-bold"
        :class="activeTab === 'image' ? 'text-navy-800' : 'text-slate-500'"
        type="button"
        @click="activeTab = 'image'"
      >
        Hình ảnh
        <span v-if="activeTab === 'image'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" />
      </button>
      <button
        class="relative border-0 bg-transparent px-1 py-3 font-bold"
        :class="activeTab === 'video' ? 'text-navy-800' : 'text-slate-500'"
        type="button"
        @click="activeTab = 'video'"
      >
        Video
        <span v-if="activeTab === 'video'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" />
      </button>
    </div>

    <div v-if="visibleItems.length" class="grid grid-cols-2 gap-5 tb:grid-cols-3">
      <figure v-for="(item, index) in visibleItems" :key="`${item.type}-${item.src}-${index}`" class="m-0">
        <a v-if="item.type === 'video'" class="block text-inherit no-underline" :href="item.href" target="_blank" rel="noopener noreferrer">
          <img v-if="item.src" :src="item.src" :alt="item.caption || 'Media dự án'" class="block aspect-[4/3] w-full rounded-chip bg-slate-50 object-cover" loading="lazy" decoding="async">
          <span class="mt-2.5 block font-bold text-gold-700">Mở video ↗</span>
        </a>
        <img v-else :src="item.src" :alt="item.caption || 'Hình ảnh dự án'" class="block aspect-[4/3] w-full rounded-chip bg-slate-50 object-cover" loading="lazy" decoding="async">
        <figcaption v-if="item.caption" class="pt-2 text-sm leading-relaxed text-slate-500">{{ item.caption }}</figcaption>
      </figure>
    </div>
    <p v-else class="leading-relaxed text-slate-500">Chưa có media được công bố cho dự án này.</p>
  </section>
</template>

<script setup lang="ts">
import type { PublicProjectGalleryItem } from '#shared/contracts/public-project-detail'
import { projectVideoEmbedUrl } from '#shared/project-gallery'

const props = defineProps<{ items: PublicProjectGalleryItem[] }>()

const imageItems = computed(() => props.items.filter((item) => item.type === 'image'))
const videoItems = computed(() => props.items.filter((item) => item.type === 'video'))
const hasVideo = computed(() => videoItems.value.length > 0)
const activeTab = ref<'image' | 'video'>('image')
const visibleItems = computed(() => activeTab.value === 'image' ? imageItems.value : videoItems.value)
const selected = ref<PublicProjectGalleryItem | null>(null)
const selectedEmbedUrl = computed(() => selected.value?.type === 'video' ? projectVideoEmbedUrl(selected.value.href) : '')
const imageTabButton = ref<HTMLButtonElement | null>(null)
const videoTabButton = ref<HTMLButtonElement | null>(null)
const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)

function onTabKeydown(event: KeyboardEvent, tab: 'image' | 'video') {
  if (!hasVideo.value) return
  let next: 'image' | 'video' | null = null
  if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = tab === 'image' ? 'video' : 'image'
  else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = tab === 'video' ? 'image' : 'video'
  else if (event.key === 'Home') next = 'image'
  else if (event.key === 'End') next = 'video'
  if (!next) return
  event.preventDefault()
  activeTab.value = next
  nextTick(() => (next === 'image' ? imageTabButton.value : videoTabButton.value)?.focus())
}

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a[href], button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])')
  first?.focus()
}

function trapFocus(event: KeyboardEvent, container: HTMLElement | null) {
  if (!container) return
  const focusable = [...container.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])')]
  if (!focusable.length) return
  const first = focusable[0]!
  const last = focusable[focusable.length - 1]!
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

function open(item: PublicProjectGalleryItem, trigger?: HTMLElement) {
  selected.value = item
  lastTrigger.value = trigger ?? (document.activeElement as HTMLElement | null)
}

function close() {
  selected.value = null
  if (import.meta.client) nextTick(() => lastTrigger.value?.focus())
}

function moveImage(delta: number) {
  if (selected.value?.type !== 'image' || imageItems.value.length < 2) return
  const current = imageItems.value.indexOf(selected.value)
  selected.value = imageItems.value[(current + delta + imageItems.value.length) % imageItems.value.length] ?? null
}

function onKeydown(event: KeyboardEvent) {
  if (!selected.value) return
  if (event.key === 'Escape') close()
  else if (selected.value.type === 'image' && event.key === 'ArrowLeft') moveImage(-1)
  else if (selected.value.type === 'image' && event.key === 'ArrowRight') moveImage(1)
  else if (event.key === 'Tab') trapFocus(event, lightboxPanel.value)
}

watch(selected, (value, oldValue) => {
  if (import.meta.client) document.body.style.overflow = value ? 'hidden' : ''
  if (value && oldValue === null) nextTick(() => focusFirst(lightboxPanel.value))
})
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <section aria-labelledby="project-gallery-title">
    <h2 id="project-gallery-title" class="mb-5 text-[clamp(24px,3vw,36px)] font-black tracking-tight text-navy-950">Hình ảnh và media</h2>

    <div v-if="hasVideo" class="mb-6 flex gap-6 border-b border-gray-200" role="tablist" aria-label="Loại media dự án">
      <button
        ref="imageTabButton"
        id="project-gallery-images-tab"
        class="relative min-h-11 border-0 bg-transparent px-1 py-3 font-bold"
        :class="activeTab === 'image' ? 'text-navy-800' : 'text-slate-500'"
        type="button"
        role="tab"
        :tabindex="activeTab === 'image' ? 0 : -1"
        :aria-selected="activeTab === 'image'"
        aria-controls="project-gallery-panel"
        @click="activeTab = 'image'"
        @keydown="onTabKeydown($event, 'image')"
      >
        Hình ảnh
        <span v-if="activeTab === 'image'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" aria-hidden="true" />
      </button>
      <button
        ref="videoTabButton"
        id="project-gallery-videos-tab"
        class="relative min-h-11 border-0 bg-transparent px-1 py-3 font-bold"
        :class="activeTab === 'video' ? 'text-navy-800' : 'text-slate-500'"
        type="button"
        role="tab"
        :tabindex="activeTab === 'video' ? 0 : -1"
        :aria-selected="activeTab === 'video'"
        aria-controls="project-gallery-panel"
        @click="activeTab = 'video'"
        @keydown="onTabKeydown($event, 'video')"
      >
        Video
        <span v-if="activeTab === 'video'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" aria-hidden="true" />
      </button>
    </div>

    <div id="project-gallery-panel" role="tabpanel" :aria-labelledby="activeTab === 'image' ? 'project-gallery-images-tab' : 'project-gallery-videos-tab'">
      <div v-if="visibleItems.length" class="grid grid-cols-2 gap-5 tb:grid-cols-3">
        <figure v-for="(item, index) in visibleItems" :key="`${item.type}-${item.src}-${index}`" class="m-0">
          <button
            type="button"
            class="group block w-full border-0 bg-transparent p-0 text-left"
            :aria-label="item.type === 'video' ? 'Phát video ' + (item.caption || 'dự án') : 'Mở ảnh ' + (index + 1) + ' trong thư viện dự án'"
            @click="open(item, $event.currentTarget as HTMLElement)"
          >
            <span class="relative block overflow-hidden">
              <img v-if="item.src" :src="item.src" :alt="item.caption || (item.type === 'video' ? 'Video dự án' : 'Hình ảnh dự án')" class="block aspect-[4/3] w-full rounded-chip bg-slate-50 object-cover" loading="lazy" decoding="async">
              <span v-if="item.type === 'video'" class="absolute left-1/2 top-1/2 grid h-12 w-12 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-navy-950/85 text-xl text-white" aria-hidden="true">▶</span>
            </span>
            <span v-if="item.caption" class="mt-2 block text-sm leading-relaxed text-slate-500">{{ item.caption }}</span>
          </button>
        </figure>
      </div>
      <p v-else class="leading-relaxed text-slate-500">Chưa có media được công bố cho dự án này.</p>
    </div>
  </section>

  <Teleport to="body">
    <div
      v-if="selected"
      ref="lightboxPanel"
      class="fixed inset-0 z-[100] grid place-items-center bg-black/90 p-4"
      role="dialog"
      aria-modal="true"
      :aria-label="selected.type === 'video' ? 'Phát video dự án' : 'Xem ảnh dự án'"
      @click.self="close"
    >
      <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Đóng media" @click="close">×</button>
      <button v-if="selected.type === 'image' && imageItems.length > 1" type="button" class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh trước" @click="moveImage(-1)">‹</button>
      <figure v-if="selected.type === 'image'" class="m-0 max-h-[90vh] max-w-[min(1100px,90vw)] text-center">
        <img :src="selected.src" :alt="selected.caption || 'Hình ảnh dự án'" class="max-h-[82vh] max-w-full object-contain">
        <figcaption v-if="selected.caption" class="mt-3 text-sm text-white/80">{{ selected.caption }}</figcaption>
      </figure>
      <div v-else class="w-[min(1100px,94vw)] overflow-hidden rounded-chip bg-black">
        <div v-if="selectedEmbedUrl" class="aspect-video">
          <iframe
            class="h-full w-full border-0"
            :src="selectedEmbedUrl"
            :title="selected.caption || 'Video dự án Phúc Thịnh Solar'"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
          />
        </div>
        <p v-else class="m-0 p-6 text-center text-white">
          Không thể nhúng video này.
          <a class="ml-1 font-bold text-gold-400 underline" :href="selected.href" target="_blank" rel="noopener noreferrer">Mở video ở tab mới</a>
        </p>
      </div>
      <button v-if="selected.type === 'image' && imageItems.length > 1" type="button" class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh sau" @click="moveImage(1)">›</button>
    </div>
  </Teleport>
</template>

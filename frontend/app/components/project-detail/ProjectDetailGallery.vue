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
const setDocumentScrollLock = useDocumentScrollLock()
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

// Match by source, not identity: props are reactive proxies, so indexOf() misses.
const selectedImageIndex = computed(() => selected.value?.type === 'image' ? imageItems.value.findIndex((item) => item.src === selected.value?.src) : -1)
function moveImage(delta: number) {
  if (selected.value?.type !== 'image' || imageItems.value.length < 2) return
  const current = Math.max(0, selectedImageIndex.value)
  selected.value = imageItems.value[(current + delta + imageItems.value.length) % imageItems.value.length] ?? null
}
const thumbStrip = ref<HTMLElement | null>(null)
watch(selectedImageIndex, (index) => {
  if (index < 0) return
  nextTick(() => thumbStrip.value?.children[index]?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }))
})

function onKeydown(event: KeyboardEvent) {
  if (!selected.value) return
  if (event.key === 'Escape') close()
  else if (selected.value.type === 'image' && event.key === 'ArrowLeft') moveImage(-1)
  else if (selected.value.type === 'image' && event.key === 'ArrowRight') moveImage(1)
  else if (event.key === 'Tab') trapFocus(event, lightboxPanel.value)
}

watch(selected, (value, oldValue) => {
  setDocumentScrollLock(Boolean(value))
  if (value && oldValue === null) nextTick(() => focusFirst(lightboxPanel.value))
})
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  setDocumentScrollLock(false)
})
</script>

<template>
  <section aria-label="Thư viện tư liệu dự án">
    <div v-if="hasVideo" class="mb-6 flex gap-6 border-b border-gray-200" role="tablist" aria-label="Loại media dự án">
      <button
        ref="imageTabButton"
        id="project-gallery-images-tab"
        class="relative flex min-h-11 items-center gap-2 border-0 bg-transparent px-1 py-3 font-bold cursor-pointer"
        :class="activeTab === 'image' ? 'text-navy-800' : 'text-slate-500 hover:text-navy-800'"
        type="button"
        role="tab"
        :tabindex="activeTab === 'image' ? 0 : -1"
        :aria-selected="activeTab === 'image'"
        aria-controls="project-gallery-panel"
        @click="activeTab = 'image'"
        @keydown="onTabKeydown($event, 'image')"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
        <span>Hình ảnh</span>
        <span v-if="activeTab === 'image'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" aria-hidden="true" />
      </button>
      <button
        ref="videoTabButton"
        id="project-gallery-videos-tab"
        class="relative flex min-h-11 items-center gap-2 border-0 bg-transparent px-1 py-3 font-bold cursor-pointer"
        :class="activeTab === 'video' ? 'text-navy-800' : 'text-slate-500 hover:text-navy-800'"
        type="button"
        role="tab"
        :tabindex="activeTab === 'video' ? 0 : -1"
        :aria-selected="activeTab === 'video'"
        aria-controls="project-gallery-panel"
        @click="activeTab = 'video'"
        @keydown="onTabKeydown($event, 'video')"
      >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
        <span>Video</span>
        <span v-if="activeTab === 'video'" class="absolute inset-x-0 -bottom-px h-[3px] bg-gold-500" aria-hidden="true" />
      </button>
    </div>

    <div id="project-gallery-panel" role="tabpanel" :aria-labelledby="activeTab === 'image' ? 'project-gallery-images-tab' : 'project-gallery-videos-tab'">
      <div
        v-if="visibleItems.length"
        class="project-gallery__grid grid grid-cols-1 gap-4 tb:gap-5"
        :class="(visibleItems.length === 3 || visibleItems.length >= 5) ? 'tb:grid-cols-3' : 'tb:grid-cols-2'"
      >
        <figure
          v-for="(item, index) in visibleItems"
          :key="`${item.type}-${item.src}-${index}`"
          class="project-gallery__item m-0"
          :class="{
            'project-gallery__lead': (visibleItems.length === 3 || visibleItems.length >= 6) && index === 0,
            'project-gallery__single': visibleItems.length === 1,
          }"
        >
          <button
            type="button"
            class="group block w-full border-0 bg-transparent p-0 text-left cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-600"
            :aria-label="item.type === 'video' ? 'Phát video ' + (item.caption || 'dự án') : 'Mở ảnh ' + (index + 1) + ' trong thư viện dự án'"
            @click="open(item, $event.currentTarget as HTMLElement)"
          >
            <span class="relative block overflow-hidden rounded-btn bg-slate-100">
              <img v-if="item.src" :src="optimizedImage(item.src, 900, 675)" :alt="item.caption || (item.type === 'video' ? 'Video dự án' : 'Hình ảnh dự án')" class="block aspect-[4/3] w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none" loading="lazy" decoding="async">
              <span v-if="item.type === 'video'" class="absolute left-1/2 top-1/2 grid h-12 w-12 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-navy-950/85 text-xl text-white shadow-lg transition-transform group-hover:scale-110 motion-reduce:group-hover:scale-100 motion-reduce:transition-none" aria-hidden="true">▶</span>
              <span
                v-else
                class="absolute bottom-2.5 right-2.5 grid size-9 place-items-center rounded-full bg-black/45 text-white opacity-0 backdrop-blur-xs transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
                aria-hidden="true"
              >
                <UIcon name="i-lucide-maximize-2" class="size-4" />
              </span>
            </span>
            <span v-if="item.caption" class="mt-2 block text-sm leading-relaxed text-slate-600 transition-colors group-hover:text-gold-700">{{ item.caption }}</span>
          </button>
        </figure>
      </div>
      <div v-else class="grid min-h-40 place-items-center bg-slate-50 px-6 py-10 text-center">
        <p class="m-0 max-w-[42ch] leading-relaxed text-slate-500">Hình ảnh và video của công trình đang được cập nhật.</p>
        <span class="sr-only">Không tìm thấy kết quả</span>
      </div>
    </div>
  </section>

  <Teleport to="body">
    <div
      v-if="selected"
      ref="lightboxPanel"
      class="fixed inset-0 z-[100] grid place-items-center bg-[rgb(10_11_14/0.96)] p-4 pb-28 transition-opacity duration-200"
      role="dialog"
      aria-modal="true"
      :aria-label="selected.type === 'video' ? 'Phát video dự án' : 'Xem ảnh dự án'"
      @click.self="close"
    >
      <button
        type="button"
        class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/20 active:scale-95 cursor-pointer"
        aria-label="Đóng media"
        @click="close"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>

      <button
        v-if="selected.type === 'image' && imageItems.length > 1"
        type="button"
        class="absolute left-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/25 active:scale-95 cursor-pointer backdrop-blur-xs"
        aria-label="Ảnh trước"
        @click="moveImage(-1)"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>

      <figure v-if="selected.type === 'image'" class="m-0 max-h-[90vh] max-w-[min(1100px,90vw)] text-center">
        <img :src="selected.src" :alt="selected.caption || 'Hình ảnh dự án'" class="max-h-[74vh] max-w-full object-contain rounded-btn">
        <figcaption v-if="selected.caption" class="mt-3 text-sm text-white/90">{{ selected.caption }}</figcaption>
      </figure>
      <div v-else class="w-[min(1100px,94vw)] overflow-hidden rounded-chip bg-black shadow-2xl">
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

      <button
        v-if="selected.type === 'image' && imageItems.length > 1"
        type="button"
        class="absolute right-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition-all hover:bg-white/25 active:scale-95 cursor-pointer backdrop-blur-xs"
        aria-label="Ảnh sau"
        @click="moveImage(1)"
      >
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>

      <div v-if="selected.type === 'image' && imageItems.length > 1" ref="thumbStrip" class="lightbox-strip" aria-label="Chọn ảnh">
        <button
          v-for="(item, index) in imageItems"
          :key="`thumb-${item.src}-${index}`"
          type="button"
          class="lightbox-strip__thumb"
          :aria-current="index === selectedImageIndex ? 'true' : undefined"
          :aria-label="'Xem ảnh ' + (index + 1)"
          @click="selected = item"
        >
          <img :src="optimizedImage(item.src, 240, 180)" alt="" loading="lazy" decoding="async">
        </button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.lightbox-strip { position: absolute; right: 0; bottom: 16px; left: 0; display: flex; justify-content: safe center; gap: 8px; overflow-x: auto; padding: 4px 16px; scrollbar-width: none; }
.lightbox-strip::-webkit-scrollbar { display: none; }
.lightbox-strip__thumb { width: 72px; height: 54px; flex: none; overflow: hidden; padding: 0; border: 2px solid transparent; border-radius: var(--radius-btn); background: #1c1f26; opacity: 0.5; cursor: pointer; transition: opacity 0.2s ease, border-color 0.2s ease; }
.lightbox-strip__thumb img { width: 100%; height: 100%; object-fit: cover; }
.lightbox-strip__thumb:hover { opacity: 0.85; }
.lightbox-strip__thumb[aria-current] { border-color: var(--color-gold-400); opacity: 1; }
.lightbox-strip__thumb:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
.project-gallery__grid {
  grid-auto-flow: dense;
}

.project-gallery__single {
  grid-column: 1 / -1;
  max-width: 860px;
}

@media (min-width: 761px) {
  .project-gallery__lead {
    grid-column: span 2;
    grid-row: span 2;
  }
}
</style>

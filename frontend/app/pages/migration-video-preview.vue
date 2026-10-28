<script setup lang="ts">
import { isPublicVideoPayload, type PublicVideoItem, type PublicVideoPayload } from '#shared/contracts/public-video'

const { site } = await usePublicSite()
const route = useRoute()
const page = computed(() => typeof route.query.p === 'string' && /^[1-9][0-9]*$/.test(route.query.p) ? Number(route.query.p) : 1)
const query = computed(() => page.value > 1 ? { p: String(page.value) } : {})
const { data, error, pending } = await useFetch<unknown>('/_migration/video', { query })
const payload = computed<PublicVideoPayload | null>(() => isPublicVideoPayload(data.value) ? data.value : null)
const selected = ref<PublicVideoItem | null>(null)
const totalPages = computed(() => payload.value ? Math.max(1, Math.ceil(payload.value.pagination.total / payload.value.pagination.perPage)) : 1)

function pageTo(value: number) {
  return { path: '/migration-video-preview', query: value > 1 ? { p: String(value) } : {} }
}
const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])')
  first?.focus()
}
function trapFocus(event: KeyboardEvent, container: HTMLElement | null) {
  if (!container) return
  const focusable = [...container.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])')]
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

function open(item: PublicVideoItem, trigger?: HTMLElement) {
  lastTrigger.value = trigger ?? (document.activeElement as HTMLElement | null)
  selected.value = item
}
function close() {
  selected.value = null
  if (import.meta.client) nextTick(() => lastTrigger.value?.focus())
}
function onKeydown(event: KeyboardEvent) {
  if (!selected.value) return
  if (event.key === 'Escape') close()
  else if (event.key === 'Tab') trapFocus(event, lightboxPanel.value)
}
watch(selected, (value) => {
  if (import.meta.client) document.body.style.overflow = value ? 'hidden' : ''
  if (value) nextTick(() => focusFirst(lightboxPanel.value))
})
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})

useSeoMeta({
  title: computed(() => payload.value?.seo.title || 'Video — Nuxt preview'),
  description: computed(() => payload.value?.seo.description || ''),
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1320px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Video' }]" />
      <header class="mb-8 max-w-[820px]">
        <p class="mb-2 text-xs font-extrabold uppercase tracking-[.14em] text-gold-700">Video thực tế</p>
        <h1 class="mb-3 text-[clamp(32px,5vw,52px)] font-black tracking-tight">{{ payload?.title || 'Video' }}</h1>
        <p class="mb-0 text-lg leading-relaxed text-slate-500">Video sản phẩm, công trình và hoạt động được xuất từ nguồn CMS đã xác thực YouTube.</p>
      </header>

      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải video…</div>
      <div v-else-if="error || !payload" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        Không thể tải dữ liệu video từ CMS local.
      </div>
      <template v-else>
        <div v-if="payload.videos.length" class="grid grid-cols-1 gap-5 ph:grid-cols-2 tb:grid-cols-3 wd:grid-cols-4">
          <button
            v-for="item in payload.videos"
            :key="item.id"
            type="button"
            class="group overflow-hidden rounded-chip border border-gray-200 bg-white text-left"
            :aria-label="'Phát video ' + item.title"
            @click="open(item, $event.currentTarget as HTMLElement)"
          >
            <div class="relative aspect-video overflow-hidden bg-slate-100">
              <img :src="item.thumbnail" :alt="item.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
              <span class="absolute left-1/2 top-1/2 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-navy-950/85 text-2xl text-white shadow-lg" aria-hidden="true">▶</span>
            </div>
            <h2 class="m-0 p-4 text-lg font-black leading-snug text-navy-800">{{ item.title }}</h2>
          </button>
        </div>
        <div v-else class="rounded-btn border border-dashed border-gray-300 bg-slate-50 px-6 py-14 text-center">
          <h2 class="mb-2 text-xl font-black">Video đang được cập nhật</h2>
          <p class="mb-0 text-slate-500">Khi CMS có video YouTube được bật hiển thị, nội dung sẽ xuất hiện tự động tại đây.</p>
        </div>

        <nav v-if="totalPages > 1" class="mt-10 flex items-center justify-center gap-3" aria-label="Phân trang video">
          <NuxtLink v-if="page > 1" :to="pageTo(page - 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Trước</NuxtLink>
          <span class="text-sm text-slate-500">Trang {{ page }} / {{ totalPages }}</span>
          <NuxtLink v-if="page < totalPages" :to="pageTo(page + 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Sau</NuxtLink>
        </nav>
      </template>
    </main>
    <SiteFooter :site="site" />

    <Teleport to="body">
      <div
        v-if="selected"
        ref="lightboxPanel"
        class="fixed inset-0 z-[100] grid place-items-center bg-black/90 p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="'Video ' + selected.title"
        @click.self="close"
      >
        <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Đóng video" @click="close">×</button>
        <div class="w-[min(1100px,94vw)] overflow-hidden rounded-chip bg-black shadow-2xl">
          <div class="aspect-video">
            <iframe
              :src="selected.embedUrl"
              :title="selected.title"
              class="h-full w-full border-0"
              loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            />
          </div>
          <p class="m-0 bg-navy-950 px-4 py-3 font-semibold text-white">{{ selected.title }}</p>
        </div>
      </div>
    </Teleport>
  </div>
</template>

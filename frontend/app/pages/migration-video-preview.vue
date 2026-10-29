<script setup lang="ts">
import { isPublicVideoPayload, type PublicVideoItem, type PublicVideoPayload } from '#shared/contracts/public-video'
import { VIDEO_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicHubResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'

const { site } = await usePublicSite()
const route = useRoute()
const config = useRuntimeConfig()
const isPublicRoute = computed(() => route.path === VIDEO_PUBLIC_PATH)
const page = computed(() => typeof route.query.p === 'string' && /^[1-9][0-9]*$/.test(route.query.p) ? Number(route.query.p) : 1)
const query = computed(() => page.value > 1 ? { p: String(page.value) } : {})
const { data, error, pending } = await useFetch<unknown>('/_migration/video', { query })
const payload = computed<PublicVideoPayload | null>(() => isPublicVideoPayload(data.value) ? data.value : null)
const selected = ref<PublicVideoItem | null>(null)
const totalPages = computed(() => payload.value ? Math.max(1, Math.ceil(payload.value.pagination.total / payload.value.pagination.perPage)) : 1)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicHubResponseStatus(
  isPublicRoute.value && config.public.videoPublicRouteEnabled,
  errorStatus,
  payload.value === null,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

function pageTo(value: number) {
  return { path: isPublicRoute.value ? VIDEO_PUBLIC_PATH : '/migration-video-preview', query: value > 1 ? { p: String(value) } : {} }
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
  title: computed(() => payload.value?.seo.title || (isPublicRoute.value ? 'Video' : 'Video — Nuxt preview')),
  description: computed(() => payload.value?.seo.description || ''),
  robots: computed(() => editorialPublicRouteRobots(isPublicRoute.value && config.public.videoPublicRouteEnabled, config.public.videoPublicRouteIndexable, !!payload.value && !error.value, 'p' in route.query)),
})
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && config.public.videoPublicRouteEnabled && !!payload.value && !error.value),
  title: computed(() => payload.value?.seo.title || 'Video'),
  description: computed(() => payload.value?.seo.description || ''),
  canonicalPath: VIDEO_PUBLIC_PATH,
  image: computed(() => payload.value?.videos[0]?.thumbnail),
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1320px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Video' }]" />
      <header class="mb-8">
        <h1 class="text-[clamp(32px,5vw,52px)] font-black tracking-tight">{{ payload?.title || 'Video' }}</h1>
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
            class="group block min-w-0 border-0 bg-transparent p-0 text-left"
            :aria-label="'Phát video ' + item.title"
            @click="open(item, $event.currentTarget as HTMLElement)"
          >
            <div class="relative aspect-video overflow-hidden bg-slate-100">
              <img :src="item.thumbnail" :alt="item.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
              <span class="absolute left-1/2 top-1/2 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-navy-950/85 text-2xl text-white shadow-lg" aria-hidden="true">▶</span>
            </div>
            <h2 class="m-0 pt-3 text-lg font-black leading-snug text-navy-800 group-hover:text-gold-700">{{ item.title }}</h2>
          </button>
        </div>
        <div v-else class="rounded-btn border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900" role="status">
          <strong>Không tìm thấy kết quả</strong>
        </div>

        <nav v-if="totalPages > 1" class="mt-10 flex items-center justify-center gap-3" aria-label="Phân trang video">
          <NuxtLink v-if="page > 1" :to="pageTo(page - 1)" class="inline-flex min-h-11 items-center rounded-chip border border-gray-200 px-4 font-bold no-underline">Trước</NuxtLink>
          <span class="text-sm text-slate-500">Trang {{ page }} / {{ totalPages }}</span>
          <NuxtLink v-if="page < totalPages" :to="pageTo(page + 1)" class="inline-flex min-h-11 items-center rounded-chip border border-gray-200 px-4 font-bold no-underline">Sau</NuxtLink>
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

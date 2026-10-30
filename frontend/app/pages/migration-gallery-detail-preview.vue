<script setup lang="ts">
import { isPublicGalleryDetailPayload, type PublicGalleryDetailPayload } from '#shared/contracts/public-gallery'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'

const { site } = await usePublicSite()
const route = useRoute()
const config = useRuntimeConfig()
const links = useMigrationLinks()
const isBarePublicRoute = computed(() => typeof route.params.slug === 'string' && route.path === `/${route.params.slug}/`)
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : (typeof route.query.slug === 'string' ? route.query.slug : ''))
const { data, error, pending } = await useFetch<unknown>('/_migration/gallery-detail', { query: computed(() => ({ slug: slug.value })) })
const gallery = computed<PublicGalleryDetailPayload | null>(() => isPublicGalleryDetailPayload(data.value) ? data.value : null)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(isBarePublicRoute.value, errorStatus, !gallery.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const selectedIndex = ref<number | null>(null)
const lightbox = computed(() => {
  const detail = gallery.value
  const index = selectedIndex.value
  if (!detail || index === null) return null
  const image = detail.images[index]
  return image ? { detail, image } : null
})

const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)

function focusFirst(container: HTMLElement | null) {
  if (!container) return
  const first = container.querySelector<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')
  first?.focus()
}
function trapFocus(event: KeyboardEvent, container: HTMLElement | null) {
  if (!container) return
  const focusable = [...container.querySelectorAll<HTMLElement>('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')]
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

function open(index: number, trigger?: HTMLElement) {
  if (!gallery.value?.images[index]) return
  lastTrigger.value = trigger ?? (document.activeElement as HTMLElement | null)
  selectedIndex.value = index
}
function close() {
  selectedIndex.value = null
  if (import.meta.client) nextTick(() => lastTrigger.value?.focus())
}
function move(delta: number) {
  const count = gallery.value?.images.length ?? 0
  if (!count || selectedIndex.value === null) return
  selectedIndex.value = (selectedIndex.value + delta + count) % count
}
function onKeydown(event: KeyboardEvent) {
  if (selectedIndex.value === null) return
  if (event.key === 'Escape') close()
  else if (event.key === 'ArrowLeft') move(-1)
  else if (event.key === 'ArrowRight') move(1)
  else if (event.key === 'Tab') trapFocus(event, lightboxPanel.value)
}
watch(selectedIndex, (value, oldValue) => {
  if (import.meta.client) document.body.style.overflow = value === null ? '' : 'hidden'
  // Only steal focus on the closed -> open transition; ArrowLeft/ArrowRight
  // also change selectedIndex to move between images and must not re-trap
  // focus on every navigation, or keyboard users get yanked back each time.
  if (value !== null && oldValue === null) nextTick(() => focusFirst(lightboxPanel.value))
})
onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})

useSeoMeta({
  title: computed(() => gallery.value?.seo.title || (isBarePublicRoute.value ? 'Thư viện ảnh' : 'Album ảnh — Nuxt preview')),
  description: computed(() => gallery.value?.seo.description || ''),
  robots: computed(() => editorialPublicRouteRobots(isBarePublicRoute.value, config.public.galleryDetailPublicRouteIndexable, !!gallery.value && !error.value)),
})
usePublicSocialMeta({
  enabled: computed(() => isBarePublicRoute.value && !!gallery.value && !error.value),
  title: computed(() => gallery.value?.seo.title || 'Thư viện ảnh'),
  description: computed(() => gallery.value?.seo.description || ''),
  canonicalPath: computed(() => route.path),
  image: computed(() => gallery.value?.images[0]?.image),
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="pts-container py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Thư viện ảnh', to: links.generic('/thu-vien-anh/') }, ...(gallery ? [{ label: gallery.album.title }] : [])]" />
      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải album…</div>
      <div v-else-if="error || !gallery" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-amber-900">
        <h1 class="mb-2 text-2xl font-black">{{ isBarePublicRoute && responseStatus === 404 ? 'Không tìm thấy album' : 'Không thể tải album' }}</h1>
        <p class="mb-4">{{ isBarePublicRoute ? 'Nội dung album hiện không khả dụng.' : 'Album có thể chưa được bật hiển thị hoặc slug không còn tồn tại.' }}</p>
        <NuxtLink :to="links.generic('/thu-vien-anh/')" class="font-bold underline">Quay lại thư viện ảnh</NuxtLink>
      </div>
      <article v-else>
        <header class="mb-8 max-w-[820px]">
          <h1 class="mb-3 text-[clamp(32px,5vw,52px)] font-black leading-tight tracking-tight">{{ gallery.album.title }}</h1>
          <p v-if="gallery.album.description" class="mb-0 text-lg leading-relaxed text-slate-500">{{ gallery.album.description }}</p>
        </header>

        <div v-if="gallery.images.length" class="grid grid-cols-2 gap-3 tb:grid-cols-3 wd:grid-cols-4">
          <button
            v-for="(image, index) in gallery.images"
            :key="image.id + '-' + image.image"
            type="button"
            class="group relative overflow-hidden rounded-chip border border-gray-200 bg-slate-50 text-left"
            :aria-label="'Mở ảnh ' + (index + 1) + ' trong album ' + gallery.album.title"
            @click="open(index, $event.currentTarget as HTMLElement)"
          >
            <img :src="image.image" :alt="image.caption || gallery.album.title" class="aspect-[4/3] h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
            <span v-if="image.caption" class="absolute inset-x-0 bottom-0 bg-navy-950/75 px-3 py-2 text-xs font-semibold text-white">{{ image.caption }}</span>
          </button>
        </div>
        <div v-else class="rounded-btn border border-dashed border-gray-300 bg-slate-50 px-6 py-14 text-center text-slate-500">
          Không tìm thấy kết quả
        </div>
      </article>
    </main>
    <SiteFooter :site="site" />

    <Teleport to="body">
      <div
        v-if="lightbox"
        ref="lightboxPanel"
        class="fixed inset-0 z-[100] grid place-items-center bg-black/90 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Xem ảnh album"
        @click.self="close"
      >
        <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Đóng" @click="close">×</button>
        <button v-if="lightbox.detail.images.length > 1" type="button" class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh trước" @click="move(-1)">‹</button>
        <figure class="m-0 max-h-[90vh] max-w-[min(1100px,90vw)] text-center">
          <img :src="lightbox.image.image" :alt="lightbox.image.caption || lightbox.detail.album.title" class="max-h-[82vh] max-w-full object-contain">
          <figcaption v-if="lightbox.image.caption" class="mt-3 text-sm text-white/80">{{ lightbox.image.caption }}</figcaption>
        </figure>
        <button v-if="lightbox.detail.images.length > 1" type="button" class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh sau" @click="move(1)">›</button>
      </div>
    </Teleport>
  </div>
</template>

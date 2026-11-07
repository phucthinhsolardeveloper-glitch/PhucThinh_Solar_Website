<script setup lang="ts">
import MediaEmptyState from '~/components/editorial/MediaEmptyState.vue'
import { isPublicGalleryPayload, type PublicGalleryPayload } from '#shared/contracts/public-gallery'
import { GALLERY_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicHubResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { publicSeoTitle } from '#shared/public-seo'

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const route = useRoute()
const config = useRuntimeConfig()
const links = useMigrationLinks()
const isPublicRoute = computed(() => route.path === GALLERY_PUBLIC_PATH)
const page = computed(() => typeof route.query.p === 'string' && /^[1-9][0-9]*$/.test(route.query.p) ? Number(route.query.p) : 1)
const query = computed(() => page.value > 1 ? { p: String(page.value) } : {})
const { data, error, pending } = await useFetch<unknown>('/_migration/gallery', { query })
const gallery = computed<PublicGalleryPayload | null>(() => isPublicGalleryPayload(data.value) ? data.value : null)
const totalPages = computed(() => gallery.value ? Math.max(1, Math.ceil(gallery.value.pagination.total / gallery.value.pagination.perPage)) : 1)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicHubResponseStatus(
  isPublicRoute.value,
  errorStatus,
  gallery.value === null,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

function pageTo(value: number) {
  return { path: isPublicRoute.value ? GALLERY_PUBLIC_PATH : '/migration-gallery-preview', query: value > 1 ? { p: String(value) } : {} }
}

const pageTitle = computed(() => isPublicRoute.value
  ? publicSeoTitle(gallery.value?.seo.title, 'Thư viện ảnh')
  : gallery.value?.seo.title || 'Thư viện ảnh — Nuxt preview')

useSeoMeta({
  title: pageTitle,
  description: computed(() => gallery.value?.seo.description || ''),
  robots: computed(() => editorialPublicRouteRobots(isPublicRoute.value, config.public.galleryPublicRouteIndexable, !!gallery.value && !error.value, 'p' in route.query)),
})
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && !!gallery.value && !error.value),
  title: pageTitle,
  description: computed(() => gallery.value?.seo.description || ''),
  canonicalPath: GALLERY_PUBLIC_PATH,
  image: computed(() => gallery.value?.seo.image || gallery.value?.albums.find((album) => album.image)?.image),
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Thư viện ảnh' }]" />
      </div>
    </div>
    <main class="pts-container py-10 nav:py-14" :aria-busy="pending">
      <header class="mb-8">
        <h1 class="text-[clamp(32px,5vw,52px)] font-black tracking-tight">{{ gallery?.title || 'Thư viện ảnh' }}</h1>
      </header>

      <div v-if="pending && !gallery" class="py-16 text-slate-500" role="status">Đang tải thư viện ảnh…</div>
      <div v-else-if="!gallery" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" :role="error ? 'alert' : 'status'">
        Không thể tải dữ liệu thư viện ảnh từ CMS local.
      </div>
      <template v-else>
        <p v-if="error" class="mb-5 rounded-btn bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">Không thể cập nhật trang thư viện. Đang giữ kết quả vừa tải trước đó.</p>
        <div v-if="gallery.albums.length" class="grid grid-cols-1 gap-5 ph:grid-cols-2 tb:grid-cols-3 wd:grid-cols-4">
          <article v-for="album in gallery.albums" :key="album.id" class="group min-w-0">
            <NuxtLink :to="links.gallery(album.slug)" class="block no-underline">
              <div class="grid aspect-[4/3] place-items-center overflow-hidden rounded-chip bg-slate-50">
                <img v-if="album.image" :src="album.image" :alt="album.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
                <span v-else class="px-4 text-center text-sm text-slate-400">Album chưa có ảnh đại diện</span>
              </div>
              <div class="pt-3">
                <h2 class="m-0 text-lg font-black leading-snug text-navy-800 group-hover:text-gold-700">{{ album.title }}</h2>
                <p v-if="album.description" class="mb-0 mt-2 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ album.description }}</p>
              </div>
            </NuxtLink>
          </article>
        </div>
        <MediaEmptyState
          v-else
          kind="gallery"
          title="Chưa có album ảnh được công bố"
          description="Các bộ sưu tập chuyên đề đang được cập nhật. Bạn có thể xem ngay hồ sơ công trình đã bàn giao, giải pháp hệ thống hoặc danh mục thiết bị chính hãng."
          consult-subject="Thư viện: Yêu cầu tư vấn công trình"
          consult-label="Nhận tư vấn công trình"
        />

        <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="Phân trang thư viện ảnh">
          <SitePaginationButton v-if="page > 1" :to="pageTo(page - 1)">Trước</SitePaginationButton>
          <span class="text-sm text-slate-500">Trang {{ page }} / {{ totalPages }}</span>
          <SitePaginationButton v-if="page < totalPages" :to="pageTo(page + 1)">Sau</SitePaginationButton>
        </nav>
      </template>
    </main>
  </div>
</template>

<script setup lang="ts">
import { isPublicGalleryPayload, type PublicGalleryPayload } from '#shared/contracts/public-gallery'

const { site } = await usePublicSite()
const route = useRoute()
const page = computed(() => typeof route.query.p === 'string' && /^[1-9][0-9]*$/.test(route.query.p) ? Number(route.query.p) : 1)
const query = computed(() => page.value > 1 ? { p: String(page.value) } : {})
const { data, error, pending } = await useFetch<unknown>('/_migration/gallery', { query })
const gallery = computed<PublicGalleryPayload | null>(() => isPublicGalleryPayload(data.value) ? data.value : null)
const totalPages = computed(() => gallery.value ? Math.max(1, Math.ceil(gallery.value.pagination.total / gallery.value.pagination.perPage)) : 1)

function pageTo(value: number) {
  return { path: '/migration-gallery-preview', query: value > 1 ? { p: String(value) } : {} }
}

useSeoMeta({
  title: computed(() => gallery.value?.seo.title || 'Thư viện ảnh — Nuxt preview'),
  description: computed(() => gallery.value?.seo.description || ''),
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1320px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Thư viện ảnh' }]" />
      <header class="mb-8 max-w-[820px]">
        <p class="mb-2 text-xs font-extrabold uppercase tracking-[.14em] text-gold-700">Hình ảnh thực tế</p>
        <h1 class="mb-3 text-[clamp(32px,5vw,52px)] font-black tracking-tight">{{ gallery?.title || 'Thư viện ảnh' }}</h1>
        <p class="mb-0 text-lg leading-relaxed text-slate-500">Hình ảnh công trình, sản phẩm và hoạt động được quản lý trực tiếp từ CMS Phúc Thịnh Solar.</p>
      </header>

      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải thư viện ảnh…</div>
      <div v-else-if="error || !gallery" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        Không thể tải dữ liệu thư viện ảnh từ CMS local.
      </div>
      <template v-else>
        <div v-if="gallery.albums.length" class="grid grid-cols-1 gap-5 ph:grid-cols-2 tb:grid-cols-3 wd:grid-cols-4">
          <article v-for="album in gallery.albums" :key="album.id" class="group overflow-hidden rounded-chip border border-gray-200 bg-white">
            <NuxtLink :to="{ path: '/migration-gallery-detail-preview', query: { slug: album.slug } }" class="block no-underline">
              <div class="grid aspect-[4/3] place-items-center overflow-hidden bg-slate-50">
                <img v-if="album.image" :src="album.image" :alt="album.title" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
                <span v-else class="px-4 text-center text-sm text-slate-400">Album chưa có ảnh đại diện</span>
              </div>
              <div class="p-4">
                <h2 class="m-0 text-lg font-black leading-snug text-navy-800">{{ album.title }}</h2>
                <p v-if="album.description" class="mb-0 mt-2 line-clamp-2 text-sm leading-relaxed text-slate-500">{{ album.description }}</p>
              </div>
            </NuxtLink>
          </article>
        </div>
        <div v-else class="rounded-btn border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900" role="status">
          <strong>Không tìm thấy kết quả</strong>
        </div>

        <nav v-if="totalPages > 1" class="mt-10 flex items-center justify-center gap-3" aria-label="Phân trang thư viện ảnh">
          <NuxtLink v-if="page > 1" :to="pageTo(page - 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Trước</NuxtLink>
          <span class="text-sm text-slate-500">Trang {{ page }} / {{ totalPages }}</span>
          <NuxtLink v-if="page < totalPages" :to="pageTo(page + 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Sau</NuxtLink>
        </nav>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

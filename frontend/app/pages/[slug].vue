<script setup lang="ts">
import { isPublicNewsPayload, type PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsDetailPayload, type PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import { isPublicProjectDetailPayload, type PublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { isPublicProjectsPayload, type PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicSolutionCategoryPayload, type PublicSolutionCategoryPayload } from '#shared/contracts/public-solutions'
import { isPublicGalleryDetailPayload, type PublicGalleryDetailPayload } from '#shared/contracts/public-gallery'
import { isPublicRouteResolution, type PublicRouteResolution } from '#shared/contracts/public-route-resolution'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import NewsDetailArticle from '~/components/news-detail/NewsDetailArticle.vue'
import NewsDetailRelated from '~/components/news-detail/NewsDetailRelated.vue'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsFeatured from '~/components/news/NewsFeatured.vue'
import ProjectDetailPage from '~/components/project-detail/ProjectDetailPage.vue'
import ProjectCard from '~/components/projects/ProjectCard.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { data: routeData, error: routeError } = await useFetch<unknown>('/_migration/resolve-public-slug', {
  key: () => 'public-route-resolution-' + slug.value,
  query: computed(() => ({ slug: slug.value })),
})
const resolution = computed<PublicRouteResolution | null>(() => isPublicRouteResolution(routeData.value) ? routeData.value : null)

if (resolution.value && resolution.value.entity !== 'not-found' && resolution.value.canonical !== route.path) {
  await navigateTo(resolution.value.canonical, { redirectCode: 301 })
}
if (!resolution.value || resolution.value.entity === 'not-found') setResponseStatus(404)
if (routeError.value) setResponseStatus(routeError.value.statusCode || 502)

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const detailKey = computed(() => (resolution.value?.entity || 'missing') + '-' + slug.value + '-page-' + (typeof route.query.p === 'string' ? route.query.p : '1'))
const { data: pageData, error: pageError } = await useAsyncData<unknown>(
  () => 'public-entity-' + detailKey.value,
  async () => {
    const entity = resolution.value?.entity
    if (entity === 'news') return $fetch('/_migration/news-detail', { query: { slug: slug.value } })
    if (entity === 'project') return $fetch('/_migration/project-detail', { query: { slug: slug.value } })
    if (entity === 'project-category' && resolution.value?.category) return $fetch('/_migration/projects', { query: { category_type: resolution.value.category.level, category_path: resolution.value.category.path, page: typeof route.query.p === 'string' ? route.query.p : '1' } })
    if (entity === 'solution-category' && resolution.value?.category) return $fetch('/_migration/solutions', { query: { category_type: resolution.value.category.level, category_path: resolution.value.category.path, page: typeof route.query.p === 'string' ? route.query.p : '1' } })
    if (entity === 'gallery') return $fetch('/_migration/gallery-detail', { query: { slug: slug.value } })
    if (entity === 'news-category' && resolution.value?.category) {
      const category = resolution.value.category
      return $fetch('/_migration/news', { query: { category_type: category.level, category_path: category.path } })
    }
    return null
  },
  { watch: [detailKey] },
)

const newsDetail = computed<PublicNewsDetailPayload | null>(() => resolution.value?.entity === 'news' && isPublicNewsDetailPayload(pageData.value) ? pageData.value : null)
const projectDetail = computed<PublicProjectDetailPayload | null>(() => resolution.value?.entity === 'project' && isPublicProjectDetailPayload(pageData.value) ? pageData.value : null)
const projectCategory = computed<PublicProjectsPayload | null>(() => resolution.value?.entity === 'project-category' && isPublicProjectsPayload(pageData.value) ? pageData.value : null)
const solutionCategory = computed<PublicSolutionCategoryPayload | null>(() => resolution.value?.entity === 'solution-category' && isPublicSolutionCategoryPayload(pageData.value) ? pageData.value : null)
const galleryDetail = computed<PublicGalleryDetailPayload | null>(() => resolution.value?.entity === 'gallery' && isPublicGalleryDetailPayload(pageData.value) ? pageData.value : null)
const newsCategory = computed<PublicNewsPayload | null>(() => resolution.value?.entity === 'news-category' && isPublicNewsPayload(pageData.value) ? pageData.value : null)
const projectCategoryPageUrl = (page: number) => page <= 1 ? `/${slug.value}/` : `/${slug.value}/?p=${page}`
const projectCategoryPageNumbers = computed(() => {
  if (!projectCategory.value) return []
  const total = Math.ceil(projectCategory.value.pagination.total / projectCategory.value.pagination.perPage)
  const current = projectCategory.value.pagination.page
  const pages: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (pages.length && typeof pages[pages.length - 1] === 'number' && page > (pages[pages.length - 1] as number) + 1) pages.push('…'); pages.push(page) }
  return pages
})
const solutionCategoryPageUrl = (page: number) => page <= 1 ? `/${slug.value}/` : `/${slug.value}/?p=${page}`
const solutionCategoryPageNumbers = computed(() => {
  if (!solutionCategory.value) return []
  const total = Math.ceil(solutionCategory.value.pagination.total / solutionCategory.value.pagination.perPage)
  const current = solutionCategory.value.pagination.page
  const pages: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (pages.length && typeof pages[pages.length - 1] === 'number' && page > (pages[pages.length - 1] as number) + 1) pages.push('…'); pages.push(page) }
  return pages
})

if (pageError.value && pageError.value.statusCode === 404) setResponseStatus(404)
else if (pageError.value) setResponseStatus(pageError.value.statusCode || 502)

const requestUrl = useRequestURL()
useSeoMeta({
  title: computed(() => newsDetail.value?.article.seo.title || projectDetail.value?.project.title || galleryDetail.value?.seo.title || newsCategory.value?.title || projectCategory.value?.title || solutionCategory.value?.title || 'Không tìm thấy nội dung — Phúc Thịnh Solar'),
  description: computed(() => newsDetail.value?.article.seo.description || projectDetail.value?.project.summary || galleryDetail.value?.seo.description || newsCategory.value?.description || projectCategory.value?.intro?.description || solutionCategory.value?.description || ''),
  robots: computed(() => resolution.value?.entity && resolution.value.entity !== 'not-found' && !routeError.value && !pageError.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({
  link: resolution.value && resolution.value.entity !== 'not-found'
    ? [{ rel: 'canonical', href: new URL(resolution.value.canonical, requestUrl.origin).toString() }]
    : [],
}))

const routeNewsQuery = computed(() => {
  const query: Record<string, string> = {}
  if (typeof route.query.q === 'string' && route.query.q) query.q = route.query.q
  if (typeof route.query.p === 'string' && route.query.p) query.p = route.query.p
  if (resolution.value?.category) {
    query.category_type = resolution.value.category.level
    query.category_path = resolution.value.category.path
  }
  return query
})
const categoryPageUrl = (page: number) => {
  const query = new URLSearchParams(routeNewsQuery.value)
  if (page <= 1) query.delete('p'); else query.set('p', String(page))
  return '/' + slug.value + '/' + (query.toString() ? '?' + query.toString() : '')
}
const categoryPageNumbers = computed(() => {
  if (!newsCategory.value) return []
  const total = Math.ceil(newsCategory.value.pagination.total / newsCategory.value.pagination.perPage)
  const current = newsCategory.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…'); result.push(page) }
  return result
})
const newsCategoryUrl = (categorySlug?: string) => {
  const query = new URLSearchParams()
  if (typeof route.query.q === 'string' && route.query.q) query.set('q', route.query.q)
  return categorySlug ? '/' + categorySlug + '/' + (query.toString() ? '?' + query.toString() : '') : '/tin-tuc/' + (query.toString() ? '?' + query.toString() : '')
}

const galleryIndex = ref<number | null>(null)
const lightbox = computed(() => {
  const image = galleryIndex.value === null ? null : galleryDetail.value?.images[galleryIndex.value]
  return image && galleryDetail.value ? { image, detail: galleryDetail.value } : null
})
const lightboxPanel = ref<HTMLElement | null>(null)
const lastTrigger = ref<HTMLElement | null>(null)
function focusFirst(container: HTMLElement | null) {
  container?.querySelector<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')?.focus()
}
function openGalleryImage(index: number, trigger?: HTMLElement) {
  if (!galleryDetail.value?.images[index]) return
  lastTrigger.value = trigger ?? (document.activeElement as HTMLElement | null)
  galleryIndex.value = index
}
function closeGalleryImage() {
  galleryIndex.value = null
  if (import.meta.client) nextTick(() => lastTrigger.value?.focus())
}
function moveGalleryImage(delta: number) {
  const count = galleryDetail.value?.images.length ?? 0
  if (!count || galleryIndex.value === null) return
  galleryIndex.value = (galleryIndex.value + delta + count) % count
}
function onGalleryKeydown(event: KeyboardEvent) {
  if (galleryIndex.value === null) return
  if (event.key === 'Escape') closeGalleryImage()
  else if (event.key === 'ArrowLeft') moveGalleryImage(-1)
  else if (event.key === 'ArrowRight') moveGalleryImage(1)
  else if (event.key === 'Tab') {
    const focusable = [...(lightboxPanel.value?.querySelectorAll<HTMLElement>('button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])') ?? [])]
    if (!focusable.length) return
    const first = focusable[0]!
    const last = focusable[focusable.length - 1]!
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
  }
}
watch(galleryIndex, (value, oldValue) => {
  if (import.meta.client) document.body.style.overflow = value === null ? '' : 'hidden'
  if (value !== null && oldValue === null) nextTick(() => focusFirst(lightboxPanel.value))
})
onMounted(() => document.addEventListener('keydown', onGalleryKeydown))
onBeforeUnmount(() => {
  document.removeEventListener('keydown', onGalleryKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <div v-if="!resolution || resolution.entity === 'not-found' || routeError || pageError" class="mx-auto min-h-[45vh] w-full max-w-[1180px] px-4 py-16" role="status">
      <h1 class="mb-3 text-3xl font-black tracking-tight">Không tìm thấy nội dung</h1>
      <p class="mb-5 leading-relaxed text-slate-600">Trang này không còn tồn tại hoặc chưa được công bố.</p>
      <a class="font-bold text-gold-700" href="/">Về trang chủ →</a>
    </div>

    <article v-else-if="newsDetail" class="mx-auto w-full max-w-[1766px] px-4 py-8 nav:py-12">
      <SiteBreadcrumb :items="[{ label: 'Kiến thức', to: '/tin-tuc/' }, ...(newsDetail.article.category && 'name' in newsDetail.article.category ? [{ label: newsDetail.article.category.name }] : [])]" />
      <div class="grid grid-cols-1 gap-8 tb:grid-cols-[minmax(0,2fr)_minmax(240px,1fr)] tb:gap-12">
        <NewsDetailArticle :article="newsDetail.article" />
        <NewsDetailRelated :items="newsDetail.related" />
      </div>
    </article>

    <div v-else-if="projectDetail">
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Dự án đã thực hiện', to: '/du-an-da-thuc-hien/' }, ...(projectDetail.project.category ? [{ label: projectDetail.project.category.name }] : []), { label: projectDetail.project.title }]" />
      </div>
      <ProjectDetailPage :project="projectDetail.project" :content="projectDetail.content" />
    </div>

    <article v-else-if="galleryDetail" class="mx-auto w-full max-w-[1320px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Thư viện ảnh', to: '/thu-vien-anh/' }, { label: galleryDetail.album.title }]" />
      <header class="mb-8 max-w-[820px]">
        <p class="mb-2 text-xs font-extrabold uppercase tracking-[.14em] text-gold-700">Album ảnh</p>
        <h1 class="mb-3 text-[clamp(32px,5vw,52px)] font-black leading-tight tracking-tight">{{ galleryDetail.album.title }}</h1>
        <p v-if="galleryDetail.album.description" class="mb-0 text-lg leading-relaxed text-slate-500">{{ galleryDetail.album.description }}</p>
      </header>
      <div v-if="galleryDetail.images.length" class="grid grid-cols-2 gap-3 tb:grid-cols-3 wd:grid-cols-4">
        <button v-for="(image, index) in galleryDetail.images" :key="image.id + '-' + image.image" type="button" class="group relative overflow-hidden rounded-chip border border-gray-200 bg-slate-50 text-left" :aria-label="'Mở ảnh ' + (index + 1) + ' trong album ' + galleryDetail.album.title" @click="openGalleryImage(index, $event.currentTarget as HTMLElement)">
          <img :src="image.image" :alt="image.caption || galleryDetail.album.title" class="aspect-[4/3] h-full w-full object-cover transition duration-300 group-hover:scale-[1.025]" loading="lazy">
          <span v-if="image.caption" class="absolute inset-x-0 bottom-0 bg-navy-950/75 px-3 py-2 text-xs font-semibold text-white">{{ image.caption }}</span>
        </button>
      </div>
      <p v-else class="py-12 text-center text-slate-500">Không tìm thấy kết quả</p>
    </article>

    <section v-else-if="newsCategory" class="mx-auto w-full max-w-[1766px] px-4 pb-16">
      <SiteBreadcrumb :items="[{ label: 'Kiến thức', to: '/tin-tuc/' }, { label: newsCategory.title }]" />
      <header class="pt-6 pb-7">
        <h1 class="mb-2.5 text-[clamp(30px,4vw,44px)] font-black leading-[1.15] tracking-tight text-navy-800">{{ newsCategory.title }}</h1>
        <p v-if="newsCategory.description" class="m-0 leading-relaxed text-slate-500">{{ newsCategory.description }}</p>
      </header>
      <div class="mb-6 flex flex-col items-start justify-between gap-3.5 nav:flex-row nav:items-center">
        <nav class="flex w-full max-w-full min-w-0 gap-[7px] overflow-x-auto px-0.5 pb-2 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Danh mục kiến thức">
          <a class="flex min-h-[42px] flex-none items-center whitespace-nowrap rounded-chip bg-slate-50 px-[15px] text-[13px] font-bold text-navy-800 no-underline hover:bg-gray-200" href="/tin-tuc/">Tất cả</a>
          <a v-for="category in newsCategory.categories" :key="category.id" class="flex min-h-[42px] flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[13px] font-bold no-underline" :class="category.slug === resolution?.category?.path.split('/').at(-1) ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'" :href="newsCategoryUrl(category.slug)">
            <img v-if="category.image" :src="category.image" alt="" class="h-[22px] w-[22px] object-contain" width="22" height="22">{{ category.name }}
          </a>
        </nav>
        <form class="flex h-11 w-full flex-none items-center rounded-chip border border-gray-200 bg-white nav:w-[250px]" method="get" :action="'/' + slug + '/'" role="search">
          <label class="sr-only" for="public-news-search">Tìm kiếm bài viết</label>
          <input id="public-news-search" name="q" type="search" class="w-full min-w-0 border-0 px-3 text-[13px] text-navy-800 outline-none focus-visible:ring-2 focus-visible:ring-gold-500" :value="route.query.q || ''" placeholder="Tìm kiếm bài viết" maxlength="120">
          <button type="submit" class="h-[42px] w-[42px] flex-none border-0 bg-transparent text-navy-800" aria-label="Tìm kiếm">⌕</button>
        </form>
      </div>
      <NewsFeatured v-if="newsCategory.featured.length" :articles="newsCategory.featured" :label="newsCategory.labels.featured" :read-label="newsCategory.labels.read" :read-more-label="newsCategory.labels.readMore" />
      <section v-if="newsCategory.articles.length" class="grid grid-cols-1 gap-x-5 gap-y-6 tb:grid-cols-2 nav:grid-cols-3" :aria-label="newsCategory.labels.results">
        <NewsCard v-for="article in newsCategory.articles" :key="article.id" :article="article" :read-label="newsCategory.labels.read" :read-more-label="newsCategory.labels.readMore" />
      </section>
      <div v-else class="min-h-[260px] py-[52px] text-center text-slate-500" role="status"><h2 class="mb-3 text-navy-800">{{ newsCategory.labels.empty }}</h2><a class="font-bold text-gold-700" href="/tin-tuc/">{{ newsCategory.labels.reset }} →</a></div>
      <nav v-if="newsCategory.pagination.total > newsCategory.pagination.perPage" class="mt-7 flex flex-wrap justify-center gap-2" aria-label="Trang bài viết">
        <a v-if="newsCategory.pagination.page > 1" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="prev" :href="categoryPageUrl(newsCategory.pagination.page - 1)">‹</a>
        <template v-for="(page, index) in categoryPageNumbers" :key="page + '-' + index">
          <span v-if="page === '…'" class="grid h-[38px] min-w-[38px] place-items-center">…</span>
          <a v-else class="grid h-[38px] min-w-[38px] place-items-center rounded-full border text-navy-800 no-underline" :class="page === newsCategory.pagination.page ? 'border-gold-500 bg-gold-400' : 'border-gray-200'" :aria-current="page === newsCategory.pagination.page ? 'page' : undefined" :href="categoryPageUrl(page)">{{ page }}</a>
        </template>
        <a v-if="newsCategory.pagination.page < Math.ceil(newsCategory.pagination.total / newsCategory.pagination.perPage)" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="next" :href="categoryPageUrl(newsCategory.pagination.page + 1)">›</a>
      </nav>
      <NewsCta v-if="newsCategory.cta" :cta="newsCategory.cta" :phone-label="newsCategory.labels.ctaPhone" />
    </section>

    <main v-else-if="projectCategory" class="mx-auto w-full max-w-[1766px] px-4 pb-16">
      <SiteBreadcrumb :items="[{ label: 'Dự án đã thực hiện', to: '/du-an-da-thuc-hien/' }, { label: projectCategory.title }]" />
      <header v-if="projectCategory.intro?.visible !== false" class="py-7">
        <p v-if="projectCategory.intro?.eyebrow" class="mb-2 text-sm font-semibold uppercase tracking-wide text-gold-700">{{ projectCategory.intro.eyebrow }}</p>
        <h1 class="text-[clamp(30px,4vw,44px)] font-black leading-tight tracking-tight">{{ projectCategory.title }}</h1>
        <p v-if="projectCategory.intro?.description" class="mt-3 max-w-3xl leading-relaxed text-slate-500">{{ projectCategory.intro.description }}</p>
      </header>
      <h1 v-else class="sr-only">{{ projectCategory.title }}</h1>
      <section v-if="projectCategory.projects.length" class="grid grid-cols-1 gap-x-6 gap-y-8 tb:grid-cols-2 nav:grid-cols-3" aria-label="Dự án">
        <ProjectCard v-for="project in projectCategory.projects" :key="project.id" :project="project" :view-label="projectCategory.labels.view" />
      </section>
      <p v-else class="min-h-[30vh] py-6 text-slate-500">{{ projectCategory.labels.empty }}</p>
      <nav v-if="projectCategory.pagination.total > projectCategory.pagination.perPage" class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Trang dự án">
        <a v-if="projectCategory.pagination.page > 1" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="prev" :href="projectCategoryPageUrl(projectCategory.pagination.page - 1)">‹</a>
        <template v-for="(page, index) in projectCategoryPageNumbers" :key="`${page}-${index}`">
          <span v-if="page === '…'" class="grid h-[38px] min-w-[38px] place-items-center">…</span>
          <a v-else class="grid h-[38px] min-w-[38px] place-items-center rounded-full border text-navy-800 no-underline" :class="page === projectCategory.pagination.page ? 'border-gold-500 bg-gold-400' : 'border-gray-200'" :aria-current="page === projectCategory.pagination.page ? 'page' : undefined" :href="projectCategoryPageUrl(page)">{{ page }}</a>
        </template>
        <a v-if="projectCategory.pagination.page < Math.ceil(projectCategory.pagination.total / projectCategory.pagination.perPage)" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="next" :href="projectCategoryPageUrl(projectCategory.pagination.page + 1)">›</a>
      </nav>
      <ProjectsCta v-if="projectCategory.cta" :cta="projectCategory.cta" :label="projectCategory.labels.cta" />
    </main>

    <main v-else-if="solutionCategory" class="mx-auto w-full max-w-[1766px] px-4 pb-16">
      <SiteBreadcrumb :items="[{ label: 'Giải pháp', to: '/giai-phap/' }, { label: solutionCategory.title }]" />
      <header class="py-7">
        <p class="mb-2 text-sm font-semibold uppercase tracking-wide text-gold-700">Giải pháp theo nhu cầu</p>
        <h1 class="text-[clamp(30px,4vw,44px)] font-black leading-tight tracking-tight">{{ solutionCategory.title }}</h1>
        <p class="mt-3 max-w-3xl leading-relaxed text-slate-500">{{ solutionCategory.description }}</p>
      </header>
      <section v-if="solutionCategory.cards.length" class="grid grid-cols-1 gap-6 tb:grid-cols-2 nav:grid-cols-3" aria-label="Các giải pháp">
        <article v-for="card in solutionCategory.cards" :key="card.slug" class="flex flex-col">
          <a :href="card.url" class="block overflow-hidden rounded-chip bg-slate-50"><img v-if="card.image" :src="card.image" :alt="card.title" class="aspect-[4/3] w-full object-cover" loading="lazy"></a>
          <h2 class="mb-2 mt-4 text-xl font-bold"><a :href="card.url" class="text-navy-800 no-underline hover:text-gold-700">{{ card.title }}</a></h2>
          <p v-if="card.description" class="mb-3 leading-relaxed text-slate-600">{{ card.description }}</p>
          <a :href="card.url" class="mt-auto font-semibold text-gold-700">{{ card.actionLabel }} →</a>
        </article>
      </section>
      <div v-else class="min-h-[30vh] py-8 text-slate-500" role="status"><p>{{ solutionCategory.labels.empty }}</p><a href="#ptsContactModal" class="font-semibold text-gold-700">{{ solutionCategory.labels.consult }} →</a></div>
      <nav v-if="solutionCategory.pagination.total > solutionCategory.pagination.perPage" class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Trang giải pháp">
        <a v-if="solutionCategory.pagination.page > 1" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="prev" :href="solutionCategoryPageUrl(solutionCategory.pagination.page - 1)">‹</a>
        <template v-for="(page, index) in solutionCategoryPageNumbers" :key="`${page}-${index}`">
          <span v-if="page === '…'" class="grid h-[38px] min-w-[38px] place-items-center">…</span>
          <a v-else class="grid h-[38px] min-w-[38px] place-items-center rounded-full border text-navy-800 no-underline" :class="page === solutionCategory.pagination.page ? 'border-gold-500 bg-gold-400' : 'border-gray-200'" :aria-current="page === solutionCategory.pagination.page ? 'page' : undefined" :href="solutionCategoryPageUrl(page)">{{ page }}</a>
        </template>
        <a v-if="solutionCategory.pagination.page < Math.ceil(solutionCategory.pagination.total / solutionCategory.pagination.perPage)" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline" rel="next" :href="solutionCategoryPageUrl(solutionCategory.pagination.page + 1)">›</a>
      </nav>
      <section v-if="solutionCategory.cta" class="mt-12 overflow-hidden rounded-chip bg-navy-900 p-7 text-white">
        <div class="grid gap-5 nav:grid-cols-[1fr_auto] nav:items-center"><div><h2 class="mb-2 text-2xl font-bold">{{ solutionCategory.cta.title }}</h2><p class="mb-0 text-white/80">{{ solutionCategory.cta.description }}</p></div><a href="#ptsContactModal" class="font-bold text-gold-400">{{ solutionCategory.cta.buttonLabel }} →</a></div>
      </section>
    </main>

    <Teleport to="body">
      <div v-if="lightbox" ref="lightboxPanel" class="fixed inset-0 z-[100] grid place-items-center bg-black/90 p-4" role="dialog" aria-modal="true" aria-label="Xem ảnh album" @click.self="closeGalleryImage">
        <button type="button" class="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-2xl text-white hover:bg-white/20" aria-label="Đóng" @click="closeGalleryImage">×</button>
        <button v-if="lightbox.detail.images.length > 1" type="button" class="absolute left-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh trước" @click="moveGalleryImage(-1)">‹</button>
        <figure class="m-0 max-h-[90vh] max-w-[min(1100px,90vw)] text-center">
          <img :src="lightbox.image.image" :alt="lightbox.image.caption || lightbox.detail.album.title" class="max-h-[82vh] max-w-full object-contain">
          <figcaption v-if="lightbox.image.caption" class="mt-3 text-sm text-white/80">{{ lightbox.image.caption }}</figcaption>
        </figure>
        <button v-if="lightbox.detail.images.length > 1" type="button" class="absolute right-3 top-1/2 grid h-12 w-12 -translate-y-1/2 place-items-center rounded-full bg-white/10 text-3xl text-white hover:bg-white/20" aria-label="Ảnh sau" @click="moveGalleryImage(1)">›</button>
      </div>
    </Teleport>

    <SiteFooter :site="site" />
  </div>
</template>

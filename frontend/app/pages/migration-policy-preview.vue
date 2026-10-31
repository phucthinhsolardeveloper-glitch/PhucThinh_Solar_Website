<script setup lang="ts">
import { isPublicPolicyDetail, type PublicPolicyDetail } from '#shared/contracts/public-policy-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import EditorialShare from '~/components/editorial/EditorialShare.vue'
import { buildEditorialToc } from '#shared/editorial-toc'
import { POLICY_PUBLIC_ROOT, editorialPublicResponseStatus, editorialPublicRouteRobots, isSafeEditorialSlug } from '#shared/editorial-public-route'
import { canonicalPublicOrigin, canonicalPublicUrl, publicSeoDescription, publicSeoTitle } from '#shared/public-seo'

const { site } = await usePublicSite()
const route = useRoute()
const config = useRuntimeConfig()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : (typeof route.query.slug === 'string' ? route.query.slug : ''))
if (!isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
const isPublicRoute = computed(() => route.path.startsWith(POLICY_PUBLIC_ROOT))
const { data, error, pending } = await useAsyncData<PublicPolicyDetail>(
  () => 'migration-policy-' + slug.value,
  async () => {
    if (!isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
    const payload = await $fetch<unknown>('/_migration/policy-detail', { query: { slug: slug.value } })
    if (!isPublicPolicyDetail(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid policy contract' })
    return payload
  },
  { watch: [slug] },
)
const responseStatus = editorialPublicResponseStatus(
  isPublicRoute.value,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const links = useMigrationLinks()
const emptyRichText = { format: 'sanitized-html' as const, html: '', sanitizedBy: 'pts_public_richtext' as const, sanitizerVersion: 1 as const }
const editorial = computed(() => buildEditorialToc(data.value?.content ?? emptyRichText))
const tocContainerRef = ref<HTMLElement | null>(null)
const relatedTrackRef = ref<HTMLElement | null>(null)
const canScrollRelatedBack = ref(false)
const canScrollRelatedForward = ref(true)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '#policy-article-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})

function syncRelatedControls(): void {
  const track = relatedTrackRef.value
  if (!track) return
  canScrollRelatedBack.value = track.scrollLeft > 2
  canScrollRelatedForward.value = track.scrollLeft + track.clientWidth < track.scrollWidth - 2
}

function scrollRelated(direction: -1 | 1): void {
  const track = relatedTrackRef.value
  if (!track) return
  track.scrollBy({ left: direction * track.clientWidth, behavior: 'smooth' })
}

onMounted(syncRelatedControls)

function formatMetadataDate(value: string): string {
  const date = new Date(`${value}T00:00:00Z`)
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('en-US', { month: 'short', day: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(date)
}

function formatRelatedDate(value: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  return match ? `${match[3]}/${match[2]}/${match[1]}` : value
}

const pageTitle = computed(() => isPublicRoute.value
  ? publicSeoTitle(data.value?.seo.title || data.value?.title, 'Chính sách hỗ trợ')
  : data.value?.seo.title || 'Chính sách hỗ trợ — Nuxt preview')
const socialDescription = computed(() => publicSeoDescription(data.value?.seo.description, site.value.brand.name))

useSeoMeta({
  title: pageTitle,
  description: computed(() => data.value?.seo.description || ''),
  robots: computed(() => editorialPublicRouteRobots(isPublicRoute.value, config.public.policyDetailPublicRouteIndexable, !!data.value && !error.value)),
})
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && !!data.value && !error.value),
  title: pageTitle,
  description: socialDescription,
  canonicalPath: computed(() => route.path),
  image: computed(() => data.value?.cover || undefined),
  type: 'article',
})
const policyJsonLd = computed(() => {
  const policy = data.value
  if (!isPublicRoute.value || !policy || error.value) return null
  const siteOrigin = canonicalPublicOrigin(config.public.canonicalSiteOrigin)
  const url = canonicalPublicUrl(siteOrigin, policy.canonical)
  if (!siteOrigin || !url || !policy.title.trim() || !policy.author.name.trim()) return null

  const node: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    mainEntityOfPage: { '@type': 'WebPage', '@id': url },
    headline: policy.title,
    url,
    author: { '@type': 'Person', name: policy.author.name },
    publisher: { '@type': 'Organization', '@id': `${siteOrigin}/#organization`, name: 'Phúc Thịnh Solar' },
  }
  const image = policy.cover ? canonicalPublicUrl(siteOrigin, policy.cover) : null
  if (image) node.image = [image]
  const publishedDate = /^\d{4}-\d{2}-\d{2}$/.test(policy.date) ? new Date(`${policy.date}T00:00:00Z`) : null
  if (publishedDate && !Number.isNaN(publishedDate.valueOf()) && publishedDate.toISOString().slice(0, 10) === policy.date) node.datePublished = policy.date
  const description = policy.seo.description || policy.description
  if (description) node.description = description
  return node
})
useHead({
  script: computed(() => policyJsonLd.value ? [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify(policyJsonLd.value).replace(/</g, '\\u003c'),
  }] : []),
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1766px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Chính sách & Hỗ trợ' }, ...(data ? [{ label: data.title }] : [])]" />
      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải nội dung chính sách…</div>
      <div v-else-if="error || !data" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-amber-900">
        <h1 class="mb-2 text-2xl font-black">Không thể tải chính sách</h1>
        <p class="mb-0">Slug không hợp lệ hoặc CMS local chưa khả dụng.</p>
      </div>
      <article v-else class="mx-auto max-w-[1080px]">
        <header class="mb-8 max-w-[1080px]">
          <h1 class="mb-3 text-[clamp(30px,5vw,48px)] font-black leading-tight tracking-tight">{{ data.title }}</h1>
          <p v-if="data.description" class="mb-3 max-w-[760px] text-lg leading-relaxed text-slate-500">{{ data.description }}</p>
          <p class="mb-0 text-sm text-slate-500">{{ formatMetadataDate(data.date) }} <span aria-hidden="true">·</span> {{ data.author.name }}</p>
        </header>
        <section v-if="data.content.html" class="max-w-[1080px]" aria-label="Nội dung chính sách">
          <div class="grid gap-8" :class="editorial.toc.length ? 'nav:grid-cols-[220px_minmax(0,1fr)]' : ''">
            <nav v-if="editorial.toc.length" ref="tocContainerRef" class="h-fit rounded-btn border border-slate-200/90 bg-slate-50/50 p-4 nav:sticky nav:top-24" aria-label="Mục lục nội dung chính sách">
              <strong class="mb-3 block text-sm text-navy-800">Nội dung bài viết</strong>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="item in editorial.toc" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a
                    :data-toc-id="item.id"
                    class="flex min-h-11 items-center px-2 py-2 text-sm leading-snug no-underline hover:text-gold-700"
                    :class="activeHeadingId === item.id ? 'font-extrabold text-gold-700' : 'text-slate-600'"
                    :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                    :href="`#${item.id}`"
                    @click.prevent="scrollToHeading(item.id)"
                  >{{ item.label }}</a>
                </li>
              </ul>
            </nav>
            <div class="w-full max-w-[960px]">
              <div id="policy-article-content"><EditorialRichText :content="editorial.content" /></div>
              <EditorialShare :title="data.title" :canonical-path="data.canonical" />
            </div>
          </div>
        </section>
        <p v-else class="my-8 text-slate-600" role="status">Nội dung đang cập nhật</p>
      </article>

      <section v-if="data && data.related.length" class="mx-auto mt-12 w-full" aria-labelledby="policy-related-title">
          <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="policy-related-title" class="m-0 text-2xl font-black text-navy-800">Tin liên quan</h2>
            <div class="flex shrink-0 gap-3" role="group" aria-label="Điều hướng tin liên quan">
              <button type="button" class="policy-related-arrow" aria-label="Tin trước" :disabled="!canScrollRelatedBack" @click="scrollRelated(-1)">
                <span aria-hidden="true">‹</span>
              </button>
              <button type="button" class="policy-related-arrow" aria-label="Tin tiếp theo" :disabled="!canScrollRelatedForward" @click="scrollRelated(1)">
                <span aria-hidden="true">›</span>
              </button>
            </div>
          </div>
          <ul ref="relatedTrackRef" tabindex="0" class="policy-related-track m-0 flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth p-0" aria-label="Các chính sách liên quan" @scroll.passive="syncRelatedControls">
            <li v-for="item in data.related" :key="item.id" class="policy-related-card min-w-0 snap-start">
              <a class="group block h-full text-navy-800 no-underline" :href="links.generic(`/ho-tro/${item.slug}/`)">
                <img v-if="item.image" :src="item.image" :alt="item.title" class="mb-3 block aspect-video w-full object-cover" loading="lazy" decoding="async">
                <div v-else class="mb-3 aspect-video bg-navy-50" aria-hidden="true" />
                <p v-if="item.date" class="mb-2 text-sm text-slate-500">{{ formatRelatedDate(item.date) }}</p>
                <h3 class="mb-2 text-lg font-bold leading-snug group-hover:text-gold-700">{{ item.title }}</h3>
                <p v-if="item.description" class="mb-0 line-clamp-3 leading-relaxed text-slate-600">{{ item.description }}</p>
              </a>
            </li>
          </ul>
      </section>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

<style>
.policy-related-track { scrollbar-width: none; }
.policy-related-track::-webkit-scrollbar { display: none; }
.policy-related-card { flex: 0 0 100%; }
.policy-related-arrow {
  display: grid;
  width: 44px;
  height: 44px;
  place-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 9999px;
  color: #1e3a5f;
  background: #fff;
  font-size: 26px;
  line-height: 1;
}
.policy-related-arrow:disabled { color: #94a3b8; cursor: not-allowed; opacity: .5; }
.policy-related-arrow:not(:disabled):hover { border-color: #c9962e; color: #9a6a00; }
@media (min-width: 455px) { .policy-related-card { flex-basis: calc(50% - 12px); } }
@media (min-width: 768px) { .policy-related-card { flex-basis: calc(33.333333% - 16px); } }
@media (min-width: 1024px) { .policy-related-card { flex-basis: calc(25% - 18px); } }
@media (prefers-reduced-motion: reduce) { .policy-related-track { scroll-behavior: auto; } }
</style>

<script setup lang="ts">
import { isPublicPolicyDetail, type PublicPolicyDetail } from '#shared/contracts/public-policy-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import EditorialShare from '~/components/editorial/EditorialShare.vue'
import { buildEditorialToc } from '#shared/editorial-toc'
import { POLICY_PUBLIC_ROOT, editorialPublicResponseStatus, editorialPublicRouteRobots, isSafeEditorialSlug } from '#shared/editorial-public-route'
import { canonicalPublicOrigin, canonicalPublicUrl, publicSeoDescription, publicSeoTitle } from '#shared/public-seo'

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
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
    <div class="pts-container">
      <SiteBreadcrumb :items="[{ label: 'Chính sách & Hỗ trợ' }, ...(data ? [{ label: data.title }] : [])]" />
    </div>

    <main class="pts-container py-8 nav:py-16" :aria-busy="pending">
      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải nội dung chính sách…</div>
      <div v-else-if="error || !data" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-amber-900">
        <h1 class="mb-2 text-2xl font-black">Không thể tải chính sách</h1>
        <p class="mb-0">Slug không hợp lệ hoặc CMS local chưa khả dụng.</p>
      </div>
      <article v-else class="mx-auto w-full max-w-[1280px]">
        <header class="mb-7 max-w-[1000px] nav:mb-9">
          <h1 class="mb-3 text-[clamp(30px,5vw,48px)] font-black leading-tight tracking-tight">{{ data.title }}</h1>
          <p v-if="data.description" class="mb-3 max-w-[760px] text-lg leading-relaxed text-slate-500">{{ data.description }}</p>
          <p class="mb-0 text-sm text-slate-500">{{ formatMetadataDate(data.date) }} <span aria-hidden="true">·</span> {{ data.author.name }}</p>
        </header>
        <section v-if="data.content.html" aria-label="Nội dung chính sách">
          <div class="grid grid-cols-1 gap-8 nav:grid-cols-[minmax(240px,1fr)_minmax(0,2fr)] nav:gap-12">
            <nav v-if="editorial.toc.length" ref="tocContainerRef" class="h-fit nav:sticky nav:top-24" aria-label="Mục lục nội dung chính sách">
              <strong class="mb-3 flex items-center gap-2 text-sm font-bold text-navy-800">
                <UIcon name="i-lucide-list" class="size-4 text-gold-600" aria-hidden="true" />
                <span>Nội dung bài viết</span>
              </strong>
              <ul class="m-0 list-none space-y-1 border-l border-slate-200 p-0">
                <li v-for="item in editorial.toc" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a
                    :data-toc-id="item.id"
                    class="flex min-h-11 min-w-11 items-center border-l-2 px-3 text-sm leading-5 transition-colors no-underline"
                    :class="activeHeadingId === item.id ? 'border-gold-500 font-semibold text-navy-800' : 'border-transparent text-slate-600 hover:text-navy-800'"
                    :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                    :href="`#${item.id}`"
                    @click.prevent="scrollToHeading(item.id)"
                  >{{ item.label }}</a>
                </li>
              </ul>

              <div v-if="site.footer?.policies?.length" class="mt-6 border-t border-slate-200/80 pt-5">
                <strong class="mb-2.5 flex items-center gap-2 text-sm font-bold text-navy-800">
                  <UIcon name="i-lucide-shield-check" class="size-4 text-gold-600" aria-hidden="true" />
                  <span>Chính sách & Hỗ trợ</span>
                </strong>
                <ul class="m-0 list-none space-y-1 p-0">
                  <li v-for="pol in site.footer.policies" :key="pol.href">
                    <NuxtLink
                      :to="links.generic(pol.href)"
                      class="flex items-center justify-between gap-2 rounded-[3px] px-2.5 py-2 text-xs leading-snug no-underline transition-colors"
                      :class="pol.href.includes(slug) ? 'bg-navy-950 font-bold text-gold-300' : 'text-slate-600 hover:bg-slate-100 hover:text-navy-900'"
                    >
                      <span>{{ pol.label }}</span>
                      <UIcon name="i-lucide-chevron-right" class="size-3.5 shrink-0 opacity-60" aria-hidden="true" />
                    </NuxtLink>
                  </li>
                </ul>
              </div>

              <div class="mt-6 hidden rounded-[4px] border-l-[3px] border-gold-500 bg-navy-950 p-4 text-white nav:block">
                <p class="m-0 text-sm font-bold text-white">Hỗ trợ bảo hành & kỹ thuật</p>
                <p class="m-0 mt-1 text-xs leading-relaxed text-white/75">
                  Liên hệ trực tiếp bộ phận kỹ thuật hoặc gửi mã đơn hàng để được kiểm tra nhanh.
                </p>
                <div class="mt-3 grid gap-1.5 text-xs">
                  <a href="tel:0945536899" class="flex items-center justify-between rounded-[3px] bg-white/10 px-2.5 py-1.5 text-white no-underline hover:bg-white/15">
                    <span class="text-white/75">Kỹ thuật:</span>
                    <strong class="tabular-nums text-gold-300">0945 536 899</strong>
                  </a>
                  <a href="tel:0944555957" class="flex items-center justify-between rounded-[3px] bg-white/10 px-2.5 py-1.5 text-white no-underline hover:bg-white/15">
                    <span class="text-white/75">Bán hàng:</span>
                    <strong class="tabular-nums text-gold-300">0944 555 957</strong>
                  </a>
                </div>
                <button
                  type="button"
                  class="mt-3 inline-flex min-h-9 w-full cursor-pointer items-center justify-center gap-1.5 rounded-[3px] bg-gold-500 px-3 py-1.5 text-xs font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
                  @click="openConsultation('Chính sách & Hỗ trợ: ' + data.title)"
                >
                  <span>Gửi yêu cầu hỗ trợ</span>
                  <span aria-hidden="true">→</span>
                </button>
              </div>
            </nav>
            <div class="min-w-0 w-full max-w-[960px]">
              <div id="policy-article-content"><EditorialRichText :content="editorial.content" variant="news" /></div>
              <div class="my-7 flex flex-col gap-3 rounded-[4px] border-l-[3px] border-gold-500 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="m-0 text-sm font-bold text-navy-900">Cần giải đáp thêm về {{ data.title.toLowerCase() }}?</p>
                  <p class="m-0 mt-0.5 text-xs text-slate-600">Đội ngũ chăm sóc khách hàng và kỹ thuật viên Phúc Thịnh Solar luôn sẵn sàng hỗ trợ.</p>
                </div>
                <button
                  type="button"
                  class="inline-flex min-h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-[3px] bg-navy-900 px-4 py-2 text-xs font-extrabold text-white transition-colors hover:bg-navy-800"
                  @click="openConsultation('Chính sách & Hỗ trợ: ' + data.title)"
                >
                  <span>Liên hệ hỗ trợ ngay</span>
                  <span aria-hidden="true">→</span>
                </button>
              </div>
              <EditorialShare :title="data.title" :canonical-path="data.canonical" />
            </div>
          </div>
        </section>
        <p v-else class="my-8 text-slate-600" role="status">Nội dung đang cập nhật</p>
      </article>

      <section v-if="data && data.related.length" class="mx-auto mt-10 w-full nav:mt-14" aria-labelledby="policy-related-title">
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

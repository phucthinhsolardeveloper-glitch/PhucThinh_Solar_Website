<script setup lang="ts">
import type { DraftPreviewQuery } from '#shared/draft-preview'
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
const props = defineProps<{ preview?: DraftPreviewQuery }>()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
if (!props.preview && !isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
const { data: fetched, error, pending } = await useAsyncData<PublicPolicyDetail>(
  () => props.preview ? 'policy-preview-' + props.preview.revision : 'policy-' + slug.value,
  async () => {
    if (!props.preview && !isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
    const payload = await $fetch<unknown>(props.preview ? '/_api/post-draft-preview' : '/_api/policy-detail', { query: props.preview ?? { slug: slug.value } })
    if (!isPublicPolicyDetail(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid policy contract' })
    return payload
  },
  { watch: [slug] },
)
// The async key changes with the slug, which empties the result until the new policy arrives.
// Keep showing the current policy until then, so the page never blanks.
const data = shallowRef(fetched.value)
watch(fetched, (value) => { if (value) data.value = value })
// Moving between policies is the main action on this page, so have them ready.
onMounted(() => {
  const warm = () => {
    for (const policy of site.value.footer?.policies ?? []) {
      const next = policy.href.replace(/\/+$/, '').split('/').pop()
      if (next && isSafeEditorialSlug(next)) void $fetch('/_api/policy-detail', { query: { slug: next } }).catch(() => {})
    }
  }
  if ('requestIdleCallback' in window) window.requestIdleCallback(warm)
  else setTimeout(warm, 800)
})
const responseStatus = editorialPublicResponseStatus(
  true,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const links = usePublicLinks()
const emptyRichText = { format: 'sanitized-html' as const, html: '', sanitizedBy: 'pts_public_richtext' as const, sanitizerVersion: 1 as const }
const editorial = computed(() => buildEditorialToc(data.value?.content ?? emptyRichText))
const tocContainerRef = ref<HTMLElement | null>(null)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '#policy-article-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})

function formatMetadataDate(value: string): string {
  const date = new Date(`${value}T00:00:00Z`)
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(date)
}

const policyIcons: Array<[RegExp, string]> = [
  [/bảo mật|dữ liệu/i, 'i-lucide-shield-check'],
  [/giao hàng|kiểm hàng/i, 'i-lucide-truck'],
  [/đổi trả|hoàn tiền/i, 'i-lucide-rotate-ccw'],
  [/đặt hàng|thanh toán/i, 'i-lucide-credit-card'],
  [/bảo hành|bảo trì|kỹ thuật/i, 'i-lucide-wrench'],
  [/khiếu nại/i, 'i-lucide-message-square-warning'],
  [/điều kiện|giao dịch chung/i, 'i-lucide-file-text'],
]
function policyIcon(label: string) { return policyIcons.find(([re]) => re.test(label))?.[1] ?? 'i-lucide-file-text' }
const policyRailRef = ref<HTMLElement | null>(null)
watch(slug, async () => {
  if (!import.meta.client) return
  await nextTick()
  const rail = policyRailRef.value
  const current = rail?.querySelector<HTMLElement>('[aria-current="page"]')
  if (!rail || !current) return
  const target = current.offsetLeft - rail.offsetLeft - (rail.clientWidth - current.clientWidth) / 2
  const left = Math.max(0, Math.min(target, rail.scrollWidth - rail.clientWidth))
  const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
  rail.scrollTo({ left, behavior })
}, { immediate: true, flush: 'post' })
const policies = computed(() => site.value.footer?.policies ?? [])
function isCurrentPolicy(href: string) { return href.replace(/\/+$/, '').endsWith(`/${slug.value}`) }

const pageTitle = computed(() => publicSeoTitle(data.value?.seo.title || data.value?.title, 'Chính sách hỗ trợ'))
const socialDescription = computed(() => publicSeoDescription(data.value?.seo.description, site.value.brand.name))

useSeoMeta({
  title: pageTitle,
  description: computed(() => data.value?.seo.description || ''),
  robots: computed(() => props.preview ? 'noindex, nofollow, noarchive' : editorialPublicRouteRobots(true, config.public.policyDetailPublicRouteIndexable, !!data.value && !error.value)),
})
usePublicSocialMeta({
  enabled: computed(() => !!data.value && !error.value),
  title: pageTitle,
  description: socialDescription,
  canonicalPath: computed(() => route.path),
  image: computed(() => data.value?.cover || undefined),
  type: 'article',
})
const policyJsonLd = computed(() => {
  const policy = data.value
  if (!policy || error.value) return null
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
    <div class="pts-container">
      <SiteBreadcrumb :items="[{ label: 'Chính sách & Hỗ trợ' }, ...(data ? [{ label: data.title }] : [])]" />
    </div>

    <section v-if="data" class="policy-hub" aria-labelledby="policy-title">
      <div class="pts-container">
        <!-- Same height on every policy (room for a two-line title), so the policy cards below never jump when switching. -->
        <div class="policy-hub__head" :class="{ 'has-lede': !!data.description }">
          <h1 id="policy-title" class="policy-hub__title">{{ data.title }}</h1>
          <p v-if="data.description" class="policy-hub__lede">{{ data.description }}</p>
          <p class="policy-hub__meta">Cập nhật {{ formatMetadataDate(data.date) }} · {{ data.author.name }}</p>
        </div>
        <nav v-if="policies.length" ref="policyRailRef" class="policy-rail" aria-label="Chính sách và hỗ trợ">
          <NuxtLink
            v-for="pol in policies"
            :key="pol.href"
            :to="links.generic(pol.href)"
            class="policy-rail__item"
            :class="{ 'is-current': isCurrentPolicy(pol.href) }"
            :aria-current="isCurrentPolicy(pol.href) ? 'page' : undefined"
          >
            <UIcon :name="policyIcon(pol.label)" class="size-6 flex-none" aria-hidden="true" />
            <span>{{ pol.label }}</span>
          </NuxtLink>
        </nav>
      </div>
    </section>

    <main class="pts-container py-8 nav:py-14" :aria-busy="pending">
      <div v-if="pending && !data" class="py-16 text-slate-500" role="status">Đang tải nội dung chính sách…</div>
      <div v-else-if="error || !data" class="rounded-btn border border-slate-200 bg-slate-50 p-8 text-navy-900">
        <h1 class="mb-2 text-2xl font-black">Chưa tìm thấy chính sách này</h1>
        <p class="mb-5 text-slate-600">Trang có thể đã đổi địa chỉ. Bạn xem các chính sách khác hoặc liên hệ để được hỗ trợ.</p>
        <NuxtLink to="/lien-he/" class="inline-flex min-h-11 items-center gap-2 font-bold text-navy-900 underline underline-offset-4">Liên hệ Phúc Thịnh Solar</NuxtLink>
      </div>
      <article v-else class="mx-auto w-full max-w-[1280px]">
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
                    class="flex min-h-11 min-w-11 items-center border-l-2 px-3 text-[15px] leading-5 transition-colors no-underline"
                    :class="activeHeadingId === item.id ? 'border-gold-500 font-semibold text-navy-800' : 'border-transparent text-slate-600 hover:text-navy-800'"
                    :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                    :href="`#${item.id}`"
                    @click.prevent="scrollToHeading(item.id)"
                  >{{ item.label }}</a>
                </li>
              </ul>

              <div class="mt-6 hidden rounded-btn bg-navy-950 p-5 text-white nav:block">
                <p class="m-0 text-base font-extrabold text-white">Cần hỗ trợ ngay?</p>
                <p class="m-0 mt-1.5 text-sm leading-relaxed text-white/75">
                  Liên hệ trực tiếp bộ phận kỹ thuật hoặc gửi mã đơn hàng để được kiểm tra nhanh.
                </p>
                <div class="mt-4 grid gap-2 text-sm">
                  <a href="tel:0945536899" class="flex min-h-11 items-center justify-between rounded-chip bg-white/10 px-3 text-white no-underline hover:bg-white/15">
                    <span class="text-white/75">Kỹ thuật:</span>
                    <strong class="tabular-nums text-gold-300">0945 536 899</strong>
                  </a>
                  <a href="tel:0944555957" class="flex min-h-11 items-center justify-between rounded-chip bg-white/10 px-3 text-white no-underline hover:bg-white/15">
                    <span class="text-white/75">Bán hàng:</span>
                    <strong class="tabular-nums text-gold-300">0944 555 957</strong>
                  </a>
                </div>
                <button
                  type="button"
                  class="mt-4 inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-1.5 rounded-chip bg-gold-500 px-3 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
                  @click="openConsultation('Chính sách & Hỗ trợ: ' + data.title)"
                >
                  <span>Gửi yêu cầu hỗ trợ</span>
                  <span aria-hidden="true">→</span>
                </button>
              </div>
            </nav>
            <div class="min-w-0 w-full max-w-[960px]">
              <div id="policy-article-content"><EditorialRichText :content="editorial.content" variant="news" /></div>
              <div class="my-7 flex flex-col gap-3 rounded-btn border-l-[3px] border-gold-500 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="m-0 text-sm font-bold text-navy-900">Cần giải đáp thêm về {{ data.title.toLowerCase() }}?</p>
                  <p class="m-0 mt-0.5 text-xs text-slate-600">Đội ngũ chăm sóc khách hàng và kỹ thuật viên Phúc Thịnh Solar luôn sẵn sàng hỗ trợ.</p>
                </div>
                <button
                  type="button"
                  class="inline-flex min-h-10 shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-chip bg-navy-900 px-4 py-2 text-xs font-extrabold text-white transition-colors hover:bg-navy-800"
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

    </main>
  </div>
</template>

<style>
.policy-hub { padding: clamp(36px, 5vw, 64px) 0 clamp(28px, 3vw, 40px); background: radial-gradient(120% 140% at 85% 0%, #17407a 0%, var(--color-navy-950) 60%); color: #fff; }
.policy-hub__head { display: flex; flex-direction: column; justify-content: flex-end; }
/* Two title lines (font × line-height 1.05 × 2) + the date line under it, on phones too. */
.policy-hub__head { min-height: calc(clamp(32px, 4.4vw, 60px) * 2.1 + 14px + 22px); }
.policy-hub__head.has-lede { min-height: 0; }
.policy-hub__title { margin: 0; max-width: 22ch; text-wrap: balance; font-size: clamp(32px, 4.4vw, 60px); font-weight: 900; line-height: 1.05; letter-spacing: -0.035em; }
.policy-hub__lede { margin: 16px 0 0; max-width: 64ch; color: #cbd5e1; font-size: clamp(16px, 1.2vw, 19px); line-height: 1.65; }
.policy-hub__meta { margin: 14px 0 0; color: #94a3b8; font-size: 14.5px; }
.policy-rail { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(150px, 1fr); gap: 8px; margin-top: clamp(24px, 3vw, 36px); overflow-x: auto; scrollbar-width: none; padding-bottom: 2px; }
.policy-rail::-webkit-scrollbar { display: none; }
.policy-rail__item { display: flex; min-height: 92px; flex-direction: column; justify-content: space-between; gap: 10px; padding: 14px; border: 1px solid rgb(255 255 255 / 14%); border-radius: var(--radius-btn); background: rgb(255 255 255 / 4%); color: #dbe4ef; font-size: 14px; font-weight: 700; line-height: 1.3; text-decoration: none; transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.25s ease; }
.policy-rail__item svg { color: var(--color-gold-400); }
.policy-rail__item:hover { border-color: rgb(224 182 84 / 60%); transform: translateY(-2px); }
.policy-rail__item.is-current { border-color: var(--color-gold-400); background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.policy-rail__item.is-current svg { color: var(--color-navy-950); }
.policy-rail__item:focus-visible { outline: 2px solid var(--color-gold-300); outline-offset: 2px; }
@media (max-width: 760px) { .policy-rail { grid-auto-columns: 46%; } }
@media (prefers-reduced-motion: reduce) { .policy-rail__item { transition: none; } .policy-rail__item:hover { transform: none; } .policy-route-enter-active, .policy-route-leave-active { transition: none; } }
</style>

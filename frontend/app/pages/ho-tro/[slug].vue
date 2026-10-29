<script setup lang="ts">
import { isPublicPolicyDetail, type PublicPolicyDetail } from '#shared/contracts/public-policy-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import EditorialShare from '~/components/editorial/EditorialShare.vue'

const { site } = await usePublicSite()
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { data, error, pending } = await useAsyncData<PublicPolicyDetail>(
  () => 'public-policy-' + slug.value,
  async () => {
    const payload = await $fetch<unknown>('/_migration/policy-detail', { query: { slug: slug.value } })
    if (!isPublicPolicyDetail(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid policy contract' })
    return payload
  },
  { watch: [slug] },
)
const links = useMigrationLinks()
const editorial = computed(() => {
  const html = data.value?.content.html ?? ''
  const toc: { id: string; label: string; level: number }[] = []
  let index = 0
  const contentHtml = html.replace(/<h([23])\b([^>]*)>([\s\S]*?)<\/h\1>/gi, (_heading, level: string, attributes: string, inner: string) => {
    const label = decodeHeadingText(inner)
    if (!label) return _heading
    const id = `policy-section-${index++}`
    toc.push({ id, label, level: Number(level) })
    const safeAttributes = attributes.replace(/\s+id=("|')[^"']*\1/gi, '')
    return `<h${level}${safeAttributes} id="${id}">${inner}</h${level}>`
  })
  return {
    content: data.value
      ? { ...data.value.content, html: contentHtml }
      : { format: 'sanitized-html' as const, html: '', sanitizedBy: 'pts_public_richtext' as const, sanitizerVersion: 1 as const },
    toc,
  }
})

function decodeHeadingText(value: string): string {
  return value.replace(/<[^>]*>/g, '').replace(/&(#x[\da-f]+|#\d+|amp|lt|gt|quot|apos|nbsp);/gi, (entity, code: string) => {
    if (code[0] === '#') {
      const point = code[1]?.toLowerCase() === 'x' ? Number.parseInt(code.slice(2), 16) : Number.parseInt(code.slice(1), 10)
      return Number.isFinite(point) && point > 0 && point <= 0x10ffff ? String.fromCodePoint(point) : ''
    }
    return ({ amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' } as Record<string, string>)[code.toLowerCase()] ?? entity
  }).trim()
}

function formatDate(value: string): string {
  const date = new Date(`${value}T00:00:00Z`)
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('vi-VN', { dateStyle: 'long', timeZone: 'UTC' }).format(date)
}

if (error.value && error.value.statusCode === 404) setResponseStatus(404)

const requestUrl = useRequestURL()
useSeoMeta({
  title: computed(() => data.value?.seo.title || 'Chính sách & Hỗ trợ — Phúc Thịnh Solar'),
  description: computed(() => data.value?.seo.description || ''),
  robots: computed(() => data.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({
  link: data.value ? [{ rel: 'canonical', href: new URL(data.value.seo.canonical, requestUrl.origin).toString() }] : [],
}))
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1180px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Chính sách & Hỗ trợ' }, ...(data ? [{ label: data.title }] : [])]" />
      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải nội dung chính sách…</div>
      <div v-else-if="error || !data" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-amber-900">
        <h1 class="mb-2 text-2xl font-black">Không tìm thấy chính sách</h1>
        <p class="mb-0">Nội dung có thể đã được chuyển hoặc không còn được hiển thị.</p>
      </div>
      <article v-else class="mx-auto max-w-[1180px]">
        <header class="mb-8 max-w-[900px]">
          <h1 class="mb-3 text-[clamp(30px,5vw,48px)] font-black leading-tight tracking-tight">{{ data.title }}</h1>
          <p v-if="data.description" class="mb-3 max-w-[760px] text-lg leading-relaxed text-slate-500">{{ data.description }}</p>
          <p class="mb-0 text-sm text-slate-500">{{ formatDate(data.date) }} <span aria-hidden="true">·</span> {{ data.author.name }}</p>
        </header>
        <section v-if="data.content.html" class="max-w-[900px]" aria-label="Nội dung chính sách">
          <div class="grid gap-8" :class="editorial.toc.length ? 'nav:grid-cols-[240px_minmax(0,1fr)]' : ''">
            <nav v-if="editorial.toc.length" class="h-fit rounded-btn border border-gray-200 p-4 nav:sticky nav:top-4" aria-label="Mục lục nội dung chính sách">
              <strong class="mb-3 block text-sm text-navy-800">Nội dung bài viết</strong>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="item in editorial.toc" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a class="block px-2 py-2 text-sm leading-snug text-slate-600 no-underline hover:text-gold-700" :href="`#${item.id}`">{{ item.label }}</a>
                </li>
              </ul>
            </nav>
            <EditorialRichText :content="editorial.content" />
          </div>
          <EditorialShare :title="data.title" :canonical-path="data.canonical" />
        </section>

        <section v-if="data.related.length" class="mt-12" aria-labelledby="policy-related-title">
          <h2 id="policy-related-title" class="mb-5 text-2xl font-black text-navy-800">Tin liên quan</h2>
          <ul class="m-0 grid list-none grid-cols-1 gap-6 p-0 tb:grid-cols-2 nav:grid-cols-3">
            <li v-for="item in data.related" :key="item.id" class="min-w-0">
              <a class="group block h-full text-navy-800 no-underline" :href="links.generic(`/ho-tro/${item.slug}/`)">
                <img v-if="item.image" :src="item.image" :alt="item.title" class="mb-3 block aspect-[3/2] w-full object-cover" loading="lazy" decoding="async">
                <div v-else class="mb-3 aspect-[3/2] bg-navy-50" aria-hidden="true" />
                <p v-if="item.date" class="mb-2 text-sm text-slate-500">{{ formatDate(item.date) }}</p>
                <h3 class="mb-2 text-lg font-bold leading-snug group-hover:text-gold-700">{{ item.title }}</h3>
                <p v-if="item.description" class="mb-0 line-clamp-3 leading-relaxed text-slate-600">{{ item.description }}</p>
              </a>
            </li>
          </ul>
        </section>
      </article>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

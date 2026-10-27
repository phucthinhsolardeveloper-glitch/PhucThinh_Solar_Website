<script setup lang="ts">
import { isPublicPolicyDetail, type PublicPolicyDetail } from '#shared/contracts/public-policy-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

const { site } = await usePublicSite()
const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { data, error, pending } = await useAsyncData<PublicPolicyDetail>(
  () => 'migration-policy-' + slug.value,
  async () => {
    const payload = await $fetch<unknown>('/_migration/policy-detail', { query: { slug: slug.value } })
    if (!isPublicPolicyDetail(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid policy contract' })
    return payload
  },
  { watch: [slug] },
)

useSeoMeta({
  title: computed(() => data.value?.seo.title || 'Chính sách hỗ trợ — Nuxt preview'),
  description: computed(() => data.value?.seo.description || ''),
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1180px] px-4 py-10 nav:py-14">
      <SiteBreadcrumb :items="[{ label: 'Chính sách & Hỗ trợ' }, ...(data ? [{ label: data.title }] : [])]" />
      <div v-if="pending" class="py-16 text-slate-500" role="status">Đang tải nội dung chính sách…</div>
      <div v-else-if="error || !data" class="rounded-btn border border-amber-200 bg-amber-50 p-8 text-amber-900">
        <h1 class="mb-2 text-2xl font-black">Không thể tải chính sách</h1>
        <p class="mb-0">Slug không hợp lệ hoặc CMS local chưa khả dụng.</p>
      </div>
      <article v-else class="mx-auto max-w-[900px]">
        <header class="mb-8 border-b border-gray-200 pb-6">
          <p class="mb-2 text-xs font-extrabold uppercase tracking-[.12em] text-gold-700">Chính sách & Hỗ trợ</p>
          <h1 class="mb-3 text-[clamp(30px,5vw,48px)] font-black leading-tight tracking-tight">{{ data.title }}</h1>
          <p v-if="data.description" class="mb-0 max-w-[760px] text-lg leading-relaxed text-slate-500">{{ data.description }}</p>
        </header>
        <EditorialRichText :content="data.content" />
      </article>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

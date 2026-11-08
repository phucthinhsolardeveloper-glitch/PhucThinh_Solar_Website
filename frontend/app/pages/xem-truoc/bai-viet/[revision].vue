<script setup lang="ts">
import { isPublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsDetailArticle from '~/components/news-detail/NewsDetailArticle.vue'
import NewsDetailProducts from '~/components/news-detail/NewsDetailProducts.vue'
import NewsDetailRelated from '~/components/news-detail/NewsDetailRelated.vue'
import NewsReadingProgress from '~/components/news-detail/NewsReadingProgress.vue'

const route = useRoute()
const revision = typeof route.params.revision === 'string' ? route.params.revision : ''
const expires = typeof route.query.expires === 'string' ? route.query.expires : ''
const signature = typeof route.query.signature === 'string' ? route.query.signature : ''
const { data, error } = await useAsyncData(`article-preview-${revision}-${expires}`, () => $fetch('/_api/article-draft-preview', {
  query: { revision, expires, signature },
}))
const payload = computed(() => isPublicNewsDetailPayload(data.value) ? data.value : null)

useSeoMeta({
  title: () => payload.value ? `Xem trước: ${payload.value.article.title}` : 'Xem trước bài viết',
  robots: 'noindex, nofollow, noarchive',
})
if (import.meta.server) {
  useResponseHeader('Cache-Control').value = 'private, no-store'
  useResponseHeader('X-Robots-Tag').value = 'noindex, nofollow, noarchive'
  useResponseHeader('Referrer-Policy').value = 'no-referrer'
  if (!payload.value) setResponseStatus(403, 'Preview unavailable')
}
</script>

<template>
  <main class="min-h-[60vh] bg-white text-navy-800">
    <div class="pts-container py-6 nav:py-8">
      <div class="mb-8 rounded-md border border-gold-500/30 bg-amber-50 px-5 py-4 text-sm font-semibold text-navy-800" role="status">
        Bản xem trước bài viết · Chỉ người có liên kết tạm thời mới xem được
      </div>
      <div v-if="error || !payload" class="py-20 text-center">
        <h1 class="text-2xl font-bold">Liên kết xem trước không còn hiệu lực</h1>
        <p class="mt-3 text-slate-600">Mở lại bản nháp trong Admin để lấy liên kết mới.</p>
      </div>
      <template v-else>
        <NewsReadingProgress target="#news-article-body" />
        <div id="news-article-body"><NewsDetailArticle :article="payload.article" preview /></div>
        <div class="mx-auto w-full max-w-[1280px]"><NewsDetailProducts :article="payload.article" /></div>
        <NewsDetailRelated :items="payload.related" />
        <div v-if="payload.cta" inert><NewsCta :cta="payload.cta" :phone-label="payload.cta.hotline?.label || 'Gọi ngay hotline'" /></div>
      </template>
    </div>
  </main>
</template>

<script setup lang="ts">
import type { PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import EditorialShare from '~/components/editorial/EditorialShare.vue'
import { buildEditorialToc } from '#shared/editorial-toc'

const props = defineProps<{ article: PublicNewsDetailPayload['article'] }>()
const editorial = computed(() => buildEditorialToc(props.article.content))
const tocContainerRef = ref<HTMLElement | null>(null)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '#news-article-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})

const categoryName = computed(() => {
  const category = props.article.category
  return 'name' in category && category.name.trim() ? category.name : ''
})

function formatDate(value: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  if (!match) return value
  const date = new Date(`${value}T00:00:00.000Z`)
  if (Number.isNaN(date.valueOf()) || date.toISOString().slice(0, 10) !== value) return value
  return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(date)
}
</script>

<template>
  <article class="mx-auto w-full max-w-[1280px]">
    <header class="mb-7 nav:mb-9">
      <UBadge v-if="categoryName" color="primary" variant="soft" class="mb-4 rounded-[3px] px-2.5 py-1 text-xs font-semibold">
        {{ categoryName }}
      </UBadge>
      <h1 class="max-w-[1000px] text-[clamp(30px,4vw,52px)] font-bold leading-[1.12] tracking-[-0.035em] text-navy-800">
        {{ article.title }}
      </h1>
      <div class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
        <time v-if="article.date" :datetime="article.date">{{ formatDate(article.date) }}</time>
        <span v-if="article.date" aria-hidden="true" class="text-slate-300">·</span>
        <span>{{ article.author.name }}</span>
      </div>
    </header>

    <img
      v-if="article.cover && article.showCover"
      :src="article.cover"
      :alt="article.title"
      width="1000"
      height="560"
      sizes="(min-width: 1024px) 66vw, 100vw"
      class="mb-8 block aspect-video w-full object-cover nav:mb-10"
    >

    <div class="grid grid-cols-1 gap-8 nav:grid-cols-[minmax(0,2fr)_minmax(240px,1fr)] nav:gap-12">
      <div class="order-2 min-w-0 nav:order-1">
        <!-- Sanitized rich text is rendered once; heading IDs support the TOC anchors. -->
        <div v-if="article.content.html" id="news-article-content">
          <EditorialRichText :content="editorial.content" variant="news" />
        </div>
        <p v-else class="my-8 text-slate-600" role="status">Nội dung đang cập nhật</p>

        <div v-if="editorial.content.html && article.quote?.html" class="my-8">
          <blockquote class="m-0 border-l-[3px] border-gold-500 pl-4 text-slate-600">
            <EditorialRichText :content="article.quote" variant="news" />
          </blockquote>
        </div>

        <div v-if="article.media.gallery.length" class="mt-8 grid grid-cols-1 gap-4 tb:grid-cols-2">
          <img v-for="media in article.media.gallery" :key="media.image" :src="media.image" :alt="media.alt || article.title" class="block h-auto w-full object-contain">
        </div>

        <ul v-if="article.media.videos.length" class="m-0 mt-6 list-none space-y-2 p-0">
          <li v-for="video in article.media.videos" :key="video.url">
            <a class="inline-flex min-h-11 items-center gap-2 font-semibold text-gold-700 underline-offset-2 hover:underline" :href="video.url" target="_blank" rel="noopener noreferrer">
              <UIcon name="i-lucide-play" class="size-4" aria-hidden="true" />
              {{ video.title || 'Xem video' }}
            </a>
          </li>
        </ul>

        <EditorialShare v-if="article.content.html" class="mt-8" :title="article.title" :canonical-path="article.seo.canonical" />
      </div>

      <aside class="order-1 min-w-0 nav:order-2">
        <nav
          v-if="article.content.html && editorial.toc.length"
          ref="tocContainerRef"
          class="nav:sticky nav:top-28"
          aria-label="Mục lục bài viết"
        >
          <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-navy-800">
            <UIcon name="i-lucide-list" class="size-4 text-gold-600" aria-hidden="true" />
            <span>Nội dung bài viết</span>
          </h2>
          <ol class="m-0 list-none space-y-1 border-l border-slate-200 p-0">
            <li v-for="item in editorial.toc" :key="item.id" :class="item.level === 3 ? 'pl-4' : ''">
              <a
                :data-toc-id="item.id"
                class="flex min-h-11 min-w-11 items-center border-l-2 px-3 text-sm leading-5 transition-colors"
                :class="activeHeadingId === item.id ? 'border-gold-500 font-semibold text-navy-800' : 'border-transparent text-slate-600 hover:text-navy-800'"
                :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                :href="`#${item.id}`"
                @click.prevent="scrollToHeading(item.id)"
              >{{ item.label }}</a>
            </li>
          </ol>
        </nav>
      </aside>
    </div>
  </article>
</template>

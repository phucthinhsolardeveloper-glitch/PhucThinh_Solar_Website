<script setup lang="ts">
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionFinder from '~/components/solutions/SolutionFinder.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import SolutionsStory from '~/components/solutions/SolutionsStory.vue'
import GridSurface from '~/components/experience-v3/GridSurface.vue'
import { editorialPublicHubResponseStatus } from '#shared/editorial-public-route'

definePageMeta({
  layout: false,
  middleware: 'solutions-public-route',
})

const config = useRuntimeConfig()
const route = useRoute()
const isHubRoute = computed(() => route.path === SOLUTIONS_PUBLIC_PATH || route.path === SOLUTIONS_PUBLIC_PATH.slice(0, -1))
const indexable = computed(() => isHubRoute.value && config.public.solutionsPublicRouteIndexable === true && !!data.value && !error.value)

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error } = await useAsyncData<PublicSolutionsPayload | null>(
  'public-solutions',
  () => isHubRoute.value ? $fetch('/_migration/solutions') : Promise.resolve(null),
  { watch: [isHubRoute] },
)
const responseStatus = editorialPublicHubResponseStatus(
  isHubRoute.value,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

const pageTitle = computed(() => data.value?.seo?.title || data.value?.title || 'Giải pháp — Phúc Thịnh Solar')
const pageDescription = computed(() => data.value?.seo?.description || data.value?.intro?.description || '')
const pageKeywords = computed(() => data.value?.seo?.keywords || '')
const pageImage = computed(() => data.value?.seo?.image || data.value?.intro?.image || undefined)
const links = useMigrationLinks()
useSeoMeta({
  title: computed(() => isHubRoute.value ? pageTitle.value : undefined),
  description: computed(() => isHubRoute.value ? pageDescription.value : undefined),
  robots: computed(() => isHubRoute.value ? (indexable.value && !Object.keys(route.query).length ? 'index, follow' : 'noindex, nofollow, noarchive') : undefined),
})
useHead({ meta: computed(() => isHubRoute.value && pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isHubRoute.value && !!data.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/giai-phap/',
  image: pageImage,
})

const recommendedSolutions = ref<string[]>([])
</script>

<template>
  <NuxtPage v-if="!isHubRoute" />

  <div v-else class="solutions-energy-page text-navy-800">

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giải pháp' }]" />
      </div>
    </div>

    <main class="pts-container solution-main pb-6 min-[561px]:px-5 min-[821px]:px-6 min-[1321px]:px-[34px]">
      <div v-if="error || !data" class="min-h-[45vh] py-12">
        <h1 class="mb-4 text-[clamp(28px,4vw,42px)] font-black tracking-tight">Giải pháp</h1>
        <p class="max-w-[62ch] leading-relaxed text-slate-500">Nội dung giải pháp tạm thời chưa tải được. Vui lòng thử lại sau ít phút hoặc gọi hotline để được tư vấn.</p>
      </div>

      <template v-else>
        <GridSurface v-if="data.intro" :size="32">
          <section class="solution-energy-intro" aria-labelledby="solutions-title">
            <div class="solution-energy-intro-copy">
              <h1 id="solutions-title">{{ data.intro?.title || data.title }}</h1>
              <p v-if="data.intro?.description"><ChunkedText :text="data.intro.description" /></p>
              <div class="solution-intro-links">
                <a v-if="data.intro" class="solution-primary-link" :href="links.generic(data.intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : data.intro.links.consult.href)">{{ data.intro.links.consult.label }} <span aria-hidden="true">→</span></a>
                <a v-if="data.intro" class="solution-secondary-link" :href="links.generic(data.intro.links.explore.href)">{{ data.intro.links.explore.label }} <span aria-hidden="true">↓</span></a>
              </div>
            </div>
            <figure v-if="data.intro?.image" class="solution-energy-intro-image">
              <img :src="data.intro.image" :alt="data.intro.title" width="1200" height="750" fetchpriority="high">
            </figure>
            <SolutionFinder v-else :cards="data.cards" @recommend="recommendedSolutions = $event" />
          </section>
        </GridSurface>
        <h1 v-else class="sr-only">{{ data.title }}</h1>

        <SolutionCards :highlight="recommendedSolutions" :cards="data.cards" :choices-label="data.labels.choices" :empty-label="data.labels.empty" :consult-label="data.labels.consult" />
      </template>
    </main>

    <SolutionsStory v-if="data && !error && data.editorial.content.html" :contents="[data.editorial.content]" index-label="Các phần trong trang Giải pháp" />

    <div v-if="data && !error" class="pts-container pb-6 min-[561px]:px-5 min-[821px]:px-6 min-[1321px]:px-[34px]">
        <SolutionsEquipment :equipment="data.equipment" :category-tree="site.productCategoryTree ?? []" :title="data.labels.equipment" :all-products-url="data.allProductsUrl" :all-products-label="data.labels.allProducts" />
      <SolutionsCta v-if="data.cta" :cta="data.cta" />
    </div>

  </div>
</template>

<style scoped>
/* The chapter bar follows the solution tiles directly. */
.solution-main :deep(#cac-giai-phap) { padding-bottom: 8px; }
.solutions-energy-page { overflow: clip; }
.solution-main { padding-top: 4px; }
.solution-energy-intro { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); align-items: center; gap: clamp(24px, 5vw, 72px); padding: clamp(32px, 5vw, 68px) 0 clamp(30px, 4vw, 54px); }
.solution-energy-intro-copy { max-width: 700px; }
.solution-energy-intro h1 { margin: 0; color: #071b46; font-size: clamp(36px, 5vw, 64px); font-weight: 750; line-height: 1.06; letter-spacing: -.04em; text-wrap: balance; }
.solution-energy-intro-copy > p { max-width: 60ch; margin: 20px 0 0; color: #52627a; font-size: clamp(16px, 1.35vw, 18px); line-height: 1.75; }
.solution-intro-links { display: flex; flex-wrap: wrap; align-items: center; gap: 18px 24px; margin-top: 24px; }
.solution-primary-link, .solution-secondary-link { display: inline-flex; min-height: 44px; align-items: center; gap: 12px; font-weight: 750; text-decoration: none; }
.solution-primary-link { padding: 10px 18px; border-radius: var(--radius-btn); background: var(--pts-gold-gradient); color: var(--color-navy-950); transition: filter .2s ease; }
.solution-primary-link:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.solution-secondary-link { color: #0a2251; }
.solution-energy-intro-image { justify-self: end; width: 100%; max-width: 440px; aspect-ratio: 8 / 5; margin: 0; overflow: hidden; border-radius: var(--radius-chip); }
.solution-energy-intro-image img { display: block; width: 100%; height: 100%; object-fit: cover; }
@media (max-width: 900px) {
  .solution-energy-intro { grid-template-columns: 1fr; gap: 24px; }
  .solution-energy-intro-image { justify-self: start; max-width: 620px; }
}
@media (max-width: 520px) {
  .solution-main { padding-top: 0; }
  .solution-energy-intro { padding: 28px 0 30px; }
  .solution-energy-intro h1 { font-size: clamp(33px, 10vw, 42px); }
  .solution-intro-links { align-items: stretch; flex-direction: column; gap: 6px; }
  .solution-primary-link, .solution-secondary-link { justify-content: center; }
}
/* Phones: the intro is one tight block, with both actions on one line where they fit. */
@media (max-width: 820px) {
  .solution-main { padding-top: 0; }
  .solution-energy-intro { grid-template-columns: minmax(0, 1fr); gap: 16px; padding: 16px 0; }
  .solution-energy-intro-copy > p { margin-top: 10px; font-size: 16px; line-height: 1.6; }
  .solution-intro-links { flex-direction: row; flex-wrap: wrap; align-items: center; gap: 4px 12px; margin-top: 14px; }
  .solution-primary-link { min-height: 48px; gap: 8px; padding-inline: 14px; font-size: 14.5px; }
  .solution-secondary-link { gap: 6px; font-size: 14px; }
}
</style>

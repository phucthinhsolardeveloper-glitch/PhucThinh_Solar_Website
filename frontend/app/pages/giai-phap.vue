<script setup lang="ts">
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { SOLUTIONS_PUBLIC_PATH, isRuntimeFlagEnabled } from '#shared/public-route-gate'
import SolutionsIntro from '~/components/solutions/SolutionsIntro.vue'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import solutionsPublicRoute from '~/middleware/solutions-public-route'
import { editorialPublicHubResponseStatus } from '#shared/editorial-public-route'

definePageMeta({
  layout: false,
  middleware: solutionsPublicRoute,
})

const config = useRuntimeConfig()
const route = useRoute()
const isHubRoute = computed(() => route.path === SOLUTIONS_PUBLIC_PATH || route.path === SOLUTIONS_PUBLIC_PATH.slice(0, -1))
const indexable = computed(() => isRuntimeFlagEnabled(config.public.solutionsPublicRouteEnabled)
  && isHubRoute.value && isRuntimeFlagEnabled(config.public.solutionsPublicRouteIndexable) && !!data.value && !error.value)

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error } = await useAsyncData<PublicSolutionsPayload | null>(
  'public-solutions',
  () => isHubRoute.value ? $fetch('/_migration/solutions') : Promise.resolve(null),
  { watch: [isHubRoute] },
)
const responseStatus = editorialPublicHubResponseStatus(
  isHubRoute.value && config.public.solutionsPublicRouteEnabled,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

const pageTitle = computed(() => data.value?.seo?.title || data.value?.title || 'Giải pháp — Phúc Thịnh Solar')
const pageDescription = computed(() => data.value?.seo?.description || data.value?.intro?.description || '')
const pageKeywords = computed(() => data.value?.seo?.keywords || '')
const pageImage = computed(() => data.value?.seo?.image || data.value?.intro?.image || undefined)
useSeoMeta({
  title: computed(() => isHubRoute.value ? pageTitle.value : undefined),
  description: computed(() => isHubRoute.value ? pageDescription.value : undefined),
  robots: computed(() => isHubRoute.value ? (indexable.value && !Object.keys(route.query).length ? 'index, follow' : 'noindex, nofollow, noarchive') : undefined),
})
useHead({ meta: computed(() => isHubRoute.value && pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isHubRoute.value && isRuntimeFlagEnabled(config.public.solutionsPublicRouteEnabled) && !!data.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/giai-phap/',
  image: pageImage,
})

const tocContainerRef = ref<HTMLElement | null>(null)
const { activeHeadingId, scrollToHeading } = useTocScrollspy({
  contentSelector: '.cms-content',
  headingSelector: 'h2[id], h3[id]',
  tocContainerRef,
  headerOffset: 100,
})
</script>

<template>
  <NuxtPage v-if="!isHubRoute" />

  <div v-else class="text-navy-800">
    <SiteHeader :site="site" />

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giải pháp' }]" />
      </div>
    </div>

    <main class="pts-container pt-[10px] pb-6 min-[801px]:pt-4 max-[800px]:pb-4 min-[561px]:px-5 min-[821px]:px-6 min-[1321px]:px-[34px]">
      <div v-if="error || !data" class="min-h-[45vh] py-12">
        <h1 class="mb-4 text-[clamp(28px,4vw,42px)] font-black tracking-tight">Giải pháp</h1>
        <p class="max-w-[62ch] leading-relaxed text-slate-500">Không thể tải dữ liệu PHP DTO trong môi trường hiện tại.</p>
      </div>

      <template v-else>
        <SolutionsIntro v-if="data.intro" :intro="data.intro" />
        <h1 v-else class="sr-only">{{ data.title }}</h1>

        <SolutionCards :cards="data.cards" :choices-label="data.labels.choices" :empty-label="data.labels.empty" :consult-label="data.labels.consult" />

        <section v-if="data.editorial.content.html" class="pb-[54px]" aria-label="Nội dung tư vấn giải pháp">
          <div
            class="grid gap-8 nav:gap-12"
            :class="data.editorial.toc.rendered ? 'nav:grid-cols-[270px_minmax(0,1fr)]' : ''"
          >
            <nav
              v-if="data.editorial.toc.rendered"
              ref="tocContainerRef"
              class="h-fit max-h-[220px] overflow-y-auto overscroll-contain rounded-[3px] bg-navy-800 p-5 shadow-sm min-[901px]:sticky min-[901px]:top-24 min-[901px]:max-h-[calc(100vh-116px)]"
              aria-label="Mục lục nội dung giải pháp"
            >
              <strong class="mb-3 block border-b border-white/12 pb-2.5 text-[13px] font-extrabold uppercase tracking-wider text-gold-300">Trong trang này</strong>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="item in data.editorial.toc.items" :key="item.id" :class="item.level === 3 ? 'pl-3' : ''">
                  <a
                    :data-toc-id="item.id"
                    :href="'#' + item.id"
                    class="flex min-h-11 items-center border-l-2 py-1 pl-3 text-[13px] leading-[1.45] no-underline transition-all duration-200"
                    :class="[
                      item.level === 2 ? 'font-bold' : 'font-semibold',
                      activeHeadingId === item.id
                        ? 'border-[#e4a849] bg-white/10 text-white font-extrabold rounded-r'
                        : 'border-transparent text-[#c9d5e6] hover:border-gold-400 hover:text-white hover:bg-white/5',
                    ]"
                    :aria-current="activeHeadingId === item.id ? 'true' : undefined"
                    @click.prevent="scrollToHeading(item.id)"
                  >
                    {{ item.label }}
                  </a>
                </li>
              </ul>
            </nav>
            <EditorialRichText :content="data.editorial.content" variant="solution" />
          </div>
        </section>

        <SolutionsEquipment :equipment="data.equipment" :title="data.labels.equipment" :all-products-url="data.allProductsUrl" :all-products-label="data.labels.allProducts" />
        <SolutionsCta v-if="data.cta" :cta="data.cta" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

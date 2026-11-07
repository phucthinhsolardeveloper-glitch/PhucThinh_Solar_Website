<script setup lang="ts">
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import { isPublicSolutionsPayload } from '#shared/contracts/public-solutions'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { publicSeoDescription, publicSeoTitle } from '#shared/public-seo'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'

const route = useRoute()
const config = useRuntimeConfig()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : typeof route.query.slug === 'string' ? route.query.slug : '')
const { data: siteData } = await useFetch<unknown>('/_api/site', {
  default: (): PublicSitePayload => ({ schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }),
})
const { data, error, pending } = await useAsyncData<unknown>(
  () => `public-solution-category-${slug.value}`,
  () => $fetch('/_api/solutions', { query: { category: slug.value } }),
  { watch: [slug] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : { schemaVersion: 1 as const, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] })
const solutions = computed<PublicSolutionsPayload | null>(() => isPublicSolutionsPayload(data.value) ? data.value : null)
const matchingCategory = computed(() => solutions.value?.category?.slug === slug.value ? solutions.value.category : null)
const lastResolvedCategory = shallowRef<NonNullable<typeof matchingCategory.value> | null>(null)
watch(matchingCategory, (value) => { if (value) lastResolvedCategory.value = value }, { immediate: true })
const category = computed(() => matchingCategory.value ?? (pending.value ? lastResolvedCategory.value : null))
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(true, errorStatus, !category.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const hasFilters = computed(() => Object.keys(route.query).length > 0)
const pageTitle = computed(() => publicSeoTitle(category.value?.seo?.title || category.value?.name, 'Giải pháp'))
const pageDescription = computed(() => category.value?.seo?.description || category.value?.description || '')
const socialDescription = computed(() => publicSeoDescription(category.value?.seo?.description, site.value.brand.name))
const pageKeywords = computed(() => category.value?.seo?.keywords || '')
const pageImage = computed(() => category.value?.seo?.image || undefined)
const solutionRailRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(solutionRailRef)
useSlidingPill(solutionRailRef, 'solutions')

useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: computed(() => editorialPublicRouteRobots(true, config.public.solutionsPublicRouteIndexable, !!category.value && !error.value, hasFilters.value)),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!category.value && !error.value),
  title: pageTitle,
  description: socialDescription,
  canonicalPath: computed(() => category.value?.canonicalPath || `/${slug.value}`),
  image: pageImage,
})
</script>

<template>
  <div class="text-navy-800">
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giải pháp', to: '/giai-phap/' }, { label: category?.name || 'Danh mục giải pháp' }]" />
      </div>
    </div>
    <main class="pts-container pb-12">
      <section v-if="error || !solutions || !category" class="min-h-[40vh] py-10" :role="responseStatus === 404 ? 'status' : 'alert'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy danh mục giải pháp' : 'Tạm thời không thể tải danh mục giải pháp' }}</h1>
        <p class="mb-5 text-slate-600">{{ responseStatus === 404 ? 'Danh mục này hiện không tồn tại hoặc đã ẩn.' : 'Nguồn nội dung hiện không khả dụng.' }}</p>
        <div class="flex flex-wrap gap-3">
          <NuxtLink :to="links.generic('/giai-phap/')" class="inline-flex min-h-11 items-center rounded-btn pts-gold-fill px-5 py-2.5 text-sm font-bold no-underline ">Xem tất cả giải pháp</NuxtLink>
          <button type="button" class="inline-flex min-h-11 cursor-pointer items-center rounded-btn bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 hover:bg-gold-400" @click="openConsultation('Tư vấn giải pháp năng lượng')">Nhận tư vấn kỹ thuật →</button>
        </div>
      </section>

      <template v-else>
        <header class="my-7 flex flex-wrap items-end justify-between gap-4 max-tb:my-4 max-tb:gap-3">
          <div class="max-w-[820px]">
            <div class="mb-2 flex flex-wrap items-center gap-2.5">
              <h1 class="m-0 text-[clamp(30px,4.2vw,48px)] font-black leading-tight tracking-tight text-navy-950">{{ category.name }}</h1>
              <span v-if="category.cards.length" class="text-base font-semibold text-slate-500">{{ category.cards.length }} giải pháp</span>
            </div>
            <p class="m-0 text-base leading-relaxed text-slate-600 tb:text-lg">{{ category.description }}</p>
          </div>
          <button
            type="button"
            class="inline-flex min-h-11 shrink-0 cursor-pointer items-center gap-2 rounded-btn bg-gold-500 px-4 py-2 text-xs font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
            @click="openConsultation('Giải pháp: ' + category.name)"
          >
            <span>Tư vấn cấu hình này</span>
            <span aria-hidden="true">→</span>
          </button>
        </header>

        <nav v-if="category.navigation.length" ref="solutionRailRef" class="mb-7 flex gap-2.5 overflow-x-auto py-1 max-tb:mb-1 [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden" aria-label="Danh mục giải pháp">
          <NuxtLink
            class="public-filter-chip flex min-h-11 flex-none items-center whitespace-nowrap px-[15px] text-[15px] font-bold text-navy-800 no-underline"
            :to="links.generic('/giai-phap/')"
          >Tất cả giải pháp</NuxtLink>
          <NuxtLink
            v-for="item in category.navigation"
            :key="item.id"
            class="public-filter-chip flex min-h-11 flex-none items-center whitespace-nowrap px-[15px] text-[15px] font-bold no-underline"
            :to="links.generic(item.url)"
            :class="item.active ? 'text-navy-950' : 'text-navy-800'"
            :aria-current="item.active ? 'page' : undefined"
          >{{ item.name }}</NuxtLink>
        </nav>

        <SolutionCards :cards="category.cards" :choices-label="solutions.labels.choices" :empty-label="solutions.labels.empty" :consult-label="solutions.labels.consult" />
        <SolutionsCta v-if="solutions.cta" :cta="solutions.cta" />
      </template>
    </main>
  </div>
</template>

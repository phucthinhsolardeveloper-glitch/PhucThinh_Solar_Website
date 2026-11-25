<script setup lang="ts">
import { isPublicSeoIndexable, productSeoDescription } from '#shared/public-seo'
import { buildAnalyticsPageContext } from '~/utils/analyticsPageContext'
import { isPublicProductRetiredPayload } from '#shared/contracts/public-product-retired'
import NotFoundExperience from '~/components/NotFoundExperience.vue'
import ProductDetailPage from '~/components/product-detail/ProductDetailPage.vue'
definePageMeta({
  layout: false,
  middleware: 'product-detail-public-route',
})

const config = useRuntimeConfig()
const indexable = computed(() => isPublicSeoIndexable(config.public.productDetailPublicRouteIndexable, true))
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { site, payload, extras, product, error, pending, ready } = useProductDetailData(slug)
await ready
const productErrorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
// A hidden product (taken off sale) answers 410 with its name and the nearest category, instead of a bare error.
const retiredFetch = useFetch<unknown>('/_api/product-retired', { query: computed(() => ({ slug: slug.value })), immediate: false, default: () => null })
if (productErrorStatus === 404) await retiredFetch.execute()
const retired = computed(() => productErrorStatus === 404 && isPublicProductRetiredPayload(retiredFetch.data.value) ? retiredFetch.data.value : null)
if (import.meta.server && productErrorStatus === 404) { if (retired.value) setResponseStatus(410, 'Gone'); else setResponseStatus(404, 'Not Found') }
else if (import.meta.server && (error.value || !product.value)) setResponseStatus(502, 'Bad Gateway')
const productTitle = computed(() => payload.value?.seo.title || product.value?.name || (retired.value ? `${retired.value.name} — Phúc Thịnh Solar` : 'Sản phẩm — Phúc Thịnh Solar'))
const productDescription = computed(() => productSeoDescription({
  seo: payload.value?.seo.description,
  summary: product.value?.summary,
  name: product.value?.name,
  category: payload.value?.breadcrumbs?.at(-1)?.label,
}))
const productKeywords = computed(() => payload.value?.seo.keywords ?? '')
const breadcrumbItems = computed(() => [
  ...(payload.value?.breadcrumbs ?? [{ label: 'Sản phẩm', url: '/san-pham/' }]).map((item) => ({ label: item.label, to: item.url })),
  ...(product.value ? [{ label: product.value.name }] : []),
])

// Wait until Nuxt has rendered the route so the event uses the new page's title and route context.
if (import.meta.client) {
  const nuxtApp = useNuxtApp()
  let lastViewedProductId: number | string | null = null
  const waitForPageRender = async () => {
    const context = window.__ptsAnalyticsRouteContext
    if (!context?.rendered || context.path !== route.path) {
      await new Promise<void>((resolve) => {
        let offFinish = () => {}
        let offLoadingEnd = () => {}
        let offMounted = () => {}
        const finish = () => {
          offFinish()
          offLoadingEnd()
          offMounted()
          resolve()
        }
        offFinish = nuxtApp.hook('page:finish', finish)
        offLoadingEnd = nuxtApp.hook('page:loading:end', finish)
        offMounted = nuxtApp.hook('app:mounted', finish)
      })
    }
    await nextTick()
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()))
  }

  watch(() => product.value?.id, async (id) => {
    if (!id || !product.value || id === lastViewedProductId) return
    await waitForPageRender()
    const p = product.value
    const context = window.__ptsAnalyticsRouteContext
    if (!p || p.id !== id || !context?.rendered || context.path !== route.path) return
    lastViewedProductId = id
    const pricing = payload.value?.pricing
    const price = pricing?.mode === 'price' ? (pricing.salePrice ?? pricing.regularPrice ?? undefined) : undefined
    const category = payload.value?.breadcrumbs?.at(-1)?.label
    const pageContext = buildAnalyticsPageContext(window.location.href, document.title, context.referrer)
    pushEcommerce('view_item', [{ item_id: String(p.id), item_name: p.name, price, quantity: 1, ...(p.brand ? { item_brand: p.brand } : {}), ...(category ? { item_category: category } : {}) }], pageContext)
  }, { immediate: true })
}

useSeoMeta({
  title: productTitle,
  description: productDescription,
  robots: computed(() => indexable.value && !!product.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead({
  meta: computed(() => productKeywords.value ? [{ name: 'keywords', content: productKeywords.value }] : []),
  link: computed(() => product.value?.media?.[0] ? [{ rel: 'preload', as: 'image', href: optimizedImage(product.value.media[0], 960, 960), fetchpriority: 'high' }] : []),
})
usePublicSocialMeta({
  enabled: computed(() => !!product.value && !error.value),
  title: productTitle,
  description: productDescription,
  canonicalPath: computed(() => payload.value?.canonical.url || route.path),
  image: computed(() => product.value?.media?.[0]),
  type: 'product',
})
</script>
<template>
  <div class="text-navy-800">

    <main :aria-busy="pending">
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="breadcrumbItems" />
        </div>
      </div>

      <div v-if="pending && !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang tải thông tin sản phẩm…</p>
      </div>

      <div v-else-if="retired" class="pts-container min-h-[45vh] pt-12 pb-20" role="status">
        <h1 class="mb-3 max-w-[26ch] text-3xl font-black leading-tight tracking-tight">{{ retired.name }}</h1>
        <p class="max-w-[60ch] text-lg leading-relaxed text-slate-600">Sản phẩm này hiện không còn trong danh mục của Phúc Thịnh Solar. Bạn có thể xem các thiết bị cùng loại, hoặc gọi để được tư vấn mẫu thay thế phù hợp.</p>
        <div class="mt-6 flex flex-wrap items-center gap-4">
          <NuxtLink v-if="retired.category" :to="retired.category.url" class="inline-flex min-h-11 items-center rounded-btn bg-gold-500 px-5 py-2.5 font-extrabold text-navy-950 no-underline">Xem {{ retired.category.name }} →</NuxtLink>
          <NuxtLink to="/san-pham/" class="inline-flex min-h-11 items-center font-extrabold text-navy-800 underline decoration-gold-500 decoration-2 underline-offset-4">Xem tất cả sản phẩm</NuxtLink>
          <a v-if="site.brand.hotline" :href="`tel:${site.brand.hotline.replace(/[^0-9+]/g, '')}`" class="inline-flex min-h-11 items-center font-extrabold text-navy-800">Gọi {{ site.brand.hotline }}</a>
        </div>
      </div>

      <NotFoundExperience v-else-if="productErrorStatus === 404" :status-code="404" :hotline="site.brand.hotline" />

      <div v-else-if="error || !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Sản phẩm</h1>
        <p class="text-slate-500">Không thể tải sản phẩm hoặc slug chưa hợp lệ.</p>
      </div>

      <ProductDetailPage v-else :key="product.id" :product="product" :breadcrumbs="payload!.breadcrumbs ?? []" :pricing="payload!.pricing" :content="payload!.content" :applications="payload!.applications" :buying-guides="payload!.buyingGuides" :documents="payload!.documents" :extras="extras" />
    </main>

  </div>
</template>

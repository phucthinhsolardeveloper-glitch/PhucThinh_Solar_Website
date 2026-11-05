<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import { isPublicSolutionsPayload } from '#shared/contracts/public-solutions'
import SolutionsIntro from '~/components/solutions/SolutionsIntro.vue'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: solutionsData, error: solutionsError } = await useFetch<unknown>('/_migration/solutions', { default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const solutions = computed(() => isPublicSolutionsPayload(solutionsData.value) ? solutionsData.value : null)

useSeoMeta({ title: 'Site preview — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4 pt-5">
        <p class="text-xs font-extrabold uppercase tracking-[.1em] text-gold-700">LOCAL MIGRATION PREVIEW</p>
        <SiteBreadcrumb :items="[{ label: 'Site preview' }]" />
      </div>

      <div v-if="siteError || solutionsError || !solutions" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 pb-20 pt-14" role="status">
        <h1 class="mb-4 text-[clamp(28px,4vw,44px)] font-black tracking-tight">Phúc Thịnh Solar — site preview</h1>
        <p class="max-w-[62ch] leading-relaxed text-slate-500">Nguồn PHP local chưa khả dụng hoặc DTO chưa hợp lệ. Preview đang giữ trạng thái an toàn, không hiển thị nội dung giả.</p>
      </div>

      <template v-else>
        <div class="mx-auto w-full max-w-[1766px] px-4 py-8">
          <SolutionsIntro v-if="solutions.intro" :intro="solutions.intro" />
          <h1 v-else class="min-h-[45vh] py-12 text-[clamp(28px,4vw,42px)] font-black tracking-tight">{{ solutions.title }}</h1>
          <SolutionCards :cards="solutions.cards" :choices-label="solutions.labels.choices" :empty-label="solutions.labels.empty" :consult-label="solutions.labels.consult" />
          <section v-if="solutions.editorial.content.html" class="pb-[54px]" aria-label="Nội dung tư vấn giải pháp">
            <EditorialRichText :content="solutions.editorial.content" />
          </section>
          <SolutionsEquipment :equipment="solutions.equipment" :category-tree="site.productCategoryTree ?? []" :title="solutions.labels.equipment" :all-products-url="solutions.allProductsUrl" :all-products-label="solutions.labels.allProducts" />
          <SolutionsCta v-if="solutions.cta" :cta="solutions.cta" />
        </div>
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

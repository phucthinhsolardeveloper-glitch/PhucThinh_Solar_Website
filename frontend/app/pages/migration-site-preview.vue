<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicSolutionsPayload } from '#shared/contracts/public-solutions'
import { isPublicSolutionsPayload } from '#shared/contracts/public-solutions'
import SolutionsIntro from '~/components/solutions/SolutionsIntro.vue'
import SolutionCards from '~/components/solutions/SolutionCards.vue'
import SolutionsEquipment from '~/components/solutions/SolutionsEquipment.vue'
import SolutionsCta from '~/components/solutions/SolutionsCta.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: solutionsData, error: solutionsError } = await useFetch<unknown>('/_migration/solutions', { default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const solutions = computed(() => isPublicSolutionsPayload(solutionsData.value) ? solutionsData.value : null)

useSeoMeta({ title: 'Site preview — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="migration-site-preview">
    <SiteHeader :site="site" />
    <main>
      <div class="pts-container migration-site-preview__intro">
        <div class="migration-site-preview__label">LOCAL MIGRATION PREVIEW</div>
        <SiteBreadcrumb :items="[{ label: 'Site preview' }]" />
      </div>
      <div v-if="siteError || solutionsError || !solutions" class="pts-container migration-site-preview__fallback" role="status">
        <h1>Phúc Thịnh Solar — site preview</h1>
        <p>Nguồn PHP local chưa khả dụng hoặc DTO chưa hợp lệ. Preview đang giữ trạng thái an toàn, không hiển thị nội dung giả.</p>
      </div>
      <template v-else>
        <div class="solutions-preview"><div class="solutions-preview__inner">
          <SolutionsIntro v-if="solutions.intro" :intro="solutions.intro" />
          <h1 v-else class="solutions-fallback">{{ solutions.title }}</h1>
          <SolutionCards :cards="solutions.cards" :choices-label="solutions.labels.choices" :empty-label="solutions.labels.empty" :consult-label="solutions.labels.consult" />
          <p v-if="!solutions.editorial.rendered" class="solutions-editorial-note">Nội dung biên tập dài chưa được render: {{ solutions.editorial.reason }}.</p>
          <SolutionsEquipment :equipment="solutions.equipment" :title="solutions.labels.equipment" :all-products-url="solutions.allProductsUrl" :all-products-label="solutions.labels.allProducts" />
          <SolutionsCta v-if="solutions.cta" :cta="solutions.cta" />
        </div></div>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

<style>
@import '~/assets/css/solutions-preview.css';
.migration-site-preview__intro { padding-top: 20px; }
.migration-site-preview__label { color: var(--pts-gold-dark); font-size: 12px; font-weight: 800; letter-spacing: .1em; }
.migration-site-preview__fallback { min-height: 45vh; padding-top: 56px; padding-bottom: 80px; }
.migration-site-preview__fallback h1 { margin: 0 0 16px; font-size: clamp(28px, 4vw, 44px); }
.migration-site-preview__fallback p { max-width: 62ch; color: var(--pts-muted); line-height: 1.7; }
</style>

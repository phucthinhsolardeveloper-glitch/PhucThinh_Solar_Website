<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

const fallbackSite: PublicSitePayload = {
  schemaVersion: 1,
  brand: { name: 'Phúc Thịnh Solar', hotline: '' },
  productCategories: [],
}

const { data, error } = await useFetch<unknown>('/_migration/site', {
  default: () => fallbackSite,
})

const site = computed<PublicSitePayload>(() => (isPublicSitePayload(data.value) ? data.value : fallbackSite))

useSeoMeta({
  title: 'PTS Nuxt shared shell — internal preview',
  description: 'Internal preview for the Nuxt migration shell.',
  robots: 'noindex, nofollow, noarchive',
})
</script>

<template>
  <div class="pts-preview-page">
    <SiteHeader :site="site" />

    <main>
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Nuxt shared shell preview' }]" />
        <section class="pts-preview-intro" aria-labelledby="preview-title">
          <h1 id="preview-title">Nuxt shared shell preview</h1>
          <p>Route nội bộ để kiểm tra header, menu danh mục, breadcrumb và footer. Không phải route public.</p>
          <p v-if="error" class="pts-preview-warning" role="status">Nguồn dữ liệu public chưa khả dụng; đang hiển thị trạng thái thiếu dữ liệu an toàn.</p>
        </section>
      </div>

      <div class="pts-preview-spacer" aria-hidden="true" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

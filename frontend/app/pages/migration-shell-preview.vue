<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

const fallbackSite: PublicSitePayload = {
  schemaVersion: 1,
  brand: { name: 'Phúc Thịnh Solar', hotline: '' },
  productCategories: [],
}

const { data, error } = await useFetch<unknown>('/_api/site', {
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
  <!--
    Trang smoke-test nội bộ cho shell dùng chung (header/menu/breadcrumb/
    footer) — không phải route public, không có ảnh production tương ứng.
    Các class pts-preview-* cũ chưa từng có định nghĩa CSS ở bất kỳ đâu trong
    repo (kể cả trước khi shell.css bị xoá) — dangling từ trước, không phải
    hồi quy do đợt chuyển đổi này.
  -->
  <div class="text-navy-800">

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Nuxt shared shell preview' }]" />
        <section class="py-8" aria-labelledby="preview-title">
          <h1 id="preview-title" class="mb-3 text-2xl font-black tracking-tight">Nuxt shared shell preview</h1>
          <p class="text-slate-500">Route nội bộ để kiểm tra header, menu danh mục, breadcrumb và footer. Không phải route public.</p>
          <p v-if="error" class="mt-3 rounded-btn bg-slate-50 p-3 text-slate-500" role="status">Nguồn dữ liệu public chưa khả dụng; đang hiển thị trạng thái thiếu dữ liệu an toàn.</p>
        </section>
      </div>

      <div class="h-16" aria-hidden="true" />
    </main>

  </div>
</template>

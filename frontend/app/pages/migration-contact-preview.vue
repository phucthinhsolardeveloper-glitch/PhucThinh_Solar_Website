<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
import { isPublicContactPayload } from '#shared/contracts/public-contact'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error, pending } = await useFetch<unknown>('/_migration/contact')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const contact = computed(() => isPublicContactPayload(data.value) ? data.value : null)
useSeoMeta({ title: computed(() => contact.value?.seo.title || 'Liên hệ — internal preview'), description: computed(() => contact.value?.seo.description || ''), robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="min-h-screen bg-white font-sans text-navy-950">
    <SiteHeader :site="site" />
    <main class="pb-16 tb:pb-20">
      <div class="mx-auto w-full max-w-(--container-page) px-4 tb:px-6 ul:px-(--spacing-gutter)">
        <SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" />
        <div v-if="pending" class="py-20" role="status" aria-live="polite"><h1 class="text-3xl font-extrabold text-navy-800">Đang tải trang liên hệ…</h1></div>
        <div v-else-if="error || !contact" class="py-20" role="status"><h1 class="text-3xl font-extrabold text-navy-800">Liên hệ — internal preview</h1><p class="mt-3 max-w-2xl text-navy-900/65">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu liên hệ giả.</p></div>
        <template v-else>
          <ContactHero :contact="contact" />
          <ContactFormHandoff :labels="contact.labels" />
          <ContactLocations :locations="contact.locations" :title="contact.labels.locations" />
        </template>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

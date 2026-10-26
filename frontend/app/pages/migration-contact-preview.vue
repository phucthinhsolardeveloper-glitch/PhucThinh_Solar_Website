<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
import { isPublicContactPayload } from '#shared/contracts/public-contact'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/contact')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const contact = computed(() => isPublicContactPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Liên hệ — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="contact-preview"><SiteHeader :site="site" /><main class="contact-preview__main"><div class="pts-container"><SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" /><div v-if="error || !contact" class="contact-preview__fallback" role="status"><h1>Liên hệ — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu liên hệ giả.</p></div><template v-else><ContactHero :contact="contact" /><ContactInfo :contact="contact.contact" /><p v-if="contact.intro" class="contact-preview__intro">{{ contact.intro }}</p><ContactLocations :locations="contact.locations" :title="contact.labels.locations" /><aside class="contact-preview__handoff"><strong>{{ contact.labels.formCta }}</strong><span>{{ contact.labels.formNote }} Nuxt preview chưa nhận POST.</span><a href="/lien-he/">Mở trang PHP</a></aside></template></div></main><SiteFooter :site="site" /></div>
</template>
<style>@import '~/assets/css/contact-preview.css';</style>

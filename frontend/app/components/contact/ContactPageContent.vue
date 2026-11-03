<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
import { isPublicContactPayload } from '#shared/contracts/public-contact'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const props = withDefaults(defineProps<{ preview?: boolean }>(), { preview: false })
const config = useRuntimeConfig()
const indexable = computed(() => !props.preview && config.public.contactPublicRouteIndexable === true
  && !!contact.value && !siteError.value && !error.value)
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/contact')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const contact = computed(() => isPublicContactPayload(data.value) ? data.value : null)
const pageTitle = computed(() => props.preview ? 'Liên hệ — internal preview' : contact.value?.seo.title || 'Liên hệ — Phúc Thịnh Solar')
const pageKeywords = computed(() => props.preview ? '' : contact.value?.seo.keywords || '')

if (import.meta.server && !props.preview && (siteError.value || !isPublicSitePayload(siteData.value) || error.value || !contact.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

useSeoMeta({
  title: pageTitle,
  description: () => contact.value?.seo.description || '',
  robots: () => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive',
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !props.preview && !!contact.value && !error.value && !siteError.value),
  title: pageTitle,
  description: computed(() => contact.value?.seo.description || ''),
  canonicalPath: '/lien-he/',
  image: computed(() => contact.value?.seo.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" />
      </div>

      <div class="pts-container">
        <div v-if="error || !contact" class="min-h-[45vh] py-12" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">{{ pageTitle }}</h1>
          <p class="text-slate-500">Thông tin liên hệ tạm thời chưa tải được. Vui lòng thử lại sau ít phút.</p>
        </div>

        <template v-else>
          <ContactHero :contact="contact" :page-title="contact.title" />

          <section class="py-12 nav:py-16">
            <ContactLeadForm />
          </section>

          <ContactLocations :locations="contact.locations" :title="contact.labels.locations" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

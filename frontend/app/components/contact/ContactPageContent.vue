<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
import { isPublicContactPayload } from '#shared/contracts/public-contact'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const props = withDefaults(defineProps<{ preview?: boolean }>(), { preview: false })
const config = useRuntimeConfig()
const indexable = computed(() => !props.preview && isRuntimeFlagEnabled(config.public.contactPublicRouteIndexable)
  && !!contact.value && !siteError.value && !error.value)
const { data: siteData, error: siteError } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/contact')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const contact = computed(() => isPublicContactPayload(data.value) ? data.value : null)
const pageTitle = computed(() => props.preview ? 'Liên hệ — internal preview' : contact.value?.seo.title || 'Liên hệ — Phúc Thịnh Solar')

if (import.meta.server && !props.preview && (siteError.value || !isPublicSitePayload(siteData.value) || error.value || !contact.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

useSeoMeta({
  title: pageTitle,
  description: () => contact.value?.seo.description || '',
  robots: () => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive',
})
usePublicSocialMeta({
  enabled: computed(() => !props.preview && isRuntimeFlagEnabled(config.public.contactPublicRouteEnabled) && !!contact.value && !error.value && !siteError.value),
  title: pageTitle,
  description: computed(() => contact.value?.seo.description || ''),
  canonicalPath: '/lien-he/',
  image: computed(() => contact.value?.visual?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="border-b border-slate-200/80 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" />
        </div>
      </div>

      <div class="pts-container">
        <div v-if="error || !contact" class="min-h-[45vh] py-12" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">{{ pageTitle }}</h1>
          <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. Không hiển thị dữ liệu liên hệ giả.</p>
        </div>

        <template v-else>
          <ContactHero :contact="contact" />

          <section class="my-10">
            <ContactLeadForm />
          </section>

          <ContactLocations :locations="contact.locations" :title="contact.labels.locations" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

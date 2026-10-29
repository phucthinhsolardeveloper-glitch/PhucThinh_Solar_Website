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

useSeoMeta({
  title: computed(() => contact.value?.seo.title || 'Liên hệ — Phúc Thịnh Solar'),
  description: computed(() => contact.value?.seo.description || ''),
  robots: computed(() => contact.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
const requestUrl = useRequestURL()
useHead({ link: [{ rel: 'canonical', href: computed(() => new URL('/lien-he/', requestUrl.origin).href) }] })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4 tb:px-6 nav:px-(--spacing-gutter)">
        <SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" />

        <div v-if="error || !contact" class="min-h-[45vh] py-12" role="status">
            <h1 class="mb-3 text-3xl font-black tracking-tight">Liên hệ</h1>
            <p class="text-slate-500">Không thể tải dữ liệu liên hệ. Vui lòng thử lại sau.</p>
        </div>

        <template v-else>
          <ContactHero :contact="contact" />

          <section class="-mx-3 mb-16 grid items-start gap-7 rounded-btn bg-[#f4f7fb] px-7 py-10 max-[767px]:-mx-4 max-[767px]:rounded-none max-[767px]:px-4 min-[1101px]:grid-cols-[minmax(245px,315px)_minmax(0,1fr)] min-[1101px]:gap-x-[clamp(48px,6vw,92px)] min-[1101px]:px-[clamp(26px,4vw,64px)] min-[1101px]:py-[clamp(38px,4vw,58px)]">
            <div>
              <h2 class="mb-3 text-[clamp(30px,2.7vw,43px)] font-black uppercase leading-[1.12] tracking-[-.035em] text-navy-950 min-[1101px]:max-w-[315px]">{{ contact.labels.formCta }}</h2>
              <p class="m-0 max-w-[290px] text-base leading-[1.75] text-slate-500">Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ để tư vấn giải pháp phù hợp.</p>
            </div>
            <ContactLeadForm />
          </section>

          <ContactLocations :locations="contact.locations" :title="contact.labels.locations" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

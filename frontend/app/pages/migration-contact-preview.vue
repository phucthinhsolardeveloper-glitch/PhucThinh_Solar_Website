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
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Liên hệ' }]" />

        <div v-if="error || !contact" class="min-h-[45vh] py-12" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">Liên hệ — internal preview</h1>
          <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu liên hệ giả.</p>
        </div>

        <template v-else>
          <ContactHero :contact="contact" />

          <!--
            ĐỐI CHIẾU ẢNH PRODUCTION: không có section "Thông tin liên hệ" dạng
            lưới 4 cột độc lập (hotline/email/address/giờ làm việc) như
            component ContactInfo cũ mô tả. Company/hotline/email/giờ làm việc
            đã hiện trong panel navy của ContactHero; địa chỉ trụ sở trùng với
            mục đầu tiên của ContactLocations bên dưới. Bỏ hẳn ContactInfo
            thay vì giữ một section không khớp ảnh thật.

            contact.intro khớp đúng đoạn giới thiệu bên trái khối form PHP
            ("Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ..."),
            không phải một đoạn văn rời rạc như preview cũ đặt nó.
          -->
          <section class="my-10 grid items-start gap-8 nav:grid-cols-[minmax(0,.8fr)_minmax(0,1.2fr)] nav:gap-12">
            <div class="py-2">
              <h2 class="mb-3 text-2xl font-black tracking-tight">{{ contact.labels.formCta }}</h2>
              <p v-if="contact.intro" class="mb-2 max-w-[60ch] leading-relaxed text-slate-500">{{ contact.intro }}</p>
              <p class="mb-0 text-sm text-slate-500">{{ contact.labels.formNote }}</p>
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

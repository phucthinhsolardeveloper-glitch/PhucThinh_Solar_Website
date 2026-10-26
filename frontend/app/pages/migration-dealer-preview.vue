<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
import { isPublicDealerPayload } from '#shared/contracts/public-dealer'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import DealerSections from '~/components/dealer/DealerSections.vue'
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error, pending } = await useAsyncData<unknown>('migration-dealer', () => $fetch('/_migration/dealer'))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const dealer = computed(() => isPublicDealerPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Đại lý — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="min-h-screen bg-white font-sans text-navy-950">
    <SiteHeader :site="site" />
    <main>
      <div class="mx-auto w-full max-w-(--container-page) px-4 tb:px-6 ul:px-(--spacing-gutter)"><SiteBreadcrumb :items="[{ label: 'Hợp tác đại lý' }]" /></div>
      <div v-if="pending" class="mx-auto w-full max-w-(--container-page) px-4 py-20 tb:px-6 ul:px-(--spacing-gutter)" role="status" aria-live="polite"><h1 class="text-3xl font-extrabold text-navy-800">Đang tải trang đại lý…</h1></div>
      <div v-else-if="error || !dealer" class="mx-auto w-full max-w-(--container-page) px-4 py-20 tb:px-6 ul:px-(--spacing-gutter)" role="status"><h1 class="text-3xl font-extrabold text-navy-800">Đại lý — internal preview</h1><p class="mt-3 max-w-2xl text-navy-900/65">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung đại lý giả.</p></div>
      <template v-else>
        <section class="relative overflow-hidden bg-navy-950" aria-labelledby="dealer-title">
          <img v-if="dealer.hero" class="absolute inset-0 h-full w-full object-cover" :src="dealer.hero.image" :alt="dealer.hero.title" width="1920" height="600">
          <div class="absolute inset-0 bg-navy-950/65" aria-hidden="true" />
          <div class="relative mx-auto flex min-h-[300px] w-full max-w-(--container-page) items-center px-4 py-14 tb:px-6 nav:min-h-[420px] ul:px-(--spacing-gutter)">
            <h1 id="dealer-title" class="max-w-3xl text-[clamp(2.25rem,4.1vw,4.5rem)] font-extrabold leading-[1.06] tracking-[-0.035em] text-white">{{ dealer.title }}</h1>
          </div>
        </section>
        <nav class="border-b border-navy-100" aria-label="Điều hướng trang đại lý">
          <div class="mx-auto grid w-full max-w-(--container-page) grid-cols-3 px-4 tb:px-6 ul:px-(--spacing-gutter)">
            <a class="flex min-h-16 items-center justify-center px-3 text-center text-sm font-bold text-navy-800 hover:text-gold-700" href="#dang-ky-dai-ly">Đăng ký hợp tác</a>
            <a class="flex min-h-16 items-center justify-center px-3 text-center text-sm font-bold text-navy-800 hover:text-gold-700" href="#nang-luc-phan-phoi">Sản phẩm phân phối</a>
            <a class="flex min-h-16 items-center justify-center px-3 text-center text-sm font-bold text-navy-800 hover:text-gold-700" href="#ho-tro-dai-ly">Quyền lợi đại lý</a>
          </div>
        </nav>
        <section id="nang-luc-phan-phoi" class="mx-auto grid w-full max-w-(--container-page) gap-10 px-4 py-14 tb:px-6 nav:grid-cols-[minmax(0,1fr)_440px] nav:items-start nav:py-20 ul:px-(--spacing-gutter)">
          <div class="min-w-0">
            <p v-if="dealer.overview.description" class="whitespace-pre-line text-base leading-8 text-navy-900/68">{{ dealer.overview.description }}</p>
            <h2 v-if="dealer.overview.title" class="mt-12 text-3xl font-extrabold uppercase leading-tight tracking-[-0.025em] text-navy-800 tb:text-4xl">{{ dealer.overview.title }}</h2>
            <div v-if="dealer.overview.products.length" class="mt-8 grid gap-x-5 gap-y-7 tb:grid-cols-2 nav:grid-cols-3">
              <a v-for="product in dealer.overview.products" :key="product.title + product.image" class="group min-w-0 text-navy-950" :href="product.href || undefined" target="_blank" rel="noopener noreferrer">
                <span class="block aspect-[8/5] overflow-hidden rounded-[3px] bg-navy-50"><img v-if="product.image" class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]" :src="product.image" :alt="product.title" loading="lazy"></span>
                <span class="mt-2 block font-bold leading-6">{{ product.title }}</span>
              </a>
            </div>
          </div>
          <DealerRegistrationHandoff :form="dealer.form" />
        </section>
        <DealerSections :dealer="dealer" />
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

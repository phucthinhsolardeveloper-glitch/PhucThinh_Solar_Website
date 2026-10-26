<script setup lang="ts">
import type { PublicAboutPayload } from '#shared/contracts/public-about'
import { isPublicAboutPayload } from '#shared/contracts/public-about'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import AboutPreviewPage from '~/components/about/AboutPreviewPage.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData('migration-about', () => $fetch('/_migration/about'))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const about = computed(() => isPublicAboutPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Giới thiệu — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="about-preview"><SiteHeader :site="site" /><div class="pts-container"><SiteBreadcrumb :items="[{ label: 'Giới thiệu' }]" /></div><div v-if="error || !about" class="about-fallback" role="status"><h1>Giới thiệu — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu giới thiệu giả.</p></div><AboutPreviewPage v-else :about="about" /><SiteFooter :site="site" /></div>
</template>

<style>@import '~/assets/css/about-preview.css';</style>

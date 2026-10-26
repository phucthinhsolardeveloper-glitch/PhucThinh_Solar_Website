<script setup lang="ts">
import { isPublicAboutPayload } from '#shared/contracts/public-about'
import AboutPreviewPage from '~/components/about/AboutPreviewPage.vue'

definePageMeta({
  layout: 'migration',
})

const { data, error, pending } = await useAsyncData<unknown>('migration-about', () => $fetch('/_migration/about'), { default: () => null })
const about = computed(() => isPublicAboutPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Giới thiệu — internal preview' })
</script>

<template>
  <div>
    <div class="mx-auto w-[min(calc(100%-32px),2200px)]">
      <SiteBreadcrumb :items="[{ label: 'Giới thiệu' }]" />
    </div>
    <div v-if="pending" class="mx-auto min-h-[40vh] w-[min(calc(100%-32px),2200px)] py-16 text-center" role="status" aria-live="polite">
      <h1 class="text-3xl font-bold text-[#061b46]">Giới thiệu — internal preview</h1>
      <p class="mt-3 leading-[1.68] text-[#52627c]">Đang tải dữ liệu CMS…</p>
    </div>
    <div v-else-if="error || !about" class="mx-auto min-h-[40vh] w-[min(calc(100%-32px),2200px)] py-16 text-center" role="status">
      <h1 class="text-3xl font-bold text-[#061b46]">Giới thiệu — internal preview</h1>
      <p class="mt-3 leading-[1.68] text-[#52627c]">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu giới thiệu giả.</p>
    </div>
    <AboutPreviewPage v-else :about="about" />
  </div>
</template>

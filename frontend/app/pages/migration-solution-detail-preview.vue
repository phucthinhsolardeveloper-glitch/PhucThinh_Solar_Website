<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../shared/contracts/public-solution-detail'
import SolutionDetailPage from '~/components/solution-detail/SolutionDetailPage.vue'
useSeoMeta({ title: 'Giải pháp — internal preview', robots: 'noindex, nofollow, noarchive' })
const slug = String(useRoute().query.slug || '')
const { data, error } = await useAsyncData<PublicSolutionDetailPayload>(`migration-solution-detail-${slug}`, () => $fetch('/_migration/solution-detail', { query: { slug } }))
</script>
<template><div><main v-if="error || !data" class="solution-detail-fallback"><div><h1>Giải pháp — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung giả.</p></div></main><SolutionDetailPage v-else :data="data" /></div></template>
<style>@import '~/assets/css/solution-detail-preview.css';</style>

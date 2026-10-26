<script setup lang="ts">
import type { PublicSolutionsPayload } from '../../shared/contracts/public-solutions'
useSeoMeta({ title: 'Giải pháp — internal preview', robots: 'noindex, nofollow, noarchive' })
const { data, error } = await useAsyncData<PublicSolutionsPayload>('migration-solutions', () => $fetch('/_migration/solutions'))
</script>
<template>
  <main class="solutions-preview"><div class="solutions-preview__inner">
    <div v-if="error || !data" class="solutions-fallback"><h1>Giải pháp — internal preview</h1><p>Không thể tải dữ liệu PHP DTO trong môi trường hiện tại. Preview không hiển thị sản phẩm hoặc nội dung giả.</p><p>Editorial rich-text đang được trì hoãn tới khi có sanitizer/allowlist gate.</p></div>
    <template v-else><SolutionsIntro v-if="data.intro" :intro="data.intro" /><h1 v-else class="solutions-fallback">{{ data.title }}</h1><SolutionCards :cards="data.cards" :choices-label="data.labels.choices" :empty-label="data.labels.empty" :consult-label="data.labels.consult" /><p v-if="!data.editorial.rendered" class="solutions-editorial-note">Nội dung biên tập dài chưa được render: {{ data.editorial.reason }}.</p><SolutionsEquipment :equipment="data.equipment" :title="data.labels.equipment" :all-products-url="data.allProductsUrl" :all-products-label="data.labels.allProducts" /><SolutionsCta v-if="data.cta" :cta="data.cta" /></template>
  </div></main>
</template>
<style>@import '~/assets/css/solutions-preview.css';</style>

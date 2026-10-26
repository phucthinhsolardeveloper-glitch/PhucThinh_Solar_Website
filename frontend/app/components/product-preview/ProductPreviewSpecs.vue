<script setup lang="ts">
import type { PublicProductSpecification } from '#shared/contracts/public-product-detail'
defineProps<{ specifications: PublicProductSpecification[] }>()
</script>
<template>
  <section class="product-preview-specs" aria-labelledby="product-preview-specs-title">
    <h2 id="product-preview-specs-title">Thông số kỹ thuật</h2>
    <p v-if="!specifications.length">CMS chưa cung cấp thông số được allowlist.</p>
    <table v-else><tbody><tr v-for="item in specifications" :key="`${item.key}-${item.label}`"><th scope="row">{{ item.label }}</th><td>{{ item.value }}</td></tr></tbody></table>
  </section>
</template>

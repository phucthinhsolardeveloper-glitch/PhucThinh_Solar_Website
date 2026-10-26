<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
defineProps<{ data: PublicSolutionDetailPayload }>()
</script>
<template>
  <main class="solution-detail-preview"><div class="solution-detail-preview__inner">
    <nav class="solution-detail-nav" aria-label="Các giải pháp khác"><a :href="data.navigation.allUrl">Tất cả giải pháp</a><a v-for="item in data.navigation.items" :key="item.slug" :class="{ 'is-active': item.active }" :href="item.url">{{ item.title }}</a></nav>
    <article class="solution-detail-article">
      <header class="solution-detail-hero"><div><h1>{{ data.solution.title }}</h1><p v-if="data.solution.summary">{{ data.solution.summary }}</p><a class="solution-detail-button" :href="data.cta.href">{{ data.cta.label }} <span aria-hidden="true">→</span></a></div><img v-if="data.solution.image" :src="data.solution.image" :alt="data.solution.title" width="1600" height="1000" fetchpriority="high"></header>
      <div class="solution-detail-body"><div><p v-for="paragraph in data.solution.paragraphs" :key="paragraph">{{ paragraph }}</p><section v-if="data.solution.extraParagraphs.length" aria-label="Thông tin bổ sung"><p v-for="paragraph in data.solution.extraParagraphs" :key="`extra-${paragraph}`">{{ paragraph }}</p></section><p v-if="!data.solution.paragraphs.length && !data.solution.extraParagraphs.length" class="solution-detail-muted">Nội dung chi tiết đang được cập nhật.</p></div></div>
      <section v-if="data.solution.gallery.length" class="solution-detail-gallery" aria-labelledby="solution-gallery-title"><h2 id="solution-gallery-title">Hình ảnh giải pháp</h2><div><a v-for="image in data.solution.gallery" :key="image" :href="image"><img :src="image" :alt="data.solution.title" width="960" height="600" loading="lazy" decoding="async"></a></div></section>
      <p class="solution-detail-note">Nội dung biên tập HTML và mục lục chưa được render: {{ data.editorial.reason }}.</p>
    </article>
    <section v-if="data.equipment.length" class="solution-detail-equipment" aria-labelledby="solution-equipment-title"><header><h2 id="solution-equipment-title">Thiết bị liên quan</h2><a href="/san-pham/">Xem tất cả sản phẩm <span aria-hidden="true">→</span></a></header><div><a v-for="item in data.equipment" :key="item.url" :href="item.url"><img v-if="item.image" :src="item.image" :alt="" width="80" height="80" loading="lazy"><span><strong>{{ item.name }}</strong><small v-if="item.description">{{ item.description }}</small></span><span aria-hidden="true">→</span></a></div></section>
  </div></main>
</template>

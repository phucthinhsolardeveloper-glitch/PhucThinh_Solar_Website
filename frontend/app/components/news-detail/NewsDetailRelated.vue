<script setup lang="ts">
import type { PublicNewsDetailRelated } from '#shared/contracts/public-news-detail'
defineProps<{ items: PublicNewsDetailRelated[] }>()
</script>
<template><aside v-if="items.length" class="news-detail-related"><h2>Tin liên quan</h2><ul><li v-for="item in items" :key="item.id"><a :href="item.canonical">{{ item.title }}</a><p v-if="item.description">{{ item.description }}</p></li></ul></aside></template>

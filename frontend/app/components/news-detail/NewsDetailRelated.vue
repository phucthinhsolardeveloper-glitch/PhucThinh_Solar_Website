<script setup lang="ts">
import type { PublicNewsDetailRelated } from '#shared/contracts/public-news-detail'

defineProps<{ items: PublicNewsDetailRelated[] }>()
</script>

<template>
  <aside v-if="items.length" class="pt-4">
    <h2 class="mb-3 text-xl font-black text-navy-800">Tin liên quan</h2>
    <ul class="m-0 list-none p-0">
      <li v-for="item in items" :key="item.id" class="border-b border-gray-200 py-4 last:border-b-0">
        <a class="font-semibold text-gold-700 no-underline" :href="item.canonical">{{ item.title }}</a>
        <p v-if="item.description" class="mt-1 text-slate-500">{{ item.description }}</p>
      </li>
    </ul>
  </aside>
</template>

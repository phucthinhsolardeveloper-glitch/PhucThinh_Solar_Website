<script setup lang="ts">
import type { PublicSolutionEquipment } from '../../../shared/contracts/public-solutions'
defineProps<{ equipment: PublicSolutionEquipment[]; title: string; allProductsUrl: string; allProductsLabel: string }>()
</script>
<template>
  <section v-if="equipment.length" class="solutions-equipment" aria-labelledby="solutions-equipment-title"><header><h2 id="solutions-equipment-title">{{ title }}</h2><a :href="allProductsUrl">{{ allProductsLabel }} <span aria-hidden="true">→</span></a></header><div class="solutions-equipment__grid"><a v-for="item in equipment" :key="item.url" class="equipment-link" :href="item.url"><img v-if="item.image" :src="item.image" alt="" width="80" height="80" loading="lazy" decoding="async"><span><strong>{{ item.name }}</strong><small v-if="item.description">{{ item.description }}</small></span><span class="equipment-link__arrow" aria-hidden="true">→</span></a></div></section>
</template>

<script setup lang="ts">
import type { PublicProjectEquipment } from '#shared/contracts/public-project-detail'
defineProps<{ items: PublicProjectEquipment[] }>()
</script>
<template>
  <section v-if="items.length" class="project-detail-equipment" aria-labelledby="project-equipment-title">
    <h2 id="project-equipment-title">Thiết bị liên quan</h2>
    <div class="project-detail-equipment__grid">
      <a v-for="item in items" :key="item.id" :href="item.url" class="project-detail-equipment__item">
        <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy" decoding="async">
        <span><strong>{{ item.name }}</strong><small v-if="item.code">Mã: {{ item.code }}</small></span>
      </a>
    </div>
  </section>
</template>

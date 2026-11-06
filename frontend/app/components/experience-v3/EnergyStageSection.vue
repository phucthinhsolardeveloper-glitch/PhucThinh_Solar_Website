<script setup lang="ts">
import EnergyScene3DExperience from './EnergyScene3DExperience.vue'

const atmosphere = ref('day')
</script>

<template>
  <section class="energy-showcase" :data-atmosphere="atmosphere" aria-labelledby="energy-showcase-title">
    <h2 id="energy-showcase-title" class="sr-only">Khám phá mô hình 3D năng lượng mặt trời</h2>
    <EnergyScene3DExperience @atmosphere="atmosphere = $event" />
    <p class="sr-only">Mô hình minh họa nguyên lý, không thể hiện sản lượng hay cấu hình của một công trình thực tế. Chế độ dự phòng chỉ cấp các tải ưu tiên tương thích.</p>
  </section>
</template>

<style scoped>
.energy-showcase {
  width: 100%;
  height: clamp(600px, 100svh, 980px);
  overflow: hidden;
  background: #f0f5f3;
  transition: background-color .5s ease;
}
.energy-showcase[data-atmosphere='dusk'] { background: #efe8de; }
.energy-showcase[data-atmosphere='night'] { background: #091a2c; }
@media (max-width: 700px) {
  .energy-showcase { height: max(560px, calc(100svh - 80px)); }
}
@media (prefers-reduced-motion: reduce) {
  .energy-showcase { transition: none; }
}
</style>

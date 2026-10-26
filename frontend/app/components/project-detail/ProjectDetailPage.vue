<script setup lang="ts">
import type { PublicProjectDetail } from '#shared/contracts/public-project-detail'
import ProjectDetailEquipment from './ProjectDetailEquipment.vue'
import ProjectDetailGallery from './ProjectDetailGallery.vue'
defineProps<{ project: PublicProjectDetail }>()
</script>
<template>
  <article class="project-detail">
    <header class="project-detail__hero">
      <div class="project-detail__hero-copy">
        <p class="project-detail__label">Dự án</p>
        <h1>{{ project.title }}</h1>
        <p v-if="project.summary" class="project-detail__summary">{{ project.summary }}</p>
      </div>
      <img v-if="project.coverImage" :src="project.coverImage" :alt="project.title" class="project-detail__cover" fetchpriority="high" decoding="async">
    </header>
    <dl class="project-detail__facts">
      <template v-if="project.category"><div><dt>Danh mục</dt><dd>{{ project.category.name }}</dd></div></template>
      <template v-if="project.client"><div><dt>Khách hàng</dt><dd>{{ project.client }}</dd></div></template>
      <template v-if="project.address"><div><dt>Địa chỉ</dt><dd>{{ project.address }}</dd></div></template>
      <template v-if="project.startDate || project.endDate"><div><dt>Thời gian thực hiện</dt><dd>{{ project.startDate || 'Đang cập nhật' }}<span v-if="project.endDate"> – {{ project.endDate }}</span></dd></div></template>
    </dl>
    <p class="project-detail__content-note">Nội dung biên tập chi tiết chưa được render trong preview vì đang chờ lớp kiểm duyệt HTML CKEditor.</p>
    <ProjectDetailGallery :items="project.gallery" />
    <ProjectDetailEquipment :items="project.relatedEquipment" />
  </article>
</template>

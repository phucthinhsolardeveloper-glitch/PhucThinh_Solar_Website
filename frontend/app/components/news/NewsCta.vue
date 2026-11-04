<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'

const props = defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
const backgroundImage = computed(() => props.cta.image || '')
</script>

<template>
  <PublicCtaBanner
    class="mt-7"
    variant="editorial"
    :image="backgroundImage"
    title-id="news-contact-title"
    :title="cta.title"
    :description="cta.description"
    :hotline="cta.hotline?.text"
    :hotline-label="phoneLabel"
    :button-label="cta.buttonLabel || 'Nhận tư vấn ngay'"
    :subject="'Kiến thức: ' + cta.title"
  />
</template>

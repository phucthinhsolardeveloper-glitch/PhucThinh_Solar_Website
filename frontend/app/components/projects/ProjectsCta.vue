<script setup lang="ts">
import type { PublicProjectsCta } from '#shared/contracts/public-projects'

const props = defineProps<{ cta: PublicProjectsCta; label: string }>()
const backgroundImage = computed(() => {
  const src = props.cta.image
  return /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? thumbUrl(src, 1600, 500)
    : ''
})
const trustIcons = { headset: 'i-lucide-headset', cogs: 'i-lucide-settings-2', shield: 'i-lucide-shield-check' } as const
const trustItems = computed(() => (props.cta.trust?.length ? props.cta.trust : [
  { label: 'Tư vấn tận tâm', image: '', icon: 'headset' as const },
  { label: 'Giải pháp tối ưu', image: '', icon: 'cogs' as const },
  { label: 'Đồng hành lâu dài', image: '', icon: 'shield' as const },
]).map((item) => ({ label: item.label, image: item.image, icon: trustIcons[item.icon] })))
</script>

<template>
  <section class="mt-[38px] min-[901px]:mt-[50px]">
    <PublicCtaBanner
      variant="editorial"
      :image="backgroundImage"
      title-id="projects-contact-title"
      :title="cta.title"
      :description="cta.description"
      :trust="trustItems"
      :hotline="cta.hotline?.text"
      :button-label="cta.buttonLabel || label"
      :subject="'Dự án: ' + cta.title"
    />
  </section>
</template>

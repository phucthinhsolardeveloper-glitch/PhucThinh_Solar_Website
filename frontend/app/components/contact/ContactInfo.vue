<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
defineProps<{ contact: PublicContactPayload['contact'] }>()
</script>
<template>
  <section class="contact-preview__info" aria-labelledby="contact-info-title"><h2 id="contact-info-title">Thông tin liên hệ</h2><div class="contact-preview__info-grid"><a v-if="contact.hotline" :href="`tel:${contact.hotline.replace(/[^+\d]/g, '')}`"><span>Hotline</span><strong>{{ contact.hotline }}</strong></a><a v-if="contact.email" :href="`mailto:${contact.email}`"><span>Email</span><strong>{{ contact.email }}</strong></a><p v-if="contact.address"><span>Địa chỉ</span><strong>{{ contact.address }}</strong></p><p v-if="contact.workingHours"><span>Thời gian làm việc</span><strong>{{ contact.workingHours }}</strong></p></div></section>
</template>

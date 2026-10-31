<script setup lang="ts">
import ContactPageContent from '~/components/contact/ContactPageContent.vue'
definePageMeta({ layout: false, middleware: 'contact-public-route' })
</script>

<template>
  <ContactPageContent />
</template>

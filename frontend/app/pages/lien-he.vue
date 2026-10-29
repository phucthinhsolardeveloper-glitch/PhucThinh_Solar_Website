<script setup lang="ts">
import ContactPageContent from '~/components/contact/ContactPageContent.vue'
import contactPublicRoute from '~/middleware/contact-public-route'

definePageMeta({ layout: false, middleware: contactPublicRoute })
</script>

<template>
  <ContactPageContent />
</template>

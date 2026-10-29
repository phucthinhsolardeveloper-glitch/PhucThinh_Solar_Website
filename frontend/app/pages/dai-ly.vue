<script setup lang="ts">
import DealerPageContent from '~/components/dealer/DealerPageContent.vue'
import dealerPublicRoute from '~/middleware/dealer-public-route'

definePageMeta({ layout: false, middleware: dealerPublicRoute })
</script>

<template>
  <DealerPageContent />
</template>

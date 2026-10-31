<script setup lang="ts">
import DealerPageContent from '~/components/dealer/DealerPageContent.vue'
definePageMeta({ layout: false, middleware: 'dealer-public-route' })
</script>

<template>
  <DealerPageContent />
</template>

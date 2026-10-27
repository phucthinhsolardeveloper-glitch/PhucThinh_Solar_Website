<script setup lang="ts">
export interface BreadcrumbItem {
  label: string
  to?: string
}

defineProps<{
  items: BreadcrumbItem[]
}>()

const { active: isMigrationPreview, generic: migrationHref } = useMigrationLinks()
const homeTo = computed(() => migrationHref('/'))
</script>

<template>
  <!-- shell.css: padding 16px 0 8px, font-size 13px, separator #94a3b8 = slate-400. -->
  <nav class="flex flex-wrap items-center gap-2 py-4 pb-2 text-[13px] leading-[1.4] text-slate-500" aria-label="Breadcrumb">
    <NuxtLink :external="!isMigrationPreview" class="text-inherit no-underline hover:text-gold-700" :to="homeTo">Trang chủ</NuxtLink>
    <template v-for="item in items" :key="`${item.label}-${item.to ?? 'current'}`">
      <span class="text-slate-400" aria-hidden="true">/</span>
      <NuxtLink v-if="item.to" :external="!isMigrationPreview" class="text-inherit no-underline hover:text-gold-700" :to="migrationHref(item.to)">{{ item.label }}</NuxtLink>
      <span v-else class="font-bold text-navy-800" aria-current="page">{{ item.label }}</span>
    </template>
  </nav>
</template>

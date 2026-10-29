<script setup lang="ts">
export interface BreadcrumbItem {
  label: string
  to?: string
}

defineProps<{
  items: BreadcrumbItem[]
}>()

const { generic: migrationHref, external: isExternalLink } = useMigrationLinks()
const homeTo = computed(() => migrationHref('/'))
</script>

<template>
  <nav class="flex min-h-10 flex-wrap items-center gap-2 py-2.5 text-[13px] leading-[1.45] text-slate-500" aria-label="Breadcrumb">
    <NuxtLink
      :external="isExternalLink(homeTo)"
      class="inline-flex items-center gap-1.5 text-slate-600 no-underline transition-colors hover:text-gold-700"
      :to="homeTo"
    >
      <svg class="h-3.5 w-3.5 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" />
      </svg>
      <span>Trang chủ</span>
    </NuxtLink>
    <template v-for="item in items" :key="`${item.label}-${item.to ?? 'current'}`">
      <span class="text-slate-300" aria-hidden="true">/</span>
      <NuxtLink
        v-if="item.to"
        :external="isExternalLink(migrationHref(item.to))"
        class="text-slate-600 no-underline transition-colors hover:text-gold-700"
        :to="migrationHref(item.to)"
      >
        {{ item.label }}
      </NuxtLink>
      <span v-else class="font-bold text-navy-800" aria-current="page">{{ item.label }}</span>
    </template>
  </nav>
</template>

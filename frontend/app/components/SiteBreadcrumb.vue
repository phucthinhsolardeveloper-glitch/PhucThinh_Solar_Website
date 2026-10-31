<script setup lang="ts">
import { publicBreadcrumbStructuredData } from '#shared/public-seo'

export interface BreadcrumbItem {
  label: string
  to?: string
}

const props = defineProps<{
  items: BreadcrumbItem[]
}>()

const { generic: migrationHref, external: isExternalLink } = useMigrationLinks()
const route = useRoute()
const config = useRuntimeConfig()
const homeTo = computed(() => migrationHref('/'))
const compactItems = computed(() => props.items.length > 2 ? props.items.slice(-2) : props.items)
const breadcrumbStructuredData = computed(() => {
  if (route.path.startsWith('/migration-') || route.path.startsWith('/_migration') || /^\/san-pham\/[^/]+$/.test(route.path)) return null
  return publicBreadcrumbStructuredData(config.public.canonicalSiteOrigin, route.path, props.items.map((item) => ({
    label: item.label,
    ...(item.to ? { path: item.to } : {}),
  })))
})

useHead({
  script: computed(() => breadcrumbStructuredData.value ? [{
    type: 'application/ld+json',
    innerHTML: breadcrumbStructuredData.value,
  }] : []),
})
</script>

<template>
  <nav class="flex min-h-[33px] flex-wrap items-center gap-2 py-[5px] text-[13px] leading-[1.45] text-slate-500 max-[769px]:min-h-[31px] max-[769px]:flex-nowrap max-[769px]:gap-1.5 max-[769px]:py-1" aria-label="Breadcrumb">
    <NuxtLink
      :external="isExternalLink(homeTo)"
      class="inline-flex shrink-0 items-center gap-1.5 text-slate-600 no-underline transition-colors hover:text-gold-700"
      :to="homeTo"
    >
      <svg class="h-3.5 w-3.5 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" />
      </svg>
      <span>Trang chủ</span>
    </NuxtLink>
    <div class="hidden flex-wrap items-center gap-2 min-[770px]:flex">
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
    </div>
    <div class="flex min-w-0 items-center gap-1.5 overflow-hidden whitespace-nowrap min-[770px]:hidden">
      <template v-if="items.length > 2">
        <span class="text-slate-300" aria-hidden="true">/</span>
        <span class="text-slate-400" aria-label="Các mục trước">…</span>
      </template>
      <template v-for="item in compactItems" :key="`compact-${item.label}-${item.to ?? 'current'}`">
        <span class="text-slate-300" aria-hidden="true">/</span>
        <NuxtLink
          v-if="item.to"
          :external="isExternalLink(migrationHref(item.to))"
          class="shrink-0 text-slate-600 no-underline transition-colors hover:text-gold-700"
          :to="migrationHref(item.to)"
        >
          {{ item.label }}
        </NuxtLink>
        <span v-else class="max-w-[34vw] truncate font-bold text-navy-800" aria-current="page">{{ item.label }}</span>
      </template>
    </div>
  </nav>
</template>

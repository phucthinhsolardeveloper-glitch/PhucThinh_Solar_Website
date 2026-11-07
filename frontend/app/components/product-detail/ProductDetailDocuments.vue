<script setup lang="ts">
import type { PublicProductDocument } from '#shared/contracts/public-product-detail'

defineProps<{ documents: PublicProductDocument[] }>()
const links = usePublicLinks()

const typeLabels: Record<PublicProductDocument['type'], string> = {
  datasheet: 'Datasheet',
  manual: 'Hướng dẫn',
  warranty: 'Bảo hành',
  certificate: 'Chứng nhận',
  other: 'Tài liệu',
}

function documentHref(document: PublicProductDocument): string {
  return document.url
}
</script>

<template>
  <section v-if="documents.length" id="tai-lieu-san-pham" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-documents-title">
    <h2 id="product-documents-title" class="mb-6 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-800">
      Tài liệu sản phẩm
    </h2>
    <div class="grid gap-3 md:grid-cols-2">
      <a
        v-for="document in documents"
        :key="`${document.type}:${document.url}`"
        :href="documentHref(document)"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center justify-between gap-4 rounded-btn border border-gray-200 px-4 py-4 text-navy-800 transition hover:border-gold-500 hover:bg-slate-50"
      >
        <span class="min-w-0">
          <small class="mb-1 block text-[13px] font-bold text-gold-700">{{ typeLabels[document.type] }}</small>
          <strong class="block truncate">{{ document.title }}</strong>
        </span>
        <span class="shrink-0 text-sm font-bold text-slate-500" aria-hidden="true">Tải xuống ↗</span>
      </a>
    </div>
  </section>
</template>

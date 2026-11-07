<script setup lang="ts">
import type { PublicProductSpecification } from '#shared/contracts/public-product-detail'

const props = defineProps<{ specifications: PublicProductSpecification[] }>()
const visibleSpecifications = computed(() => props.specifications.filter((item) => item.value.trim() !== ''))
const filterQuery = ref('')
const copied = ref(false)
let copyTimer: number | undefined

const filteredSpecifications = computed(() => {
  const q = filterQuery.value.trim().toLocaleLowerCase('vi')
  if (!q) return visibleSpecifications.value
  return visibleSpecifications.value.filter(
    (item) => item.label.toLocaleLowerCase('vi').includes(q) || item.value.toLocaleLowerCase('vi').includes(q),
  )
})

async function copySpecs() {
  if (!import.meta.client || !navigator.clipboard) return
  const text = visibleSpecifications.value.map((item) => `${item.label}: ${item.value}`).join('\n')
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    if (copyTimer !== undefined) window.clearTimeout(copyTimer)
    copyTimer = window.setTimeout(() => { copied.value = false }, 2400)
  } catch {
    copied.value = false
  }
}

onBeforeUnmount(() => {
  if (copyTimer !== undefined) window.clearTimeout(copyTimer)
})
</script>

<template>
  <section id="thong-so-ky-thuat" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-preview-specs-title">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="product-preview-specs-title" class="m-0 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-900">
          Thông số kỹ thuật
        </h2>
        <p v-if="visibleSpecifications.length" class="mb-0 mt-1 text-sm text-slate-500">
          {{ visibleSpecifications.length }} thông số kỹ thuật công bố theo model
        </p>
      </div>

      <!-- One slim row: the search grows, copying is a single icon beside it. -->
      <div v-if="visibleSpecifications.length" class="flex items-center gap-1.5 max-ph:w-full">
        <div v-if="visibleSpecifications.length > 6" class="relative min-w-0 flex-1 ph:w-[220px] ph:flex-initial">
          <UIcon name="i-lucide-search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            v-model="filterQuery"
            style="font-size: 16px"
            type="search"
            placeholder="Tìm thông số"
            aria-label="Tìm nhanh thông số kỹ thuật"
            class="h-10 w-full rounded-btn border border-slate-200 bg-slate-50 pl-9 pr-3 text-navy-900 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gold-500/30"
          >
        </div>
        <button
          type="button"
          class="inline-flex size-11 flex-none cursor-pointer items-center justify-center rounded-btn text-gold-700 transition-colors hover:bg-gold-500/15"
          :aria-label="copied ? 'Đã sao chép thông số' : 'Sao chép thông số'"
          :title="copied ? 'Đã sao chép thông số' : 'Sao chép thông số'"
          @click="copySpecs"
        >
          <UIcon :name="copied ? 'i-lucide-check' : 'i-lucide-copy'" class="size-5" aria-hidden="true" />
        </button>
        <span class="sr-only" role="status">{{ copied ? 'Đã sao chép thông số' : '' }}</span>
      </div>
    </div>

    <p v-if="!visibleSpecifications.length" class="text-slate-500">Thông số kỹ thuật chi tiết đang được cập nhật. Liên hệ để nhận bảng thông số đầy đủ.</p>
    <p v-else-if="!filteredSpecifications.length" class="py-4 text-slate-500">
      Không tìm thấy thông số khớp với “{{ filterQuery }}”.
      <button type="button" class="ml-1 font-bold text-gold-700 underline cursor-pointer" @click="filterQuery = ''">Xóa bộ lọc</button>
    </p>

    <div v-else class="overflow-x-auto border border-[#dce3ea] max-tb:rounded-md">
      <table class="w-full table-fixed border-collapse">
        <thead>
          <tr class="bg-navy-900 text-white">
            <th scope="col" class="w-1/2 px-[18px] py-3.5 text-left text-[13.5px] font-bold max-ph:w-[42%] max-ph:px-3">Hạng mục</th>
            <th scope="col" class="w-1/2 px-[18px] py-3.5 text-left text-[13.5px] font-bold max-ph:w-[58%] max-ph:px-3">Thông tin theo model</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in filteredSpecifications"
            :key="`${item.key}-${item.label}`"
            class="border-t border-[#e8edf3] transition-colors hover:bg-amber-50/40"
            :class="index % 2 === 1 ? 'bg-[#fafbfc]' : 'bg-white'"
          >
            <th scope="row" class="w-1/2 px-[18px] py-3.5 text-left align-top text-[15px] font-semibold text-slate-700 max-ph:w-[42%] max-ph:px-3 max-ph:py-3">
              {{ item.label }}
            </th>
            <td class="w-1/2 px-[18px] py-3.5 text-left align-top text-[15.5px] font-extrabold text-navy-900 tabular-nums max-ph:w-[58%] max-ph:px-3 max-ph:py-3">
              {{ item.value }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

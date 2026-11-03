<script setup lang="ts">
import type { PublicProductSpecification } from '#shared/contracts/public-product-detail'

const props = defineProps<{ specifications: PublicProductSpecification[] }>()
const visibleSpecifications = computed(() => props.specifications.filter((item) => item.value.trim() !== ''))
</script>

<template>
  <section id="thong-so-ky-thuat" class="scroll-mt-24 py-12 tb:py-16" aria-labelledby="product-preview-specs-title">
    <h2 id="product-preview-specs-title" class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">
      Thông số kỹ thuật
    </h2>

    <p v-if="!visibleSpecifications.length" class="text-slate-500">CMS chưa cung cấp thông số được allowlist.</p>

    <div v-else class="overflow-x-auto border border-[#dce3ea]">
      <table class="w-full table-fixed border-collapse">
        <thead>
          <tr class="bg-[#edf1f5] text-navy-800">
            <th scope="col" class="w-1/2 px-[18px] py-3 text-left text-[13px] font-bold max-ph:px-3">Hạng mục</th>
            <th scope="col" class="w-1/2 px-[18px] py-3 text-left text-[13px] font-bold max-ph:px-3">Thông tin theo model</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in visibleSpecifications"
            :key="`${item.key}-${item.label}`"
            :class="index % 2 === 1 ? 'bg-[#fafbfc]' : 'bg-white'"
          >
            <th scope="row" class="w-1/2 px-[18px] py-3 text-left align-top text-slate-700 font-semibold max-ph:w-[38%] max-ph:px-3 max-ph:py-[11px]">
              {{ item.label }}
            </th>
            <td class="w-1/2 px-[18px] py-3 text-left align-top text-navy-800 font-bold max-ph:w-[62%] max-ph:px-3 max-ph:py-[11px]">
              {{ item.value }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

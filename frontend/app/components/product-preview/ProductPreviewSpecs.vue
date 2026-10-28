<script setup lang="ts">
import type { PublicProductSpecification } from '#shared/contracts/public-product-detail'

defineProps<{ specifications: PublicProductSpecification[] }>()
</script>

<template>
  <section id="thong-so-ky-thuat" class="scroll-mt-24 py-8" aria-labelledby="product-preview-specs-title">
    <h2 id="product-preview-specs-title" class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">
      Thông số kỹ thuật
    </h2>

    <p v-if="!specifications.length" class="text-slate-500">CMS chưa cung cấp thông số được allowlist.</p>

    <!--
      Bảng hai cột: nhãn 36% / giá trị phần còn lại, như bản PHP. Ảnh production
      tô nền xen kẽ theo hàng; giữ nền xen kẽ vì với bảng 19 dòng thông số thì
      đó là thứ giúp mắt bám đúng hàng khi đọc ngang.
      Bọc overflow-x-auto: một số giá trị (kích thước tấm pin, điện áp hệ thống)
      dài và không ngắt được, cuộn trong bảng tốt hơn là đẩy cả trang trôi ngang.
    -->
    <div v-else class="overflow-x-auto">
      <table class="w-full border-collapse">
        <tbody>
          <tr
            v-for="(item, index) in specifications"
            :key="`${item.key}-${item.label}`"
            :class="index % 2 === 1 ? 'bg-slate-50' : ''"
          >
            <th scope="row" class="w-[36%] border border-gray-200 px-4 py-3.5 text-left align-top font-bold max-ph:px-3 max-ph:py-3">
              {{ item.label }}
            </th>
            <td class="border border-gray-200 px-4 py-3.5 text-left align-top max-ph:px-3 max-ph:py-3">
              {{ item.value }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

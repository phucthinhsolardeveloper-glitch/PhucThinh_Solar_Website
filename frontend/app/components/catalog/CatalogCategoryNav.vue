<script setup lang="ts">
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'
import CatalogCategoryBranch from './CatalogCategoryBranch.vue'

defineProps<{ categories: PublicProductCategoryNode[]; selected: string }>()
</script>

<template>
  <!--
    Cột trái. Ảnh production đặt ở đây hộp "BỘ LỌC" đầy đủ (Loại sản phẩm /
    Thương hiệu / Mức giá / Xóa bộ lọc). DTO hiện chỉ có cây danh mục:
    public-catalog.ts khai `parity.filters.included = false` và
    `parity.sort.included = false`.

    Dựng cây danh mục thật thay vì vẽ sẵn khung bộ lọc rỗng — một bộ lọc không
    lọc được gì còn tệ hơn là chưa có bộ lọc.
  -->
  <nav v-if="categories.length" aria-label="Danh mục sản phẩm">
    <h2 class="mb-4 text-lg font-extrabold text-navy-800">Danh mục sản phẩm</h2>
    <ul class="m-0 list-none p-0">
      <CatalogCategoryBranch
        v-for="category in categories"
        :key="category.id"
        :node="category"
        :selected="selected"
      />
    </ul>
  </nav>
</template>

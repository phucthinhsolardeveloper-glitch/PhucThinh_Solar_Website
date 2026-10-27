<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

defineProps<{ products: PublicCatalogProduct[] }>()
const failedImages = ref<Set<number>>(new Set())

function markImageFailed(productId: number) {
  failedImages.value = new Set(failedImages.value).add(productId)
}
</script>

<template>
  <!--
    Lưới đo từ ảnh production 2026-10-26 (danh mục tấm pin, 1920px): 4 cột rộng
    đúng 337px, mép trái 447/800/1153/1506 -> khoảng cách ngang 16px, tổng lưới
    1396px. Nút trong thẻ cao 40px.

    CÒN THIẾU so với ảnh, KHÔNG dựng được từ DTO hiện tại: ảnh có công suất
    (650W), giá (2.648.100đ), nút "Thêm giỏ hàng" và "Xem chi tiết".
    public-catalog.ts khai thẳng `parity.prices.included = false`, và
    PublicCatalogProduct chỉ mang {id, name, slug, url, media}. Không render giá
    hay nút mua khi chưa có dữ liệu — giá sai ở trang danh mục là lỗi chạm thẳng
    vào tiền của khách. Cần task mở rộng PHP DTO trước khi phần này khớp ảnh.
  -->
  <div
    v-if="products.length"
    class="grid grid-cols-2 gap-x-4 gap-y-8 max-ph:gap-x-3 max-ph:gap-y-7 tb:grid-cols-3 nav:grid-cols-4"
  >
    <article v-for="product in products" :key="product.id" class="min-w-0">
      <NuxtLink
        external
        :to="product.url"
        class="grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50 no-underline"
      >
        <img
          v-if="product.media[0] && !failedImages.has(product.id)"
          :src="product.media[0]"
          :alt="product.name"
          class="h-full w-full object-contain"
          loading="lazy"
          @error="markImageFailed(product.id)"
        >
        <span v-else class="p-4 text-center text-sm text-slate-500">Chưa có hình ảnh</span>
      </NuxtLink>

      <h3 class="mt-3 mb-1.5 text-[17px] leading-[1.4] max-ph:text-base">
        <NuxtLink
          external
          :to="product.url"
          class="text-inherit no-underline hover:underline hover:underline-offset-[3px] focus-visible:underline"
        >
          {{ product.name }}
        </NuxtLink>
      </h3>

      <p class="m-0 text-sm text-slate-500">Liên hệ để nhận báo giá</p>
    </article>
  </div>

  <p v-else class="py-8 text-slate-500" role="status">Chưa có sản phẩm trong danh mục này.</p>
</template>

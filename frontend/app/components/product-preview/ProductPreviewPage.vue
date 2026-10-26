<script setup lang="ts">
import type { PublicProductDetail } from '#shared/contracts/public-product-detail'
import ProductPreviewGallery from './ProductPreviewGallery.vue'
import ProductPreviewSpecs from './ProductPreviewSpecs.vue'
defineProps<{ product: PublicProductDetail }>()
</script>
<template>
  <div class="product-preview"><div class="product-preview__hero">
    <ProductPreviewGallery :name="product.name" :media="product.media" />
    <div class="product-preview__summary"><h1>{{ product.name }}</h1><p v-if="product.code" class="product-preview__code">Mã sản phẩm: {{ product.code }}</p><p v-if="product.summary" class="product-preview__description">{{ product.summary }}</p><p class="product-preview__price-note">Giá và đặt hàng vẫn thuộc luồng PHP legacy; preview không tự hiển thị giá.</p></div>
  </div><ProductPreviewSpecs :specifications="product.specifications" /><section class="product-preview__deferred" aria-labelledby="product-preview-deferred-title"><h2 id="product-preview-deferred-title">Nội dung chi tiết</h2><p>Khối CKEditor, tài liệu, đánh giá và dữ liệu liên quan chưa được render trong preview vì chưa có sanitizer/parity gate.</p></section></div>
</template>

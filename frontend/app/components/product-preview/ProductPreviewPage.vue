<script setup lang="ts">
import type { PublicProductDetail } from '#shared/contracts/public-product-detail'
import ProductPreviewGallery from './ProductPreviewGallery.vue'
import ProductPreviewSpecs from './ProductPreviewSpecs.vue'

defineProps<{ product: PublicProductDetail }>()
</script>

<template>
  <!--
    Khung trang chi tiết sản phẩm đo từ ảnh production 2026-10-26 (1920px):
    breadcrumb bắt đầu tại x=76, cột phải tại x=983, nội dung kết thúc tại 1842
    -> vùng nội dung rộng 1766, khoảng cách hai cột 43px.

    Lưu ý: khung này RỘNG HƠN footer (footer là 154..1766 theo --pts-container
    1680 + gutter 34). Hai vùng dùng container khác nhau trên site thật, nên
    không gộp về một giá trị chung — gộp sẽ làm một trong hai lệch khỏi ảnh.

    CSS preview cũ đặt gap clamp(32px,5vw,96px), ở 1920px ra 96px — hơn gấp đôi
    số đo thật. Thay bằng 40px (gần 43px đo được, vẫn nằm trên lưới 8px).
  -->
  <div class="mx-auto w-full max-w-[1766px] px-4 pb-16 pt-6 max-tb:pt-2">
    <div class="grid grid-cols-1 items-start gap-10 pb-16 pt-8 max-tb:gap-8 max-tb:pb-10 max-tb:pt-6 nav:grid-cols-2">
      <ProductPreviewGallery :name="product.name" :media="product.media" />

      <div class="min-w-0">
        <h1 class="mb-3 text-[clamp(30px,4vw,54px)] font-black leading-[1.1] tracking-tight max-tb:text-[32px]">
          {{ product.name }}
        </h1>

        <p v-if="product.code" class="leading-relaxed text-slate-500">Mã sản phẩm: {{ product.code }}</p>
        <p v-if="product.summary" class="leading-relaxed text-slate-500">{{ product.summary }}</p>

        <!--
          CÒN THIẾU so với ảnh production — tất cả đều do DTO khai không cung cấp:
          giá 2.648.100đ/tờ, 4 chip thông số nổi bật, ô chọn số lượng, nút
          "Mua ngay" + "Thêm giỏ hàng", 3 chip bảo chứng, tab neo, video, FAQ,
          đánh giá, form tư vấn và dải sản phẩm liên quan.
          public-product-detail.ts: pricing.included = false,
          content.rendered = false, parity.{quote,reviews,related,jsonLd,blocks}
          đều included = false. Không dựng nút mua khi chưa có luồng giá thật:
          nút mua không mua được là lỗi tệ hơn không có nút.
        -->
        <p class="mt-8 border-t border-gray-200 pt-4 font-semibold text-navy-800">
          Giá và đặt hàng vẫn thuộc luồng PHP legacy; preview không tự hiển thị giá.
        </p>
      </div>
    </div>

    <ProductPreviewSpecs :specifications="product.specifications" />

    <section class="py-8" aria-labelledby="product-preview-deferred-title">
      <h2 id="product-preview-deferred-title" class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">
        Nội dung chi tiết
      </h2>
      <p class="text-slate-500">
        Khối CKEditor, tài liệu, đánh giá và dữ liệu liên quan chưa được render trong preview vì chưa có sanitizer/parity gate.
      </p>
    </section>
  </div>
</template>

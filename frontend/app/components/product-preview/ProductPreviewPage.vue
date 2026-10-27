<script setup lang="ts">
import type { PublicProductContent, PublicProductDetail, PublicProductDocument } from '#shared/contracts/public-product-detail'
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import ProductPreviewGallery from './ProductPreviewGallery.vue'
import ProductPreviewSpecs from './ProductPreviewSpecs.vue'
import ProductPreviewContent from './ProductPreviewContent.vue'
import ProductPreviewDocuments from './ProductPreviewDocuments.vue'

const props = defineProps<{ product: PublicProductDetail; pricing: PublicCatalogPricing; content: PublicProductContent; documents: PublicProductDocument[] }>()

/*
 * Dinh dang tien khop PHP: class.Functions.php::formatMoney() ->
 * number_format($price,0,',','.') + 'd'. Giong CatalogProductGrid.vue.
 */
function formatMoney(value: number): string {
  return `${new Intl.NumberFormat('vi-VN').format(value)}đ`
}

const discountPercent = computed(() => {
  if (props.pricing.mode !== 'price' || !props.pricing.salePrice || !props.pricing.regularPrice) return 0
  if (props.pricing.regularPrice <= props.pricing.salePrice) return 0
  return Math.round(((props.pricing.regularPrice - props.pricing.salePrice) / props.pricing.regularPrice) * 100)
})
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
          Khối giá: tai dung cung logic/mau voi templates/product/product_detail_tpl.php
          (publicProductRequiresQuote) va cung mau #d52525 do duoc tu
          assets/css/product-detail-v2.css (load SAU theme-v2.css tren trang PDP
          thuc, nen #d52525!important thang #dc2626!important cua theme-v2 —
          khac voi CatalogProductGrid.vue dung #dc2626 vi trang danh muc khong
          load product-detail-v2.css).
        -->
        <div class="mt-6 border-t border-gray-200 pt-5">
          <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Giá bán</p>
          <div v-if="pricing.mode === 'quote'">
            <p class="m-0 text-[clamp(27px,2vw,36px)] font-extrabold leading-none text-[#d52525]">Liên hệ</p>
            <p class="mt-1.5 text-[12.5px] leading-relaxed text-slate-500">Nhận báo giá theo số lượng và nhu cầu sử dụng</p>
          </div>
          <div v-else class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-[clamp(27px,2vw,36px)] font-extrabold leading-none text-[#d52525]">
              {{ formatMoney(pricing.salePrice ?? pricing.regularPrice ?? 0) }}
            </span>
            <span class="text-sm text-slate-500">/ bộ</span>
            <template v-if="pricing.salePrice && pricing.regularPrice && pricing.regularPrice > pricing.salePrice">
              <span class="text-sm text-slate-400 line-through">{{ formatMoney(pricing.regularPrice) }}</span>
              <span v-if="discountPercent > 0" class="rounded-md bg-red-600 px-2 py-1 text-xs font-bold text-white">-{{ discountPercent }}%</span>
            </template>
            <p class="mt-1 basis-full text-[12.5px] leading-relaxed text-slate-500">Giá đã gồm VAT; chi phí vận chuyển và lắp đặt báo riêng theo khu vực.</p>
          </div>
        </div>

        <!--
          CÒN THIẾU so với ảnh production: chip thông số nổi bật, ô chọn số
          lượng, nút "Mua ngay" + "Thêm giỏ hàng", bảo chứng, tab neo, video,
          đánh giá, form tư vấn và sản phẩm liên quan. Khối mô tả
          content_section + FAQ do Admin soạn đã được nối bằng DTO có kiểu,
          không dùng raw HTML/v-html.
        -->
        <p class="mt-6 border-t border-gray-200 pt-4 font-semibold text-navy-800">
          Đặt hàng vẫn thuộc luồng PHP legacy; preview chưa dựng nút mua.
        </p>
      </div>
    </div>

    <ProductPreviewSpecs :specifications="product.specifications" />
    <ProductPreviewContent :content="content" />
    <ProductPreviewDocuments :documents="documents" />

    <section class="py-8" aria-labelledby="product-preview-deferred-title">
      <h2 id="product-preview-deferred-title" class="mb-5 text-[clamp(24px,3vw,34px)] font-black tracking-tight">
        Các phần còn thuộc PHP legacy
      </h2>
      <p class="text-slate-500">
        Đánh giá, giỏ hàng/đặt mua, structured data và sản phẩm liên quan vẫn chưa được delegate sang preview.
      </p>
    </section>
  </div>
</template>

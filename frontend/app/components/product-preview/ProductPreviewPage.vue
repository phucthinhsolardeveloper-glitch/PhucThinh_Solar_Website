<script setup lang="ts">
import type { PublicProductApplications, PublicProductBuyingGuide, PublicProductContent, PublicProductDetail, PublicProductDocument } from '#shared/contracts/public-product-detail'
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import ProductPreviewGallery from './ProductPreviewGallery.vue'
import ProductPreviewSpecs from './ProductPreviewSpecs.vue'
import ProductPreviewContent from './ProductPreviewContent.vue'
import ProductPreviewDocuments from './ProductPreviewDocuments.vue'
import ProductPreviewDecisionMetrics from './ProductPreviewDecisionMetrics.vue'
import ProductPreviewApplications from './ProductPreviewApplications.vue'
import ProductPreviewBuyingGuide from './ProductPreviewBuyingGuide.vue'
import ProductPurchasePanel from './ProductPurchasePanel.vue'
import ProductSupportExtras from './ProductSupportExtras.vue'
import ProductReviews from './ProductReviews.vue'
import ProductConsultationForm from './ProductConsultationForm.vue'
import ProductTrustRelated from './ProductTrustRelated.vue'

const props = defineProps<{
  product: PublicProductDetail
  pricing: PublicCatalogPricing
  content: PublicProductContent
  applications?: PublicProductApplications | null
  buyingGuides?: PublicProductBuyingGuide[]
  documents: PublicProductDocument[]
  extras: PublicProductExtrasPayload | null
}>()

function formatMoney(value: number): string {
  return new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}
const discountPercent = computed(() => {
  if (props.pricing.mode !== 'price' || !props.pricing.salePrice || !props.pricing.regularPrice) return 0
  if (props.pricing.regularPrice <= props.pricing.salePrice) return 0
  return Math.round(((props.pricing.regularPrice - props.pricing.salePrice) / props.pricing.regularPrice) * 100)
})

const navItems = computed(() => {
  const items: Array<{ id: string; label: string }> = []
  if (props.content.blocks.length || props.content.faqs.length) items.push({ id: 'tong-quan', label: 'Tổng quan' })
  if (props.applications?.items.length) items.push({ id: 'ung-dung-san-pham', label: 'Ứng dụng' })
  if (props.buyingGuides?.length) items.push({ id: 'huong-dan-lua-chon', label: 'Lựa chọn' })
  items.push({ id: 'thong-so-ky-thuat', label: 'Thông số' })
  if (props.documents.length) items.push({ id: 'tai-lieu-san-pham', label: 'Tài liệu' })
  if (props.extras?.videoEmbedUrl) items.push({ id: 'video-san-pham', label: 'Video' })
  if (props.extras && (props.extras.warranty.summary || props.extras.warranty.override || props.extras.warranty.policies.length)) {
    items.push({ id: 'chinh-sach-bao-hanh', label: 'Bảo hành' })
  }
  if (props.extras) {
    items.push({ id: 'danh-gia-san-pham', label: 'Đánh giá' })
    items.push({ id: 'pts-pdp-consultation', label: 'Tư vấn' })
    if (props.extras.commitments.length) items.push({ id: 'ly-do-chon-phuc-thinh-solar', label: 'Cam kết' })
    if (props.extras.related.length) items.push({ id: 'san-pham-lien-quan', label: 'Liên quan' })
  }
  return items
})
const activeSection = ref('')
let observer: IntersectionObserver | null = null

function setupObserver() {
  observer?.disconnect()
  if (!import.meta.client) return
  const nodes = navItems.value
    .map((item) => document.getElementById(item.id))
    .filter((node): node is HTMLElement => Boolean(node))
  if (!nodes.length) return
  if (!activeSection.value) activeSection.value = nodes[0]?.id ?? ''
  observer = new IntersectionObserver((entries) => {
    const visible = entries
      .filter((entry) => entry.isIntersecting)
      .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
    const first = visible[0]?.target as HTMLElement | undefined
    if (first?.id) activeSection.value = first.id
  }, { rootMargin: '-120px 0px -60% 0px', threshold: [0, 0.01] })
  nodes.forEach((node) => observer?.observe(node))
}
onMounted(() => nextTick(setupObserver))
watch(navItems, () => nextTick(setupObserver), { deep: true })
onBeforeUnmount(() => observer?.disconnect())
</script>

<template>
  <div class="mx-auto w-full max-w-[1766px] px-4 pb-16 pt-6 max-tb:pt-2">
    <div class="grid grid-cols-1 items-start gap-10 pb-12 pt-8 max-tb:gap-8 max-tb:pb-8 max-tb:pt-6 nav:grid-cols-2">
      <ProductPreviewGallery :name="product.name" :media="product.media" />

      <div class="min-w-0">
        <h1 class="mb-3 text-[clamp(30px,4vw,54px)] font-black leading-[1.1] tracking-tight max-tb:text-[32px]">
          {{ product.name }}
        </h1>

        <p v-if="product.code" class="leading-relaxed text-slate-500">Mã sản phẩm: {{ product.code }}</p>
        <p v-if="product.summary" class="leading-relaxed text-slate-500">{{ product.summary }}</p>

        <ProductPreviewDecisionMetrics :metrics="product.decisionMetrics ?? []" />
        <p v-if="product.packageContents" class="mt-4 text-sm leading-6 text-slate-600">
          <strong class="text-navy-800">Bộ sản phẩm gồm:</strong> {{ product.packageContents }}
        </p>

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

        <ProductPurchasePanel :product-id="product.id" :pricing="pricing" :consultation-available="Boolean(extras)" />

        <div v-if="extras?.reviews.total" class="mt-5 flex items-center gap-3 text-sm">
          <span class="font-bold text-gold-700">★ {{ extras.reviews.average.toFixed(1) }}/5</span>
          <a href="#danh-gia-san-pham" class="text-slate-500 underline-offset-4 hover:underline">{{ extras.reviews.total }} nhận xét</a>
        </div>
      </div>
    </div>

    <nav v-if="navItems.length" class="sticky top-0 z-20 -mx-4 mb-2 overflow-x-auto border-y border-gray-200 bg-white/95 px-4 backdrop-blur" aria-label="Mục nội dung sản phẩm">
      <div class="flex min-w-max gap-1 py-2">
        <a
          v-for="item in navItems"
          :key="item.id"
          :href="'#' + item.id"
          class="rounded-chip px-3 py-2 text-sm font-bold no-underline transition"
          :class="activeSection === item.id ? 'bg-navy-800 text-white' : 'text-navy-800 hover:bg-slate-50'"
        >{{ item.label }}</a>
      </div>
    </nav>

    <ProductPreviewContent :content="content" />
    <ProductPreviewApplications v-if="applications?.items.length" :applications="applications" />
    <ProductPreviewBuyingGuide v-if="buyingGuides?.length" :guides="buyingGuides" />
    <ProductPreviewSpecs :specifications="product.specifications" />
    <ProductPreviewDocuments :documents="documents" />

    <template v-if="extras">
      <ProductSupportExtras :extras="extras" />
      <ProductReviews :product-id="product.id" :reviews="extras.reviews" :csrf-token="extras.csrfToken" />
      <ProductConsultationForm
        :product-id="product.id"
        :product-name="product.name"
        :profile-key="extras.profileKey"
        :needs="extras.consultation.needs"
      />
      <ProductTrustRelated :extras="extras" />
    </template>

    <section v-else class="border-t border-gray-200 py-8" aria-live="polite">
      <p class="mb-0 text-sm text-slate-500">Các dữ liệu bổ sung của PDP chưa tải được; phần sản phẩm cốt lõi vẫn hiển thị từ contract chính.</p>
    </section>
  </div>
</template>

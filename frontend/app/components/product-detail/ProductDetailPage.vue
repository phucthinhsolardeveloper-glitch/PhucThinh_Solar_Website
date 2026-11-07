<script setup lang="ts">
import type { PublicProductApplications, PublicProductBreadcrumb, PublicProductBuyingGuide, PublicProductContent, PublicProductDetail, PublicProductDocument } from '#shared/contracts/public-product-detail'
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import ProductDetailGallery from './ProductDetailGallery.vue'
import ProductDetailSpecs from './ProductDetailSpecs.vue'
import ProductDetailContent from './ProductDetailContent.vue'
import ProductDetailDecisionMetrics from './ProductDetailDecisionMetrics.vue'
import ProductDetailApplications from './ProductDetailApplications.vue'
import ProductDetailBuyingGuide from './ProductDetailBuyingGuide.vue'
import ProductPurchasePanel from './ProductPurchasePanel.vue'
import ProductSupportExtras from './ProductSupportExtras.vue'
import ProductReviews from './ProductReviews.vue'
import ProductConsultationForm from './ProductConsultationForm.vue'
import ProductTrustRelated from './ProductTrustRelated.vue'

const props = defineProps<{
  product: PublicProductDetail
  breadcrumbs?: PublicProductBreadcrumb[]
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
const productCategories = computed(() => (props.breadcrumbs ?? []).filter((item) => item.url !== '/san-pham/').slice(0, 2))
const { isHeaderHidden } = useSiteHeader()
const productNavRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(productNavRef)

const navItems = computed(() => {
  const items: Array<{ id: string; label: string }> = []
  if (props.content.blocks.length) items.push({ id: 'tong-quan', label: 'Tổng quan' })
  if (props.applications?.items.length) items.push({ id: 'ung-dung-san-pham', label: 'Ứng dụng' })
  if (props.buyingGuides?.length) items.push({ id: 'huong-dan-lua-chon', label: 'Lựa chọn' })
  items.push({ id: 'thong-so-ky-thuat', label: 'Thông số kỹ thuật' })
  if (props.extras && (props.extras.warranty.summary || props.extras.warranty.override || props.extras.warranty.policies.length)) {
    items.push({ id: 'chinh-sach-bao-hanh', label: 'Chính sách bảo hành' })
  }
  if (props.content.faqs.length) items.push({ id: 'cau-hoi-thuong-gap', label: 'Câu hỏi thường gặp' })
  items.push({ id: 'pts-pdp-consultation', label: 'Yêu cầu tư vấn' })
  return items
})
const activeSection = ref('')
useSlidingPill(productNavRef, 'product-sections', { chip: 'a' })
const sectionSpy = useSectionSpy(() => navItems.value.map((item) => item.id), activeSection, productNavRef)

function openReviewsSection() {
  if (!import.meta.client) return
  const details = document.getElementById('danh-gia-san-pham') as HTMLDetailsElement | null
  if (details) {
    details.open = true
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    details.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' })
  }
}
const summaryOpen = ref(false)
// The title sits right above the summary, so a summary opening with the product name skips the repeat.
const summaryText = computed(() => {
  const { summary, name } = props.product
  if (!summary.startsWith(name)) return summary
  const rest = summary.slice(name.length).trim()
  return /^(phù hợp|thích hợp)\s/i.test(rest) ? rest.charAt(0).toUpperCase() + rest.slice(1) : summary
})
const descriptionOpen = ref(false)
const descriptionRef = ref<HTMLElement | null>(null)
function toggleDescription() {
  descriptionOpen.value = !descriptionOpen.value
  if (descriptionOpen.value) return
  nextTick(() => {
    const top = descriptionRef.value?.getBoundingClientRect().top ?? 0
    if (top < 0) window.scrollBy({ top: top - 72 })
  })
}
</script>

<template>
  <div class="product-preview-page pts-container pb-16 pt-6 max-tb:pt-0">
    <div class="grid grid-cols-1 items-start gap-12 pb-12 pt-6 max-tb:gap-0 max-tb:pb-6 max-tb:pt-0 nav:grid-cols-2 nav:gap-14">
      <div class="pdp-photo min-w-0 nav:sticky nav:top-28">
        <ProductDetailGallery :name="product.name" :media="product.media" />
      </div>

      <!-- Phone reading order: name, price, then details. -->
      <div class="pdp-sheet min-w-0 max-tb:flex max-tb:flex-col">
        <h1 class="pdp-title mb-3 text-[clamp(30px,2.5vw,44px)] font-black leading-[1.08] tracking-tight text-navy-950 max-tb:order-1 max-tb:mb-2 max-tb:text-[22px] max-tb:leading-[1.25]">
          {{ product.name }}
        </h1>

        <div v-if="product.code || product.brand || extras?.reviews.total" class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-sm text-slate-600 max-tb:order-3 max-tb:mb-2 max-tb:text-[13px]">
          <span v-if="product.code">Mã sản phẩm: <strong class="font-bold text-navy-800">{{ product.code }}</strong></span>
          <span v-if="product.code && product.brand" class="text-slate-300" aria-hidden="true">·</span>
          <span v-if="product.brand">Thương hiệu: <strong class="font-bold text-navy-800">{{ product.brand }}</strong></span>
          <template v-if="extras?.reviews.total">
            <span class="text-slate-300" aria-hidden="true">·</span>
            <a href="#danh-gia-san-pham" class="inline-flex items-center gap-1 font-bold text-gold-700 no-underline hover:underline" @click.prevent="openReviewsSection">
              <span>★ {{ extras.reviews.average.toFixed(1) }}/5</span>
              <span class="font-normal text-slate-500">({{ extras.reviews.total }} nhận xét)</span>
            </a>
          </template>
        </div>

        <div v-if="product.summary" class="max-tb:order-4">
          <p class="mb-0 text-[16px] leading-[1.7] text-slate-600 max-tb:text-[15px] max-tb:leading-[1.6]" :class="summaryOpen ? '' : 'max-tb:line-clamp-3'">{{ summaryText }}</p>
          <button v-if="summaryText.length > 110" type="button" class="min-h-9 text-sm font-bold text-navy-800 tb:hidden" :aria-expanded="summaryOpen" @click="summaryOpen = !summaryOpen">{{ summaryOpen ? 'Thu gọn' : 'Xem thêm' }}</button>
        </div>

        <div class="max-tb:order-5">
          <ProductDetailDecisionMetrics :metrics="product.decisionMetrics ?? []" />
        </div>
        <div v-if="product.packageContents" class="mt-5 border-l-2 border-gold-500 pl-4 max-tb:order-6">
          <p class="mb-1 text-xs font-bold text-slate-500">Bộ sản phẩm tiêu chuẩn</p>
          <p class="m-0 text-[15px] font-semibold leading-6 text-navy-800">{{ product.packageContents }}</p>
        </div>

        <div class="mt-6 border-y border-gray-200 py-4 max-tb:order-2 max-tb:mt-0 max-tb:mb-2 max-tb:border-0 max-tb:py-0">
          <p class="mb-1.5 text-xs font-bold text-slate-500 max-tb:hidden">Giá bán tham khảo</p>
          <div v-if="pricing.mode === 'quote'">
            <p class="m-0 text-[clamp(28px,2.2vw,36px)] font-extrabold leading-none text-[#d52525]">Liên hệ báo giá</p>
            <p class="mt-1.5 text-[13.5px] leading-relaxed text-slate-500">Nhận báo giá kỹ thuật theo số lượng, cấu hình và khu vực lắp đặt</p>
          </div>
          <div v-else class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <span class="text-[clamp(28px,2.2vw,38px)] font-black leading-none tracking-tight text-[#d52525] tabular-nums">
              {{ formatMoney(pricing.salePrice ?? pricing.regularPrice ?? 0) }}
            </span>
            <span class="text-sm font-semibold text-slate-500">/ bộ</span>
            <template v-if="pricing.salePrice && pricing.regularPrice && pricing.regularPrice > pricing.salePrice">
              <span class="text-sm text-slate-400 line-through tabular-nums">{{ formatMoney(pricing.regularPrice) }}</span>
              <span v-if="discountPercent > 0" class="rounded-chip bg-red-600 px-2 py-0.5 text-xs font-bold text-white">-{{ discountPercent }}%</span>
            </template>
            <p class="mt-1.5 basis-full text-[13px] leading-relaxed text-slate-500">Giá đã gồm VAT; chi phí vận chuyển và lắp đặt báo riêng theo khu vực.</p>
          </div>
        </div>

        <ProductPurchasePanel class="max-tb:order-7" :product-id="product.id" :pricing="pricing" :consultation-available="Boolean(extras)" :product-name="product.name" :product-image="product.media[0]">
          <template #product-info>
            <div v-if="productCategories.length" class="mt-[18px] min-w-0 text-[12.5px] leading-[1.5] text-slate-500">
              <span class="mb-0.5 block font-semibold">Danh mục:</span>
              <span class="font-bold text-navy-800">{{ productCategories.map((item) => item.label).join(', ') }}</span>
            </div>
          </template>
        </ProductPurchasePanel>
      </div>
    </div>

    <nav
      v-if="navItems.length"
      ref="productNavRef"
      class="sticky m-glass-light z-20 mb-2 overflow-x-auto overscroll-x-contain border-y border-slate-200 bg-white/95 backdrop-blur-md [scrollbar-width:none] [&::-webkit-scrollbar]:hidden transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
      :class="isHeaderHidden ? 'top-0 shadow-sm' : 'top-[65px] min-[821px]:top-[88px] min-[1321px]:top-[104px] min-[1501px]:top-[120px] min-[1800px]:top-[136px]'"
      aria-label="Mục nội dung sản phẩm"
      @click="descriptionOpen = true"
    >
      <div class="flex min-w-max gap-1.5 py-2">
        <a
          v-for="item in navItems"
          :key="item.id"
          :href="'#' + item.id"
          :aria-current="activeSection === item.id ? 'location' : undefined"
          class="inline-flex min-h-11 items-center rounded-chip px-3.5 py-1.5 text-[13px] font-bold no-underline transition-all duration-150 active:scale-95 cursor-pointer"
          :class="activeSection === item.id ? 'text-navy-950' : 'text-navy-800 hover:text-gold-700'"
          @click="sectionSpy.lockTo(item.id)"
        >{{ item.label }}</a>
      </div>
    </nav>

    <!-- Phone: long description stays one tap away instead of pushing specs and reviews down. -->
    <div ref="descriptionRef" class="pdp-description" :class="{ 'is-open': descriptionOpen }">
      <ProductDetailContent :content="content" />
    </div>
    <button type="button" class="m-more-button mb-1 tb:hidden" :aria-expanded="descriptionOpen" @click="toggleDescription"><span>{{ descriptionOpen ? 'Thu gọn mô tả' : 'Xem toàn bộ mô tả' }}</span></button>
    <ProductDetailApplications v-if="applications?.items.length" :applications="applications" />
    <ProductDetailBuyingGuide v-if="buyingGuides?.length" :guides="buyingGuides" />
    <ProductDetailSpecs :specifications="product.specifications" />
    <ProductSupportExtras :extras="extras" :documents="documents" />

    <section v-if="content.faqs.length" id="cau-hoi-thuong-gap" class="scroll-mt-24 border-t border-gray-200 py-12 tb:py-16" aria-labelledby="product-faq-title">
      <h2 id="product-faq-title" class="mb-5 text-[clamp(24px,3vw,34px)] font-black text-navy-800">Câu hỏi thường gặp</h2>
      <div class="divide-y divide-gray-200 border-y border-gray-200">
        <details v-for="(faq, index) in content.faqs" :key="index" class="group py-4">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-navy-800 transition-colors duration-150 hover:text-gold-700 select-none py-0.5">
            {{ faq.question }}
            <span class="text-2xl text-gold-600 transition-transform duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] group-open:rotate-45" aria-hidden="true">+</span>
          </summary>
          <p class="mb-0 mt-3 whitespace-pre-line pr-8 text-[15px] leading-relaxed text-slate-600">{{ faq.answer }}</p>
        </details>
      </div>
    </section>

    <template v-if="extras">
      <ProductReviews :product-id="product.id" :slug="product.slug" :reviews="extras.reviews" :csrf-token="extras.csrfToken" />
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

<style scoped>
@media (max-width: 820px) {
  .pdp-photo { margin-inline: calc(50% - 50vw); }
  .pdp-sheet { position: relative; z-index: 1; margin: -22px calc(50% - 50vw) 0; padding: 18px 16px 0; border-radius: 16px 16px 0 0; background: #fff; box-shadow: 0 -10px 24px rgb(6 21 55 / 10%); }
  /* One control for the description on phones: the button under it. */
  .pdp-description :deep(details) { border: 0; }
  .pdp-description :deep(details > summary) { display: none; }
  .pdp-description:not(.is-open) { max-height: 460px; overflow: hidden; mask-image: linear-gradient(180deg, #000 70%, transparent); }
}
</style>

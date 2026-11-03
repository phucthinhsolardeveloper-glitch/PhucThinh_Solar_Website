<script setup lang="ts">
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCheckoutNoticePayload, type PublicCheckoutNoticePayload, type PublicCheckoutNoticeForm } from '#shared/contracts/public-checkout'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const { cart, loading, error, refresh, mutate } = useMigrationCart()
const links = useMigrationLinks()
const isMigrationPreview = links.active
const checkoutNotice = ref<PublicCheckoutNoticePayload | null>(null)
const checkoutDraft = useState<PublicCheckoutNoticeForm | null>('pts-checkout-form-draft', () => null)
const { data: initialCart, error: initialCartError } = await useFetch<unknown>('/_migration/cart', { key: 'migration-cart-page', default: () => null })
if (isPublicCartPayload(initialCart.value)) cart.value = initialCart.value
else if (!isMigrationPreview.value && import.meta.server) setResponseStatus(502, 'Bad Gateway')
if (!isMigrationPreview.value && import.meta.server && initialCartError.value) setResponseStatus(502, 'Bad Gateway')

usePublicSocialMeta({
  enabled: computed(() => !isMigrationPreview.value),
  title: 'Giỏ hàng | Phúc Thịnh Solar',
  description: computed(() => site.value.brand.name || 'Phúc Thịnh Solar'),
  canonicalPath: '/gio-hang/',
  image: computed(() => site.value.brand.logo),
})

const quantityDraft = reactive<Record<string, number>>({})

watchEffect(() => {
  for (const item of cart.value?.items ?? []) {
    if (!(item.code in quantityDraft)) quantityDraft[item.code] = item.quantity
  }
})

function formatMoney(value: number | null): string {
  return value === null ? 'Liên hệ' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

async function updateItem(code: string, productId: number, value: unknown = quantityDraft[code] ?? 1) {
  const quantity = normalizeCommerceQuantity(value)
  quantityDraft[code] = quantity
  try {
    await mutate({ action: 'update', code, productId, quantity })
  } catch {
    const current = cart.value?.items.find((item) => item.code === code)
    if (current) quantityDraft[code] = current.quantity
  }
}

async function removeItem(code: string) {
  try {
    await mutate({ action: 'delete', code })
    delete quantityDraft[code]
  } catch {
    // The composable retains the error for the alert below; avoid an unhandled
    // event-handler rejection when the PHP adapter refuses the mutation.
  }
}

const payload = computed<PublicCartPayload | null>(() => cart.value)

onMounted(async () => {
  try {
    const notice = await $fetch<unknown>('/_migration/checkout-notice')
    if (isPublicCheckoutNoticePayload(notice)) checkoutNotice.value = notice
  } catch {
    // Checkout flash is optional; cart/session rendering remains independent.
  }
})

function openCheckout() {
  checkoutDraft.value = checkoutNotice.value?.form ?? null
}
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <div class="border-b border-slate-200/80 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giỏ hàng' }]" />
      </div>
    </div>
    <main class="pts-container py-8 max-ph:py-6">
      <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="m-0 text-[clamp(30px,5vw,48px)] font-black tracking-tight">Giỏ hàng của bạn</h1>
        </div>
        <NuxtLink :to="links.generic('/san-pham/')" class="font-bold text-navy-800 underline-offset-4 hover:underline">Tiếp tục xem sản phẩm</NuxtLink>
      </div>

      <div v-if="!payload && loading" class="rounded-btn border border-gray-200 p-8 text-slate-500" role="status">Đang tải giỏ hàng…</div>
      <div v-else-if="!payload" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        <strong>Không thể tải session giỏ hàng.</strong>
        <p class="mb-0 mt-2">{{ error || 'PHP cart API local chưa khả dụng.' }}</p>
        <button type="button" class="public-neutral-button mt-4 rounded-chip px-4 py-2 font-bold" @click="refresh()">Thử lại</button>
      </div>

      <template v-else>
        <p v-if="error" class="mb-6 rounded-btn border border-red-200 bg-red-50 p-4 text-red-800" role="alert">{{ error }}</p>
        <div
          v-if="checkoutNotice && (checkoutNotice.messages.length || checkoutNotice.toast)"
          class="mb-6 rounded-btn border border-red-200 bg-red-50 p-4 text-red-800"
          :role="checkoutNotice.messages.length || checkoutNotice.toast?.status === 'error' ? 'alert' : 'status'"
        >
          <p v-if="checkoutNotice.toast" class="mb-1">{{ checkoutNotice.toast.text }}</p>
          <ul v-if="checkoutNotice.messages.length" class="mb-0 list-disc pl-5">
            <li v-for="(message, index) in checkoutNotice.messages" :key="index">{{ message }}</li>
          </ul>
        </div>
        <div v-if="!payload.items.length" class="rounded-btn border border-gray-200 px-6 py-12 text-center">
          <div class="mx-auto mb-4 inline-flex size-14 items-center justify-center rounded-full bg-gold-500/15 text-gold-700">
            <UIcon name="i-lucide-shopping-bag" class="size-7" aria-hidden="true" />
          </div>
          <h2 class="mb-2 text-2xl font-black text-navy-900">Giỏ hàng đang trống</h2>
          <p class="mx-auto mb-6 max-w-[520px] text-slate-600">
            Chọn sản phẩm có giá công khai để thêm vào giỏ hàng, hoặc gửi yêu cầu để đội ngũ Phúc Thịnh Solar bóc tách vật tư và báo giá trọn gói theo công trình.
          </p>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center gap-2 rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400">
              <span>Xem sản phẩm</span>
              <span aria-hidden="true">→</span>
            </NuxtLink>
            <button
              type="button"
              class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-chip border border-navy-800/20 bg-white px-5 font-bold text-navy-900 transition-colors hover:border-navy-800 hover:bg-slate-50"
              @click="openConsultation('Giỏ hàng: Tư vấn báo giá vật tư')"
            >
              <UIcon name="i-lucide-headset" class="size-4 text-gold-600" aria-hidden="true" />
              <span>Yêu cầu báo giá theo công trình</span>
            </button>
          </div>

          <div v-if="site.productCategories.length" class="mx-auto mt-10 max-w-[880px] border-t border-slate-200/80 pt-7 text-left">
            <p class="m-0 mb-3.5 text-center text-sm font-bold text-navy-800">Khám phá nhanh theo nhóm thiết bị chính hãng</p>
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
              <NuxtLink
                v-for="cat in site.productCategories.slice(0, 6)"
                :key="cat.slug"
                :to="links.generic(cat.url)"
                class="group flex items-center justify-between gap-3 rounded-[4px] border-l-[3px] border-gold-500 bg-slate-50 px-4 py-3 text-sm font-bold text-navy-900 no-underline transition-colors hover:bg-gold-500/10"
              >
                <span class="truncate">{{ cat.name }}</span>
                <UIcon name="i-lucide-arrow-right" class="size-4 shrink-0 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-gold-700" aria-hidden="true" />
              </NuxtLink>
            </div>
          </div>
        </div>

        <div v-else class="grid gap-8 nav:grid-cols-[minmax(0,1fr)_340px]">
          <section aria-label="Sản phẩm trong giỏ" class="divide-y divide-gray-200 border-y border-gray-200">
            <article v-for="item in payload.items" :key="item.code" class="grid grid-cols-[96px_minmax(0,1fr)] gap-x-4 gap-y-2 py-5 tb:grid-cols-[112px_minmax(0,1fr)_150px_156px] tb:items-center">
              <NuxtLink :to="links.product(item.slug)" class="row-span-3 grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50 tb:row-auto">
                <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-contain" loading="lazy">
                <span v-else class="px-2 text-center text-xs text-slate-400">Chưa có ảnh</span>
              </NuxtLink>

              <div class="col-start-2 row-start-1 min-w-0 tb:col-start-2 tb:row-auto">
                <NuxtLink :to="links.product(item.slug)" class="font-extrabold leading-snug text-navy-800 no-underline hover:underline">{{ item.name }}</NuxtLink>
                <div v-if="item.color || item.size" class="mt-1 text-sm text-slate-500">
                  <span v-if="item.color">Màu: {{ item.color }}</span><span v-if="item.color && item.size"> · </span><span v-if="item.size">Size: {{ item.size }}</span>
                </div>
              </div>

              <div class="col-start-2 row-start-2 text-sm tb:col-start-3 tb:row-auto tb:text-right">
                <strong class="block text-red-600">{{ formatMoney(item.lineTotal) }}</strong>
                <small class="text-slate-400">{{ formatMoney(item.unitPrice) }} / sản phẩm</small>
              </div>

              <div class="relative col-start-2 row-start-3 w-fit self-center tb:col-start-4 tb:row-auto">
                <QuantityStepper
                  :model-value="quantityDraft[item.code] ?? item.quantity"
                  label="Số lượng"
                  :disabled="loading"
                  @update:model-value="quantityDraft[item.code] = $event"
                  @change="updateItem(item.code, item.productId, $event)"
                />
                <button
                  type="button"
                  class="absolute left-0 top-full mt-1 text-sm font-bold text-red-700 hover:underline disabled:opacity-50"
                  :disabled="loading"
                  @click="removeItem(item.code)"
                >Xóa</button>
              </div>
            </article>
          </section>

          <aside class="h-fit rounded-btn border border-slate-200/90 bg-white p-5 shadow-sm nav:sticky nav:top-24">
            <h2 class="mb-5 text-xl font-black">Tóm tắt đơn hàng</h2>
            <div class="flex items-center justify-between border-b border-gray-200 pb-4">
              <span class="text-slate-500">{{ payload.quantityCount }} sản phẩm</span>
              <strong>{{ formatMoney(payload.subtotal) }}</strong>
            </div>
            <div class="flex items-center justify-between py-4 text-lg">
              <strong>Tạm tính</strong>
              <strong class="text-red-600">{{ formatMoney(payload.subtotal) }}</strong>
            </div>
            <p v-if="!payload.canCheckout" class="rounded-chip bg-amber-50 p-3 text-sm leading-relaxed text-amber-800">
              Giỏ có sản phẩm cần báo giá; hãy liên hệ tư vấn trước khi đặt hàng.
            </p>
            <p v-else-if="isMigrationPreview" class="mt-4 rounded-chip bg-slate-50 p-3 text-sm leading-relaxed text-slate-600" role="status">
              Checkout đang ở chế độ preview chỉ đọc; trang này không nhận thông tin giao hàng và không tạo đơn hàng.
            </p>
            <NuxtLink
              v-else
              to="/thanh-toan/"
              @click="openCheckout"
              class="mt-4 flex min-h-12 w-full items-center justify-center rounded-chip bg-gold-500 px-5 text-center font-extrabold text-navy-950 no-underline hover:bg-gold-600"
            >
              Tiến hành thanh toán
            </NuxtLink>
          </aside>
        </div>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

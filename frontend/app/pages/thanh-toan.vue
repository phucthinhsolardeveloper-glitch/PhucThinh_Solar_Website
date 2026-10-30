<script setup lang="ts">
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCheckoutPayload, isPublicCheckoutWardsPayload, type PublicCheckoutPayload, type PublicCheckoutOption, type PublicCheckoutNoticeForm } from '#shared/contracts/public-checkout'
import { CART_CHECKOUT_POST_PATH, phpPublicRouteUrl } from '#shared/public-route-gate'
import checkoutPublicRoute from '~/middleware/checkout-public-route'

definePageMeta({
  layout: false,
  middleware: checkoutPublicRoute,
})

const { site } = await usePublicSite()
const runtimeConfig = useRuntimeConfig()
const checkoutPostAction = phpPublicRouteUrl(runtimeConfig.public.phpPublicOrigin, CART_CHECKOUT_POST_PATH)
const { data: cartData } = await useFetch<unknown>('/_migration/cart', { key: 'migration-checkout-cart', default: () => null })
const { data: checkoutData, error: checkoutLoadError } = await useFetch<unknown>('/_migration/checkout', { key: 'migration-checkout-meta', default: () => null })
const cart = computed<PublicCartPayload | null>(() => isPublicCartPayload(cartData.value) ? cartData.value : null)
const checkout = computed<PublicCheckoutPayload | null>(() => isPublicCheckoutPayload(checkoutData.value) ? checkoutData.value : null)

const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  city: 0,
  ward: 0,
  address: '',
  requirements: '',
  payment: 0,
})
const wards = ref<PublicCheckoutOption[]>([])
const shippingAmount = ref<number | null>(null)
const shippingPending = ref(false)
const restoringDraft = ref(false)
const checkoutDraft = useState<PublicCheckoutNoticeForm | null>('pts-checkout-form-draft', () => null)

function formatMoney(value: number | null): string {
  return value === null ? 'Chờ xác nhận' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

async function loadWards() {
  wards.value = []
  form.ward = 0
  shippingAmount.value = null
  shippingPending.value = false
  if (!form.city) return
  try {
    const payload = await $fetch<unknown>('/_migration/checkout-wards', { query: { city: String(form.city) } })
    if (isPublicCheckoutWardsPayload(payload)) wards.value = payload.wards
  } catch {
    wards.value = []
  }
}

onMounted(async () => {
  const draft = checkoutDraft.value
  if (!draft) return
  checkoutDraft.value = null
  restoringDraft.value = true
  form.fullname = draft.fullname ?? ''
  form.phone = draft.phone ?? ''
  form.email = draft.email ?? ''
  form.address = draft.address ?? ''
  form.requirements = draft.requirements ?? ''
  form.payment = draft.payments ? Number(draft.payments) : 0
  const city = draft.city ? Number(draft.city) : 0
  if (Number.isSafeInteger(city) && city > 0) {
    form.city = city
    await loadWards()
    const ward = draft.ward ? Number(draft.ward) : 0
    if (Number.isSafeInteger(ward) && wards.value.some((option) => option.id === ward)) form.ward = ward
  }
  restoringDraft.value = false
})

async function quoteShipping() {
  shippingAmount.value = null
  shippingPending.value = false
  if (!form.city || !form.ward || !checkout.value?.shippingEnabled) return
  try {
    const payload: any = await $fetch('/_migration/shipping-quote', { method: 'POST', body: { city: form.city, ward: form.ward } })
    if (payload?.ok && payload?.priced && typeof payload?.selected?.amount === 'number') shippingAmount.value = Math.max(0, Math.round(payload.selected.amount))
    else shippingPending.value = true
  } catch {
    shippingPending.value = true
  }
}

watch(() => form.city, () => { if (!restoringDraft.value) void loadWards() })
watch(() => form.ward, () => void quoteShipping())

const orderTotal = computed(() => {
  if (!cart.value || cart.value.subtotal === null) return null
  return cart.value.subtotal + (shippingAmount.value ?? 0)
})

useSeoMeta({ title: 'Thanh toán — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1320px] px-4 py-10 max-ph:py-7">
      <SiteBreadcrumb :items="[{ label: 'Giỏ hàng', to: '/gio-hang/' }, { label: 'Thanh toán' }]" />
      <h1 class="mb-8 text-[clamp(30px,5vw,48px)] font-black tracking-tight">Thông tin thanh toán</h1>

      <div v-if="checkoutLoadError || !checkout || !cart" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        Không thể tải dữ liệu checkout từ PHP local.
      </div>
      <div v-else-if="!checkout.cartReady || !cart.canCheckout" class="rounded-btn border border-amber-200 bg-amber-50 p-6 text-amber-900">
        <h2 class="mb-2 text-xl font-black">Giỏ hàng chưa thể thanh toán</h2>
        <p class="mb-4">Giỏ đang trống hoặc có sản phẩm cần liên hệ báo giá.</p>
        <NuxtLink to="/gio-hang/" class="font-bold underline">Quay lại giỏ hàng</NuxtLink>
      </div>

      <form v-else method="post" :action="checkoutPostAction" class="grid gap-8 nav:grid-cols-[minmax(0,1fr)_380px]">
        <input type="hidden" name="form_csrf" :value="checkout.csrfToken">
        <input type="hidden" name="checkout_token" :value="checkout.checkoutToken">
        <section class="space-y-6">
          <div class="rounded-btn border border-gray-200 p-5">
            <h2 class="mb-5 text-xl font-black">Thông tin giao hàng</h2>
            <div class="grid gap-4 tb:grid-cols-2">
              <label class="grid gap-1.5 text-sm font-bold">Họ và tên
                <input v-model.trim="form.fullname" name="dataOrder[fullname]" autocomplete="name" maxlength="150" required class="h-11 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600">
              </label>
              <label class="grid gap-1.5 text-sm font-bold">Số điện thoại
                <input v-model.trim="form.phone" name="dataOrder[phone]" autocomplete="tel" inputmode="tel" maxlength="30" required class="h-11 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600">
              </label>
              <label class="grid gap-1.5 text-sm font-bold tb:col-span-2">Email
                <input v-model.trim="form.email" type="email" name="dataOrder[email]" autocomplete="email" maxlength="190" required class="h-11 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600">
              </label>
              <label class="grid gap-1.5 text-sm font-bold">Tỉnh/Thành phố
                <select v-model.number="form.city" name="dataOrder[city]" required class="h-11 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600">
                  <option :value="0">Chọn tỉnh/thành</option>
                  <option v-for="city in checkout.cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                </select>
              </label>
              <label class="grid gap-1.5 text-sm font-bold">Phường/Xã
                <select v-model.number="form.ward" name="dataOrder[ward]" :disabled="!form.city" required class="h-11 rounded-chip border border-gray-300 bg-white px-3 font-normal outline-none focus:border-gold-600 disabled:bg-slate-50">
                  <option :value="0">Chọn phường/xã</option>
                  <option v-for="ward in wards" :key="ward.id" :value="ward.id">{{ ward.name }}</option>
                </select>
              </label>
              <label class="grid gap-1.5 text-sm font-bold tb:col-span-2">Địa chỉ chi tiết
                <input v-model.trim="form.address" name="dataOrder[address]" autocomplete="street-address" maxlength="255" required class="h-11 rounded-chip border border-gray-300 px-3 font-normal outline-none focus:border-gold-600">
              </label>
              <label class="grid gap-1.5 text-sm font-bold tb:col-span-2">Yêu cầu khác
                <textarea v-model.trim="form.requirements" name="dataOrder[requirements]" maxlength="1000" rows="4" class="rounded-chip border border-gray-300 px-3 py-2 font-normal outline-none focus:border-gold-600" />
              </label>
            </div>
          </div>

          <div class="rounded-btn border border-gray-200 p-5">
            <h2 class="mb-5 text-xl font-black">Hình thức thanh toán</h2>
            <div class="space-y-3">
              <label v-for="payment in checkout.payments" :key="payment.id" class="flex cursor-pointer items-start gap-3 rounded-chip border border-gray-200 p-4 has-[:checked]:border-gold-600 has-[:checked]:bg-amber-50/40">
                <input v-model.number="form.payment" type="radio" name="dataOrder[payments]" :value="payment.id" required class="mt-1">
                <span>
                  <strong class="block">{{ payment.name }}</strong>
                  <small v-if="payment.description" class="mt-1 block whitespace-pre-line leading-relaxed text-slate-500">{{ payment.description }}</small>
                </span>
              </label>
            </div>
          </div>
        </section>

        <aside class="h-fit rounded-btn border border-gray-200 p-5 nav:sticky nav:top-5">
          <h2 class="mb-5 text-xl font-black">Đơn hàng</h2>
          <div class="max-h-[340px] divide-y divide-gray-100 overflow-auto">
            <div v-for="item in cart.items" :key="item.code" class="flex justify-between gap-3 py-3 first:pt-0">
              <span class="min-w-0 text-sm leading-snug"><strong>{{ item.quantity }}×</strong> {{ item.name }}</span>
              <strong class="shrink-0 text-sm text-red-600">{{ formatMoney(item.lineTotal) }}</strong>
            </div>
          </div>
          <div class="mt-4 space-y-3 border-t border-gray-200 pt-4">
            <div class="flex justify-between"><span class="text-slate-500">Tạm tính</span><strong>{{ formatMoney(cart.subtotal) }}</strong></div>
            <div v-if="checkout.shippingEnabled" class="flex justify-between gap-4">
              <span class="text-slate-500">Vận chuyển</span>
              <strong class="text-right">{{ shippingPending ? 'Sẽ xác nhận sau' : formatMoney(shippingAmount) }}</strong>
            </div>
            <div class="flex justify-between border-t border-gray-200 pt-3 text-lg"><strong>Tổng dự kiến</strong><strong class="text-red-600">{{ formatMoney(orderTotal) }}</strong></div>
          </div>

          <button type="submit" name="thanhtoan" value="1" class="mt-5 min-h-12 w-full rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 hover:bg-gold-600">
            Đặt hàng
          </button>
        </aside>
      </form>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

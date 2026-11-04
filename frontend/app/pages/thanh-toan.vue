<script setup lang="ts">
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCheckoutPayload, isPublicCheckoutWardsPayload, type PublicCheckoutPayload, type PublicCheckoutOption, type PublicCheckoutNoticeForm } from '#shared/contracts/public-checkout'
import { CART_CHECKOUT_POST_PATH } from '#shared/public-route-gate'
import CommerceSteps from '~/components/commerce/CommerceSteps.vue'
import AnimatedMoney from '~/components/commerce/AnimatedMoney.vue'
definePageMeta({
  layout: false,
  middleware: 'checkout-public-route',
})

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const checkoutPostAction = CART_CHECKOUT_POST_PATH
const quickRequirementTags = [
  'Cần xuất hóa đơn VAT',
  'Giao trong giờ hành chính',
  'Gọi trước khi giao 30 phút',
  'Cần kỹ thuật hướng dẫn đấu nối',
]

function appendRequirement(tag: string) {
  const current = form.requirements.trim()
  if (current.includes(tag)) return
  form.requirements = current ? `${current}; ${tag}` : tag
}
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
const wardsLoading = ref(false)
const wardsError = ref('')
const shippingAmount = ref<number | null>(null)
const shippingPending = ref(false)
const shippingLoading = ref(false)
const shippingError = ref('')
const restoringDraft = ref(false)
const checkoutDraft = useState<PublicCheckoutNoticeForm | null>('pts-checkout-form-draft', () => null)
let wardsRequestId = 0
let shippingRequestId = 0
let wardsController: AbortController | null = null
let shippingController: AbortController | null = null

function formatMoney(value: number | null): string {
  return value === null ? 'Chờ xác nhận' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

async function loadWards() {
  const requestId = ++wardsRequestId
  wardsController?.abort()
  wardsController = null
  shippingRequestId++
  shippingController?.abort()
  shippingController = null
  wards.value = []
  form.ward = 0
  shippingAmount.value = null
  shippingPending.value = false
  shippingLoading.value = false
  shippingError.value = ''
  wardsError.value = ''
  const city = form.city
  if (!city) {
    wardsLoading.value = false
    return
  }

  const controller = new AbortController()
  wardsController = controller
  wardsLoading.value = true
  try {
    const payload = await $fetch<unknown>('/_migration/checkout-wards', {
      query: { city: String(city) },
      signal: controller.signal,
    })
    if (requestId !== wardsRequestId || form.city !== city) return
    if (isPublicCheckoutWardsPayload(payload)) wards.value = payload.wards
    else wardsError.value = 'Không thể đọc danh sách phường/xã. Vui lòng thử lại.'
  } catch {
    if (requestId === wardsRequestId && form.city === city) {
      wardsError.value = 'Không thể tải danh sách phường/xã. Vui lòng thử lại.'
    }
  } finally {
    if (requestId === wardsRequestId) {
      wardsLoading.value = false
      wardsController = null
    }
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
  const requestId = ++shippingRequestId
  shippingController?.abort()
  shippingController = null
  shippingAmount.value = null
  shippingPending.value = false
  shippingLoading.value = false
  shippingError.value = ''
  const city = form.city
  const ward = form.ward
  if (!city || !ward || !checkout.value?.shippingEnabled) return

  const controller = new AbortController()
  shippingController = controller
  shippingLoading.value = true
  try {
    const payload: any = await $fetch('/_migration/shipping-quote', {
      method: 'POST',
      body: { city, ward },
      signal: controller.signal,
    })
    if (requestId !== shippingRequestId || form.city !== city || form.ward !== ward) return
    if (payload?.ok && payload?.priced && typeof payload?.selected?.amount === 'number') shippingAmount.value = Math.max(0, Math.round(payload.selected.amount))
    else shippingPending.value = true
  } catch {
    if (requestId === shippingRequestId && form.city === city && form.ward === ward) {
      shippingPending.value = true
      shippingError.value = 'Chưa thể tính phí vận chuyển. Phí sẽ được xác nhận sau.'
    }
  } finally {
    if (requestId === shippingRequestId) {
      shippingLoading.value = false
      shippingController = null
    }
  }
}

watch(() => form.city, () => { if (!restoringDraft.value) void loadWards() })
watch(() => form.ward, () => void quoteShipping())

onBeforeUnmount(() => {
  wardsRequestId++
  shippingRequestId++
  wardsController?.abort()
  shippingController?.abort()
})

const orderTotal = computed(() => {
  if (!cart.value || cart.value.subtotal === null) return null
  return cart.value.subtotal + (shippingAmount.value ?? 0)
})

useSeoMeta({ title: 'Thanh toán — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })

// Inline validation once a field has been left, so errors never shout before typing.
const touched = reactive<Record<string, boolean>>({})
const fieldState = computed(() => ({
  fullname: form.fullname.length >= 2,
  phone: /^(?:\+?84|0)(?:\d[\s.-]?){8,10}$/.test(form.phone),
  email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(form.email),
  address: form.address.length >= 5,
}))
function stateOf(field: keyof typeof fieldState.value) {
  if (!touched[field]) return ''
  return fieldState.value[field] ? 'is-valid' : 'is-invalid'
}
const fieldHints: Record<string, string> = {
  fullname: 'Vui lòng nhập họ tên người nhận.',
  phone: 'Số điện thoại chưa đúng (ví dụ 0944 555 957).',
  email: 'Email chưa đúng định dạng.',
  address: 'Vui lòng nhập số nhà, tên đường.',
}

function paymentIcon(name: string): string {
  if (/chuyển khoản|ngân hàng/i.test(name)) return 'i-lucide-landmark'
  if (/giao hàng|nhận hàng|cod/i.test(name)) return 'i-lucide-hand-coins'
  if (/công ty|văn phòng/i.test(name)) return 'i-lucide-building-2'
  return 'i-lucide-credit-card'
}

// Native POST keeps the PHP contract; this only blocks a second click while the browser navigates.
const submitting = ref(false)
function onSubmit(event: Event) {
  if (submitting.value) { event.preventDefault(); return }
  for (const key of Object.keys(fieldState.value)) touched[key] = true
  submitting.value = true
}
onMounted(() => { window.addEventListener('pageshow', () => { submitting.value = false }) })
</script>

<template>
  <div class="min-h-screen bg-[#f6f8fb] text-navy-800">
    <SiteHeader :site="site" />
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giỏ hàng', to: '/gio-hang/' }, { label: 'Thanh toán' }]" />
      </div>
    </div>
    <main class="pts-container py-8 max-ph:py-6 checkout-main">
      <header class="checkout-head">
        <h1 class="m-0 text-[clamp(30px,4vw,46px)] font-black tracking-tight text-navy-950">Thông tin thanh toán</h1>
        <CommerceSteps current="checkout" />
      </header>

      <div v-if="checkoutLoadError || !checkout || !cart" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        Không thể tải dữ liệu checkout từ PHP local.
      </div>
      <div v-else-if="!checkout.cartReady || !cart.canCheckout" class="checkout-card p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <h2 class="mb-2 text-xl font-black text-navy-950">Giỏ hàng chưa thể thanh toán</h2>
            <p class="mb-0 text-sm leading-relaxed text-slate-600 sm:text-base">
              Giỏ đang trống hoặc có sản phẩm cần liên hệ báo giá. Vui lòng kiểm tra lại giỏ hàng hoặc gửi yêu cầu để bộ phận kinh doanh báo giá trực tiếp.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <NuxtLink to="/gio-hang/" class="inline-flex min-h-11 items-center justify-center rounded-card bg-navy-900 px-5 py-2.5 text-sm font-bold text-white no-underline transition-colors hover:bg-navy-800">
              Quay lại giỏ hàng
            </NuxtLink>
            <button
              type="button"
              class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-card bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
              @click="openConsultation('Thanh toán: Yêu cầu báo giá đơn hàng')"
            >
              <span>Nhận báo giá nhanh</span>
              <span aria-hidden="true">→</span>
            </button>
          </div>
        </div>

        <ul class="checkout-assure">
          <li>
            <UIcon name="i-lucide-scan-search" class="size-6" aria-hidden="true" />
            <strong>Đơn hàng có sản phẩm báo giá</strong>
            <p>Sản phẩm cần báo giá chưa thể đặt trực tuyến. Liên hệ để xác nhận cấu hình và giá trước khi đặt hàng.</p>
          </li>
          <li>
            <UIcon name="i-lucide-file-check" class="size-6" aria-hidden="true" />
            <strong>Thông tin hóa đơn và chứng từ</strong>
            <p>Trao đổi nhu cầu hóa đơn và chứng từ với tư vấn viên để được xác nhận theo từng đơn hàng.</p>
          </li>
          <li v-if="site.brand.hotline">
            <UIcon name="i-lucide-phone-call" class="size-6" aria-hidden="true" />
            <strong>Hotline hỗ trợ đặt hàng</strong>
            <p>Liên hệ trực tiếp <a :href="`tel:${site.brand.hotline.replace(/\D/g, '')}`">{{ site.brand.hotline }}</a>.</p>
          </li>
        </ul>
      </div>

      <form v-else method="post" :action="checkoutPostAction" class="grid items-start gap-6 nav:grid-cols-[minmax(0,1fr)_400px] nav:gap-8" @submit="onSubmit">
        <input type="hidden" name="form_csrf" :value="checkout.csrfToken">
        <input type="hidden" name="checkout_token" :value="checkout.checkoutToken">
        <section class="space-y-5">
          <div class="checkout-card">
            <h2 class="checkout-card__title"><UIcon name="i-lucide-user-round" class="size-5" aria-hidden="true" />Người nhận</h2>
            <div class="grid gap-4 tb:grid-cols-2">
              <label class="checkout-field" :class="stateOf('fullname')">
                <span>Họ và tên</span>
                <span class="checkout-field__control">
                  <input v-model.trim="form.fullname" name="dataOrder[fullname]" autocomplete="name" maxlength="150" required placeholder="Nguyễn Văn A" @blur="touched.fullname = true">
                  <UIcon name="i-lucide-circle-check" class="checkout-field__ok size-5" aria-hidden="true" />
                </span>
                <small v-if="stateOf('fullname') === 'is-invalid'" role="alert">{{ fieldHints.fullname }}</small>
              </label>
              <label class="checkout-field" :class="stateOf('phone')">
                <span>Số điện thoại</span>
                <span class="checkout-field__control">
                  <input v-model.trim="form.phone" name="dataOrder[phone]" autocomplete="tel" inputmode="tel" maxlength="30" required placeholder="0944 555 957" @blur="touched.phone = true">
                  <UIcon name="i-lucide-circle-check" class="checkout-field__ok size-5" aria-hidden="true" />
                </span>
                <small v-if="stateOf('phone') === 'is-invalid'" role="alert">{{ fieldHints.phone }}</small>
              </label>
              <label class="checkout-field tb:col-span-2" :class="stateOf('email')">
                <span>Email nhận xác nhận đơn</span>
                <span class="checkout-field__control">
                  <input v-model.trim="form.email" type="email" name="dataOrder[email]" autocomplete="email" maxlength="190" required placeholder="ban@email.com" @blur="touched.email = true">
                  <UIcon name="i-lucide-circle-check" class="checkout-field__ok size-5" aria-hidden="true" />
                </span>
                <small v-if="stateOf('email') === 'is-invalid'" role="alert">{{ fieldHints.email }}</small>
              </label>
            </div>
          </div>

          <div class="checkout-card">
            <h2 class="checkout-card__title"><UIcon name="i-lucide-map-pin" class="size-5" aria-hidden="true" />Địa chỉ giao hàng</h2>
            <div class="grid gap-4 tb:grid-cols-2">
              <label class="checkout-field">
                <span>Tỉnh/Thành phố</span>
                <span class="checkout-field__control">
                  <select v-model.number="form.city" name="dataOrder[city]" required>
                    <option :value="0">Chọn tỉnh/thành</option>
                    <option v-for="city in checkout.cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                  </select>
                </span>
              </label>
              <div class="checkout-field">
                <label for="checkout-ward">Phường/Xã</label>
                <span class="checkout-field__control">
                  <select id="checkout-ward" v-model.number="form.ward" name="dataOrder[ward]" :disabled="!form.city || wardsLoading || Boolean(wardsError)" required>
                    <option :value="0">{{ wardsLoading ? 'Đang tải…' : 'Chọn phường/xã' }}</option>
                    <option v-for="ward in wards" :key="ward.id" :value="ward.id">{{ ward.name }}</option>
                  </select>
                </span>
                <small v-if="wardsLoading" class="is-muted" role="status">Đang tải danh sách phường/xã…</small>
                <small v-else-if="wardsError" role="alert">
                  {{ wardsError }}
                  <button type="button" class="font-bold underline" @click="void loadWards()">Tải lại</button>
                </small>
              </div>
              <label class="checkout-field tb:col-span-2" :class="stateOf('address')">
                <span>Địa chỉ chi tiết</span>
                <span class="checkout-field__control">
                  <input v-model.trim="form.address" name="dataOrder[address]" autocomplete="street-address" maxlength="255" required placeholder="Số nhà, tên đường" @blur="touched.address = true">
                  <UIcon name="i-lucide-circle-check" class="checkout-field__ok size-5" aria-hidden="true" />
                </span>
                <small v-if="stateOf('address') === 'is-invalid'" role="alert">{{ fieldHints.address }}</small>
              </label>
              <div class="checkout-field tb:col-span-2">
                <label for="checkout-requirements">Yêu cầu khác <em class="font-normal not-italic text-slate-400">(không bắt buộc)</em></label>
                <div class="checkout-tags">
                  <button
                    v-for="tag in quickRequirementTags"
                    :key="tag"
                    type="button"
                    :class="{ 'is-on': form.requirements.includes(tag) }"
                    @click="appendRequirement(tag)"
                  >
                    <UIcon :name="form.requirements.includes(tag) ? 'i-lucide-check' : 'i-lucide-plus'" class="size-3.5" aria-hidden="true" />{{ tag }}
                  </button>
                </div>
                <span class="checkout-field__control">
                  <textarea id="checkout-requirements" v-model.trim="form.requirements" name="dataOrder[requirements]" maxlength="1000" rows="3" placeholder="Ví dụ: giao buổi sáng, cần hướng dẫn lắp đặt…" />
                </span>
              </div>
            </div>
          </div>

          <div class="checkout-card">
            <h2 class="checkout-card__title"><UIcon name="i-lucide-wallet" class="size-5" aria-hidden="true" />Hình thức thanh toán</h2>
            <div class="checkout-pay">
              <label v-for="payment in checkout.payments" :key="payment.id" class="checkout-pay__option">
                <input v-model.number="form.payment" type="radio" name="dataOrder[payments]" :value="payment.id" required class="sr-only">
                <span class="checkout-pay__icon" aria-hidden="true"><UIcon :name="paymentIcon(payment.name)" class="size-6" /></span>
                <span class="min-w-0 flex-1">
                  <strong class="block">{{ payment.name }}</strong>
                  <small v-if="payment.description" class="mt-1 block whitespace-pre-line leading-relaxed text-slate-500">{{ payment.description }}</small>
                </span>
                <span class="checkout-pay__radio" aria-hidden="true" />
              </label>
            </div>
          </div>
        </section>

        <aside class="checkout-summary nav:sticky nav:top-28">
          <h2 class="m-0 text-lg font-extrabold text-white">Đơn hàng của bạn</h2>
          <ul class="checkout-summary__items">
            <li v-for="item in cart.items" :key="item.code">
              <span class="checkout-summary__thumb">
                <img v-if="item.image" :src="item.image" :alt="item.name" loading="lazy">
                <b>{{ item.quantity }}</b>
              </span>
              <span class="min-w-0 flex-1 text-sm leading-snug text-blue-50">{{ item.name }}</span>
              <strong class="shrink-0 text-sm">{{ formatMoney(item.lineTotal) }}</strong>
            </li>
          </ul>
          <dl class="checkout-summary__rows">
            <div><dt>Tạm tính</dt><dd>{{ formatMoney(cart.subtotal) }}</dd></div>
            <div v-if="checkout.shippingEnabled">
              <dt>Vận chuyển</dt>
              <dd :class="{ 'is-pending': shippingLoading || shippingPending || shippingAmount === null }">
                <span v-if="shippingLoading" class="checkout-dots" aria-label="Đang tính"><i /><i /><i /></span>
                <template v-else-if="shippingPending">Sẽ xác nhận sau</template>
                <template v-else-if="shippingAmount === null">Chọn địa chỉ để tính</template>
                <AnimatedMoney v-else :value="shippingAmount" />
              </dd>
            </div>
          </dl>
          <p v-if="shippingError" class="m-0 mt-2 text-sm text-amber-200" role="status">{{ shippingError }}</p>
          <div class="checkout-summary__total">
            <span>Tổng dự kiến</span>
            <strong><AnimatedMoney :value="orderTotal" empty="Chờ xác nhận" /></strong>
          </div>

          <button type="submit" name="thanhtoan" value="1" class="checkout-summary__cta" :disabled="submitting">
            <template v-if="submitting"><span class="checkout-spinner" aria-hidden="true" />Đang đặt hàng…</template>
            <template v-else>Đặt hàng<span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span></template>
          </button>

          <div class="checkout-summary__assure">
            <UIcon name="i-lucide-shield-check" class="size-5 flex-none" aria-hidden="true" />
            <p>
              Kiểm tra sản phẩm, địa chỉ và phương thức thanh toán trước khi đặt hàng.<template v-if="site.brand.hotline"> Hỗ trợ đặt hàng: <a :href="`tel:${site.brand.hotline.replace(/\D/g, '')}`">{{ site.brand.hotline }}</a></template>
            </p>
          </div>
        </aside>

        <!-- Phones: the total and the order action stay within reach while filling the form. -->
        <div class="checkout-bar">
          <span><small>Tổng dự kiến</small><strong><AnimatedMoney :value="orderTotal" empty="Chờ xác nhận" /></strong></span>
          <button type="submit" name="thanhtoan" value="1" :disabled="submitting">{{ submitting ? 'Đang đặt…' : 'Đặt hàng' }}</button>
        </div>
      </form>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

<style scoped>
.checkout-head { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 520px); align-items: center; gap: 24px 48px; margin: 8px 0 clamp(24px, 3vw, 40px); }
.checkout-card { padding: clamp(18px, 2vw, 28px); border: 1px solid rgb(10 34 81 / 7%); border-radius: var(--radius-card); background: #fff; }
.checkout-card__title { display: flex; align-items: center; gap: 10px; margin: 0 0 18px; color: var(--color-navy-950); font-size: 19px; font-weight: 850; }
.checkout-card__title :deep(.iconify), .checkout-card__title > :first-child { color: var(--color-gold-600); }
.checkout-field { display: grid; align-content: start; gap: 7px; color: var(--color-navy-900); font-size: 14px; font-weight: 700; }
.checkout-field__control { position: relative; display: flex; align-items: center; }
.checkout-field input, .checkout-field select, .checkout-field textarea { width: 100%; min-height: 50px; padding: 0 44px 0 14px; border: 1px solid rgb(10 34 81 / 16%); border-radius: var(--radius-card); background: #fff; color: var(--color-navy-950); font: inherit; font-size: 15.5px; font-weight: 500; outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
.checkout-field textarea { padding: 12px 14px; resize: vertical; }
.checkout-field select { padding-right: 14px; }
.checkout-field input:focus, .checkout-field select:focus, .checkout-field textarea:focus { border-color: var(--color-gold-500); box-shadow: 0 0 0 4px rgb(228 168 73 / 18%); }
.checkout-field input::placeholder, .checkout-field textarea::placeholder { color: #a0aec0; font-weight: 400; }
.checkout-field select:disabled { background: #f6f8fb; color: #94a3b8; }
.checkout-field__ok { position: absolute; right: 14px; color: #16a34a; opacity: 0; transform: scale(0.6); transition: opacity 0.2s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); pointer-events: none; }
.checkout-field.is-valid .checkout-field__ok { opacity: 1; transform: none; }
.checkout-field.is-invalid input { border-color: #dc2626; }
.checkout-field.is-invalid input:focus { box-shadow: 0 0 0 4px rgb(220 38 38 / 14%); }
.checkout-field small { color: #b91c1c; font-size: 13px; font-weight: 600; }
.checkout-field small.is-muted { color: #64748b; }
.checkout-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.checkout-tags button { display: inline-flex; min-height: 34px; align-items: center; gap: 6px; padding: 0 12px; border: 1px solid rgb(10 34 81 / 12%); border-radius: 999px; background: #fff; color: var(--color-navy-800); font-size: 13px; font-weight: 600; cursor: pointer; transition: border-color 0.2s ease, background-color 0.2s ease; }
.checkout-tags button:hover { border-color: var(--color-gold-500); }
.checkout-tags button.is-on { border-color: var(--color-gold-500); background: var(--color-gold-50); color: var(--color-navy-950); }
.checkout-pay { display: grid; gap: 10px; }
.checkout-pay__option { display: flex; align-items: center; gap: 14px; padding: 16px; border: 1.5px solid rgb(10 34 81 / 10%); border-radius: var(--radius-card); background: #fff; cursor: pointer; transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease; }
.checkout-pay__option:hover { border-color: rgb(201 150 46 / 55%); }
.checkout-pay__option:has(input:checked) { border-color: var(--color-gold-500); background: #fffbf2; box-shadow: 0 12px 28px -22px rgb(201 150 46 / 80%); }
.checkout-pay__option:has(input:focus-visible) { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.checkout-pay__icon { display: grid; width: 48px; height: 48px; flex: none; place-items: center; border-radius: var(--radius-card); background: #f1f5f9; color: var(--color-navy-800); transition: background-color 0.2s ease, color 0.2s ease; }
.checkout-pay__option:has(input:checked) .checkout-pay__icon { background: var(--color-navy-950); color: var(--color-gold-300); }
.checkout-pay__radio { position: relative; width: 22px; height: 22px; flex: none; border: 2px solid #cbd5e1; border-radius: 50%; transition: border-color 0.2s ease; }
.checkout-pay__radio::after { content: ''; position: absolute; inset: 3px; border-radius: 50%; background: var(--color-gold-500); transform: scale(0); transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
.checkout-pay__option:has(input:checked) .checkout-pay__radio { border-color: var(--color-gold-500); }
.checkout-pay__option:has(input:checked) .checkout-pay__radio::after { transform: scale(1); }

.checkout-summary { overflow: hidden; padding: clamp(22px, 2vw, 30px); border-radius: var(--radius-card); background: radial-gradient(120% 80% at 100% 0%, rgb(228 168 73 / 18%), transparent 55%), var(--color-navy-950); color: #fff; }
.checkout-summary__items { display: grid; gap: 12px; max-height: 300px; margin: 18px 0 0; padding: 0 4px 16px 0; overflow-y: auto; border-bottom: 1px solid rgb(255 255 255 / 12%); list-style: none; scrollbar-width: thin; }
.checkout-summary__items li { display: flex; align-items: center; gap: 12px; }
.checkout-summary__thumb { position: relative; display: grid; width: 52px; height: 52px; flex: none; place-items: center; border-radius: var(--radius-card); background: #fff; }
.checkout-summary__thumb img { width: 88%; height: 88%; object-fit: contain; }
.checkout-summary__thumb b { position: absolute; top: -6px; right: -6px; display: grid; min-width: 22px; height: 22px; place-items: center; padding: 0 5px; border-radius: 999px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 12px; }
.checkout-summary__rows { display: grid; gap: 10px; margin: 16px 0 0; }
.checkout-summary__rows > div { display: flex; justify-content: space-between; gap: 12px; font-size: 15px; }
.checkout-summary__rows dt { color: #b9c6d6; }
.checkout-summary__rows dd { margin: 0; font-weight: 700; text-align: right; }
.checkout-summary__rows dd.is-pending { color: #b9c6d6; font-weight: 600; }
.checkout-summary__total { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgb(255 255 255 / 12%); }
.checkout-summary__total span { color: #d3dcea; font-weight: 700; }
.checkout-summary__total strong { background-image: var(--pts-gold-gradient); background-clip: text; color: transparent; -webkit-text-fill-color: transparent; font-size: clamp(26px, 2.2vw, 32px); font-weight: 900; letter-spacing: -0.02em; }
.checkout-summary__cta { display: flex; width: 100%; min-height: 60px; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; padding: 0 10px 0 22px; border: 0; border-radius: 4px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-size: 17px; font-weight: 850; cursor: pointer; transition: filter 0.25s ease; }
.checkout-summary__cta > span { display: grid; width: 42px; height: 42px; place-items: center; border-radius: var(--radius-card); background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.checkout-summary__cta:hover:not(:disabled) { filter: brightness(1.06); }
.checkout-summary__cta:hover:not(:disabled) > span { transform: translateX(4px); }
.checkout-summary__cta:disabled { justify-content: center; cursor: wait; opacity: 0.85; }
.checkout-summary__cta:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.checkout-summary__assure { display: flex; gap: 10px; margin-top: 18px; padding-top: 16px; border-top: 1px solid rgb(255 255 255 / 12%); color: #c3cfdf; font-size: 13px; line-height: 1.6; }
.checkout-summary__assure :deep(.iconify), .checkout-summary__assure > :first-child { color: var(--color-gold-300); }
.checkout-summary__assure p { margin: 0; }
.checkout-summary__assure a { color: var(--color-gold-300); font-weight: 800; text-decoration: none; }
.checkout-spinner { width: 18px; height: 18px; margin-right: 10px; border: 2.5px solid rgb(6 21 55 / 25%); border-top-color: var(--color-navy-950); border-radius: 50%; animation: spin 0.8s linear infinite; }
.checkout-dots { display: inline-flex; gap: 4px; }
.checkout-dots i { width: 6px; height: 6px; border-radius: 50%; background: var(--color-gold-300); animation: dot 1s ease-in-out infinite; }
.checkout-dots i:nth-child(2) { animation-delay: 0.15s; }
.checkout-dots i:nth-child(3) { animation-delay: 0.3s; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes dot { 0%, 80%, 100% { opacity: 0.3; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }

.checkout-assure { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 24px 0 0; padding: 24px 0 0; border-top: 1px solid rgb(10 34 81 / 8%); list-style: none; }
.checkout-assure li { padding: 18px; border-radius: var(--radius-card); background: var(--color-navy-950); color: #c3cfdf; font-size: 13.5px; }
.checkout-assure li :deep(.iconify), .checkout-assure li > :first-child { color: var(--color-gold-300); }
.checkout-assure strong { display: block; margin-top: 10px; color: #fff; font-size: 15px; }
.checkout-assure p { margin: 6px 0 0; line-height: 1.6; }
.checkout-assure a { color: var(--color-gold-300); font-weight: 800; text-decoration: none; }

.checkout-bar { display: none; }
@media (max-width: 1320px) {
  .checkout-main { padding-bottom: 96px; }
  .checkout-summary__cta { display: none; }
  .checkout-bar { position: fixed; right: 0; bottom: 0; left: 0; z-index: 900; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); border-top: 1px solid rgb(10 34 81 / 10%); background: rgb(255 255 255 / 96%); box-shadow: 0 -12px 30px -18px rgb(6 21 55 / 40%); backdrop-filter: blur(10px); }
  .checkout-bar small { display: block; color: #64748b; font-size: 12px; font-weight: 600; }
  .checkout-bar strong { color: var(--color-navy-950); font-size: 19px; font-weight: 900; }
  .checkout-bar button { min-height: 50px; padding: 0 26px; border: 0; border-radius: 4px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-weight: 850; cursor: pointer; }
}
@media (max-width: 900px) {
  .checkout-head { grid-template-columns: 1fr; }
  .checkout-assure { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .checkout-field__ok, .checkout-pay__radio::after { transition: none; }
  .checkout-spinner, .checkout-dots i { animation: none; }
}
</style>

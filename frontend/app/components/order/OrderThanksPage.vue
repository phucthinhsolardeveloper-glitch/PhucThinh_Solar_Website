<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'
import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'
import { purchaseAmounts } from '#shared/purchase-amounts'
import OrderTransferCard from '~/components/order/OrderTransferCard.vue'

const { site } = await usePublicSite()
const route = useRoute()
const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()
const requestFetch = useRequestFetch()
const token = computed(() => typeof route.query.token === 'string' ? route.query.token : '')
const hasValidToken = computed(() => /^[a-f0-9]{48}$/.test(token.value))
if (import.meta.server && hasValidToken.value) useResponseHeader('Cache-Control').value = 'private, no-store'
const { data, error, pending } = await useAsyncData<unknown>(
  () => `order-thanks-${token.value}`,
  () => hasValidToken.value ? requestFetch('/_api/order-thanks', { query: { token: token.value } }) : Promise.resolve(null),
  { default: () => null },
)
const payload = computed<PublicOrderThanksPayload | null>(() => isPublicOrderThanksPayload(data.value) ? data.value : null)
const paymentState = computed(() => payload.value?.paymentState ?? 'accepted')
const paymentConfirmed = computed(() => paymentState.value === 'accepted' || paymentState.value === 'paid')
const confirmationTitle = computed(() => paymentState.value === 'failed' ? 'Thanh toán chưa thành công'
  : paymentState.value === 'pending' ? 'Đang chờ xác nhận thanh toán'
  : paymentState.value === 'awaiting_transfer' ? 'Đặt hàng thành công' : 'Đặt hàng thành công')
const transfer = computed(() => payload.value?.transfer ?? null)
/** One short sentence under the title: what happens next for this payment. */
const statusLine = computed(() => {
  const name = payload.value?.order.customerName
  if (paymentState.value === 'pending') return 'Đơn hàng đã được tạo và đang chờ VNPay xác nhận thanh toán.'
  if (paymentState.value === 'failed') return 'Giao dịch VNPay chưa thành công, đơn chưa được thanh toán. Gọi hotline nếu cần hỗ trợ.'
  if (paymentState.value === 'awaiting_transfer') return `Cảm ơn ${name || 'bạn'}. Chuyển khoản theo thông tin bên dưới để hoàn tất đơn.`
  return `Cảm ơn ${name || 'bạn'}. Phúc Thịnh Solar sẽ gọi xác nhận đơn trong giờ làm việc.`
})
const transferReported = ref(false)
const reportingTransfer = ref(false)
const reportError = ref('')
async function reportTransfer() {
  if (reportingTransfer.value) return
  reportingTransfer.value = true
  reportError.value = ''
  try {
    await $fetch('/_api/order-thanks-transferred', { method: 'POST', body: { token: token.value } })
    transferReported.value = true
  } catch {
    reportError.value = 'Chưa gửi được. Vui lòng thử lại hoặc gọi hotline.'
  } finally {
    reportingTransfer.value = false
  }
}
const isPublicRoute = route.path === ORDER_SUCCESS_PUBLIC_PATH
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
usePublicSocialMeta({
  enabled: isPublicRoute,
  title: computed(() => `${confirmationTitle.value} | Phúc Thịnh Solar`),
  description: computed(() => site.value.brand.name || 'Phúc Thịnh Solar'),
  canonicalPath: ORDER_SUCCESS_PUBLIC_PATH,
  image: computed(() => site.value.brand.logo),
})
if (import.meta.server && isPublicRoute) {
  // Without an order session the page shows a generic thank-you. A 404 from
  // the read-only detail API also means “no session details”, not no page.
  if (errorStatus && errorStatus !== 404) setResponseStatus(502, 'Bad Gateway')
}

const trackedConversions = new Set<string>()
function trackOrderConversion() {
  const current = payload.value
  const currentToken = token.value
  if (!import.meta.client || !current?.conversionPending || !['accepted', 'paid', 'awaiting_transfer'].includes(current.paymentState)
    || !/^[a-f0-9]{48}$/.test(currentToken) || trackedConversions.has(currentToken)) return
  const browserWindow = window as Window & { dataLayer?: Array<Record<string, unknown>> }
  const dataLayer = browserWindow.dataLayer ??= []
  dataLayer.push({
    event: 'order_success',
    order_code: current.order.code,
    order_value: current.order.totalPrice,
    currency: 'VND',
  })
  // The same order in the GA4 shape, for Google Ads / GA4 purchase conversions (one per order, same guard as above).
  const items = (current.order.items ?? []).map((line) => ({ item_id: String(line.id), item_name: line.name, price: line.price, quantity: line.quantity }))
  pushDataLayer('purchase', { ecommerce: { transaction_id: current.order.code, ...purchaseAmounts(current.order), currency: 'VND', ...(items.length ? { items } : {}) } })
  trackedConversions.add(currentToken)
}
onMounted(trackOrderConversion)
watch([payload, token], trackOrderConversion)

</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: confirmationTitle }]" />
      </div>
    </div>
    <main class="pts-container py-[60px] text-center">
      <div v-if="pending" class="text-slate-500" role="status">Đang xác nhận đơn hàng…</div>
      <div v-else-if="error && errorStatus !== 404" class="mx-auto max-w-[760px]">
        <h1 class="mb-3 text-2xl font-black text-navy-800">Tạm thời không thể xác nhận đơn hàng</h1>
        <p class="mb-5 text-slate-600">Hệ thống đang gặp sự cố khi tải thông tin đơn hàng. Vui lòng thử lại sau.</p>
        <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline">Tiếp tục mua sắm</NuxtLink>
      </div>
      <div v-else class="mx-auto max-w-[880px]">
        <span class="mx-auto grid size-14 place-items-center rounded-full" :class="paymentConfirmed ? 'bg-emerald-50 text-emerald-600' : paymentState === 'failed' ? 'bg-red-50 text-red-600' : 'bg-gold-100 text-gold-700'" aria-hidden="true">
          <UIcon :name="paymentConfirmed ? 'i-lucide-check' : paymentState === 'failed' ? 'i-lucide-circle-alert' : 'i-lucide-clock-3'" class="size-7" />
        </span>
        <h1 class="mb-2 mt-4 text-[clamp(24px,3.2vw,32px)] font-black tracking-tight text-navy-900">{{ confirmationTitle }}</h1>
        <p class="mx-auto my-0 max-w-[600px] text-[15px] leading-relaxed text-slate-600">{{ statusLine }}</p>
        <p v-if="payload" class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3.5 py-1.5 text-sm text-slate-600">Mã đơn hàng <strong class="tracking-wide text-navy-900">{{ payload.order.code }}</strong></p>

        <OrderTransferCard v-if="transfer && paymentState === 'awaiting_transfer'" class="mt-8" :transfer="transfer" :order-code="payload?.order.code || ''">
          <div class="mt-4">
            <p v-if="transfer.reported || transferReported" class="m-0 flex items-center gap-2 text-sm font-semibold text-emerald-700" role="status">
                  <UIcon name="i-lucide-check-circle-2" class="size-4" aria-hidden="true" />
                  Đã nhận thông báo. Chúng tôi sẽ kiểm tra tài khoản và xác nhận đơn.
                </p>
                <button v-else type="button" :disabled="reportingTransfer" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-btn bg-navy-800 px-5 font-bold text-white hover:bg-navy-900 disabled:cursor-wait disabled:opacity-70" @click="reportTransfer">
                  <UIcon name="i-lucide-send" class="size-4" aria-hidden="true" />
                  {{ reportingTransfer ? 'Đang gửi…' : 'Tôi đã chuyển khoản' }}
                </button>
                <p v-if="reportError" class="m-0 mt-2 text-sm text-red-600" role="alert">{{ reportError }}</p>
              </div>
        </OrderTransferCard>
        <div class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-3">
          <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center gap-2 rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400">
            <span>Tiếp tục mua sắm</span>
            <span aria-hidden="true">→</span>
          </NuxtLink>
          <button type="button" class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-bold text-navy-800 underline decoration-slate-300 underline-offset-4 hover:decoration-navy-800" @click="openConsultation(payload ? `Hỗ trợ đơn hàng #${payload.order.code}` : 'Hỗ trợ đơn hàng mới đặt')">
            <UIcon name="i-lucide-headset" class="size-4 text-gold-700" aria-hidden="true" />
            <span>Cần tư vấn lắp đặt</span>
          </button>
        </div>

        <div v-if="paymentConfirmed" class="mt-12 grid grid-cols-1 gap-4 border-t border-slate-200/80 pt-10 text-left sm:grid-cols-3">
          <div class="rounded-btn border-t-2 border-gold-500 bg-slate-50 p-5">
            <span class="tabular-nums text-xs font-extrabold text-gold-700">01</span>
            <h2 class="m-0 mt-1.5 text-base font-bold text-navy-900">Xác nhận đơn hàng</h2>
            <p class="m-0 mt-1.5 text-sm leading-relaxed text-slate-600">
              Bộ phận điều phối Phúc Thịnh Solar liên hệ xác nhận thông tin người nhận, địa chỉ và phương thức vận chuyển.
            </p>
          </div>
          <div class="rounded-btn border-t-2 border-gold-500 bg-slate-50 p-5">
            <span class="tabular-nums text-xs font-extrabold text-gold-700">02</span>
            <h2 class="m-0 mt-1.5 text-base font-bold text-navy-900">Kiểm định & Xuất kho</h2>
            <p class="m-0 mt-1.5 text-sm leading-relaxed text-slate-600">
              Thiết bị được kiểm tra thông số kỹ thuật, tem bảo hành chính hãng và đóng gói từ kho gần nhất (Gia Lai, Đà Nẵng, TP.HCM).
            </p>
          </div>
          <div class="rounded-btn border-t-2 border-gold-500 bg-slate-50 p-5">
            <span class="tabular-nums text-xs font-extrabold text-gold-700">03</span>
            <h2 class="m-0 mt-1.5 text-base font-bold text-navy-900">Giao hàng & Hỗ trợ kỹ thuật</h2>
            <p class="m-0 mt-1.5 text-sm leading-relaxed text-slate-600">
              Đồng kiểm khi nhận hàng; đội ngũ kỹ thuật hỗ trợ hướng dẫn đấu nối, vận hành và bảo hành dài hạn.
            </p>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>

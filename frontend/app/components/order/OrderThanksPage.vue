<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'
import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const route = useRoute()
const links = usePublicLinks()
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
  : paymentState.value === 'pending' ? 'Đang chờ xác nhận thanh toán' : 'Đặt hàng thành công')
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
  if (!import.meta.client || !current?.conversionPending || !['accepted', 'paid'].includes(current.paymentState)
    || !/^[a-f0-9]{48}$/.test(currentToken) || trackedConversions.has(currentToken)) return
  const browserWindow = window as Window & { dataLayer?: Array<Record<string, unknown>> }
  const dataLayer = browserWindow.dataLayer ??= []
  dataLayer.push({
    event: 'order_success',
    order_code: current.order.code,
    order_value: current.order.totalPrice,
    currency: 'VND',
  })
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
      <div v-else class="mx-auto max-w-[900px]">
        <svg v-if="paymentConfirmed" class="mx-auto mb-4 h-16 w-16 text-[#5cb85c]" viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <circle cx="32" cy="32" r="30" fill="currentColor" />
          <path d="m17 32 10 10 21-22" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="6" />
        </svg>
        <UIcon v-else :name="paymentState === 'failed' ? 'i-lucide-circle-alert' : 'i-lucide-clock-3'" class="mx-auto mb-4 h-16 w-16 text-gold-600" aria-hidden="true" />
        <h1 class="mb-4 mt-5 text-[clamp(28px,4vw,38px)] font-black tracking-tight text-navy-800">{{ confirmationTitle }}{{ paymentConfirmed ? '!' : '' }}</h1>
        <p v-if="paymentState === 'pending'" class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Đơn hàng đã được tạo và đang chờ xác nhận thanh toán VNPay. Vui lòng kiểm tra lại trạng thái sau khi hoàn tất giao dịch.</p>
        <p v-else-if="paymentState === 'failed'" class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Giao dịch VNPay chưa thành công. Đơn hàng chưa được xác nhận thanh toán; vui lòng liên hệ Phúc Thịnh Solar nếu cần hỗ trợ.</p>
        <p v-else class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Cảm ơn {{ payload?.order.customerName || 'bạn' }} đã đặt hàng. Chúng tôi sẽ liên hệ xác nhận đơn hàng trong thời gian sớm nhất.</p>
        <p v-if="payload" class="mt-3 text-base text-slate-700">Mã đơn hàng: <strong class="text-navy-800">{{ payload.order.code }}</strong></p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
          <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center gap-2 rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400">
            <span>Tiếp tục mua sắm</span>
            <span aria-hidden="true">→</span>
          </NuxtLink>
          <button
            type="button"
            class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-btn border border-navy-800/20 bg-white px-5 font-bold text-navy-900 transition-colors hover:border-navy-800 hover:bg-slate-50"
            @click="openConsultation(payload ? `Hỗ trợ đơn hàng #${payload.order.code}` : 'Hỗ trợ đơn hàng mới đặt')"
          >
            <UIcon name="i-lucide-headset" class="size-4 text-gold-600" aria-hidden="true" />
            <span>Cần tư vấn kỹ thuật lắp đặt</span>
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
              Đồng kiểm khi nhận hàng; đội ngũ kỹ sư hỗ trợ hướng dẫn đấu nối, vận hành và bảo hành dài hạn.
            </p>
          </div>
        </div>

        <div class="mt-6 flex flex-col items-start justify-between gap-4 pts-note-dark p-5 text-left text-white sm:flex-row sm:items-center">
          <div>
            <p class="m-0 text-base font-bold text-white">Cần hỗ trợ hoặc bổ sung thông tin giao hàng?</p>
            <p class="m-0 mt-1 text-xs text-white/75 sm:text-sm">Liên hệ trực tiếp tổng đài Phúc Thịnh Solar trong giờ hành chính (T2 – T7):</p>
          </div>
          <div class="flex flex-wrap items-center gap-2.5 sm:shrink-0">
            <a href="tel:0944555957" class="inline-flex min-h-10 items-center gap-2 rounded-chip bg-gold-500 px-3.5 py-2 text-xs font-extrabold text-navy-950 no-underline hover:bg-gold-400">
              <UIcon name="i-lucide-phone-call" class="size-3.5" aria-hidden="true" />
              <span>Bán hàng: 0944 555 957</span>
            </a>
            <a href="tel:0945536899" class="inline-flex min-h-10 items-center gap-2 rounded-chip bg-white/10 px-3.5 py-2 text-xs font-bold text-white no-underline hover:bg-white/20">
              <UIcon name="i-lucide-wrench" class="size-3.5 text-gold-400" aria-hidden="true" />
              <span>Kỹ thuật: 0945 536 899</span>
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

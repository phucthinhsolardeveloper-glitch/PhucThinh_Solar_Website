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
  : paymentState.value === 'pending' ? 'Đang chờ xác nhận thanh toán'
  : paymentState.value === 'awaiting_transfer' ? 'Đặt hàng thành công, mời chuyển khoản' : 'Đặt hàng thành công')
const transfer = computed(() => payload.value?.transfer ?? null)
const transferReported = ref(false)
const reportingTransfer = ref(false)
const reportError = ref('')
const copied = ref('')
const money = (value: number) => new Intl.NumberFormat('vi-VN').format(value) + 'đ'
async function copyText(key: string, value: string) {
  try {
    await navigator.clipboard.writeText(value)
    copied.value = key
    setTimeout(() => { if (copied.value === key) copied.value = '' }, 1800)
  } catch { copied.value = '' }
}
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
        <p v-else-if="paymentState === 'awaiting_transfer'" class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Cảm ơn {{ payload?.order.customerName || 'bạn' }}. Quét mã bên dưới bằng ứng dụng ngân hàng để chuyển khoản. Đơn được xác nhận sau khi bộ phận kế toán thấy tiền vào tài khoản công ty.</p>
        <p v-else-if="paymentState === 'failed'" class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Giao dịch VNPay chưa thành công. Đơn hàng chưa được xác nhận thanh toán; vui lòng liên hệ Phúc Thịnh Solar nếu cần hỗ trợ.</p>
        <p v-else class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Cảm ơn {{ payload?.order.customerName || 'bạn' }} đã đặt hàng. Chúng tôi sẽ liên hệ xác nhận đơn hàng trong thời gian sớm nhất.</p>
        <p v-if="payload" class="mt-3 text-base text-slate-700">Mã đơn hàng: <strong class="text-navy-800">{{ payload.order.code }}</strong></p>

        <section v-if="transfer && paymentState === 'awaiting_transfer'" class="mx-auto mt-8 grid max-w-[760px] gap-6 rounded-btn border border-slate-200 bg-slate-50 p-5 text-left sm:grid-cols-[220px_1fr] sm:p-6" aria-labelledby="transfer-title">
          <div class="mx-auto w-full max-w-[220px]">
            <img :src="transfer.qrImage" :alt="`Mã VietQR chuyển khoản ${money(transfer.amount)} cho đơn ${payload?.order.code}`" width="220" height="220" class="block aspect-square w-full rounded-btn border border-slate-200 bg-white p-2">
            <p class="m-0 mt-2 text-center text-xs text-slate-500">Quét bằng ứng dụng ngân hàng hoặc ví hỗ trợ VietQR</p>
          </div>
          <div>
            <h2 id="transfer-title" class="m-0 text-lg font-bold text-navy-900">Thông tin chuyển khoản</h2>
            <dl class="m-0 mt-3 grid gap-2.5 text-sm">
              <div class="flex flex-wrap items-center justify-between gap-2"><dt class="text-slate-500">Ngân hàng</dt><dd class="m-0 font-semibold text-navy-900">{{ transfer.bankName }}</dd></div>
              <div class="flex flex-wrap items-center justify-between gap-2"><dt class="text-slate-500">Chủ tài khoản</dt><dd class="m-0 font-semibold text-navy-900">{{ transfer.accountName }}</dd></div>
              <div v-for="row in [
                { key: 'account', label: 'Số tài khoản', value: transfer.accountNo, shown: transfer.accountNo },
                { key: 'amount', label: 'Số tiền', value: String(transfer.amount), shown: money(transfer.amount) },
                { key: 'note', label: 'Nội dung', value: transfer.note, shown: transfer.note },
              ]" :key="row.key" class="flex flex-wrap items-center justify-between gap-2">
                <dt class="text-slate-500">{{ row.label }}</dt>
                <dd class="m-0 flex items-center gap-2">
                  <span class="font-bold tabular-nums text-navy-900" :class="row.key === 'note' ? 'rounded-sm bg-gold-100 px-1.5 text-gold-800' : ''">{{ row.shown }}</span>
                  <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-1 rounded-btn border border-slate-300 bg-white px-2.5 text-xs font-bold text-navy-800 hover:border-navy-800" :aria-label="`Sao chép ${row.label.toLowerCase()}`" @click="copyText(row.key, row.value)">
                    <UIcon :name="copied === row.key ? 'i-lucide-check' : 'i-lucide-copy'" class="size-3.5" aria-hidden="true" />
                    <span>{{ copied === row.key ? 'Đã chép' : 'Chép' }}</span>
                  </button>
                </dd>
              </div>
            </dl>
            <p class="m-0 mt-3 text-sm leading-relaxed text-slate-600">Giữ đúng nội dung <strong>{{ transfer.note }}</strong> để kế toán nhận ra đơn của bạn.</p>
            <div class="mt-4">
              <p v-if="transfer.reported || transferReported" class="m-0 flex items-center gap-2 text-sm font-semibold text-emerald-700" role="status">
                <UIcon name="i-lucide-check-circle-2" class="size-4" aria-hidden="true" />
                Đã nhận thông báo. Chúng tôi sẽ kiểm tra tài khoản và xác nhận đơn.
              </p>
              <button v-else type="button" :disabled="reportingTransfer" class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-btn bg-navy-800 px-5 font-bold text-white hover:bg-navy-900 disabled:cursor-wait disabled:opacity-70" @click="reportTransfer">
                <UIcon name="i-lucide-send" class="size-4" aria-hidden="true" />
                {{ reportingTransfer ? 'Đang gửi…' : 'Tôi đã chuyển khoản' }}
              </button>
              <p v-if="reportError" class="m-0 mt-2 text-sm text-red-600" role="alert">{{ reportError }}</p>
            </div>
          </div>
        </section>
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

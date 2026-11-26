<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'
import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'
import { purchaseAmounts } from '#shared/purchase-amounts'

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
// Receiving banks with a logo bundled in /assets/images/payments/banks (others show the coloured badge).
const bankLogos: Record<string, { logo: string, mark: string }> = {
  AGR: { logo: '/assets/images/payments/banks/agribank.png', mark: '/assets/images/payments/banks/agribank-mark.png' },
}
const bankLogo = computed(() => (transfer.value ? bankLogos[transfer.value.bankShort] : undefined))
const savingQr = ref(false)
/** The code with the amount and transfer note as one picture, for paying from a banking app on the same phone. */
async function saveQr() {
  const current = transfer.value
  if (!current || !import.meta.client || savingQr.value) return
  savingQr.value = true
  try {
    const load = (src: string) => new Promise<HTMLImageElement>((resolve, reject) => {
      const image = new Image()
      image.onload = () => resolve(image)
      image.onerror = reject
      image.src = src
    })
    const optional = (src?: string) => (src ? load(src).catch(() => null) : Promise.resolve(null))
    const [qr, mark, scheme, bank] = await Promise.all([load(current.qrImage), optional(bankLogo.value?.mark), optional('/assets/images/payments/vietqr.svg'), optional(bankLogo.value?.logo)])
    const canvas = document.createElement('canvas')
    canvas.width = 720
    canvas.height = 1040
    const c = canvas.getContext('2d')!
    c.fillStyle = '#ffffff'
    c.fillRect(0, 0, canvas.width, canvas.height)
    if (scheme) c.drawImage(scheme, 60, 34, 160, 46)
    if (bank) c.drawImage(bank, 660 - 220, 45, 220, 220 * bank.naturalHeight / bank.naturalWidth)
    c.drawImage(qr, 60, 110, 600, 600)
    if (mark) {
      c.fillStyle = '#ffffff'
      c.fillRect(312, 362, 96, 96)
      c.drawImage(mark, 318, 368, 84, 84)
    }
    c.translate(0, 50)
    c.textAlign = 'center'
    c.fillStyle = '#0a2251'
    c.font = 'bold 44px sans-serif'
    c.fillText(money(current.amount), 360, 730)
    c.font = '28px sans-serif'
    c.fillStyle = '#334155'
    c.fillText(`${current.bankName} · ${current.accountNo}`, 360, 790)
    // The company name is long: shrink it to the width of the picture.
    let size = 28
    do { c.font = `${size}px sans-serif`; size -= 1 } while (c.measureText(current.accountName).width > 640 && size > 16)
    c.fillText(current.accountName, 360, 832)
    c.font = 'bold 30px sans-serif'
    c.fillStyle = '#7c5c1b'
    c.fillText(`Nội dung: ${current.note}`, 360, 892)
    const link = document.createElement('a')
    link.download = `ma-qr-${current.note}.png`
    link.href = canvas.toDataURL('image/png')
    link.click()
  } catch { /* The code stays on screen to scan. */ } finally {
    savingQr.value = false
  }
}
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

        <section v-if="transfer && paymentState === 'awaiting_transfer'" class="transfer-card mx-auto mt-8 max-w-[860px] text-left" aria-labelledby="transfer-title">
          <div class="grid gap-0 overflow-hidden rounded-[20px] bg-white sm:grid-cols-[minmax(0,330px)_1fr]">
            <!-- The code, framed like the cards banks print: VietQR above, the receiving bank below. -->
            <div class="transfer-qr flex flex-col items-center gap-3 px-6 pb-6 pt-5">
              <div class="flex w-full items-center justify-between gap-3">
                <img src="/assets/images/payments/vietqr.svg" alt="VietQR" width="104" height="30" class="h-[30px] w-auto">
                <span class="text-[12px] font-bold uppercase tracking-wide text-slate-500">Quét để thanh toán</span>
              </div>
              <div class="relative w-full max-w-[260px] rounded-[14px] border border-slate-200 bg-white p-2.5 shadow-[0_8px_24px_rgb(10_34_81/10%)]">
                <img :src="transfer.qrImage" :alt="`Mã VietQR chuyển khoản ${money(transfer.amount)} cho đơn ${payload?.order.code}`" width="240" height="240" class="block aspect-square w-full">
                <span v-if="bankLogo" class="absolute left-1/2 top-1/2 grid size-[19%] -translate-x-1/2 -translate-y-1/2 place-items-center rounded-[8px] bg-white p-[3px] shadow-sm" aria-hidden="true">
                  <img :src="bankLogo.mark" alt="" width="48" height="48" class="block size-full rounded-[5px]">
                </span>
              </div>
              <div class="flex w-full items-center justify-center gap-3 border-t border-slate-100 pt-3">
                <img v-if="bankLogo" :src="bankLogo.logo" :alt="transfer.bankName" width="150" height="20" class="h-5 w-auto">
                <span v-else class="inline-flex h-6 items-center rounded-md px-2 text-[11px] font-bold text-white" :style="{ background: transfer.bankColor }">{{ transfer.bankShort || transfer.bankName }}</span>
              </div>
              <button type="button" :disabled="savingQr" class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-btn border border-navy-800/20 bg-white px-4 text-sm font-bold text-navy-900 hover:border-navy-800 disabled:cursor-wait" @click="saveQr">
                <UIcon name="i-lucide-download" class="size-4" aria-hidden="true" />
                {{ savingQr ? 'Đang lưu…' : 'Lưu ảnh mã QR' }}
              </button>
              <p class="m-0 text-center text-xs leading-relaxed text-slate-500">Mở ứng dụng ngân hàng, chọn quét mã hoặc tải ảnh mã QR từ thư viện.</p>
            </div>

            <div class="flex flex-col gap-4 border-t border-slate-100 p-5 sm:border-l sm:border-t-0 sm:p-7">
              <div>
                <h2 id="transfer-title" class="m-0 text-lg font-black text-navy-900">Thông tin chuyển khoản</h2>
                <p class="m-0 mt-1 text-sm text-slate-500">Số tiền và nội dung đã có sẵn trong mã QR.</p>
              </div>
              <div class="rounded-[14px] bg-navy-950 px-5 py-4 text-white">
                <p class="m-0 text-xs font-semibold text-white/70">Số tiền cần chuyển</p>
                <p class="m-0 mt-1 flex items-center justify-between gap-3">
                  <strong class="text-[28px] font-black tabular-nums leading-none text-gold-300">{{ money(transfer.amount) }}</strong>
                  <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-1 rounded-btn border border-white/25 px-2.5 text-xs font-bold text-white hover:border-white" aria-label="Sao chép số tiền" @click="copyText('amount', String(transfer.amount))">
                    <UIcon :name="copied === 'amount' ? 'i-lucide-check' : 'i-lucide-copy'" class="size-3.5" aria-hidden="true" />{{ copied === 'amount' ? 'Đã chép' : 'Chép' }}
                  </button>
                </p>
              </div>
              <dl class="m-0 grid gap-3 text-sm">
                <div class="grid gap-0.5">
                  <dt class="text-slate-500">Chủ tài khoản</dt>
                  <dd class="m-0 font-bold leading-snug text-navy-900">{{ transfer.accountName }}</dd>
                </div>
                <div v-for="row in [
                  { key: 'account', label: 'Số tài khoản', value: transfer.accountNo, shown: transfer.accountNo, hint: transfer.bankName },
                  { key: 'note', label: 'Nội dung chuyển khoản', value: transfer.note, shown: transfer.note, hint: 'Giữ đúng để kế toán nhận ra đơn' },
                ]" :key="row.key" class="flex items-center justify-between gap-3 rounded-[12px] border border-slate-200 px-3.5 py-2.5">
                  <div class="min-w-0">
                    <dt class="text-xs text-slate-500">{{ row.label }}</dt>
                    <dd class="m-0 mt-0.5 text-[17px] font-black tracking-wide tabular-nums" :class="row.key === 'note' ? 'text-gold-800' : 'text-navy-900'">{{ row.shown }}</dd>
                    <p class="m-0 text-xs text-slate-500">{{ row.hint }}</p>
                  </div>
                  <button type="button" class="inline-flex min-h-9 shrink-0 cursor-pointer items-center gap-1 rounded-btn border border-slate-300 bg-white px-2.5 text-xs font-bold text-navy-800 hover:border-navy-800" :aria-label="`Sao chép ${row.label.toLowerCase()}`" @click="copyText(row.key, row.value)">
                    <UIcon :name="copied === row.key ? 'i-lucide-check' : 'i-lucide-copy'" class="size-3.5" aria-hidden="true" />
                    <span>{{ copied === row.key ? 'Đã chép' : 'Chép' }}</span>
                  </button>
                </div>
              </dl>
              <div>
                <p v-if="transfer.reported || transferReported" class="m-0 flex items-center gap-2 text-sm font-semibold text-emerald-700" role="status">
                  <UIcon name="i-lucide-check-circle-2" class="size-4" aria-hidden="true" />
                  Đã nhận thông báo. Chúng tôi sẽ kiểm tra tài khoản và xác nhận đơn.
                </p>
                <button v-else type="button" :disabled="reportingTransfer" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-btn bg-navy-800 px-5 font-bold text-white hover:bg-navy-900 disabled:cursor-wait disabled:opacity-70" @click="reportTransfer">
                  <UIcon name="i-lucide-send" class="size-4" aria-hidden="true" />
                  {{ reportingTransfer ? 'Đang gửi…' : 'Tôi đã chuyển khoản' }}
                </button>
                <p v-if="reportError" class="m-0 mt-2 text-sm text-red-600" role="alert">{{ reportError }}</p>
                <p class="m-0 mt-2 text-xs leading-relaxed text-slate-500">Đơn được xác nhận khi kế toán thấy tiền vào tài khoản công ty.</p>
              </div>
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
              Đồng kiểm khi nhận hàng; đội ngũ kỹ thuật hỗ trợ hướng dẫn đấu nối, vận hành và bảo hành dài hạn.
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

<style scoped>
/* The gradient rim of a printed VietQR card, in the site's navy and gold. */
.transfer-card { padding: 2px; border-radius: 22px; background: linear-gradient(135deg, var(--color-navy-800), #2c5aa6 45%, var(--color-gold-500)); box-shadow: 0 18px 40px rgb(10 34 81 / 12%); }
.transfer-qr { background: linear-gradient(180deg, #f6f8fc, #ffffff 40%); }
</style>

<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'
import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'
import { purchaseAmounts } from '#shared/purchase-amounts'

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
/** The facts of the transfer, in the order a banking app asks for them. */
const transferRows = computed(() => {
  const current = transfer.value
  if (!current) return []
  return [
    { key: 'amount', label: 'Số tiền', shown: money(current.amount), copy: String(current.amount) },
    { key: 'account', label: `Số tài khoản · ${current.bankName}`, shown: current.accountNo, copy: current.accountNo },
    { key: 'name', label: 'Chủ tài khoản', shown: current.accountName, copy: '' },
    { key: 'note', label: 'Nội dung chuyển khoản', shown: current.note, copy: current.note },
  ]
})
const transferReported = ref(false)
const reportingTransfer = ref(false)
const reportError = ref('')
const copied = ref('')
const money = (value: number) => new Intl.NumberFormat('vi-VN').format(value) + 'đ'
// Every bank the Admin list offers has its logo in /assets/images/payments/banks (named by its badge letters); a bank
// whose logo opens with a separate emblem also has that emblem, shown in the middle of the code.
const BANK_LOGOS = new Set(['abb', 'acb', 'agr', 'bab', 'bidv', 'bvb', 'ctg', 'eib', 'hdb', 'klb', 'lpb', 'mb', 'msb', 'nab', 'ncb', 'ocb', 'pgb', 'pvc', 'scb', 'sea', 'sgb', 'shb', 'stb', 'tcb', 'tpb', 'vab', 'vbb', 'vcb', 'vib', 'vpb'])
const BANK_MARKS = new Set(['agr', 'mb', 'ocb', 'tpb'])
const bankLogo = computed(() => {
  const code = transfer.value?.bankShort.toLowerCase() ?? ''
  if (!BANK_LOGOS.has(code)) return undefined
  return { logo: `/assets/images/payments/banks/${code}.png`, mark: BANK_MARKS.has(code) ? `/assets/images/payments/banks/${code}-mark.png` : '' }
})
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
    const [qr, mark, scheme, bank] = await Promise.all([load(current.qrImage), optional(bankLogo.value?.mark || undefined), optional('/assets/images/payments/vietqr.svg'), optional(bankLogo.value?.logo)])
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
      <div v-else class="mx-auto max-w-[880px]">
        <span class="mx-auto grid size-14 place-items-center rounded-full" :class="paymentConfirmed ? 'bg-emerald-50 text-emerald-600' : paymentState === 'failed' ? 'bg-red-50 text-red-600' : 'bg-gold-100 text-gold-700'" aria-hidden="true">
          <UIcon :name="paymentConfirmed ? 'i-lucide-check' : paymentState === 'failed' ? 'i-lucide-circle-alert' : 'i-lucide-clock-3'" class="size-7" />
        </span>
        <h1 class="mb-2 mt-4 text-[clamp(24px,3.2vw,32px)] font-black tracking-tight text-navy-900">{{ confirmationTitle }}</h1>
        <p class="mx-auto my-0 max-w-[600px] text-[15px] leading-relaxed text-slate-600">{{ statusLine }}</p>
        <p v-if="payload" class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3.5 py-1.5 text-sm text-slate-600">Mã đơn hàng <strong class="tracking-wide text-navy-900">{{ payload.order.code }}</strong></p>

        <section v-if="transfer && paymentState === 'awaiting_transfer'" class="mt-8 overflow-hidden rounded-[18px] border border-slate-200 bg-white text-left shadow-[0_12px_32px_rgb(10_34_81/8%)]" aria-labelledby="transfer-title">
          <div class="grid sm:grid-cols-[300px_1fr]">
            <!-- The code with the marks of the scheme and the receiving bank, as banks print it. -->
            <div class="flex flex-col items-center gap-3 border-b border-slate-200 bg-slate-50 px-6 py-6 sm:border-b-0 sm:border-r">
              <div class="relative w-full max-w-[232px] rounded-[14px] bg-white p-2.5 shadow-[0_4px_14px_rgb(10_34_81/8%)]">
                <img :src="transfer.qrImage" :alt="`Mã VietQR chuyển khoản ${money(transfer.amount)} cho đơn ${payload?.order.code}`" width="212" height="212" class="block aspect-square w-full">
                <span v-if="bankLogo?.mark" class="absolute left-1/2 top-1/2 grid size-[19%] -translate-x-1/2 -translate-y-1/2 place-items-center rounded-[8px] bg-white p-[3px]" aria-hidden="true">
                  <img :src="bankLogo.mark" alt="" width="44" height="44" class="block size-full rounded-[5px]">
                </span>
              </div>
              <div class="flex items-center justify-center gap-3">
                <img src="/assets/images/payments/vietqr.svg" alt="VietQR" width="80" height="23" class="h-[22px] w-auto">
                <span class="h-5 w-px bg-slate-300" aria-hidden="true" />
                <img v-if="bankLogo" :src="bankLogo.logo" :alt="transfer.bankName" width="140" height="22" class="h-[22px] w-auto max-w-[150px] object-contain">
                <span v-else class="inline-flex h-6 items-center rounded-md px-2 text-[11px] font-bold text-white" :style="{ background: transfer.bankColor }">{{ transfer.bankShort || transfer.bankName }}</span>
              </div>
              <button type="button" :disabled="savingQr" class="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-btn px-3 text-sm font-bold text-navy-800 underline decoration-slate-300 underline-offset-4 hover:decoration-navy-800 disabled:cursor-wait" @click="saveQr">
                <UIcon name="i-lucide-download" class="size-4" aria-hidden="true" />
                {{ savingQr ? 'Đang lưu…' : 'Lưu ảnh mã QR' }}
              </button>
            </div>

            <div class="px-5 py-5 sm:px-7 sm:py-6">
              <h2 id="transfer-title" class="m-0 text-lg font-black text-navy-900">Chuyển khoản để hoàn tất đơn</h2>
              <p class="m-0 mt-1 text-sm text-slate-500">Quét mã bằng app ngân hàng, số tiền và nội dung đã điền sẵn.</p>
              <dl class="m-0 mt-4 divide-y divide-slate-100 border-y border-slate-100">
                <div v-for="row in transferRows" :key="row.key" class="flex items-center justify-between gap-3 py-3">
                  <div class="min-w-0">
                    <dt class="text-xs text-slate-500">{{ row.label }}</dt>
                    <dd class="m-0 mt-0.5 font-black tabular-nums" :class="row.key === 'amount' ? 'text-[24px] leading-tight text-navy-900' : row.key === 'note' ? 'text-[17px] tracking-wide text-gold-800' : row.key === 'name' ? 'text-sm font-bold text-navy-900' : 'text-[17px] tracking-wide text-navy-900'">{{ row.shown }}</dd>
                  </div>
                  <button v-if="row.copy" type="button" class="inline-flex min-h-9 shrink-0 cursor-pointer items-center gap-1 rounded-btn border border-slate-300 bg-white px-2.5 text-xs font-bold text-navy-800 hover:border-navy-800" :aria-label="`Sao chép ${row.label.toLowerCase()}`" @click="copyText(row.key, row.copy)">
                    <UIcon :name="copied === row.key ? 'i-lucide-check' : 'i-lucide-copy'" class="size-3.5" aria-hidden="true" />
                    <span>{{ copied === row.key ? 'Đã chép' : 'Chép' }}</span>
                  </button>
                </div>
              </dl>
              <p class="m-0 mt-3 text-xs leading-relaxed text-slate-500">Giữ đúng nội dung chuyển khoản. Đơn được xác nhận khi kế toán thấy tiền vào tài khoản công ty.</p>
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
            </div>
          </div>
        </section>
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

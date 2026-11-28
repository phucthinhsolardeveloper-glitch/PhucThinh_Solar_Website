<script setup lang="ts">
// The bank-transfer instruction of one order: its VietQR code with the scheme and bank marks, the same facts as rows to
// copy, and a picture to save. Used on the thank-you page and the order lookup; the caller adds its own action below.
import type { PublicOrderTransfer } from '#shared/contracts/public-checkout'

const props = defineProps<{ transfer: PublicOrderTransfer, orderCode: string }>()
const transfer = computed(() => props.transfer)
const money = (value: number) => new Intl.NumberFormat('vi-VN').format(value) + 'đ'
/** The facts of the transfer, in the order a banking app asks for them. */
const transferRows = computed(() => {
  const current = props.transfer
  if (!current) return []
  return [
    { key: 'amount', label: 'Số tiền', shown: money(current.amount), copy: String(current.amount) },
    { key: 'account', label: `Số tài khoản · ${current.bankName}`, shown: current.accountNo, copy: current.accountNo },
    { key: 'name', label: 'Chủ tài khoản', shown: current.accountName, copy: '' },
    { key: 'note', label: 'Nội dung chuyển khoản', shown: current.note, copy: current.note },
  ]
})
const copied = ref('')
// Every bank the Admin list offers has its logo in /assets/images/payments/banks (named by its badge letters); a bank
// whose logo opens with a separate emblem also has that emblem, shown in the middle of the code.
const BANK_LOGOS = new Set(['abb', 'acb', 'agr', 'bab', 'bidv', 'bvb', 'ctg', 'eib', 'hdb', 'klb', 'lpb', 'mb', 'msb', 'nab', 'ncb', 'ocb', 'pgb', 'pvc', 'scb', 'sea', 'sgb', 'shb', 'stb', 'tcb', 'tpb', 'vab', 'vbb', 'vcb', 'vib', 'vpb'])
const BANK_MARKS = new Set(['agr', 'mb', 'ocb', 'tpb'])
const bankLogo = computed(() => {
  const code = props.transfer.bankShort.toLowerCase()
  if (!BANK_LOGOS.has(code)) return undefined
  return { logo: `/assets/images/payments/banks/${code}.png`, mark: BANK_MARKS.has(code) ? `/assets/images/payments/banks/${code}-mark.png` : '' }
})
const savingQr = ref(false)
/** The code with the amount and transfer note as one picture, for paying from a banking app on the same phone. */
async function saveQr() {
  const current = props.transfer
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
</script>

<template>
  <section class="overflow-hidden rounded-[18px] border border-slate-200 bg-white text-left shadow-[0_12px_32px_rgb(10_34_81/8%)]" aria-labelledby="transfer-title">
    <div class="grid sm:grid-cols-[300px_1fr]">
      <!-- The code with the marks of the scheme and the receiving bank, as banks print it. -->
      <div class="flex flex-col items-center justify-center gap-3 border-b border-slate-200 bg-slate-50 px-6 py-6 sm:border-b-0 sm:border-r">
        <div class="relative w-full max-w-[232px] rounded-[14px] bg-white p-2.5 shadow-[0_4px_14px_rgb(10_34_81/8%)]">
          <img :src="transfer.qrImage" :alt="`Mã VietQR chuyển khoản ${money(transfer.amount)} cho đơn ${orderCode}`" width="212" height="212" class="block aspect-square w-full">
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
      <ol class="m-0 mt-1 grid w-full max-w-[232px] list-none gap-2 border-t border-slate-200 p-0 pt-4 text-left text-[13px] leading-snug text-slate-600">
        <li v-for="(step, i) in ['Mở app ngân hàng, chọn Quét mã QR', 'Kiểm tra số tiền và nội dung', 'Xác nhận chuyển khoản']" :key="step" class="flex items-start gap-2.5">
          <span class="grid size-5 flex-none place-items-center rounded-full bg-navy-800 text-[11px] font-bold text-white" aria-hidden="true">{{ i + 1 }}</span>
          <span>{{ step }}</span>
        </li>
      </ol>
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
        <slot />
      </div>
    </div>
  </section>
</template>

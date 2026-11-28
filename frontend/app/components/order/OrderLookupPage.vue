<script setup lang="ts">
// "Tra cứu đơn hàng": order code + the phone it was placed with. The request goes straight from the browser to the API
// so its rate limit counts per visitor. Nothing typed here is kept or sent to analytics.
import { isPublicLeadFormSecurity } from '#shared/contracts/public-lead-form'
import { isPublicOrderLookupPayload, type PublicOrderLookup } from '#shared/contracts/public-order-lookup'
import OrderTransferCard from '~/components/order/OrderTransferCard.vue'

const route = useRoute()
const links = usePublicLinks()
const { openConsultation } = useStorefrontModal()
const PATH = '/tra-cuu-don-hang/'

if (route.path !== PATH) await navigateTo(PATH, { redirectCode: 301 })
useSeoMeta({
  title: 'Tra cứu đơn hàng | Phúc Thịnh Solar',
  description: 'Xem trạng thái đơn hàng, thanh toán và hành trình giao hàng tại Phúc Thịnh Solar bằng mã đơn và số điện thoại đặt hàng.',
  robots: 'index, follow',
})
useHead({ link: [{ rel: 'canonical', href: PATH }] })

const code = ref(typeof route.query.ma === 'string' ? route.query.ma.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 20) : '')
const phone = ref('')
const order = ref<PublicOrderLookup | null>(null)
const message = ref('')
const busy = ref(false)
const money = (value: number) => new Intl.NumberFormat('vi-VN').format(value) + 'đ'
const day = (iso: string, withTime = false) => {
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', ...(withTime ? { hour: '2-digit', minute: '2-digit' } : {}), timeZone: 'Asia/Ho_Chi_Minh' }).format(d)
}

/** The four steps an order goes through; a cancelled order shows its own line instead. */
const STEPS = [['new', 'Đã đặt'], ['confirmed', 'Đã xác nhận'], ['shipping', 'Đang giao'], ['done', 'Hoàn tất']] as const
const stepIndex = computed(() => STEPS.findIndex(([key]) => key === order.value?.status))
const awaitingTransfer = computed(() => !!order.value?.transfer && order.value.status !== 'cancelled')

async function find() {
  if (busy.value) return
  message.value = ''
  busy.value = true
  try {
    const meta = await $fetch<unknown>('/_api/lead-form')
    if (!isPublicLeadFormSecurity(meta)) throw new Error('security')
    const result = await $fetch<unknown>('/api/v1/order-lookup', { method: 'POST', credentials: 'same-origin', body: { csrfToken: meta.csrfToken, code: code.value, phone: phone.value } })
    if (!isPublicOrderLookupPayload(result)) throw new Error('contract')
    order.value = result.order
  } catch (error) {
    order.value = null
    const answer = (error as { data?: { error?: { message?: unknown } } })?.data?.error?.message
    message.value = typeof answer === 'string' ? answer : 'Chưa tra cứu được lúc này. Vui lòng thử lại hoặc gọi hotline.'
  } finally {
    busy.value = false
  }
}
function again() {
  order.value = null
  phone.value = ''
}
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Tra cứu đơn hàng' }]" />
      </div>
    </div>
    <main class="pts-container py-10 sm:py-14">
      <div class="mx-auto max-w-[880px]">
        <template v-if="!order">
          <div class="mx-auto max-w-[460px]">
            <h1 class="m-0 text-center text-[clamp(24px,3.2vw,32px)] font-black tracking-tight text-navy-900">Tra cứu đơn hàng</h1>
            <p class="mx-auto mb-0 mt-2 text-center text-[15px] leading-relaxed text-slate-600">Nhập mã đơn trong email xác nhận và số điện thoại đã dùng khi đặt hàng.</p>
            <form class="mt-7 grid gap-4 rounded-[18px] border border-slate-200 bg-white p-5 shadow-[0_12px_32px_rgb(10_34_81/8%)] sm:p-6" novalidate @submit.prevent="find">
              <label class="grid gap-1.5 text-sm font-semibold text-navy-900">
                Mã đơn hàng
                <input v-model="code" name="code" required autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="20" placeholder="Ví dụ K7M3QX"
                  class="min-h-12 rounded-btn border border-slate-300 bg-white px-4 text-base font-bold uppercase tracking-wider text-navy-900 outline-none placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-slate-400 focus:border-navy-700 focus:ring-2 focus:ring-navy-700/15"
                  @input="code = code.toUpperCase().replace(/[^A-Z0-9]/g, '')">
              </label>
              <label class="grid gap-1.5 text-sm font-semibold text-navy-900">
                Số điện thoại đặt hàng
                <input v-model="phone" name="phone" type="tel" inputmode="tel" required autocomplete="tel" maxlength="20" placeholder="09xx xxx xxx"
                  class="min-h-12 rounded-btn border border-slate-300 bg-white px-4 text-base text-navy-900 outline-none placeholder:text-slate-400 focus:border-navy-700 focus:ring-2 focus:ring-navy-700/15">
              </label>
              <p v-if="message" class="m-0 rounded-btn bg-red-50 px-3.5 py-2.5 text-sm text-red-700" role="alert">{{ message }}</p>
              <button type="submit" :disabled="busy || code.length < 4 || phone.trim().length < 9" class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-btn bg-navy-800 px-5 font-bold text-white hover:bg-navy-900 disabled:cursor-not-allowed disabled:opacity-60">
                <UIcon name="i-lucide-search" class="size-4" aria-hidden="true" />
                {{ busy ? 'Đang tra cứu…' : 'Tra cứu' }}
              </button>
            </form>
            <p class="mt-5 text-center text-sm text-slate-600">
              Không tìm thấy mã đơn?
              <button type="button" class="cursor-pointer font-bold text-navy-800 underline decoration-slate-300 underline-offset-4 hover:decoration-navy-800" @click="openConsultation('Hỗ trợ tra cứu đơn hàng')">Nhờ nhân viên hỗ trợ</button>
            </p>
          </div>
        </template>

        <template v-else>
          <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
              <h1 class="m-0 text-[clamp(22px,3vw,28px)] font-black tracking-tight text-navy-900">Đơn hàng {{ order.code }}</h1>
              <p class="m-0 mt-1 text-sm text-slate-600">Đặt ngày {{ day(order.placedAt) }}<template v-if="order.paymentMethod"> · {{ order.paymentMethod }}</template></p>
            </div>
            <button type="button" class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 text-sm font-bold text-navy-800 underline decoration-slate-300 underline-offset-4 hover:decoration-navy-800" @click="again">
              <UIcon name="i-lucide-search" class="size-4" aria-hidden="true" />Tra đơn khác
            </button>
          </div>

          <section class="mt-6 rounded-[18px] border border-slate-200 bg-white p-5 sm:p-6" aria-label="Trạng thái đơn hàng">
            <p v-if="order.status === 'cancelled'" class="m-0 flex items-center gap-2 font-bold text-red-700">
              <UIcon name="i-lucide-circle-x" class="size-5" aria-hidden="true" />Đơn hàng đã hủy
            </p>
            <ol v-else class="m-0 grid list-none grid-cols-4 p-0">
              <li v-for="([key, label], i) in STEPS" :key="key" class="relative flex flex-col items-center gap-2 text-center">
                <span v-if="i > 0" class="absolute right-1/2 top-[13px] h-0.5 w-full" :class="i <= stepIndex ? 'bg-navy-800' : 'bg-slate-200'" aria-hidden="true" />
                <span class="relative grid size-7 place-items-center rounded-full text-xs font-bold" :class="i < stepIndex ? 'bg-navy-800 text-white' : i === stepIndex ? 'bg-gold-500 text-navy-950 ring-4 ring-gold-100' : 'bg-slate-100 text-slate-400'">
                  <UIcon v-if="i < stepIndex" name="i-lucide-check" class="size-4" aria-hidden="true" />
                  <template v-else>{{ i + 1 }}</template>
                </span>
                <span class="text-[13px] leading-tight sm:text-sm" :class="i === stepIndex ? 'font-bold text-navy-900' : i < stepIndex ? 'text-navy-800' : 'text-slate-500'">
                  {{ label }}<span v-if="i === stepIndex" class="sr-only"> (hiện tại)</span>
                </span>
              </li>
            </ol>
            <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 border-t border-slate-100 pt-4 text-sm">
              <span class="text-slate-600">Thanh toán: <strong :class="order.paymentStatus === 'paid' ? 'text-emerald-700' : 'text-navy-900'">{{ order.paymentLabel }}</strong></span>
              <span v-if="order.shipment" class="text-slate-600">Vận đơn {{ order.shipment.carrier }}: <strong class="tracking-wide text-navy-900">{{ order.shipment.billCode }}</strong></span>
            </div>
            <ol v-if="order.shipment?.events.length" class="m-0 mt-4 grid list-none gap-3 border-l-2 border-slate-200 p-0 pl-4">
              <li v-for="(event, i) in order.shipment.events" :key="i" class="relative text-sm">
                <span class="absolute -left-[21px] top-1.5 size-2 rounded-full" :class="i === 0 ? 'bg-gold-500 ring-4 ring-gold-100' : 'bg-slate-300'" aria-hidden="true" />
                <span :class="i === 0 ? 'font-bold text-navy-900' : 'text-slate-700'">{{ event.label }}</span>
                <span v-if="event.at" class="ml-2 text-xs text-slate-500">{{ day(event.at, true) }}</span>
              </li>
            </ol>
            <p v-else-if="order.status === 'confirmed' || order.status === 'new'" class="m-0 mt-3 text-sm text-slate-600">Khi hàng được gửi đi, mã vận đơn và hành trình giao hàng sẽ hiện ở đây.</p>
          </section>

          <OrderTransferCard v-if="awaitingTransfer && order.transfer" class="mt-6" :transfer="order.transfer" :order-code="order.code">
            <p v-if="order.transfer.reported" class="m-0 mt-4 flex items-center gap-2 text-sm font-semibold text-emerald-700">
              <UIcon name="i-lucide-check-circle-2" class="size-4" aria-hidden="true" />Đã nhận báo chuyển khoản, kế toán đang đối chiếu.
            </p>
          </OrderTransferCard>

          <section class="mt-6 rounded-[18px] border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="lookup-items">
            <h2 id="lookup-items" class="m-0 text-base font-bold text-navy-900">Sản phẩm</h2>
            <ul class="m-0 mt-3 list-none divide-y divide-slate-100 p-0">
              <li v-for="(item, i) in order.items" :key="i" class="flex items-start justify-between gap-4 py-3 text-sm">
                <span class="min-w-0">
                  <span class="block font-semibold text-navy-900">{{ item.name }}</span>
                  <span class="block text-slate-500"><template v-if="item.option">{{ item.option }} · </template>{{ item.quantity }} × {{ money(item.price) }}</span>
                </span>
                <span class="flex-none font-semibold tabular-nums text-navy-900">{{ money(item.total) }}</span>
              </li>
            </ul>
            <dl class="m-0 mt-2 grid gap-1.5 border-t border-slate-200 pt-3 text-sm">
              <div class="flex justify-between text-slate-600"><dt>Tạm tính</dt><dd class="m-0 tabular-nums">{{ money(order.subtotal) }}</dd></div>
              <div class="flex justify-between text-slate-600"><dt>Phí giao hàng</dt><dd class="m-0 tabular-nums">{{ order.shippingFee ? money(order.shippingFee) : 'Miễn phí' }}</dd></div>
              <div class="flex justify-between pt-1 text-base font-bold text-navy-900"><dt>Tổng cộng</dt><dd class="m-0 tabular-nums">{{ money(order.total) }}</dd></div>
            </dl>
          </section>

          <div class="mt-7 flex flex-wrap items-center justify-center gap-x-5 gap-y-3">
            <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center gap-2 rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-400">Tiếp tục mua sắm <span aria-hidden="true">→</span></NuxtLink>
            <button type="button" class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-bold text-navy-800 underline decoration-slate-300 underline-offset-4 hover:decoration-navy-800" @click="openConsultation(`Hỗ trợ đơn hàng #${order.code}`)">
              <UIcon name="i-lucide-headset" class="size-4 text-gold-700" aria-hidden="true" />Hỏi về đơn này
            </button>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { isPublicOrderThanksPayload, type PublicOrderThanksPayload } from '#shared/contracts/public-checkout'
import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'

const { site } = await usePublicSite()
const route = useRoute()
const links = useMigrationLinks()
const requestFetch = useRequestFetch()
const token = computed(() => typeof route.query.token === 'string' ? route.query.token : '')
const hasValidToken = computed(() => /^[a-f0-9]{48}$/.test(token.value))
if (import.meta.server && hasValidToken.value) useResponseHeader('Cache-Control').value = 'private, no-store'
const { data, error, pending } = await useAsyncData<unknown>(
  () => `order-thanks-${token.value}`,
  () => hasValidToken.value ? requestFetch('/_migration/order-thanks', { query: { token: token.value } }) : Promise.resolve(null),
  { default: () => null },
)
const payload = computed<PublicOrderThanksPayload | null>(() => isPublicOrderThanksPayload(data.value) ? data.value : null)
const isPublicRoute = route.path === ORDER_SUCCESS_PUBLIC_PATH
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
usePublicSocialMeta({
  enabled: isPublicRoute,
  title: 'Đặt hàng thành công — Phúc Thịnh Solar',
  description: computed(() => site.value.brand.name || 'Phúc Thịnh Solar'),
  canonicalPath: ORDER_SUCCESS_PUBLIC_PATH,
  image: computed(() => site.value.brand.logo),
})
if (import.meta.server && isPublicRoute) {
  // PHP renders a generic thank-you page without an order session. A 404 from
  // the read-only detail API also means “no session details”, not no page.
  if (errorStatus && errorStatus !== 404) setResponseStatus(502, 'Bad Gateway')
}

const trackedConversions = new Set<string>()
function trackOrderConversion() {
  const current = payload.value
  const currentToken = token.value
  if (!import.meta.client || !current?.conversionPending || !/^[a-f0-9]{48}$/.test(currentToken) || trackedConversions.has(currentToken)) return
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
    <SiteHeader :site="site" />
    <div class="pts-container">
      <SiteBreadcrumb :items="[{ label: 'Đặt hàng thành công' }]" />
    </div>
    <main class="mx-auto w-full px-[15px] py-[60px] text-center">
      <div v-if="pending" class="text-slate-500" role="status">Đang xác nhận đơn hàng…</div>
      <div v-else-if="error && errorStatus !== 404" class="mx-auto max-w-[760px]">
        <h1 class="mb-3 text-2xl font-black text-navy-800">Tạm thời không thể xác nhận đơn hàng</h1>
        <p class="mb-5 text-slate-600">Hệ thống đang gặp sự cố khi tải thông tin đơn hàng. Vui lòng thử lại sau.</p>
        <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-11 items-center rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline">Tiếp tục mua sắm</NuxtLink>
      </div>
      <div v-else class="mx-auto max-w-[900px]">
        <svg class="mx-auto mb-4 h-16 w-16 text-[#5cb85c]" viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <circle cx="32" cy="32" r="30" fill="currentColor" />
          <path d="m17 32 10 10 21-22" stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="6" />
        </svg>
        <h1 class="mb-4 mt-5 text-[clamp(28px,4vw,38px)] font-black tracking-tight text-navy-800">Đặt hàng thành công!</h1>
        <p class="mx-auto max-w-[700px] text-base leading-relaxed text-slate-700">Cảm ơn {{ payload?.order.customerName || 'bạn' }} đã đặt hàng. Chúng tôi sẽ liên hệ xác nhận đơn hàng trong thời gian sớm nhất.</p>
        <p v-if="payload" class="mt-3 text-base text-slate-700">Mã đơn hàng: <strong class="text-navy-800">{{ payload.order.code }}</strong></p>
        <NuxtLink :to="links.generic('/san-pham/')" class="mt-5 inline-flex min-h-11 items-center rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline">Tiếp tục mua sắm</NuxtLink>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

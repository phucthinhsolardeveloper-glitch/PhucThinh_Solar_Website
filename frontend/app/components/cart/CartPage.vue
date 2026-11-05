<script setup lang="ts">
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'
import CommerceSteps from '~/components/commerce/CommerceSteps.vue'
import AnimatedMoney from '~/components/commerce/AnimatedMoney.vue'
import { isPublicCartPayload, type PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCheckoutNoticePayload, type PublicCheckoutNoticePayload, type PublicCheckoutNoticeForm } from '#shared/contracts/public-checkout'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'

const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const { cart, loading, error, refresh, mutate } = useMigrationCart()
const links = useMigrationLinks()
const isMigrationPreview = links.active
const checkoutNotice = ref<PublicCheckoutNoticePayload | null>(null)
const checkoutDraft = useState<PublicCheckoutNoticeForm | null>('pts-checkout-form-draft', () => null)
const { data: initialCart, error: initialCartError } = await useFetch<unknown>('/_migration/cart', { key: 'migration-cart-page', default: () => null })
if (isPublicCartPayload(initialCart.value)) cart.value = initialCart.value
else if (!isMigrationPreview.value && import.meta.server) setResponseStatus(502, 'Bad Gateway')
if (!isMigrationPreview.value && import.meta.server && initialCartError.value) setResponseStatus(502, 'Bad Gateway')

usePublicSocialMeta({
  enabled: computed(() => !isMigrationPreview.value),
  title: 'Giỏ hàng | Phúc Thịnh Solar',
  description: computed(() => site.value.brand.name || 'Phúc Thịnh Solar'),
  canonicalPath: '/gio-hang/',
  image: computed(() => site.value.brand.logo),
})

const quantityDraft = reactive<Record<string, number>>({})
const busyCode = ref<string | null>(null)
const queuedUpdates = new Map<string, { productId: number; quantity: number }>()

watchEffect(() => {
  for (const item of cart.value?.items ?? []) {
    if (!(item.code in quantityDraft)) quantityDraft[item.code] = item.quantity
  }
})

function formatMoney(value: number | null): string {
  return value === null ? 'Liên hệ' : new Intl.NumberFormat('vi-VN').format(value) + 'đ'
}

async function updateItem(code: string, productId: number, value: unknown = quantityDraft[code] ?? 1) {
  const quantity = normalizeCommerceQuantity(value)
  quantityDraft[code] = quantity
  if (busyCode.value) {
    queuedUpdates.set(code, { productId, quantity })
    return
  }
  if (loading.value) return
  busyCode.value = code
  try {
    await mutate({ action: 'update', code, productId, quantity })
  } catch {
    const current = cart.value?.items.find((item) => item.code === code)
    if (current) quantityDraft[code] = current.quantity
  } finally {
    busyCode.value = null
    const next = queuedUpdates.entries().next().value
    if (next) {
      queuedUpdates.delete(next[0])
      void updateItem(next[0], next[1].productId, next[1].quantity)
    }
  }
}

// Removal slides the row out and offers a short undo (re-adds the same product and quantity).
const removed = ref<{ name: string; productId: number; quantity: number; canUndo: boolean } | null>(null)
let removedTimer: ReturnType<typeof setTimeout> | undefined

const removingCode = ref<string | null>(null)
async function removeItem(code: string) {
  const item = cart.value?.items.find((entry) => entry.code === code)
  removingCode.value = code
  try {
    await mutate({ action: 'delete', code })
    delete quantityDraft[code]
    if (item) {
      removed.value = { name: item.name, productId: item.productId, quantity: item.quantity, canUndo: !item.color && !item.size }
      clearTimeout(removedTimer)
      removedTimer = setTimeout(() => { removed.value = null }, 6000)
    }
  } catch {
    // The composable retains the error for the alert below; avoid an unhandled
    // event-handler rejection when the PHP adapter refuses the mutation.
  } finally {
    removingCode.value = null
  }
}

async function undoRemove() {
  const last = removed.value
  if (!last?.canUndo) return
  removed.value = null
  clearTimeout(removedTimer)
  try {
    await mutate({ action: 'add', productId: last.productId, quantity: last.quantity })
  } catch {
    // Error surfaces in the alert.
  }
}
onBeforeUnmount(() => clearTimeout(removedTimer))

const payload = computed<PublicCartPayload | null>(() => cart.value)

onMounted(async () => {
  try {
    const notice = await $fetch<unknown>('/_migration/checkout-notice')
    if (isPublicCheckoutNoticePayload(notice)) checkoutNotice.value = notice
  } catch {
    // Checkout flash is optional; cart/session rendering remains independent.
  }
})

function openCheckout() {
  checkoutDraft.value = checkoutNotice.value?.form ?? null
}
</script>

<template>
  <div class="min-h-screen bg-[#f6f8fb] text-navy-800">
    <SiteHeader :site="site" />
    <div class="border-b border-slate-200/80 bg-white">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Giỏ hàng' }]" />
      </div>
    </div>
    <main class="pts-container py-8 max-ph:py-6">
      <header class="cart-head">
        <div>
          <h1 class="m-0 text-[clamp(30px,4vw,46px)] font-black tracking-tight text-navy-950">Giỏ hàng của bạn</h1>
          <p v-if="payload?.items.length" class="mt-1.5 mb-0 text-slate-500">{{ payload.quantityCount }} sản phẩm đang chờ bạn hoàn tất</p>
        </div>
        <CommerceSteps current="cart" class="cart-head__steps" />
      </header>

      <div v-if="!payload && loading" class="cart-panel p-8 text-slate-500" role="status">Đang tải giỏ hàng…</div>
      <div v-else-if="!payload" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">
        <strong>Không thể tải session giỏ hàng.</strong>
        <p class="mb-0 mt-2">{{ error || 'PHP cart API local chưa khả dụng.' }}</p>
        <button type="button" class="public-neutral-button mt-4 rounded-chip px-4 py-2 font-bold" @click="refresh()">Thử lại</button>
      </div>

      <template v-else>
        <p v-if="error" class="mb-6 rounded-btn border border-red-200 bg-red-50 p-4 text-red-800" role="alert">{{ error }}</p>
        <div
          v-if="checkoutNotice && (checkoutNotice.messages.length || checkoutNotice.toast)"
          class="mb-6 rounded-btn border border-red-200 bg-red-50 p-4 text-red-800"
          :role="checkoutNotice.messages.length || checkoutNotice.toast?.status === 'error' ? 'alert' : 'status'"
        >
          <p v-if="checkoutNotice.toast" class="mb-1">{{ checkoutNotice.toast.text }}</p>
          <ul v-if="checkoutNotice.messages.length" class="mb-0 list-disc pl-5">
            <li v-for="(message, index) in checkoutNotice.messages" :key="index">{{ message }}</li>
          </ul>
        </div>

        <div v-if="!payload.items.length" class="cart-empty">
          <div class="cart-empty__art" aria-hidden="true">
            <span class="cart-empty__ring" />
            <UIcon name="i-lucide-shopping-bag" class="size-10" />
          </div>
          <h2 class="mb-2 text-2xl font-black text-navy-950">Giỏ hàng đang trống</h2>
          <p class="mx-auto mb-6 max-w-[520px] text-slate-600">
            Chọn sản phẩm có giá công khai để thêm vào giỏ hàng, hoặc gửi yêu cầu để đội ngũ Phúc Thịnh Solar bóc tách vật tư và báo giá trọn gói theo công trình.
          </p>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <NuxtLink :to="links.generic('/san-pham/')" class="cart-btn cart-btn--gold">
              <span>Xem sản phẩm</span>
              <span aria-hidden="true">→</span>
            </NuxtLink>
            <button type="button" class="cart-btn cart-btn--ghost" @click="openConsultation('Giỏ hàng: Tư vấn báo giá vật tư')">
              <UIcon name="i-lucide-headset" class="size-4 text-gold-600" aria-hidden="true" />
              <span>Yêu cầu báo giá theo công trình</span>
            </button>
          </div>

          <div v-if="site.productCategories.length" class="mx-auto mt-10 max-w-[880px] border-t border-slate-200/80 pt-7 text-left">
            <p class="m-0 mb-3.5 text-center text-sm font-bold text-navy-800">Khám phá nhanh theo nhóm thiết bị</p>
            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
              <NuxtLink
                v-for="cat in site.productCategories.slice(0, 6)"
                :key="cat.slug"
                :to="links.generic(cat.url)"
                class="group flex items-center justify-between gap-3 rounded-[8px] border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-navy-900 no-underline transition-colors hover:border-gold-500"
              >
                <span class="truncate">{{ cat.name }}</span>
                <UIcon name="i-lucide-arrow-right" class="size-4 shrink-0 text-slate-400 transition-transform group-hover:translate-x-0.5 group-hover:text-gold-700" aria-hidden="true" />
              </NuxtLink>
            </div>
          </div>
        </div>

        <div v-else class="grid items-start gap-6 nav:grid-cols-[minmax(0,1fr)_minmax(420px,480px)] nav:gap-8">
          <div class="min-w-0">
            <Transition name="cart-undo">
              <div v-if="removed" class="cart-undo" role="status">
                <UIcon name="i-lucide-trash" class="size-4 flex-none text-slate-400" aria-hidden="true" />
                <span class="min-w-0 flex-1 truncate">Đã xóa <strong>{{ removed.name }}</strong></span>
                <button v-if="removed.canUndo" type="button" class="cart-undo__btn" :disabled="loading" @click="undoRemove">Hoàn tác</button>
              </div>
            </Transition>

            <TransitionGroup tag="section" name="cart-row" aria-label="Sản phẩm trong giỏ" tabindex="0" class="cart-list">
              <article v-for="item in payload.items" :key="item.code" class="cart-row grid grid-cols-[96px_minmax(0,1fr)] gap-x-4 gap-y-2 tb:grid-cols-[112px_minmax(0,1fr)_150px_156px] tb:items-center">
                <NuxtLink :to="links.product(item.slug)" class="cart-row__media row-span-3 tb:row-auto">
                  <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-contain" loading="lazy">
                  <span v-else class="px-2 text-center text-xs text-slate-400">Chưa có ảnh</span>
                </NuxtLink>

                <div class="col-start-2 row-start-1 min-w-0 tb:col-start-2 tb:row-auto">
                  <NuxtLink :to="links.product(item.slug)" class="cart-row__name">{{ item.name }}</NuxtLink>
                  <div v-if="item.color || item.size" class="mt-1 text-sm text-slate-500">
                    <span v-if="item.color">Màu: {{ item.color }}</span><span v-if="item.color && item.size"> · </span><span v-if="item.size">Size: {{ item.size }}</span>
                  </div>
                  <button
                    type="button"
                    class="cart-row__remove"
                    :disabled="removingCode === item.code || busyCode === item.code"
                    @click="removeItem(item.code)"
                  ><UIcon name="i-lucide-trash" class="size-4" aria-hidden="true" />Xóa</button>
                </div>

                <div class="col-start-2 row-start-2 text-sm tb:col-start-3 tb:row-auto tb:text-right">
                  <strong class="block text-[17px] text-red-600"><AnimatedMoney :value="item.lineTotal" :animate="false" /></strong>
                  <small class="text-slate-400">{{ formatMoney(item.unitPrice) }} / sản phẩm</small>
                </div>

                <div class="col-start-2 row-start-3 w-fit self-center tb:col-start-4 tb:row-auto tb:justify-self-end">
                  <QuantityStepper
                    :model-value="quantityDraft[item.code] ?? item.quantity"
                    label="Số lượng"
                    :busy="loading"
                    :disabled="busyCode === item.code || (loading && !busyCode)"
                    @update:model-value="quantityDraft[item.code] = $event"
                    @change="updateItem(item.code, item.productId, $event)"
                  />
                </div>
              </article>
            </TransitionGroup>

            <NuxtLink :to="links.generic('/san-pham/')" class="mt-5 inline-flex min-h-11 items-center gap-2 font-bold text-navy-800 no-underline hover:text-gold-700">
              <UIcon name="i-lucide-arrow-left" class="size-4" aria-hidden="true" />Tiếp tục xem sản phẩm
            </NuxtLink>
          </div>

          <aside class="cart-summary nav:sticky nav:top-28">
            <h2 class="m-0 text-lg font-extrabold text-white">Tóm tắt đơn hàng</h2>
            <dl class="cart-summary__rows">
              <div><dt>Tạm tính ({{ payload.quantityCount }} sản phẩm)</dt><dd><AnimatedMoney :value="payload.subtotal" :animate="false" /></dd></div>
              <div><dt>Vận chuyển</dt><dd class="text-blue-100/80">Tính ở bước tiếp theo</dd></div>
            </dl>
            <div class="cart-summary__total">
              <span>Tổng tạm tính</span>
              <strong><AnimatedMoney :value="payload.subtotal" :animate="false" /></strong>
            </div>
            <p v-if="!payload.canCheckout" class="cart-summary__note">
              Giỏ có sản phẩm cần báo giá; hãy liên hệ tư vấn trước khi đặt hàng.
            </p>
            <p v-else-if="isMigrationPreview" class="cart-summary__note" role="status">
              Checkout đang ở chế độ preview chỉ đọc; trang này không nhận thông tin giao hàng và không tạo đơn hàng.
            </p>
            <NuxtLink
              v-else
              to="/thanh-toan/"
              class="cart-summary__cta"
              @click="openCheckout"
            >
              Tiến hành thanh toán
              <span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
            </NuxtLink>
            <button v-if="!payload.canCheckout" type="button" class="cart-summary__cta" @click="openConsultation('Giỏ hàng: Yêu cầu báo giá')">
              Nhận báo giá
              <span aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-5" /></span>
            </button>
            <ul class="cart-summary__trust">
              <li><UIcon name="i-lucide-badge-check" class="size-4 flex-none" aria-hidden="true" />Kiểm tra sản phẩm và số lượng trước khi đặt hàng</li>
              <li><UIcon name="i-lucide-shield-check" class="size-4 flex-none" aria-hidden="true" />Phí vận chuyển được xác nhận ở bước thanh toán</li>
              <li v-if="site.brand.hotline"><UIcon name="i-lucide-phone-call" class="size-4 flex-none" aria-hidden="true" />Hỗ trợ đặt hàng: <a :href="`tel:${site.brand.hotline.replace(/\D/g, '')}`">{{ site.brand.hotline }}</a></li>
            </ul>
          </aside>
        </div>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>

<style scoped>
.cart-head { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 520px); align-items: center; gap: 24px 48px; margin-bottom: clamp(24px, 3vw, 40px); }
.cart-panel { border-radius: var(--radius-md); background: #fff; }
.cart-list { display: grid; align-content: start; gap: 12px; max-height: min(70svh, 740px); overflow-y: auto; padding-right: 6px; scrollbar-color: var(--color-gold-500) transparent; scrollbar-width: thin; }
.cart-list:focus-visible { outline: 3px solid var(--color-gold-700); outline-offset: -3px; }
.cart-row { padding: clamp(14px, 1.6vw, 20px); border: 1px solid rgb(10 34 81 / 7%); border-radius: var(--radius-md); background: #fff; box-shadow: 0 1px 0 rgb(10 34 81 / 3%); transition: box-shadow 0.3s ease; }
.cart-row:hover { box-shadow: 0 18px 36px -28px rgb(10 34 81 / 45%); }
.cart-row__media { display: grid; aspect-ratio: 1; place-items: center; overflow: hidden; border-radius: 10px; background: #f6f8fb; }
.cart-row__name { display: block; color: var(--color-navy-950); font-size: 16.5px; font-weight: 800; line-height: 1.35; text-decoration: none; }
.cart-row__name:hover { color: var(--color-gold-700); }
.cart-row__remove { display: inline-flex; min-height: 36px; align-items: center; gap: 6px; margin-top: 4px; padding: 0; border: 0; background: none; color: #94a3b8; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: color 0.2s ease; }
.cart-row__remove:hover { color: #b91c1c; }
.cart-row__remove:disabled { cursor: wait; }
.cart-row-move { transition: transform 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.cart-row-leave-active { position: absolute; width: 100%; transition: opacity 0.3s ease, transform 0.35s cubic-bezier(0.55, 0, 1, 0.45); }
.cart-row-leave-to { opacity: 0; transform: translateX(40px); }
.cart-row-enter-active { transition: opacity 0.35s ease, transform 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.cart-row-enter-from { opacity: 0; transform: translateY(-10px); }
.cart-list { position: relative; }
.cart-undo { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 10px 10px 10px 16px; border-radius: 12px; background: var(--color-navy-950); color: #e2e8f0; font-size: 14.5px; }
.cart-undo strong { color: #fff; }
.cart-undo__btn { min-height: 44px; padding: 0 16px; border: 0; border-radius: 4px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-weight: 800; cursor: pointer; }
.cart-undo-enter-active, .cart-undo-leave-active { transition: opacity 0.25s ease, transform 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
.cart-undo-enter-from, .cart-undo-leave-to { opacity: 0; transform: translateY(-8px); }

.cart-summary { overflow: hidden; padding: clamp(22px, 2vw, 30px); border-radius: var(--radius-md); background: radial-gradient(120% 80% at 100% 0%, rgb(228 168 73 / 18%), transparent 55%), var(--color-navy-950); color: #fff; box-shadow: 0 30px 60px -36px rgb(6 21 55 / 70%); }
.cart-summary__rows { display: grid; gap: 12px; margin: 20px 0 0; padding: 0 0 18px; border-bottom: 1px solid rgb(255 255 255 / 12%); }
.cart-summary__rows > div { display: flex; justify-content: space-between; gap: 12px; font-size: 15px; }
.cart-summary__rows dt { color: #b9c6d6; }
.cart-summary__rows dd { margin: 0; font-weight: 700; text-align: right; }
.cart-summary__total { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; padding-top: 18px; white-space: nowrap; }
.cart-summary__total span { color: #d3dcea; font-weight: 700; }
.cart-summary__total strong { background-image: var(--pts-gold-gradient); background-clip: text; color: transparent; -webkit-text-fill-color: transparent; font-size: clamp(22px, 2vw, 30px); font-weight: 900; letter-spacing: -0.02em; }
.cart-summary__note { margin: 16px 0 0; padding: 12px 14px; border-radius: 10px; background: rgb(255 255 255 / 8%); color: #e2e8f0; font-size: 14px; line-height: 1.55; }
.cart-summary__cta { display: flex; width: 100%; min-height: 58px; align-items: center; justify-content: space-between; margin-top: 20px; padding: 0 10px 0 22px; border: 0; border-radius: 4px; background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 16.5px; font-weight: 850; text-decoration: none; cursor: pointer; transition: filter 0.25s ease; }
.cart-summary__cta > span { display: grid; width: 40px; height: 40px; place-items: center; border-radius: 8px; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.cart-summary__cta:hover { filter: brightness(1.06); }
.cart-summary__cta:hover > span { transform: translateX(4px); }
.cart-summary__cta:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
.cart-summary__trust { display: grid; gap: 10px; margin: 20px 0 0; padding: 18px 0 0; border-top: 1px solid rgb(255 255 255 / 12%); list-style: none; }
.cart-summary__trust li { display: flex; align-items: center; gap: 10px; color: #c3cfdf; font-size: 13.5px; }
.cart-summary__trust li :deep(.iconify), .cart-summary__trust li > :first-child { color: var(--color-gold-300); }
.cart-summary__trust a { color: var(--color-gold-300); font-weight: 800; text-decoration: none; }

.cart-empty { padding: clamp(36px, 5vw, 64px) 24px; border-radius: var(--radius-md); background: #fff; text-align: center; }
.cart-empty__art { position: relative; display: grid; width: 96px; height: 96px; margin: 0 auto 20px; place-items: center; border-radius: 50%; background: rgb(228 168 73 / 14%); color: var(--color-gold-700); }
.cart-empty__ring { position: absolute; inset: -10px; border: 1px dashed rgb(201 150 46 / 45%); border-radius: 50%; animation: empty-spin 18s linear infinite; }
@keyframes empty-spin { to { transform: rotate(360deg); } }
.cart-btn { display: inline-flex; min-height: 48px; align-items: center; gap: 8px; padding: 0 22px; border-radius: 10px; font-weight: 800; text-decoration: none; cursor: pointer; transition: background-color 0.2s ease, border-color 0.2s ease; }
.cart-btn--gold { background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.cart-btn--gold:hover { filter: brightness(1.06); }
.cart-btn--ghost { border: 1px solid rgb(10 34 81 / 18%); background: #fff; color: var(--color-navy-900); }
.cart-btn--ghost:hover { border-color: var(--color-navy-800); }

@media (max-width: 900px) {
  .cart-head { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  .cart-row-move, .cart-row-leave-active, .cart-row-enter-active, .cart-undo-enter-active, .cart-undo-leave-active { transition: none; }
  .cart-empty__ring { animation: none; }
}
</style>

<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import { normalizeCommerceQuantity } from '#shared/commerce-quantity'
import QuantityStepper from '~/components/commerce/QuantityStepper.vue'

const props = defineProps<{ productId: number; pricing: PublicCatalogPricing; consultationAvailable: boolean; productName?: string; productImage?: string }>()
const { celebrate } = useCartFeedback()
const quantity = ref(1)
const loadingAction = ref<'buy' | 'cart' | null>(null)
const { loading, error, mutate } = useShopCart()
const links = usePublicLinks()
const { openDealer, openConsultation } = useStorefrontModal()
const buyPrice = computed(() => `${new Intl.NumberFormat('vi-VN').format(props.pricing.salePrice ?? props.pricing.regularPrice ?? 0)}đ`)
const { hotline, phoneHref } = usePublicContactLinks()
const actionBarActive = useMobileActionBar()
actionBarActive.value = true
onBeforeUnmount(() => { actionBarActive.value = false })
// The footer publishes contact links after this panel renders on the server, so they appear once mounted.
const mounted = ref(false)
onMounted(() => { mounted.value = true })

// Desktop: the same island appears once the reader has scrolled past the main buttons, so buying stays one click away.
const buySentinel = ref<HTMLElement | null>(null)
const buttonsOffScreen = ref(false)
let buyObserver: IntersectionObserver | undefined
onMounted(() => {
  if (!buySentinel.value || !('IntersectionObserver' in window)) return
  // The huge bottom margin counts everything below the fold as "not yet passed", so a long jump still flips the state.
  buyObserver = new IntersectionObserver(([entry]) => { buttonsOffScreen.value = entry?.isIntersecting === false }, { rootMargin: '-88px 0px 100000px 0px' })
  buyObserver.observe(buySentinel.value)
})
onBeforeUnmount(() => buyObserver?.disconnect())

async function add(goToCart = false) {
  quantity.value = normalizeCommerceQuantity(quantity.value)
  loadingAction.value = goToCart ? 'buy' : 'cart'
  try {
    await mutate({ action: 'add', productId: props.productId, quantity: quantity.value })
  } catch {
    return
  } finally {
    loadingAction.value = null
  }
  if (goToCart) { await navigateTo(links.generic('/gio-hang/')); return }
  celebrate({ name: props.productName || 'Sản phẩm', image: props.productImage, quantity: quantity.value, from: import.meta.client ? document.querySelector('[data-cart-source]') : null })
  justAdded.value = true
  clearTimeout(justAddedTimer)
  justAddedTimer = setTimeout(() => { justAdded.value = false }, 2000)
}
// Compare: the floating "So sánh" button opens the side-by-side table once two or more are picked.
const compare = useProductCompare()
const comparing = computed(() => compare.has(props.productId))
const compareNote = ref('')
function toggleCompare() {
  const added = !comparing.value
  if (!compare.toggle({ id: props.productId, name: props.productName || 'Sản phẩm', image: props.productImage || '' })) {
    compareNote.value = `Đã đủ ${COMPARE_MAX} sản phẩm để so sánh. Bỏ bớt một sản phẩm rồi thêm lại.`
    return
  }
  compareNote.value = added && compare.items.value.length === 1 ? 'Đã thêm. Mở thêm một sản phẩm khác và bấm "So sánh" để xem cạnh nhau.' : ''
}
// Brief confirmation on the button itself; the status line below has a reserved slot.
const justAdded = ref(false)
let justAddedTimer: ReturnType<typeof setTimeout> | undefined
onBeforeUnmount(() => clearTimeout(justAddedTimer))
</script>

<template>
  <section class="mt-4" aria-label="Mua sản phẩm">
    <QuantityStepper v-if="pricing.mode === 'price'" v-model="quantity" label="Số lượng" inline-label :disabled="loading" />
    <slot name="product-info" />
    <div v-if="pricing.mode === 'price'" class="mt-5 grid gap-[9px] max-tb:hidden">
      <UButton
        type="button"
        color="primary"
        size="lg"
        :loading="loading && loadingAction === 'buy'"
        class="buy-now min-h-12 w-full justify-center rounded-btn bg-gold-500 px-14 font-extrabold text-navy-950 transition-all duration-150 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer shadow-xs"
        :disabled="loading"
        @click="add(true)"
      >
        {{ loadingAction === 'buy' ? 'Đang xử lý…' : 'Mua ngay' }}
        <span v-if="loadingAction !== 'buy'" class="buy-now__arrow" aria-hidden="true"><UIcon name="i-lucide-arrow-right" class="size-[18px]" /></span>
      </UButton>
      <UButton
        type="button"
        color="secondary"
        variant="outline"
        size="lg"
        :loading="loading && loadingAction === 'cart'"
        class="buy-add public-neutral-button public-navy-outline-button min-h-12 w-full justify-center rounded-btn px-5 font-bold text-navy-800 transition-all duration-150 active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer"
        :disabled="loading"
        @click="add(false)"
      >
        <template v-if="loadingAction === 'cart'">Đang thêm…</template>
        <span v-else-if="justAdded" class="added-label"><UIcon name="i-lucide-check" class="size-5" aria-hidden="true" /> Đã thêm vào giỏ</span>
        <template v-else><span class="buy-add__icon" aria-hidden="true"><UIcon name="i-lucide-shopping-cart" class="size-5" /><UIcon name="i-lucide-plus" class="buy-add__plus size-3" /></span>Thêm vào giỏ</template>
      </UButton>
    </div>
    <div ref="buySentinel" aria-hidden="true" />
    <!-- Confirmation plays as the flight + card (CartFeedback); only errors stay inline, in a reserved slot. -->
    <div v-if="pricing.mode === 'price'" class="purchase-status mt-3 min-h-6 max-tb:hidden" aria-live="polite">
      <Transition name="purchase-status" mode="out-in">
        <p v-if="error" key="error" class="m-0 text-sm font-semibold text-red-700" role="alert">{{ error }}</p>
      </Transition>
    </div>
    <UButton
      v-if="pricing.mode === 'quote' && consultationAvailable"
      href="#pts-pdp-consultation"
      color="primary"
      size="lg"
      class="mt-5 min-h-12 w-full justify-center rounded-btn bg-gold-500 px-6 font-extrabold text-navy-950 no-underline max-tb:hidden transition-all duration-150 hover:bg-gold-600 active:scale-[0.98] cursor-pointer shadow-xs"
      @click.prevent="openConsultation()"
    >
      Liên hệ nhận báo giá
    </UButton>
    <p v-else-if="pricing.mode === 'quote'" class="mb-0 text-sm text-slate-500" role="status">
      Thông tin tư vấn sản phẩm hiện chưa tải được.
    </p>

    <!-- Link đại lý: mở popup modal đại lý của hệ thống đại lý -->
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 font-bold text-navy-800 transition-colors hover:text-gold-700 cursor-pointer"
        @click="openDealer()"
      >
        <span>Bạn là đại lý? Đăng ký hợp tác</span>
        <span aria-hidden="true" class="text-gold-600 font-black">→</span>
      </button>
      <button
        v-if="mounted"
        type="button"
        class="compare-toggle"
        :class="{ 'is-on': comparing }"
        :aria-pressed="comparing"
        @click="toggleCompare"
      >
        <UIcon :name="comparing ? 'i-lucide-check' : 'i-lucide-git-compare-arrows'" class="size-4" aria-hidden="true" />
        {{ comparing ? 'Đang so sánh' : 'So sánh' }}
      </button>
      <p v-if="compareNote" class="compare-note" role="status">{{ compareNote }}</p>
    </div>

    <!-- Keep product trust copy aligned with the catalog's published promises. -->
    <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4" aria-label="Cam kết dịch vụ">
      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M8 14a6 6 0 1 1 8 0c-.8.7-1 1.4-1 2H9c0-.6-.2-1.3-1-2Z"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Tư vấn lựa chọn</span>
          <span class="text-slate-500 text-[11px]">Đúng nhu cầu, hiệu quả</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m14.7 6.3 3 3M3 21l6.5-6.5M14 4l2-2 6 6-2 2M9.5 14.5 5 10l4-4 4.5 4.5"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Hỗ trợ kỹ thuật</span>
          <span class="text-slate-500 text-[11px]">Trong suốt quá trình sử dụng</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Chính sách rõ ràng</span>
          <span class="text-slate-500 text-[11px]">Minh bạch, dễ hiểu</span>
        </div>
      </div>

      <div class="flex items-center gap-2.5">
        <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-amber-50 text-gold-700" aria-hidden="true">
          <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v11H3zM14 10h4l3 4v4h-7z"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="17.5" cy="18.5" r="1.5"/></svg>
        </span>
        <div class="text-[12px] leading-tight">
          <span class="font-extrabold text-navy-950 block">Giao hàng toàn quốc</span>
          <span class="text-slate-500 text-[11px]">Nhanh chóng, an toàn</span>
        </div>
      </div>
    </div>

    <!-- Phone: the buying island replaces the navigation island on this page. Desktop: it follows once the buttons scroll away. -->
    <div class="buy-island m-glass print:hidden" :class="{ 'is-on': buttonsOffScreen }">
      <p v-if="error" class="buy-island__error" role="alert">{{ error }}</p>
      <strong v-if="productName" class="buy-island__name">{{ productName }}</strong>
      <a v-if="mounted && phoneHref" :href="phoneHref" :aria-label="`Gọi hotline ${hotline}`" class="buy-island__icon" data-tip="Gọi tư vấn">
        <UIcon name="i-lucide-phone" class="size-[21px]" aria-hidden="true" />
      </a>
      <button
        v-if="pricing.mode === 'price'"
        type="button"
        :aria-label="justAdded ? 'Đã thêm vào giỏ' : 'Thêm vào giỏ'"
        class="buy-island__icon"
        :data-tip="justAdded ? 'Đã thêm vào giỏ' : 'Thêm vào giỏ'"
        :disabled="loading"
        @click="add(false)"
      >
        <!-- A small plus tells "add to cart" apart from "go to cart". -->
        <span class="buy-island__cart" aria-hidden="true">
          <UIcon :name="justAdded ? 'i-lucide-check' : 'i-lucide-shopping-cart'" class="size-[21px]" />
          <UIcon v-if="!justAdded" name="i-lucide-plus" class="buy-island__plus size-3" />
        </span>
      </button>
      <button
        type="button"
        class="buy-island__main"
        :disabled="pricing.mode === 'price' ? loading : !consultationAvailable"
        @click="pricing.mode === 'price' ? add(true) : openConsultation()"
      >
        <template v-if="pricing.mode === 'price'">
          <span>{{ loadingAction === 'buy' ? 'Đang xử lý…' : 'Mua ngay' }}</span>
          <small>{{ buyPrice }}</small>
        </template>
        <template v-else>Nhận báo giá</template>
      </button>
    </div>
  </section>
</template>

<style scoped>
/* Buy now: a navy arrow like the site's other gold actions, with a band of light crossing now and then. */
.buy-now { position: relative; overflow: hidden; isolation: isolate; }
.buy-now::after { content: ""; position: absolute; z-index: -1; inset: 0; background: linear-gradient(105deg, transparent 38%, rgb(255 255 255 / 45%) 50%, transparent 62%); transform: translateX(-110%); animation: buy-sheen 5.5s cubic-bezier(.22, 1, .36, 1) 1.5s infinite; pointer-events: none; }
.buy-now:hover:not(:disabled) { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%), 0 8px 20px -8px rgb(180 133 31 / 70%); }
.buy-now__arrow { position: absolute; top: 50%; right: 6px; display: grid; width: 36px; height: 36px; place-items: center; border-radius: var(--radius-sm); background: var(--color-navy-950); color: var(--color-gold-300); transform: translateY(-50%); }
.buy-now__arrow :deep(svg) { transition: transform .25s cubic-bezier(.22, 1, .36, 1); }
.buy-now:hover:not(:disabled) .buy-now__arrow :deep(svg) { transform: translateX(3px); }
@keyframes buy-sheen { 0% { transform: translateX(-110%); } 28%, 100% { transform: translateX(110%); } }
/* Add to cart: the cart in gold with a small plus, which drops in as the pointer arrives. */
.buy-add__icon { position: relative; display: inline-flex; margin-right: 8px; color: var(--color-gold-700); vertical-align: middle; transition: transform .25s cubic-bezier(.22, 1, .36, 1); }
.buy-add__plus { position: absolute; top: -5px; right: -6px; border-radius: 50%; background: var(--color-navy-950); color: var(--color-gold-300); transition: transform .25s cubic-bezier(.22, 1, .36, 1); }
.buy-add:hover:not(:disabled) { border-color: var(--color-gold-500) !important; background: linear-gradient(180deg, #fffdf6, #fbf3dd) !important; }
.buy-add:hover:not(:disabled) .buy-add__icon { transform: translateX(-3px) rotate(-6deg); }
.buy-add:hover:not(:disabled) .buy-add__plus { transform: scale(1.3); }
@media (prefers-reduced-motion: reduce) {
  .buy-now::after { animation: none; display: none; }
  .buy-now__arrow, .buy-add__icon, .buy-add__plus { transition: none; }
}
.buy-island { position: fixed; left: 12px; right: 72px; bottom: calc(12px + env(safe-area-inset-bottom)); z-index: 40; display: flex; align-items: center; gap: 2px; height: 60px; padding: 6px; border-radius: 30px; }
.buy-island__name { display: none; }
@media (min-width: 821px) {
  .buy-island { right: auto; bottom: 20px; left: 50%; width: min(640px, calc(100vw - 200px)); padding-left: 22px; visibility: hidden; opacity: 0; transform: translate(-50%, 14px); transition: opacity .22s ease, transform .22s ease, visibility .22s; }
  .buy-island.is-on { visibility: visible; opacity: 1; transform: translate(-50%, 0); }
  .buy-island__name { display: block; min-width: 0; flex: 1; overflow: hidden; color: #fff; font-size: 14px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
  .buy-island__main { flex: none; min-width: 170px; padding-inline: 20px; }
}
@media (min-width: 821px) and (prefers-reduced-motion: reduce) { .buy-island { transition: none; } }
.buy-island__error { position: absolute; left: 0; right: 0; bottom: calc(100% + 8px); margin: 0; padding: 8px 14px; border-radius: var(--radius-md); background: #fef2f2; color: #b91c1c; font-size: 13px; font-weight: 600; }
.buy-island__icon { display: grid; flex: none; width: 44px; height: 46px; place-items: center; color: #dbe4f0; text-decoration: none; }
.buy-island__icon:disabled, .buy-island__main:disabled { opacity: .55; }
.buy-island__main { display: flex; flex: 1; min-width: 0; height: 46px; flex-direction: column; align-items: center; justify-content: center; border-radius: 24px; box-shadow: inset 0 1px 0 rgb(255 255 255 / 45%); background: var(--pts-gold-gradient); color: var(--color-navy-950); font-size: 14px; font-weight: 800; line-height: 1.15; }
.buy-island__cart { position: relative; display: grid; place-items: center; }
.buy-island__plus { position: absolute; top: -5px; right: -7px; border-radius: 50%; background: var(--color-gold-400); color: var(--color-navy-950); }
.buy-island__main { position: relative; overflow: hidden; isolation: isolate; }
/* With a mouse: icons light up gold and name themselves, and a band of light crosses the buy button now and then. */
@media (hover: hover) and (min-width: 821px) {
  .buy-island__icon { position: relative; border-radius: 22px; transition: background-color .2s ease, color .2s ease; }
  .buy-island__icon:hover:not(:disabled) { background: rgb(255 255 255 / 12%); color: var(--color-gold-300); }
  .buy-island__icon::after { content: attr(data-tip); position: absolute; bottom: calc(100% + 10px); left: 50%; padding: 5px 10px; border-radius: var(--radius-sm); background: var(--color-navy-950); color: #fff; font-size: 12.5px; font-weight: 700; white-space: nowrap; opacity: 0; transform: translate(-50%, 4px); transition: opacity .18s ease, transform .18s ease; pointer-events: none; }
  .buy-island__icon:hover::after, .buy-island__icon:focus-visible::after { opacity: 1; transform: translate(-50%, 0); }
  .buy-island__icon:hover .buy-island__plus { transform: scale(1.25); }
  .buy-island__plus { transition: transform .2s ease; }
  .buy-island__main::after { content: ""; position: absolute; z-index: -1; inset: 0; background: linear-gradient(105deg, transparent 38%, rgb(255 255 255 / 45%) 50%, transparent 62%); transform: translateX(-110%); animation: buy-sheen 5.5s cubic-bezier(.22, 1, .36, 1) 1s infinite; pointer-events: none; }
  .buy-island__main:hover:not(:disabled) { box-shadow: inset 0 1px 0 rgb(255 255 255 / 45%), inset 0 0 0 999px rgb(255 255 255 / 10%); }
}
@media (prefers-reduced-motion: reduce) { .buy-island__main::after { display: none; } }
.buy-island__main small { font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; }
.added-label { display: inline-flex; align-items: center; gap: 8px; color: #15803d; animation: added-in 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes added-in { from { opacity: 0; transform: translateY(6px) scale(0.96); } }
.purchase-status-enter-active, .purchase-status-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.purchase-status-enter-from, .purchase-status-leave-to { opacity: 0; transform: translateY(-3px); }
@media (prefers-reduced-motion: reduce) {
  .added-label { animation: none; }
  .purchase-status-enter-active, .purchase-status-leave-active { transition: none; }
}

.compare-toggle { display: inline-flex; min-height: 36px; align-items: center; gap: 6px; padding: 0 12px; border: 1px solid rgb(10 34 81 / 16%); border-radius: var(--radius-btn, 10px); background: #fff; color: var(--color-navy-800); font-size: 13px; font-weight: 800; cursor: pointer; transition: border-color .2s ease, background .2s ease; }
.compare-toggle:hover { border-color: var(--color-gold-500); }
.compare-note { flex-basis: 100%; margin: 2px 0 0; color: #475569; font-size: 13.5px; line-height: 1.5; }
.compare-toggle.is-on { border-color: transparent; background: var(--pts-gold-gradient, var(--color-gold-500)); color: var(--color-navy-950); }
</style>

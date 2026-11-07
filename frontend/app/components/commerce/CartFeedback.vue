<script setup lang="ts">
import type { CartFeedbackEvent } from '~/composables/useCartFeedback'

const { event } = useCartFeedback()
const { showHeader } = useSiteHeader()
const links = useMigrationLinks()
const card = ref<CartFeedbackEvent | null>(null)
const position = ref<{ top: number; right: number; arrow: number; above?: boolean }>({ top: 96, right: 24, arrow: 22 })
const paused = ref(false)
let hideTimer: ReturnType<typeof setTimeout> | undefined
let remaining = 0
let startedAt = 0
const SHOW_MS = 4500

// The cart lives in the header on desktop and in the dock on phones, so aim at whichever is on screen.
function visibleCartIcon() {
  return [...document.querySelectorAll<HTMLElement>('[data-cart-target]')].find((el) => el.getClientRects().length > 0) ?? null
}

function cartTarget(): { rect: DOMRect; lift: number } | null {
  const el = visibleCartIcon()
  if (!el) return null
  // A hidden sticky header is translated up; aim where the icon will be once it slides back.
  const shell = el.closest<HTMLElement>('.pts-site-header')
  const ty = shell ? new DOMMatrixReadOnly(getComputedStyle(shell).transform).m42 : 0
  return { rect: el.getBoundingClientRect(), lift: ty < 0 ? -ty : 0 }
}

function bumpCart() {
  const el = visibleCartIcon()
  if (!el) return
  el.classList.remove('cart-bump')
  void el.offsetWidth
  el.classList.add('cart-bump')
  el.addEventListener('animationend', () => el.classList.remove('cart-bump'), { once: true })
}

async function fly(next: CartFeedbackEvent, reduced: boolean) {
  const target = cartTarget()
  if (!next.source || !next.image || !target || reduced) { bumpCart(); return }
  const size = Math.min(140, Math.max(72, Math.min(next.source.width, next.source.height) * 0.42))
  const startX = next.source.x + next.source.width / 2 - size / 2
  const startY = next.source.y + next.source.height / 2 - size / 2
  const endX = target.rect.x + target.rect.width / 2 - size / 2
  const endY = target.rect.y + target.lift + target.rect.height / 2 - size / 2
  const peakY = Math.min(startY, endY) - 140
  const ghost = document.createElement('div')
  ghost.className = 'cart-flight'
  ghost.style.cssText = `left:${startX}px;top:${startY}px;width:${size}px;height:${size}px;`
  const img = document.createElement('img')
  img.src = next.image
  img.alt = ''
  ghost.appendChild(img)
  document.body.appendChild(ghost)
  const dx = endX - startX
  const dy = endY - startY
  const animation = ghost.animate([
    { transform: 'translate(0, 0) scale(0.6) rotate(0deg)', opacity: 0 },
    { transform: 'translate(0, -12px) scale(1) rotate(-4deg)', opacity: 1, offset: 0.14 },
    { transform: `translate(${dx * 0.55}px, ${peakY - startY}px) scale(0.62) rotate(6deg)`, opacity: 1, offset: 0.55 },
    { transform: `translate(${dx}px, ${dy}px) scale(0.16) rotate(0deg)`, opacity: 0.35 },
  ], { duration: 820, easing: 'cubic-bezier(0.45, 0, 0.25, 1)', fill: 'forwards' })
  await animation.finished.catch(() => undefined)
  ghost.remove()
  bumpCart()
}

function placeCard() {
  const target = cartTarget()
  if (!target) return
  // The header may still be sliding in; place against its resting position.
  const right = window.innerWidth <= 640 ? 12 : Math.max(12, window.innerWidth - target.rect.right - 8)
  const cartCenter = target.rect.x + target.rect.width / 2
  // On phones the cart is in the bottom dock, so the card opens above it instead of below the screen edge.
  const above = target.rect.top > window.innerHeight / 2
  position.value = {
    top: above ? window.innerHeight - target.rect.top + 12 : Math.max(12, target.rect.bottom + target.lift + 12),
    right,
    arrow: Math.max(16, window.innerWidth - right - cartCenter - 7),
    above,
  }
}

function schedule(ms: number) {
  clearTimeout(hideTimer)
  remaining = ms
  startedAt = Date.now()
  hideTimer = setTimeout(close, ms)
}
function pause() { if (!card.value || paused.value) return; paused.value = true; clearTimeout(hideTimer); remaining -= Date.now() - startedAt }
function resume() { if (!card.value || !paused.value) return; paused.value = false; schedule(Math.max(800, remaining)) }
function close() { clearTimeout(hideTimer); card.value = null; paused.value = false }

watch(event, async (next) => {
  if (!next || !import.meta.client) return
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  card.value = null
  // Bring the sticky header back so the product lands on a visible cart.
  showHeader()
  await fly(next, reduced)
  placeCard()
  card.value = next
  schedule(SHOW_MS)
})

function onKey(e: KeyboardEvent) { if (e.key === 'Escape' && card.value) close() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => { window.removeEventListener('keydown', onKey); clearTimeout(hideTimer) })
</script>

<template>
  <Teleport to="body">
    <div class="cart-feedback-live" aria-live="polite">
      <Transition name="cart-card">
        <aside
          v-if="card"
          :key="card.id"
          class="m-sheet-glass cart-card"
          :class="{ 'is-paused': paused, 'is-above': position.above }"
          :style="{ [position.above ? 'bottom' : 'top']: `${position.top}px`, right: `${position.right}px`, '--show': `${SHOW_MS}ms`, '--arrow': `${position.arrow}px` }"
          aria-label="Đã thêm vào giỏ hàng"
          @mouseenter="pause"
          @mouseleave="resume"
          @focusin="pause"
          @focusout="resume"
        >
          <span class="cart-card__arrow" aria-hidden="true" />
          <div class="cart-card__head">
            <span class="cart-card__tick" aria-hidden="true"><UIcon name="i-lucide-check" class="size-4" /></span>
            <strong>Đã thêm vào giỏ hàng</strong>
            <button type="button" class="cart-card__close" aria-label="Đóng thông báo" @click="close"><UIcon name="i-lucide-x" class="size-4" /></button>
          </div>
          <div class="cart-card__item">
            <span class="cart-card__thumb"><img v-if="card.image" :src="card.image" alt="" width="64" height="64"></span>
            <span class="min-w-0">
              <span class="cart-card__name">{{ card.name }}</span>
              <span class="cart-card__qty">Số lượng: {{ card.quantity }}</span>
            </span>
          </div>
          <div class="cart-card__actions">
            <NuxtLink :to="links.generic('/gio-hang/')" class="cart-card__primary" @click="close">Xem giỏ hàng <span aria-hidden="true">→</span></NuxtLink>
            <button type="button" class="cart-card__secondary" @click="close">Tiếp tục mua</button>
          </div>
          <span class="cart-card__timer" aria-hidden="true" />
        </aside>
      </Transition>
    </div>
  </Teleport>
</template>

<style>
.cart-flight { position: fixed; z-index: 2000; display: grid; place-items: center; overflow: hidden; border-radius: var(--radius-lg); background: #fff; box-shadow: 0 18px 40px -12px rgb(6 21 55 / 45%), 0 0 0 1px rgb(10 34 81 / 6%); pointer-events: none; will-change: transform, opacity; }
.cart-flight img { width: 86%; height: 86%; object-fit: contain; }
.cart-bump { animation: cart-bump 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
.cart-bump > span:first-child { animation: cart-badge-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes cart-bump { 0% { transform: scale(1); } 30% { transform: scale(1.22) rotate(-8deg); } 60% { transform: scale(0.94) rotate(4deg); } 100% { transform: scale(1); } }
@keyframes cart-badge-pop { 0%, 30% { transform: scale(1); } 55% { transform: scale(1.5); } 100% { transform: scale(1); } }
@media (prefers-reduced-motion: reduce) { .cart-bump, .cart-bump > span:first-child { animation: none; } }
</style>

<style scoped>
.cart-card { position: fixed; z-index: 1500; width: min(360px, calc(100vw - 24px)); padding: 16px 16px 18px; border: 1px solid rgb(10 34 81 / 8%); border-radius: var(--radius-lg); background: #fff; box-shadow: 0 30px 60px -24px rgb(6 21 55 / 45%), 0 8px 20px -12px rgb(6 21 55 / 20%); color: var(--color-navy-900); }
.cart-card__arrow { position: absolute; top: -7px; right: var(--arrow, 22px); width: 14px; height: 14px; border-top: 1px solid rgb(10 34 81 / 8%); border-left: 1px solid rgb(10 34 81 / 8%); background: #fff; transform: rotate(45deg); }
.cart-card__head { display: flex; align-items: center; gap: 10px; }
.cart-card__head strong { flex: 1; font-size: 15px; font-weight: 800; }
.cart-card__tick { display: grid; width: 26px; height: 26px; place-items: center; border-radius: 50%; background: #16a34a; color: #fff; animation: tick-pop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both; }
.cart-card__close { display: grid; width: 32px; height: 32px; place-items: center; border: 0; border-radius: 50%; background: transparent; color: #64748b; cursor: pointer; }
.cart-card__close:hover { background: #f1f5f9; color: var(--color-navy-900); }
.cart-card__item { display: flex; align-items: center; gap: 12px; margin-top: 14px; padding: 10px; border-radius: var(--radius-md); background: #f6f8fb; }
.cart-card__thumb { display: grid; width: 64px; height: 64px; flex: none; place-items: center; overflow: hidden; border-radius: var(--radius-md); background: #fff; }
.cart-card__thumb img { width: 90%; height: 90%; object-fit: contain; }
.cart-card__name { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; color: var(--color-navy-950); font-size: 14px; font-weight: 700; line-height: 1.4; }
.cart-card__qty { display: block; margin-top: 3px; color: #64748b; font-size: 13px; }
.cart-card__actions { display: grid; grid-template-columns: 1fr auto; gap: 8px; margin-top: 14px; }
.cart-card__primary { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: 8px; border-radius: var(--radius-md); background: var(--pts-gold-gradient); box-shadow: inset 0 1px 0 rgb(255 255 255 / 30%), 0 2px 6px rgb(6 21 55 / 16%); color: var(--color-navy-950); font-size: 14.5px; font-weight: 800; text-decoration: none; transition: background-color 0.2s ease; }
.cart-card__primary:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.cart-card__secondary { min-height: 44px; padding: 0 14px; border: 1px solid rgb(10 34 81 / 14%); border-radius: var(--radius-md); background: #fff; color: var(--color-navy-900); font-size: 14px; font-weight: 700; cursor: pointer; }
.cart-card__secondary:hover { border-color: var(--color-gold-500); }
.cart-card a:focus-visible, .cart-card button:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.cart-card__timer { position: absolute; right: 16px; bottom: 7px; left: 16px; height: 3px; border-radius: var(--radius-chip); background: linear-gradient(90deg, var(--color-gold-500), var(--color-gold-300)); transform-origin: left; animation: cart-timer var(--show) linear forwards; }
.cart-card.is-paused .cart-card__timer { animation-play-state: paused; }
@keyframes cart-timer { from { transform: scaleX(1); } to { transform: scaleX(0); } }
@keyframes tick-pop { from { transform: scale(0); } }
.cart-card-enter-active { transition: opacity 0.25s ease, transform 0.35s cubic-bezier(0.22, 1, 0.36, 1); }
.cart-card-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.cart-card-enter-from { opacity: 0; transform: translateY(-10px) scale(0.97); transform-origin: top right; }
.cart-card-leave-to { opacity: 0; transform: translateY(-6px); }
/* Opening above the dock: the pointer sits on the bottom edge and the card rises into place. */
.cart-card.is-above .cart-card__arrow { top: auto; bottom: -7px; transform: rotate(225deg); }
.cart-card.is-above.cart-card-enter-from { transform: translateY(10px) scale(0.97); transform-origin: bottom right; }
@media (max-width: 640px) {
  .cart-card { right: 12px !important; left: 12px; width: auto; }
}
@media (prefers-reduced-motion: reduce) {
  .cart-card-enter-active, .cart-card-leave-active { transition: opacity 0.15s ease; }
  .cart-card-enter-from, .cart-card-leave-to { transform: none; }
  .cart-card__tick { animation: none; }
}
</style>

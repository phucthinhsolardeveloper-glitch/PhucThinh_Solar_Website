<script setup lang="ts">
import type { PublicHomePopup } from '#shared/contracts/public-home'

const props = defineProps<{ popup: PublicHomePopup }>()
const links = useMigrationLinks()

// Same storage key as the PHP storefront so a visitor never sees one campaign twice per session.
const STORAGE_KEY = 'pts-popup-campaign'
const open = ref(false)
const closeButton = ref<HTMLButtonElement | null>(null)
let previousFocus: HTMLElement | null = null
let timer: ReturnType<typeof setTimeout> | undefined

const label = computed(() => props.popup.title || 'Chương trình khuyến mãi')
const href = computed(() => props.popup.href ? links.generic(props.popup.href) : '')
const thumbFailed = ref(false)
const imageSrc = computed(() => thumbFailed.value ? props.popup.image : /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i.test(props.popup.image) ? phpThumb(props.popup.image, 800, 530) : props.popup.image)
const external = computed(() => /^https?:\/\//i.test(props.popup.href))

function seen() {
  try { return window.sessionStorage.getItem(STORAGE_KEY) === props.popup.key } catch { return false }
}
function remember() {
  try { window.sessionStorage.setItem(STORAGE_KEY, props.popup.key) } catch {}
}
function show() {
  if (seen()) return
  remember()
  previousFocus = document.activeElement as HTMLElement | null
  open.value = true
  nextTick(() => closeButton.value?.focus())
}
function close() {
  open.value = false
  previousFocus?.focus?.()
}
function onKeydown(event: KeyboardEvent) {
  if (!open.value) return
  if (event.key === 'Escape') close()
  if (event.key === 'Tab') {
    const focusable = [...document.querySelectorAll<HTMLElement>('.promo-popup a[href], .promo-popup button')]
    if (!focusable.length) return
    const first = focusable[0]!, last = focusable[focusable.length - 1]!
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  // Let the first screen settle before interrupting.
  timer = setTimeout(show, 1600)
})
onBeforeUnmount(() => {
  clearTimeout(timer)
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="promo-popup">
      <div v-if="open" class="promo-popup" role="dialog" aria-modal="true" :aria-label="label" @click.self="close">
        <div class="promo-popup__card">
          <button ref="closeButton" type="button" class="promo-popup__close" aria-label="Đóng quảng cáo" @click="close">
            <UIcon name="i-lucide-x" class="size-5" aria-hidden="true" />
          </button>
          <component
            :is="href ? 'a' : 'div'"
            class="promo-popup__media"
            :href="href || undefined"
            :target="href && external ? '_blank' : undefined"
            :rel="href && external ? 'noopener noreferrer' : undefined"
            @click="href ? remember() : undefined"
          >
            <img :src="imageSrc" :alt="label" width="800" height="530" decoding="async" @error="thumbFailed = true">
          </component>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.promo-popup { position: fixed; z-index: 90; inset: 0; display: grid; place-items: center; padding: 20px; background: rgb(6 21 55 / 62%); backdrop-filter: blur(6px); }
.promo-popup__card { position: relative; width: min(800px, 100%); }
.promo-popup__media { display: block; overflow: hidden; border-radius: var(--radius-lg); background: var(--color-navy-950); box-shadow: var(--pts-shadow-float); }
.promo-popup__media img { display: block; width: 100%; height: auto; aspect-ratio: 800 / 530; object-fit: cover; }
a.promo-popup__media:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 4px; }
.promo-popup__close { position: absolute; z-index: 1; top: 12px; right: 12px; display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid rgb(255 255 255 / 30%); border-radius: 50%; background: rgb(6 21 55 / 55%); color: #fff; backdrop-filter: blur(12px) saturate(1.4); cursor: pointer; transition: background-color var(--pts-dur-fast) var(--pts-ease); }
.promo-popup__close:hover { background: rgb(6 21 55 / 80%); }
.promo-popup__close:focus-visible { outline: 3px solid var(--color-gold-300); outline-offset: 2px; }
.promo-popup-enter-active, .promo-popup-leave-active { transition: opacity var(--pts-dur) var(--pts-ease); }
.promo-popup-enter-active .promo-popup__card, .promo-popup-leave-active .promo-popup__card { transition: transform var(--pts-dur) var(--pts-ease); }
.promo-popup-enter-from, .promo-popup-leave-to { opacity: 0; }
.promo-popup-enter-from .promo-popup__card, .promo-popup-leave-to .promo-popup__card { transform: translateY(14px) scale(0.97); }
@media (max-width: 560px) {
  .promo-popup { padding: 14px; }
  .promo-popup__close { top: 8px; right: 8px; }
}
@media (prefers-reduced-motion: reduce) {
  .promo-popup-enter-active .promo-popup__card, .promo-popup-leave-active .promo-popup__card { transition: none; }
  .promo-popup-enter-from .promo-popup__card, .promo-popup-leave-to .promo-popup__card { transform: none; }
}
</style>

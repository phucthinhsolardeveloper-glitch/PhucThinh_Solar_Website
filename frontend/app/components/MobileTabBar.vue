<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{ site: PublicSitePayload }>()
// Phone navigation: a navy liquid-glass island. A single lens of light slides to the current
// destination, so changing page feels like one continuous gesture rather than a redraw.
const route = useRoute()
const links = useMigrationLinks()
const { cart } = useMigrationCart()
const { $warmRoute } = useNuxtApp()
publishPublicContactLinks(props.site)
const { hotline, phoneHref, zaloHref } = usePublicContactLinks()
const actionBarActive = useMobileActionBar()
const technicalHotline = computed(() => props.site.brand.technicalHotline?.trim() ?? '')

const sheetOpen = ref(false)
// Route rules decide this on server and client alike. The action-bar state is only known once the
// product page has rendered, so it may only be consulted after hydration.
const mounted = ref(false)
onMounted(() => { mounted.value = true })
const hidden = computed(() => /^\/thanh-toan\/?$/.test(route.path) || /^\/san-pham\/[^/]+\/?$/.test(route.path) || (mounted.value && actionBarActive.value))
const items = computed(() => [
  { key: 'home', label: 'Trang chủ', icon: 'i-lucide-house', path: '/', active: route.path === '/' },
  { key: 'products', label: 'Sản phẩm', icon: 'i-lucide-package', path: '/san-pham/', active: /^\/(san-pham|danh-muc-san-pham)/.test(route.path) },
  { key: 'cart', label: 'Giỏ hàng', icon: 'i-lucide-shopping-cart', path: '/gio-hang/', active: route.path.startsWith('/gio-hang') },
])
const SUPPORT_INDEX = 3
const lensIndex = computed(() => {
  if (sheetOpen.value || /^\/(lien-he|ho-tro)/.test(route.path)) return SUPPORT_INDEX
  return items.value.findIndex((item) => item.active)
})

// The lens stretches as it travels, like a drop of liquid, then settles.
const lens = ref<HTMLElement | null>(null)
watch(lensIndex, () => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  lens.value?.animate?.([{ scale: '1 1' }, { scale: '1.22 .86', offset: .4 }, { scale: '1 1' }], { duration: 420, easing: 'ease-out' })
})

watch(() => route.fullPath, () => { sheetOpen.value = false })
// Only one overlay at a time, shared with the header's search and menu.
const activeOverlay = useState<string | null>('active-overlay', () => null)
watch(sheetOpen, (open) => { activeOverlay.value = open ? 'support' : null })
watch(activeOverlay, (overlay) => { if (overlay && overlay !== 'support') sheetOpen.value = false })
function onKey(event: KeyboardEvent) {
  if (event.key === 'Escape') sheetOpen.value = false
}
// The keyboard leaves little room on a phone: floating controls step aside while typing.
function onFocusChange(event: FocusEvent) {
  const typing = event.type === 'focusin' && (event.target as Element | null)?.matches?.('input:not([type=radio]):not([type=checkbox]), textarea, select')
  document.documentElement.classList.toggle('m-typing', Boolean(typing))
}
onMounted(() => {
  document.addEventListener('keydown', onKey)
  document.addEventListener('focusin', onFocusChange)
  document.addEventListener('focusout', onFocusChange)
})
onBeforeUnmount(() => {
  document.removeEventListener('focusin', onFocusChange)
  document.removeEventListener('focusout', onFocusChange)
})
onBeforeUnmount(() => document.removeEventListener('keydown', onKey))
</script>

<template>
  <div v-if="!hidden" class="dock-root print:hidden">
    <Transition name="sheet">
      <div v-if="sheetOpen" class="sheet-scrim" @click="sheetOpen = false" />
    </Transition>
    <Transition name="sheet">
      <div v-if="sheetOpen" class="sheet m-glass" role="dialog" aria-label="Hỗ trợ nhanh">
        <a v-if="phoneHref" :href="phoneHref" class="sheet-row">
          <UIcon name="i-lucide-phone" class="size-5" aria-hidden="true" />
          <span>Tư vấn bán hàng</span><strong>{{ hotline }}</strong>
        </a>
        <a v-if="technicalHotline" :href="publicPhoneHref(technicalHotline)" class="sheet-row">
          <UIcon name="i-lucide-wrench" class="size-5" aria-hidden="true" />
          <span>Hỗ trợ kỹ thuật</span><strong>{{ technicalHotline }}</strong>
        </a>
        <a v-if="zaloHref" :href="zaloHref" target="_blank" rel="noopener noreferrer" class="sheet-row">
          <UIcon name="i-lucide-message-circle" class="size-5" aria-hidden="true" />
          <span>Chat Zalo</span>
        </a>
        <NuxtLink :to="links.generic('/lien-he/')" class="sheet-row">
          <UIcon name="i-lucide-map-pin" class="size-5" aria-hidden="true" />
          <span>Gửi yêu cầu &amp; xem chi nhánh</span>
        </NuxtLink>
      </div>
    </Transition>

    <nav class="dock m-glass" aria-label="Điều hướng nhanh">
      <span v-show="lensIndex >= 0" ref="lens" class="dock-lens" :style="{ '--i': lensIndex }" aria-hidden="true" />
      <NuxtLink
        v-for="item in items"
        :key="item.key"
        :to="links.generic(item.path)"
        :aria-current="item.active ? 'page' : undefined"
        class="dock-item"
        :class="{ 'is-active': item.active && !sheetOpen }"
        :data-cart-target="item.key === 'cart' ? '' : undefined"
        @pointerdown="$warmRoute(item.path)"
      >
        <span class="dock-icon">
          <UIcon :name="item.icon" class="block size-5" aria-hidden="true" />
          <span v-if="mounted && item.key === 'cart' && cart?.count" class="dock-badge">{{ cart.count }}</span>
        </span>
        {{ item.label }}
      </NuxtLink>
      <button type="button" class="dock-item" :class="{ 'is-active': lensIndex === SUPPORT_INDEX }" :aria-expanded="sheetOpen" @click="sheetOpen = !sheetOpen">
        <UIcon name="i-lucide-headset" class="block size-5" aria-hidden="true" />
        Hỗ trợ
      </button>
      <a v-if="phoneHref" :href="phoneHref" :aria-label="`Gọi hotline ${hotline}`" class="dock-item dock-call">
        <UIcon name="i-lucide-phone" class="block size-5" aria-hidden="true" />
        Gọi
      </a>
    </nav>
  </div>
</template>

<style scoped>
.dock {
  position: fixed;
  left: 12px;
  right: 72px;
  bottom: calc(12px + env(safe-area-inset-bottom));
  z-index: 40;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  height: 62px;
  padding: 5px;
  border-radius: 30px;
}
.dock-root { display: contents; }
@media (min-width: 821px) { .dock-root { display: none; } }
.dock-lens {
  position: absolute;
  top: 5px;
  bottom: 5px;
  left: 5px;
  width: calc((100% - 10px) / 5);
  border-radius: 25px;
  background: var(--m-glass-lens);
  box-shadow: var(--m-glass-lens-shadow);
  transform: translateX(calc(var(--i, 0) * 100%));
  transition: transform .42s cubic-bezier(.3, 1.35, .5, 1);
}
.dock-item {
  position: relative;
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  border-radius: 25px;
  color: #d5deeb;
  font-size: 10px;
  font-weight: 600;
  letter-spacing: -.01em;
  line-height: 1.1;
  white-space: nowrap;
  text-decoration: none;
  transition: color .2s, transform .15s;
  -webkit-tap-highlight-color: transparent;
}
.dock-item:active { transform: scale(.92); }
.dock-item.is-active { color: var(--color-gold-300); font-weight: 700; }
.dock-call { color: #fff; }
.dock-call :deep(.iconify) { filter: drop-shadow(0 0 5px rgb(244 212 136 / 75%)); }
.dock-icon { position: relative; }
.dock-badge { position: absolute; top: -6px; right: -9px; min-width: 15px; padding-inline: 3px; border-radius: 999px; background: #dc2626; color: #fff; font-size: 9.5px; font-weight: 700; line-height: 15px; text-align: center; }
.dock-item:focus-visible { outline: 2px solid var(--color-gold-300); outline-offset: -2px; }

.sheet-scrim { position: fixed; inset: 0; z-index: 38; background: rgb(6 21 55 / 28%); }
.sheet { position: fixed; left: 12px; right: 12px; bottom: calc(84px + env(safe-area-inset-bottom)); z-index: 39; display: grid; gap: 2px; padding: 8px; border-radius: 22px; color: #fff; }
.sheet-row { display: flex; min-height: 50px; align-items: center; gap: 12px; padding-inline: 12px; border-radius: 14px; color: #fff; font-size: 15px; font-weight: 600; text-decoration: none; }
.sheet-row:active { background: rgb(255 255 255 / 12%); }
.sheet-row strong { margin-left: auto; color: var(--color-gold-300); font-variant-numeric: tabular-nums; }
.sheet-enter-active, .sheet-leave-active { transition: opacity .18s ease, transform .24s cubic-bezier(.22, 1, .36, 1); }
.sheet-enter-from, .sheet-leave-to { opacity: 0; transform: translateY(10px); }
@media (prefers-reduced-motion: reduce) {
  .dock-lens, .dock-item, .sheet-enter-active, .sheet-leave-active { transition: none; }
}
</style>

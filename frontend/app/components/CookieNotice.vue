<script setup lang="ts">
// One short notice about measurement cookies. Measurement stays on until the visitor refuses. The choice is kept in
// localStorage AND a first-party cookie (some browsers wipe one but not the other) and passed to Google (consent mode)
// so GTM tags for GA4 and ads can honour it.
const STORAGE_KEY = 'pts-consent'
const COOKIE_KEY = 'pts_consent'
const visible = ref(false)
const links = usePublicLinks()

function readChoice(): string {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) return stored
  } catch { /* Storage blocked: try the cookie. */ }
  const match = document.cookie.match(new RegExp(`(?:^|; )${COOKIE_KEY}=([^;]*)`))
  return match?.[1] ?? ''
}

onMounted(() => {
  if (!readChoice()) window.setTimeout(() => { visible.value = true }, 1500)
})

function choose(value: 'granted' | 'denied') {
  visible.value = false
  try { localStorage.setItem(STORAGE_KEY, value) } catch { /* Cookie below still remembers it. */ }
  try { document.cookie = `${COOKIE_KEY}=${value}; path=/; max-age=15552000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}` } catch { /* Shown again next visit. */ }
  const gtag = (window as Window & { gtag?: (...args: unknown[]) => void }).gtag
  if (typeof gtag === 'function') {
    gtag('consent', 'update', { analytics_storage: value, ad_storage: value, ad_user_data: value, ad_personalization: value })
  }
}
</script>

<template>
  <aside
    v-if="visible"
    class="cookie-notice fixed z-[850] rounded-btn border border-white/15 bg-navy-950/95 p-4 text-white shadow-2xl backdrop-blur print:hidden"
    role="region"
    aria-label="Thông báo cookie"
  >
    <p class="m-0 text-sm leading-relaxed text-white/90">
      Website dùng cookie để đo lượng truy cập và hiệu quả quảng cáo. Bạn có thể từ chối, việc mua hàng và liên hệ vẫn hoạt động bình thường.
      <NuxtLink :to="links.generic('/ho-tro/chinh-sach-bao-mat-va-bao-ve-du-lieu-ca-nhan/')" class="font-semibold text-gold-300 underline underline-offset-2">Chính sách bảo mật</NuxtLink>
    </p>
    <div class="mt-3 flex gap-2">
      <button type="button" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-btn bg-gold-500 px-4 text-sm font-extrabold text-navy-950" @click="choose('granted')">Đồng ý</button>
      <button type="button" class="inline-flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-btn border border-white/30 bg-transparent px-4 text-sm font-bold text-white" @click="choose('denied')">Từ chối</button>
    </div>
  </aside>
</template>

<style scoped>
/* Desktop: a card in the lower-left corner (floating contact buttons are on the right). Phone: above the bottom bars. */
.cookie-notice { left: 16px; bottom: calc(16px + env(safe-area-inset-bottom)); width: min(380px, calc(100vw - 32px)); }
@media (max-width: 767px) {
  .cookie-notice { left: 12px; right: 12px; width: auto; bottom: calc(92px + env(safe-area-inset-bottom)); }
}
</style>

<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{
  site: PublicSitePayload
}>()

const showBackToTop = ref(false)
const hotline = computed(() => props.site.brand.hotline.trim())
const phoneHref = computed(() => publicPhoneHref(hotline.value))
const zaloHref = computed(() => {
  if (props.site.brand.zaloHref) return props.site.brand.zaloHref
  const match = props.site.footer?.socialLinks?.find((item) => /zalo\.me\//i.test(item.href) || item.label.trim().toLowerCase() === 'zalo')
  if (match?.href) return match.href
  const digits = hotline.value.replace(/\D/g, '')
  return digits ? `https://zalo.me/${digits}` : ''
})

const hasContactChannels = computed(() => Boolean(zaloHref.value || phoneHref.value))

function updateBackToTopVisibility() {
  showBackToTop.value = window.scrollY > 300
}

function scrollToTop() {
  const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
  window.scrollTo({ top: 0, behavior })
}

onMounted(() => {
  updateBackToTopVisibility()
  window.addEventListener('scroll', updateBackToTopVisibility, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateBackToTopVisibility)
})
</script>

<template>
  <aside
    v-if="showBackToTop || hasContactChannels"
    class="fixed bottom-[calc(1.25rem+env(safe-area-inset-bottom))] right-4 z-[900] flex flex-col items-center gap-3 print:hidden tb:bottom-6 tb:right-6"
    aria-label="Liên hệ nhanh"
  >
    <!-- Nút 3: Lên đầu trang (cuộn > 300px) -->
    <div v-if="showBackToTop" class="group relative flex items-center">
      <span
        class="pointer-events-none absolute right-[calc(100%+10px)] hidden whitespace-nowrap rounded-md bg-navy-950/85 px-2.5 py-1 text-xs font-medium text-white shadow-md backdrop-blur-md transition-all duration-200 group-hover:block"
        role="tooltip"
      >
        Lên đầu trang
      </span>
      <button
        type="button"
        title="Lên đầu trang"
        aria-label="Lên đầu trang"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200/80 bg-white/95 text-navy-950 shadow-[0_4px_14px_rgba(10,34,81,0.12)] backdrop-blur-sm transition-all duration-200 hover:scale-105 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gold-500 active:scale-95"
        @click="scrollToTop"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="m18 15-6-6-6 6" />
        </svg>
      </button>
    </div>

    <!-- Nút 1: Zalo (tròn, #0068ff, mở thẳng zalo 1 chạm) -->
    <div v-if="zaloHref" class="group relative flex items-center">
      <span
        class="pointer-events-none absolute right-[calc(100%+10px)] hidden whitespace-nowrap rounded-md bg-navy-950/85 px-2.5 py-1 text-xs font-medium text-white shadow-md backdrop-blur-md transition-all duration-200 group-hover:block"
        role="tooltip"
      >
        Chat Zalo
      </span>
      <a
        :href="zaloHref"
        target="_blank"
        rel="noopener noreferrer"
        title="Chat Zalo với chúng tôi"
        aria-label="Chat Zalo với chúng tôi"
        class="relative flex h-12 w-12 items-center justify-center rounded-full bg-[#0068ff] text-white shadow-[0_4px_16px_rgba(0,104,255,0.4)] transition-all duration-200 hover:scale-105 hover:bg-[#0057d6] hover:shadow-[0_6px_20px_rgba(0,104,255,0.5)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0068ff] focus-visible:ring-offset-2 active:scale-95"
      >
        <span class="select-none text-[13px] font-black leading-none tracking-tight text-white">Zalo</span>
      </a>
    </div>

    <!-- Nút 2: Hotline (tròn, #c58a2d, hiệu ứng ping wave nhẹ, gọi ngay 1 chạm) -->
    <div v-if="phoneHref" class="group relative flex items-center">
      <span
        class="pointer-events-none absolute right-[calc(100%+10px)] hidden whitespace-nowrap rounded-md bg-navy-950/85 px-2.5 py-1 text-xs font-medium text-white shadow-md backdrop-blur-md transition-all duration-200 group-hover:block"
        role="tooltip"
      >
        Gọi ngay: {{ hotline }}
      </span>
      <a
        :href="phoneHref"
        :title="`Gọi hotline ${hotline}`"
        :aria-label="`Gọi hotline ${hotline}`"
        class="relative flex h-12 w-12 items-center justify-center rounded-full bg-[#c58a2d] text-navy-950 shadow-[0_4px_16px_rgba(197,138,45,0.4)] transition-all duration-200 hover:scale-105 hover:bg-[#b07824] hover:shadow-[0_6px_20px_rgba(197,138,45,0.55)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#c58a2d] focus-visible:ring-offset-2 active:scale-95"
      >
        <!-- Ping ring effect -->
        <span class="pointer-events-none absolute -inset-1 animate-ping rounded-full bg-[#c58a2d]/40 opacity-60" aria-hidden="true" />
        <span class="pointer-events-none absolute -inset-0.5 animate-pulse rounded-full bg-[#c58a2d]/30" aria-hidden="true" />

        <svg class="relative z-10 h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" />
        </svg>
      </a>
    </div>
  </aside>
</template>

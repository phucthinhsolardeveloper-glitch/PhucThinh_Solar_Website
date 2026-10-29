<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{
  site: PublicSitePayload
}>()

const showBackToTop = ref(false)
const footerVisible = ref(false)
const channelsOpen = ref(false)
const hotline = computed(() => props.site.brand.hotline.trim())
const phoneHref = computed(() => publicPhoneHref(hotline.value))
const smsHref = computed(() => phoneHref.value ? phoneHref.value.replace(/^tel:/i, 'sms:') : '')
const socialLinks = computed(() => props.site.footer?.socialLinks ?? [])
const facebookHref = computed(() => {
  const match = socialLinks.value.find((item) => /facebook\.com|fb\.me/i.test(item.href) || /facebook|fanpage/i.test(item.label))
  return match?.href ?? ''
})
const zaloHref = computed(() => {
  if (props.site.brand.zaloHref) return props.site.brand.zaloHref
  const match = socialLinks.value.find((item) => /zalo\.me\//i.test(item.href) || item.label.trim().toLowerCase() === 'zalo')
  return match?.href ?? ''
})
const hasContactChannels = computed(() => Boolean(facebookHref.value || zaloHref.value || phoneHref.value))
let footerObserver: IntersectionObserver | undefined

function updateBackToTopVisibility() {
  showBackToTop.value = window.scrollY > 320
}

function scrollToTop() {
  const behavior: ScrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
  window.scrollTo({ top: 0, behavior })
}

function closeChannelsOnEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') channelsOpen.value = false
}

onMounted(() => {
  updateBackToTopVisibility()
  window.addEventListener('scroll', updateBackToTopVisibility, { passive: true })
  window.addEventListener('keydown', closeChannelsOnEscape)

  const footer = document.querySelector('footer')
  if (footer && 'IntersectionObserver' in window) {
    footerObserver = new IntersectionObserver(([entry]) => {
      footerVisible.value = entry?.isIntersecting ?? false
      if (footerVisible.value) channelsOpen.value = false
    }, { threshold: 0.01 })
    footerObserver.observe(footer)
  }
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateBackToTopVisibility)
  window.removeEventListener('keydown', closeChannelsOnEscape)
  footerObserver?.disconnect()
})
</script>

<template>
  <aside
    v-if="!footerVisible && (showBackToTop || hasContactChannels)"
    class="fixed bottom-[calc(1.25rem+env(safe-area-inset-bottom))] right-4 z-[900] flex flex-col items-center gap-3 print:hidden tb:bottom-6 tb:right-6"
    aria-label="Liên hệ nhanh"
  >
    <button
      v-if="showBackToTop"
      type="button"
      title="Lên đầu trang"
      aria-label="Lên đầu trang"
      class="flex h-11 w-11 items-center justify-center rounded-full border border-white/25 bg-white text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)] transition-transform duration-200 hover:scale-105 hover:bg-slate-100"
      @click="scrollToTop"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="m5 15 7-7 7 7" />
      </svg>
    </button>

    <div v-if="hasContactChannels" id="contact-channel-list" v-show="channelsOpen" class="flex flex-col items-center gap-2" aria-label="Các kênh liên hệ nhanh">
      <a
        v-if="facebookHref"
        :href="facebookHref"
        target="_blank"
        rel="noopener noreferrer"
        title="Facebook"
        aria-label="Liên hệ qua Facebook"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/25 bg-[#1877f2] text-lg font-black text-white no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)]"
      >f</a>
      <a
        v-if="zaloHref"
        :href="zaloHref"
        target="_blank"
        rel="noopener noreferrer"
        title="Zalo"
        aria-label="Liên hệ qua Zalo"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-white/25 bg-[#0068ff] text-[10px] font-extrabold text-white no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)]"
      >Zalo</a>
      <a
        v-if="phoneHref"
        :href="phoneHref"
        :title="`Gọi hotline ${hotline}`"
        :aria-label="`Gọi hotline ${hotline}`"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-navy-950/15 bg-gold-500 text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)]"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
          <path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" />
        </svg>
      </a>
      <a
        v-if="smsHref"
        :href="smsHref"
        title="Gửi tin nhắn SMS"
        aria-label="Gửi tin nhắn SMS"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-navy-950/15 bg-white text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)]"
      >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z" />
        </svg>
      </a>
    </div>

    <button
      v-if="hasContactChannels"
      type="button"
      :aria-expanded="channelsOpen"
      aria-controls="contact-channel-list"
      :aria-label="channelsOpen ? 'Đóng các kênh liên hệ' : 'Mở các kênh liên hệ'"
      :title="channelsOpen ? 'Đóng các kênh liên hệ' : 'Mở các kênh liên hệ'"
      class="flex h-12 w-12 items-center justify-center rounded-full border border-navy-950/15 bg-gold-500 text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.24)] transition-transform duration-200 hover:scale-105 hover:bg-gold-400"
      @click="channelsOpen = !channelsOpen"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M4 13a8 8 0 0 1 16 0v3a2 2 0 0 1-2 2h-2v-6h4M4 12h4v6H6a2 2 0 0 1-2-2zM8 20h8" />
      </svg>
    </button>
  </aside>
</template>

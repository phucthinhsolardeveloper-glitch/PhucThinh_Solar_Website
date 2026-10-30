<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{
  site: PublicSitePayload
}>()

const showBackToTop = ref(false)
const hotline = computed(() => props.site.brand.hotline.trim())
const phoneHref = computed(() => publicPhoneHref(hotline.value))
const socialLinks = computed(() => props.site.footer?.socialLinks ?? [])
const zaloHref = computed(() => {
  if (props.site.brand.zaloHref) return props.site.brand.zaloHref
  const match = socialLinks.value.find((item) => /zalo\.me\//i.test(item.href) || item.label.trim().toLowerCase() === 'zalo')
  return match?.href ?? ''
})
const hasContactChannels = computed(() => Boolean(phoneHref.value || zaloHref.value))

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
    class="fixed bottom-[calc(1rem+env(safe-area-inset-bottom))] right-4 z-[900] flex flex-col items-center gap-3 print:hidden tb:bottom-6 tb:right-6"
    aria-label="Liên hệ nhanh"
  >
    <button
      v-if="showBackToTop"
      type="button"
      title="Lên đầu trang"
      aria-label="Lên đầu trang"
      class="flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-navy-950 shadow-[0_8px_20px_rgba(6,21,55,0.2)] transition-transform duration-200 hover:scale-105 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600"
      @click="scrollToTop"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="m5 15 7-7 7 7" />
      </svg>
    </button>

    <a
      v-if="phoneHref"
      :href="phoneHref"
      :title="`Gọi hotline ${hotline}`"
      :aria-label="`Gọi hotline ${hotline}`"
      class="contact-phone-pulse flex h-12 w-12 items-center justify-center rounded-full border border-navy-950/15 bg-gold-500 text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-800"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" />
      </svg>
    </a>

    <a
      v-if="zaloHref"
      :href="zaloHref"
      target="_blank"
      rel="noopener noreferrer"
      title="Nhắn tin qua Zalo"
      aria-label="Nhắn tin qua Zalo"
      class="flex h-12 w-12 items-center justify-center rounded-full border border-white/30 bg-[#0068ff] text-[11px] font-extrabold text-white no-underline shadow-[0_8px_20px_rgba(6,21,55,0.2)] transition-transform hover:scale-105 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-800"
    >Zalo</a>
  </aside>
</template>

<style scoped>
.contact-phone-pulse {
  animation: contact-phone-pulse 2.6s ease-in-out infinite;
}

@keyframes contact-phone-pulse {
  0%, 100% { box-shadow: 0 8px 20px rgba(6, 21, 55, 0.2), 0 0 0 0 rgba(201, 150, 46, 0.22); }
  50% { box-shadow: 0 8px 20px rgba(6, 21, 55, 0.2), 0 0 0 7px rgba(201, 150, 46, 0.04); }
}

@media (prefers-reduced-motion: reduce) {
  .contact-phone-pulse { animation: none; }
}
</style>

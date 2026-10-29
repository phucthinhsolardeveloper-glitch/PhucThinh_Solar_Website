<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { publicPhoneHref } from '#shared/contact-links'

const props = defineProps<{
  site: PublicSitePayload
}>()

const hotline = computed(() => props.site.brand.hotline.trim())
const phoneHref = computed(() => publicPhoneHref(hotline.value))

const zaloHref = computed(() => {
  const socials = props.site.footer?.socialLinks ?? []
  const match = socials.find((item) => /zalo\.me\//i.test(item.href) || item.label.trim().toLowerCase() === 'zalo')
  return match?.href ?? ''
})
</script>

<template>
  <aside
    v-if="zaloHref || phoneHref"
    class="fixed bottom-5 right-4 z-[900] flex flex-col items-center gap-3 print:hidden tb:bottom-6 tb:right-6"
    aria-label="Liên hệ nhanh"
  >
    <a
      v-if="zaloHref"
      :href="zaloHref"
      target="_blank"
      rel="noopener noreferrer"
      title="Chat Zalo tư vấn"
      aria-label="Chat Zalo tư vấn"
      class="flex h-12 w-12 items-center justify-center rounded-full border border-white/25 bg-[#0068ff] text-xs font-extrabold tracking-tight text-white no-underline shadow-[0_8px_20px_rgba(6,21,55,0.24)] transition-transform duration-200 hover:scale-105 hover:bg-[#0057d9]"
    >
      Zalo
    </a>
    <a
      v-if="phoneHref"
      :href="phoneHref"
      :title="`Gọi hotline ${hotline}`"
      :aria-label="`Gọi hotline ${hotline}`"
      class="flex h-12 w-12 items-center justify-center rounded-full border border-navy-950/15 bg-gold-500 text-navy-950 no-underline shadow-[0_8px_20px_rgba(6,21,55,0.24)] transition-transform duration-200 hover:scale-105 hover:bg-gold-400"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" />
      </svg>
    </a>
  </aside>
</template>

<script setup lang="ts">
import { MotionConfig } from 'motion-v'
import StorefrontConsultationModal from '~/components/modal/StorefrontConsultationModal.vue'
import CartFeedback from '~/components/commerce/CartFeedback.vue'
import StorefrontDealerModal from '~/components/modal/StorefrontDealerModal.vue'
import CompareTray from '~/components/compare/CompareTray.vue'

// One shell for the whole site. Header, footer and the phone dock stay mounted across
// navigation, so a route change only swaps the page instead of rebuilding everything.
const { site } = await usePublicSite()

// Google Tag Manager, only when Admin has a container id (validated as GTM-XXXX on both sides, so nothing else can be injected).
// Only on the real domain: local previews and the staging stack never send test visits into the owner's reports.
const liveHost = (() => { try { return new URL(useRuntimeConfig().public.canonicalSiteOrigin as string).hostname } catch { return '' } })()
const requestHost = useRequestURL().hostname
const onLiveSite = !!liveHost && (requestHost === liveHost || requestHost === `www.${liveHost}`)
const gtmId = computed(() => onLiveSite && /^GTM-[A-Z0-9]{4,12}$/.test(site.value?.gtmId ?? '') ? site.value!.gtmId! : '')
// GTM is the event destination when configured; loading standalone gtag.js as well can send the same event twice.
const ga4Id = computed(() => onLiveSite && !gtmId.value && /^G-[A-Z0-9]{6,12}$/.test(site.value?.ga4Id ?? '') ? site.value!.ga4Id! : '')
const hasTracking = computed(() => Boolean(gtmId.value || ga4Id.value))
useHead(() => hasTracking.value ? {
  // The shared gtag queue preserves events when the only Google tag is inside GTM.
  script: [{
    key: 'pts-gtag-bridge',
    tagPriority: 'critical',
    // Unknown visitors start denied; a stored explicit choice is restored before any Google tag loads.
    innerHTML: 'window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments)};'
      + '(function(){var v="";try{v=localStorage.getItem("pts-consent")||""}catch(e){}if(v!=="granted"&&v!=="denied"){var m=document.cookie.match(/(?:^|; )pts_consent=([^;]*)/);v=m?m[1]:""}var c=v==="granted"?"granted":"denied";gtag("consent","default",{analytics_storage:c,ad_storage:c,ad_user_data:c,ad_personalization:c});function s(x){if(!x)return "";try{var u=new URL(x,location.href);if(u.protocol!=="http:"&&u.protocol!=="https:")return "";var p=new URLSearchParams();if(!/^\\/cam-on(?:\\/|$)/.test(u.pathname))u.searchParams.forEach(function(v,k){if(v&&/^(?:utm_[a-z_]{1,40}|gclid|gbraid|wbraid|fbclid)$/.test(k))p.append(k,v.slice(0,200))});var t=p.toString();return u.origin+u.pathname+(t?"?"+t:"")}catch(e){return ""}}gtag("set",{page_location:s(location.href),page_referrer:s(document.referrer)})})();',
  }],
} : {})

// Use standalone gtag.js only when no GTM container is configured.
useHead(() => ga4Id.value ? {
  script: [
    { key: 'pts-ga4', innerHTML: `window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config','${ga4Id.value}');` },
  ],
} : {})

// Google scripts wait until the page has loaded and is idle, or until the visitor first interacts.
function loadTrackingScripts() {
  const w = window as unknown as { __ptsTrackingLoaded?: boolean; dataLayer?: unknown[] }
  if (w.__ptsTrackingLoaded) return
  w.__ptsTrackingLoaded = true
  const add = (src: string) => {
    const script = document.createElement('script')
    script.async = true
    script.src = src
    document.head.appendChild(script)
  }
  if (gtmId.value) {
    w.dataLayer = w.dataLayer || []
    w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' })
    add(`https://www.googletagmanager.com/gtm.js?id=${gtmId.value}`)
  }
  if (ga4Id.value) add(`https://www.googletagmanager.com/gtag/js?id=${ga4Id.value}`)
}
onMounted(() => {
  if (!gtmId.value && !ga4Id.value) return
  const requestIdle = (window as unknown as { requestIdleCallback?: (callback: () => void, options?: { timeout: number }) => number }).requestIdleCallback
  const whenIdle = () => { if (requestIdle) requestIdle.call(window, loadTrackingScripts, { timeout: 4000 }); else window.setTimeout(loadTrackingScripts, 2000) }
  if (document.readyState === 'complete') whenIdle()
  else window.addEventListener('load', whenIdle, { once: true })
  for (const type of ['pointerdown', 'keydown', 'scroll', 'touchstart']) window.addEventListener(type, loadTrackingScripts, { once: true, passive: true })
})

useHead({
  script: [
    {
      key: 'pts-gtranslate-settings',
      innerHTML: `window.gtranslateSettings = ${JSON.stringify({
        default_language: 'vi',
        // First visit is always Vietnamese. GTranslate keeps the visitor's own pick in its cookie across pages.
        detect_browser_language: false,
        native_language_names: true,
        languages: ['vi', 'en', 'zh-CN', 'ko', 'ja'],
        wrapper_selector: '.gtranslate_wrapper',
        flag_size: 26,
      })};`,
    },
  ],
})

</script>

<template>
  <!--
    UApp là provider bắt buộc của Nuxt UI v4: nó gắn teleport target cho overlay
    (UModal, UDrawer, UDropdownMenu, UTooltip) và vùng thông báo. Thiếu nó thì
    các control vẫn compile và vẫn render nút bấm, nhưng overlay không bao giờ
    hiện ra — lỗi im lặng, chỉ lộ khi bấm thử trong trình duyệt thật.

    Bọc ở app.vue chứ không ở layout: layout là tài sản của từng trang
    (CODEX 2 trở đi), còn provider phải phủ toàn bộ app kể cả route không dùng
    layout nào.
  -->
  <MotionConfig reducedMotion="user">
    <UApp>
      <!-- Immediate click feedback; the throttle keeps fast navigations from flashing it. -->
      <NuxtLoadingIndicator color="#d49b39" :height="2" :throttle="150" />
      <SiteHeader :site="site" />
      <NuxtLayout>
        <NuxtPage />
      </NuxtLayout>
      <SiteFooter :site="site" />
      <StorefrontConsultationModal />
      <StorefrontDealerModal />
      <CartFeedback />
      <CompareTray />
      <CookieNotice />
    </UApp>
  </MotionConfig>
</template>

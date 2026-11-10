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
const gtmId = computed(() => /^GTM-[A-Z0-9]{4,12}$/.test(site.value?.gtmId ?? '') ? site.value!.gtmId! : '')
useHead(() => gtmId.value ? {
  script: [{
    key: 'pts-gtm',
    tagPriority: 'critical',
    innerHTML: `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${gtmId.value}');`,
  }],
  noscript: [{
    key: 'pts-gtm-noscript',
    tagPosition: 'bodyOpen',
    innerHTML: `<iframe src="https://www.googletagmanager.com/ns.html?id=${gtmId.value}" height="0" width="0" style="display:none;visibility:hidden"></iframe>`,
  }],
} : {})

useHead({
  script: [
    {
      key: 'pts-gtranslate-settings',
      innerHTML: `window.gtranslateSettings = ${JSON.stringify({
        default_language: 'vi',
        detect_browser_language: true,
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
    </UApp>
  </MotionConfig>
</template>

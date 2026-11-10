<script setup lang="ts">
import { MotionConfig } from 'motion-v'
import StorefrontConsultationModal from '~/components/modal/StorefrontConsultationModal.vue'
import CartFeedback from '~/components/commerce/CartFeedback.vue'
import StorefrontDealerModal from '~/components/modal/StorefrontDealerModal.vue'
import CompareTray from '~/components/compare/CompareTray.vue'

// One shell for the whole site. Header, footer and the phone dock stay mounted across
// navigation, so a route change only swaps the page instead of rebuilding everything.
const { site } = await usePublicSite()

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

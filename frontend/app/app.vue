<script setup lang="ts">
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
        flag_size: 24,
      })};`,
    },
    {
      key: 'pts-gtranslate-popup',
      src: 'https://cdn.gtranslate.net/widgets/latest/popup.js',
      defer: true,
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
  <UApp>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>

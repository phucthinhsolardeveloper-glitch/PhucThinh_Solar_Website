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

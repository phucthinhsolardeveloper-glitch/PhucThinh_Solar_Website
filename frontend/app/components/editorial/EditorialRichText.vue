<script setup lang="ts">
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'

defineProps<{ content: PublicSanitizedRichText }>()
</script>

<template>
  <!--
    Dùng chung .cms-content + .cms-content--editorial (main.css) thay vì bộ style scoped
    riêng — class đó đã phủ đúng toàn bộ tập thẻ allowlist của pts_public_richtext.php
    (h2-h6, p, ul/ol/li, blockquote, pre/code, table, figure/figcaption, img, a), viết
    một lần cho mọi nơi hiển thị nội dung CKEditor đã sanitize thay vì mỗi
    component tự định nghĩa lại.

    v-html CHỈ nhận content.html — field này chỉ tồn tại sau khi qua
    pts_public_richtext.php (PHP, allowlist DOM) và được xác nhận lại bởi
    isPublicSanitizedRichText() (Nuxt, kiểm metadata + marker nguy hiểm) ở lớp
    contract gọi component này. Không bao giờ nhận raw contentvi/content1vi.
  -->
  <div v-if="content.html" class="cms-content cms-content--editorial" v-html="content.html" />
</template>

<script setup lang="ts">
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'

defineProps<{
  content: PublicSanitizedRichText
  variant?: 'solution'
}>()
</script>

<template>
  <!--
    Dùng chung .cms-content + .cms-content--editorial (main.css) cho nội dung CKEditor;
    biến thể solution chỉ phục hồi nhịp phẳng của trang hub PHP. Các class đó phủ
    đúng toàn bộ tập thẻ allowlist của pts_public_richtext.php
    (h2-h6, p, ul/ol/li, blockquote, pre/code, table, figure/figcaption, img, a), viết
    một lần cho mọi nơi hiển thị nội dung CKEditor đã sanitize thay vì mỗi
    component tự định nghĩa lại.

    v-html CHỈ nhận content.html — field này chỉ tồn tại sau khi qua
    pts_public_richtext.php (PHP, allowlist DOM) và được xác nhận lại bởi
    isPublicSanitizedRichText() (Nuxt, kiểm metadata + marker nguy hiểm) ở lớp
    contract gọi component này. Không bao giờ nhận raw contentvi/content1vi.
  -->
  <div
    v-if="content.html"
    class="cms-content cms-content--editorial"
    :class="variant === 'solution' ? 'cms-content--editorial-solution' : ''"
    v-html="content.html"
  />
</template>

<style>
.cms-content--editorial-solution {
  color: #243754;
  font-size: 17px;
  line-height: 1.78;
}

.cms-content--editorial-solution h2 {
  margin: 56px 0 18px;
  padding: 0;
  border: 0;
  color: #071b46;
  font-size: clamp(25px, 2.3vw, 34px);
  font-weight: 850;
  letter-spacing: -0.025em;
  line-height: 1.28;
  text-transform: none;
  text-wrap: balance;
}

.cms-content--editorial-solution > h2:first-child { margin-top: 0; }

.cms-content--editorial-solution h3 {
  margin: 32px 0 11px;
  padding: 0;
  border: 0;
  color: #071b46;
  font-size: 21px;
  font-weight: 800;
  line-height: 1.4;
  text-transform: none;
}

.cms-content--editorial-solution p { margin: 0 0 18px; }

.cms-content--editorial-solution ul,
.cms-content--editorial-solution ol {
  margin: 0 0 24px;
  padding: 0 0 0 22px;
  border: 0;
  border-radius: 0;
  background: transparent;
}

.cms-content--editorial-solution li { margin: 0 0 9px; color: inherit; }
.cms-content--editorial-solution li::marker { color: inherit; font-weight: inherit; }
.cms-content--editorial-solution a { color: inherit; text-decoration: none; }

.cms-content--editorial-solution img {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 30px auto;
  border-radius: 0;
  object-fit: contain;
}

.cms-content--editorial-solution figure { margin: 32px 0; }

.cms-content--editorial-solution blockquote {
  margin: 30px 0;
  padding: 24px 26px;
  border: 0;
  border-radius: 3px;
  background: #f2f5f9;
  color: #071b46;
  font-size: 18px;
  font-weight: 700;
}

.cms-content--editorial-solution p > code:only-child {
  display: block;
  max-width: 100%;
  margin: 6px 0;
  padding: 12px 14px;
  border: 0;
  border-radius: 3px;
  background: #f2f5f9;
  color: #071b46;
  font-size: 15px;
  font-weight: 400;
  line-height: 1.7;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.cms-content--editorial-solution table {
  display: table;
  width: 100%;
  margin: 30px 0;
  border-collapse: collapse;
  background: #fff;
}

.cms-content--editorial-solution th,
.cms-content--editorial-solution td {
  padding: 13px 10px;
  border: 0;
  border-bottom: 1px solid #dfe5ed;
  background: transparent;
  text-align: left;
  vertical-align: top;
}

.cms-content--editorial-solution th { color: #071b46; font-weight: 800; }

@media (max-width: 520px) {
  .cms-content--editorial-solution { font-size: 16px; line-height: 1.72; }
  .cms-content--editorial-solution h2 { margin-top: 42px; font-size: 25px; }
  .cms-content--editorial-solution h3 { font-size: 19px; }
  .cms-content--editorial-solution blockquote { padding: 20px; font-size: 16px; }
  .cms-content--editorial-solution table { display: block; max-width: 100%; overflow-x: auto; }
}
</style>

<script setup lang="ts">
import type { PublicSanitizedRichText } from '#shared/contracts/editorial-rich-text'

defineProps<{
  content: PublicSanitizedRichText
  variant?: 'solution' | 'news'
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
    :class="{
      'cms-content--editorial-solution': variant === 'solution',
      'cms-content--editorial-news': variant === 'news',
    }"
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
  scroll-margin-top: 100px;
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
  scroll-margin-top: 100px;
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

.cms-content--editorial-news {
  color: #303744;
  font-size: 17px;
  line-height: 1.8;
  overflow-wrap: anywhere;
}

.cms-content--editorial-news h2,
.cms-content--editorial-news h3,
.cms-content--editorial-news h4,
.cms-content--editorial-news h5,
.cms-content--editorial-news h6 {
  padding: 0;
  border: 0;
  color: #172033;
  font-weight: 700;
  letter-spacing: -0.025em;
  line-height: 1.3;
  text-transform: none;
  text-wrap: balance;
  scroll-margin-top: 100px;
}

.cms-content--editorial-news h2 {
  margin: 2.2em 0 0.65em;
  font-size: clamp(24px, 2.1vw, 30px);
}

.cms-content--editorial-news h3 {
  margin: 1.8em 0 0.55em;
  font-size: clamp(20px, 1.7vw, 23px);
}

.cms-content--editorial-news h4,
.cms-content--editorial-news h5,
.cms-content--editorial-news h6 {
  margin: 1.5em 0 0.5em;
  font-size: 18px;
}

.cms-content--editorial-news > :first-child { margin-top: 0; }
.cms-content--editorial-news p { margin: 0 0 1.15em; }

.cms-content--editorial-news ul,
.cms-content--editorial-news ol {
  margin: 0 0 1.25em;
  padding: 0 0 0 1.5em;
  border: 0;
  border-radius: 0;
  background: transparent;
}

.cms-content--editorial-news li { margin: 0.4em 0; color: inherit; }
.cms-content--editorial-news li::marker { color: #697586; }

.cms-content--editorial-news a {
  color: #1759a6;
  text-decoration: underline;
  text-decoration-color: rgb(23 89 166 / 35%);
  text-underline-offset: 0.18em;
}

.cms-content--editorial-news a:hover { text-decoration-color: currentColor; }
.cms-content--editorial-news a:focus-visible { outline: 2px solid #1759a6; outline-offset: 3px; }

.cms-content--editorial-news img {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 1.8em auto;
  border-radius: 0;
  object-fit: contain;
}

.cms-content--editorial-news figure { margin: 1.8em 0; }
.cms-content--editorial-news figcaption { margin-top: 0.65em; color: #667085; font-size: 14px; line-height: 1.55; }

.cms-content--editorial-news blockquote {
  margin: 1.6em 0;
  padding: 0.15em 0 0.15em 1.1em;
  border: 0;
  border-left: 3px solid #cbd5e1;
  border-radius: 0;
  background: transparent;
  color: #475467;
  font-size: 1.05em;
  font-weight: 500;
}

.cms-content--editorial-news table {
  display: block;
  width: 100%;
  margin: 1.8em 0;
  overflow-x: auto;
  border-collapse: collapse;
  background: transparent;
}

.cms-content--editorial-news th,
.cms-content--editorial-news td {
  min-width: 8rem;
  padding: 0.7em 0.8em;
  border: 0;
  border-bottom: 1px solid #e4e7ec;
  background: transparent;
  text-align: left;
  vertical-align: top;
}

.cms-content--editorial-news th { color: #172033; font-weight: 650; }

@media (max-width: 520px) {
  .cms-content--editorial-news { font-size: 16px; line-height: 1.75; }
  .cms-content--editorial-news h2 { font-size: 24px; }
  .cms-content--editorial-news h3 { font-size: 20px; }
  .cms-content--editorial-news blockquote { padding-left: 0.9em; }
}

@media (max-width: 520px) {
  .cms-content--editorial-solution { font-size: 16px; line-height: 1.72; }
  .cms-content--editorial-solution h2 { margin-top: 42px; font-size: 25px; }
  .cms-content--editorial-solution h3 { font-size: 19px; }
  .cms-content--editorial-solution blockquote { padding: 20px; font-size: 16px; }
  .cms-content--editorial-solution table { display: block; max-width: 100%; overflow-x: auto; }
}
</style>

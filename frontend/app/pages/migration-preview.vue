<script setup lang="ts">
useSeoMeta({
  title: 'PTS frontend migration — internal preview',
  robots: 'noindex, nofollow',
})
</script>

<template>
  <main class="preview">
    <span class="label">PTS / FRONTEND MIGRATION</span>
    <h1>Nuxt 4 runtime checkpoint</h1>
    <p>
      Chỉ dùng để kiểm tra Nuxt runtime. Chưa port giao diện PHP,
      chưa bật route công khai và chưa kết nối dữ liệu production.
    </p>
    <p class="footer">Base: <code>5a488633ec3b434a22505449b21a04ac981e9cd5</code></p>
  </main>
</template>

<style scoped>
.preview { max-width: 760px; padding: 64px 24px; margin: 0 auto; line-height: 1.65; }
.label { font-size: 12px; letter-spacing: .14em; color: var(--pts-gold-dark); font-weight: 700; }
h1 { font-size: clamp(28px, 4vw, 40px); line-height: 1.15; margin: 20px 0; }
p { font-size: 16px; max-width: 62ch; color: #38475e; }
.footer { font-size: 12px; overflow-wrap: anywhere; margin-top: 48px; }
</style>

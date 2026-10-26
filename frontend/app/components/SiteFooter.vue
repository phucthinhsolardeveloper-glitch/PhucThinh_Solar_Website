<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())

const footerLinks = [
  { label: 'Giới thiệu', to: '/ve-chung-toi/' },
  { label: 'Giải pháp', to: '/giai-phap/' },
  { label: 'Dự án', to: '/du-an-da-thuc-hien/' },
  { label: 'Kiến thức', to: '/tin-tuc/' },
  { label: 'Hợp tác', to: '/dai-ly/' },
]
</script>

<template>
  <footer class="pts-site-footer">
    <div class="pts-container pts-site-footer__grid">
      <div class="pts-site-footer__brand">
        <NuxtLink class="pts-brand pts-brand--footer" to="/" :aria-label="`${brandName} - Trang chủ`">
          <span>{{ brandName }}</span>
        </NuxtLink>
      </div>

      <nav aria-label="Điều hướng cuối trang">
        <h2 class="pts-site-footer__title">Điều hướng</h2>
        <ul class="pts-site-footer__links">
          <li v-for="link in footerLinks" :key="link.to">
            <NuxtLink :to="link.to">{{ link.label }}</NuxtLink>
          </li>
        </ul>
      </nav>

      <div v-if="hotline" class="pts-site-footer__contact">
        <h2 class="pts-site-footer__title">Liên hệ</h2>
        <a :href="`tel:${hotline.replace(/[^+\d]/g, '')}`">{{ hotline }}</a>
      </div>
    </div>

    <div class="pts-site-footer__bottom">
      <div class="pts-container">© {{ new Date().getFullYear() }} {{ brandName }}</div>
    </div>
  </footer>
</template>

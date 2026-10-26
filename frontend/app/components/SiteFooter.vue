<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const footerLogo = computed(() => props.site.brand.logoFooter || props.site.brand.logo || '')
const footer = computed(() => props.site.footer)

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
        <a class="pts-brand pts-brand--footer" href="/" :aria-label="`${brandName} - Trang chủ`">
          <img v-if="footerLogo" :src="footerLogo" :alt="brandName" width="184" height="72">
          <span v-else>{{ brandName }}</span>
        </a>
      </div>

      <nav aria-label="Điều hướng cuối trang">
        <h2 class="pts-site-footer__title">Điều hướng</h2>
        <ul class="pts-site-footer__links">
          <li v-for="link in footerLinks" :key="link.to">
            <NuxtLink external :to="link.to">{{ link.label }}</NuxtLink>
          </li>
        </ul>
      </nav>

      <div v-if="hotline" class="pts-site-footer__contact">
        <h2 class="pts-site-footer__title">Liên hệ</h2>
        <a :href="`tel:${hotline.replace(/[^+\d]/g, '')}`">{{ hotline }}</a>
      </div>

      <div v-if="footer?.contactText" class="pts-site-footer__contact-text">
        <h2 class="pts-site-footer__title">Thông tin</h2>
        <p>{{ footer.contactText }}</p>
      </div>

      <nav v-if="footer?.policies.length" aria-label="Chính sách">
        <h2 class="pts-site-footer__title">Chính sách &amp; hỗ trợ</h2>
        <ul class="pts-site-footer__links">
          <li v-for="policy in footer.policies" :key="policy.href"><NuxtLink external :to="policy.href">{{ policy.label }}</NuxtLink></li>
        </ul>
      </nav>

      <div v-if="footer?.locations.length" class="pts-site-footer__locations">
        <h2 class="pts-site-footer__title">Hệ thống kho &amp; điểm giao dịch</h2>
        <address v-for="location in footer.locations" :key="location.name + location.address">
          <a v-if="location.mapUrl" :href="location.mapUrl" target="_blank" rel="noopener noreferrer">{{ location.name }}<br>{{ location.address }}</a>
          <span v-else>{{ location.name }}<br>{{ location.address }}</span>
        </address>
      </div>

      <nav v-if="footer?.socialLinks.length" aria-label="Mạng xã hội">
        <h2 class="pts-site-footer__title">Kết nối</h2>
        <ul class="pts-site-footer__links">
          <li v-for="social in footer.socialLinks" :key="social.href"><a :href="social.href" target="_blank" rel="noopener noreferrer">{{ social.label }}</a></li>
        </ul>
      </nav>
    </div>

    <div class="pts-site-footer__bottom">
      <div class="pts-container">© {{ new Date().getFullYear() }} {{ brandName }}</div>
    </div>
  </footer>
</template>

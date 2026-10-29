<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import SiteFloatingContact from '~/components/SiteFloatingContact.vue'

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const technicalHotline = computed(() => props.site.brand.technicalHotline?.trim() ?? '')
const footerLogo = computed(() => props.site.brand.logoFooter || props.site.brand.logo || '')
const CMS_UPLOAD_PHOTO = /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i

const footerLogoSrc = computed(() => {
  const src = footerLogo.value
  return CMS_UPLOAD_PHOTO.test(src) ? `/thumbs/280x229x1${src}` : src
})
function trustBadgeSrc(src: string): string {
  return CMS_UPLOAD_PHOTO.test(src) ? `/thumbs/200x76x1${src}` : src
}
const footer = computed(() => props.site.footer)
const { generic: footerHref, external: isExternalLink } = useMigrationLinks()

function telHrefFor(value: string): string {
  return `tel:${value.replace(/[^+\d]/g, '')}`
}

const telHref = computed(() => telHrefFor(hotline.value))
</script>

<template>
  <footer class="border-t border-navy-900 bg-navy-950 pb-7 text-ftr-text">
    <div
      class="mx-auto grid w-full max-w-(--container-page) gap-8 px-4 max-[359px]:px-3 tb:px-(--spacing-gutter) pt-12
             grid-cols-1
             tb:grid-cols-2
             nav:grid-cols-[200px_minmax(260px,1fr)_minmax(230px,0.85fr)_minmax(340px,1.25fr)]
             wd:grid-cols-[240px_minmax(280px,1fr)_minmax(240px,0.85fr)_minmax(420px,1.35fr)]"
    >
      <!-- Cột 1: Biểu trưng thương hiệu -->
      <div class="space-y-3">
        <a class="inline-block max-w-[240px]" :href="footerHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <img
            v-if="footerLogo"
            :src="footerLogoSrc"
            :alt="brandName"
            width="280"
            height="229"
            class="block h-auto w-full max-w-[168px] wd:max-w-[228px]"
          >
          <span v-else class="text-lg font-black text-white">{{ brandName }}</span>
        </a>
        <div v-if="footer?.trustBadge" class="pt-1">
          <a
            class="inline-flex min-h-11 max-w-[138px] items-center"
            :href="footer.trustBadge.href"
            target="_blank"
            rel="noopener noreferrer"
          >
            <img :src="trustBadgeSrc(footer.trustBadge.image)" alt="Đã thông báo Bộ Công Thương" width="200" height="76" class="block h-auto w-full">
          </a>
        </div>
      </div>

      <!-- Cột 2: Pháp nhân doanh nghiệp và 2 đường dây nóng -->
      <div>
        <h2 class="mb-4 border-b border-white/12 pb-2.5 text-[15px] font-extrabold uppercase tracking-[.02em] text-gold-400">
          {{ footer?.companyName || 'Thông tin doanh nghiệp' }}
        </h2>

        <EditorialRichText v-if="footer?.contactText?.html" :content="footer.contactText" class="cms-content-on-dark mt-3 text-[14.5px] leading-[1.68]" />

        <div v-if="hotline || technicalHotline" class="mt-4 grid grid-cols-1 gap-2.5 ph:grid-cols-2">
          <a
            v-if="hotline"
            class="flex min-w-0 items-start gap-2.5 rounded-btn border border-[#bfdbfe]/20 border-l-[3px]
                   border-l-gold-400 bg-white/[.045] px-3 py-2.5 text-[14.5px] leading-[1.4] no-underline
                   transition-colors hover:bg-white/[.09]"
            :href="telHref"
          >
            <svg class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
            <span class="flex min-w-0 flex-col gap-0.5">
              <small class="text-[11.5px] leading-[1.3] text-ftr-text">Tư vấn bán hàng</small>
              <strong class="whitespace-nowrap text-[15px] font-bold leading-[1.3] text-white">{{ hotline }}</strong>
            </span>
          </a>
          <a
            v-if="technicalHotline"
            class="flex min-w-0 items-start gap-2.5 rounded-btn border border-[#bfdbfe]/20 border-l-[3px]
                   border-l-gold-400 bg-white/[.045] px-3 py-2.5 text-[14.5px] leading-[1.4] no-underline
                   transition-colors hover:bg-white/[.09]"
            :href="telHrefFor(technicalHotline)"
          >
            <svg class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a2 2 0 0 0-2 2v2.17a2 2 0 0 1-.59 1.42L7.59 9.41A2 2 0 0 0 7 10.83V13a2 2 0 0 0 2 2h.17a2 2 0 0 1 1.42.59l1.82 1.82a2 2 0 0 0 1.42.59H16a2 2 0 0 0 2-2v-2.17a2 2 0 0 1 .59-1.42L20 10.41" /></svg>
            <span class="flex min-w-0 flex-col gap-0.5">
              <small class="text-[11.5px] leading-[1.3] text-ftr-text">Hỗ trợ kỹ thuật</small>
              <strong class="whitespace-nowrap text-[15px] font-bold leading-[1.3] text-white">{{ technicalHotline }}</strong>
            </span>
          </a>
        </div>
      </div>

      <!-- Cột 3: Chính sách & Hỗ trợ khách hàng -->
      <nav v-if="footer?.policies.length" aria-label="Chính sách và hỗ trợ khách hàng">
        <h2 class="mb-4 border-b border-white/12 pb-2.5 text-[14px] font-extrabold uppercase tracking-wider text-white">
          Chính sách &amp; Hỗ trợ khách hàng
        </h2>
        <ul class="m-0 list-none space-y-0.5 p-0">
          <li v-for="policy in footer.policies" :key="policy.href">
            <NuxtLink
              :external="isExternalLink(footerHref(policy.href))"
              class="flex min-h-11 items-center py-1 text-[15px] leading-[1.6] no-underline transition-colors hover:text-gold-400"
              :to="footerHref(policy.href)"
            >
              {{ policy.label }}
            </NuxtLink>
          </li>
        </ul>
      </nav>

      <!-- Cột 4: Lưới thẻ Hệ thống kho & điểm giao dịch -->
      <div v-if="footer?.locations.length">
        <h2 class="mb-4 border-b border-white/12 pb-2.5 text-[14px] font-extrabold uppercase tracking-wider text-white">
          Hệ thống kho &amp; điểm giao dịch
        </h2>
        <div class="grid grid-cols-1 gap-3 ph:grid-cols-2">
          <address
            v-for="location in footer.locations"
            :key="location.name + location.address"
            class="m-0 rounded-btn border border-white/10 bg-white/[.04] p-3.5 not-italic transition-colors hover:border-gold-400/45 hover:bg-white/[.07]"
          >
            <component
              :is="location.mapUrl ? 'a' : 'div'"
              v-bind="location.mapUrl ? { href: location.mapUrl, target: '_blank', rel: 'noopener noreferrer' } : {}"
              class="block text-inherit no-underline transition-colors"
              :class="location.mapUrl ? 'hover:text-gold-400' : ''"
            >
              <span class="flex items-center gap-2 text-[14.5px] font-bold leading-[1.4] text-white">
                <svg class="h-3.5 w-3.5 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
                {{ location.name }}
              </span>
              <span class="mt-1.5 flex items-start gap-1.5 text-[13px] leading-[1.55] text-ftr-text">
                <svg class="mt-1 h-3 w-3 flex-none text-gold-400/80" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                {{ location.address }}
              </span>
            </component>
          </address>
        </div>
      </div>
    </div>

    <!-- Thanh bản quyền & Kết nối mạng xã hội -->
    <div class="mt-10 border-t border-white/10 pt-5">
      <div class="mx-auto flex w-full max-w-(--container-page) flex-col items-start justify-between gap-3 px-4 max-[359px]:px-3 tb:flex-row tb:items-center tb:px-(--spacing-gutter) text-[13px]">
        <p class="m-0 text-ftr-text/80">© {{ new Date().getFullYear() }} {{ brandName }}. Tất cả quyền được bảo lưu.</p>
        <ul v-if="footer?.socialLinks.length" class="m-0 flex list-none flex-wrap items-center gap-x-5 gap-y-1 p-0" aria-label="Kết nối mạng xã hội">
          <li v-for="social in footer.socialLinks" :key="social.href">
            <a
              class="inline-flex min-h-11 items-center text-[15px] font-semibold leading-[1.7] text-ftr-text no-underline transition-colors hover:text-gold-400"
              :href="social.href"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ social.label }}
            </a>
          </li>
        </ul>
      </div>
    </div>
  </footer>

  <SiteFloatingContact :site="site" />
</template>

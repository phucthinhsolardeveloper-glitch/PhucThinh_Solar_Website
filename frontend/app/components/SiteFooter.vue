<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'

const props = defineProps<{
  site: PublicSitePayload
}>()

const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const technicalHotline = computed(() => props.site.brand.technicalHotline?.trim() ?? '')
const footerLogo = computed(() => props.site.brand.logoFooter || props.site.brand.logo || '')
const CMS_UPLOAD_PHOTO = /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
// Chỉ resize ảnh CMS thật (/upload/photo/...); path khác (ví dụ static asset) giữ nguyên.
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
  <!--
    Footer dựng theo ảnh production 2026-10-26 + theme-v2.css/footer-v3.css.
    Số đo đã kiểm chứng ở 1920px:
      nền            #061537  = navy-950 (đo pixel khớp tuyệt đối)
      nhãn cột 2     #e0b654  = gold-400 (--pts-gold-light)
      tiêu đề cột    #ffffff, 14px/850, gạch dưới rgba(255,255,255,.12)
      link           #bfdbfe, 13px, line-height 1.7
      lưới           290px | 1fr | 1.3fr, gap 30px (>=1201px)
      cột 3 tự chia  1fr 1fr, gap 22px
  -->
  <footer class="bg-navy-950 pb-7 text-ftr-text">
    <div class="mx-auto grid w-full max-w-(--container-page) gap-[30px] px-4 max-[359px]:px-3 tb:px-(--spacing-gutter) pt-12
                grid-cols-1
                tb:grid-cols-[180px_minmax(280px,1fr)]
                nav:grid-cols-[180px_minmax(280px,1fr)_minmax(460px,1.3fr)]
                wd:grid-cols-[290px_minmax(280px,1fr)_minmax(460px,1.3fr)]">
      <!-- Cột 1: logo. Đây là bản emblem dọc, KHÁC wordmark ngang của header. -->
      <div>
        <a class="block max-w-[280px]" :href="footerHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <img
            v-if="footerLogo"
            :src="footerLogoSrc"
            :alt="brandName"
            width="280"
            height="229"
            class="block h-auto w-full max-w-[180px] wd:max-w-[280px]"
          >
          <span v-else class="text-lg font-black text-white">{{ brandName }}</span>
        </a>
      </div>

      <!-- Cột 2: thông tin pháp lý và hai đường dây nóng. -->
      <div>
        <h2 class="mb-[15px] border-b border-white/12 pb-[10px] text-[16px] font-extrabold uppercase tracking-[.02em] text-gold-400">
          {{ footer?.companyName || 'Thông tin doanh nghiệp' }}
        </h2>

        <EditorialRichText v-if="footer?.contactText?.html" :content="footer.contactText" class="cms-content-on-dark mt-4 text-[15px] leading-[1.65]" />

        <!--
          Ảnh production có HAI ô: "Tư vấn bán hàng" (brand.hotline) và "Hỗ trợ
          kỹ thuật" (brand.technicalHotline, merge feature/pts-nuxt-php-dto-
          20261026). Ô thứ hai chỉ hiện khi API thật sự trả số — không bịa khi
          CMS chưa cấu hình đường dây kỹ thuật riêng.
        -->
        <div v-if="hotline || technicalHotline" class="mt-[18px] grid grid-cols-1 gap-[10px] ph:grid-cols-2">
          <a
            v-if="hotline"
            class="flex min-w-0 items-start gap-[10px] rounded-btn border border-[#bfdbfe]/20 border-l-[3px]
                   border-l-gold-400 bg-white/[.045] px-3 py-[11px] text-[15px] leading-[1.45]
                   transition-colors hover:bg-white/[.08]"
            :href="telHref"
          >
            <svg class="mt-[3px] h-[14px] w-[14px] flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
            <span class="flex min-w-0 flex-col gap-0.5">
              <small class="text-[12px] leading-[1.3] text-ftr-text">Tư vấn bán hàng</small>
              <strong class="whitespace-nowrap text-[16px] leading-[1.3] font-bold text-white">{{ hotline }}</strong>
            </span>
          </a>
          <a
            v-if="technicalHotline"
            class="flex min-w-0 items-start gap-[10px] rounded-btn border border-[#bfdbfe]/20 border-l-[3px]
                   border-l-gold-400 bg-white/[.045] px-3 py-[11px] text-[15px] leading-[1.45]
                   transition-colors hover:bg-white/[.08]"
            :href="telHrefFor(technicalHotline)"
          >
            <svg class="mt-[3px] h-[14px] w-[14px] flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a2 2 0 0 0-2 2v2.17a2 2 0 0 1-.59 1.42L7.59 9.41A2 2 0 0 0 7 10.83V13a2 2 0 0 0 2 2h.17a2 2 0 0 1 1.42.59l1.82 1.82a2 2 0 0 0 1.42.59H16a2 2 0 0 0 2-2v-2.17a2 2 0 0 1 .59-1.42L20 10.41" /></svg>
            <span class="flex min-w-0 flex-col gap-0.5">
              <small class="text-[12px] leading-[1.3] text-ftr-text">Hỗ trợ kỹ thuật</small>
              <strong class="whitespace-nowrap text-[16px] leading-[1.3] font-bold text-white">{{ technicalHotline }}</strong>
            </span>
          </a>
        </div>
      </div>

      <!-- Cột 3: tự chia đôi thành Chính sách và Hệ thống kho. -->
      <div class="grid grid-cols-1 gap-[22px] ph:grid-cols-2">
        <nav v-if="footer?.policies.length" aria-label="Chính sách và hỗ trợ khách hàng">
          <h2 class="mb-[15px] border-b border-white/12 pb-[10px] text-[14px] font-extrabold text-white">
            Chính sách &amp; Hỗ trợ khách hàng
          </h2>
          <ul>
            <li v-for="policy in footer.policies" :key="policy.href">
              <NuxtLink
                :external="isExternalLink(footerHref(policy.href))"
                class="flex min-h-11 items-center py-1 text-[15px] leading-[1.7] transition-colors hover:text-gold-400 min-[1600px]:text-[17px]"
                :to="footerHref(policy.href)"
              >
                {{ policy.label }}
              </NuxtLink>
            </li>
          </ul>
          <!--
            footer.trustBadge (merge feature/pts-nuxt-php-dto-20261026): huy
            hiệu "Đã thông báo Bộ Công Thương", nguồn table_photo type=bct,
            nên luôn là ảnh thật do CMS quản lý — không có fallback vẽ tay.
          -->
          <a
            v-if="footer?.trustBadge"
            class="mt-[18px] inline-flex min-h-11 items-center max-w-[130px]"
            :href="footer.trustBadge.href"
            target="_blank"
            rel="noopener noreferrer"
          >
            <img :src="trustBadgeSrc(footer.trustBadge.image)" alt="Đã thông báo Bộ Công Thương" width="200" height="76" class="block h-auto w-full">
          </a>
          <div v-if="footer?.socialLinks.length" class="mt-[18px]">
            <h3 class="mb-2 text-[13px] font-extrabold text-white">Kết nối</h3>
            <ul class="flex flex-wrap gap-x-4 gap-y-1">
              <li v-for="social in footer.socialLinks" :key="social.href">
                <a
                  class="inline-flex min-h-11 items-center text-[15px] leading-[1.7] transition-colors hover:text-gold-400"
                  :href="social.href"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {{ social.label }}
                </a>
              </li>
            </ul>
          </div>
        </nav>

        <div v-if="footer?.locations.length">
          <h2 class="mb-[15px] border-b border-white/12 pb-[10px] text-[14px] font-extrabold text-white">
            Hệ thống kho &amp; điểm giao dịch
          </h2>
          <address
            v-for="location in footer.locations"
            :key="location.name + location.address"
            class="mb-[14px] not-italic last:mb-0"
          >
            <component
              :is="location.mapUrl ? 'a' : 'div'"
              v-bind="location.mapUrl ? { href: location.mapUrl, target: '_blank', rel: 'noopener noreferrer' } : {}"
              class="block transition-colors"
              :class="location.mapUrl ? 'hover:text-gold-400' : ''"
            >
              <span class="flex items-center gap-2 text-[16px] font-bold leading-[1.45] text-white">
                <svg class="h-[14px] w-[14px] flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
                {{ location.name }}
              </span>
              <span class="mt-1 flex items-start gap-2 text-[15px] leading-[1.65]">
                <svg class="mt-[3px] h-[13px] w-[13px] flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                {{ location.address }}
              </span>
            </component>
          </address>
        </div>
      </div>
    </div>

    <!--
      Dải bản quyền: bản PHP KHÔNG có dải này (ảnh production kết thúc ngay sau
      lưới 4 cột). Giữ lại vì đây là thông tin đúng và hữu ích, nhưng tách bằng
      đường kẻ mảnh để không bị đọc nhầm là một cột nữa.
    -->
    <div class="mt-10 border-t border-white/10 pt-5">
      <div class="mx-auto w-full max-w-(--container-page) px-4 max-[359px]:px-3 tb:px-(--spacing-gutter) text-[13px]">
        © {{ new Date().getFullYear() }} {{ brandName }}
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { sentenceCase } from '~/utils/sentenceCase'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import SiteFloatingContact from '~/components/SiteFloatingContact.vue'

const props = defineProps<{
  site: PublicSitePayload
}>()

const footOpen = reactive({ company: false, policies: false, locations: false })
const brandName = computed(() => props.site.brand.name.trim() || 'Phúc Thịnh Solar')
const hotline = computed(() => props.site.brand.hotline.trim())
const technicalHotline = computed(() => props.site.brand.technicalHotline?.trim() ?? '')
const footerLogo = computed(() => props.site.brand.logoFooter || props.site.brand.logo || '')
const CMS_UPLOAD_PHOTO = /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i
const footerLogoThumbnailFailed = ref(false)
const footerLogoUnavailable = ref(false)

const footerLogoSrc = computed(() => {
  const src = footerLogo.value
  if (CMS_UPLOAD_PHOTO.test(src) && !footerLogoThumbnailFailed.value) return thumbUrl(src, 520, 426, 3)
  return src
})
function onFooterLogoError() {
  if (CMS_UPLOAD_PHOTO.test(footerLogo.value) && !footerLogoThumbnailFailed.value) {
    footerLogoThumbnailFailed.value = true
    return
  }
  footerLogoUnavailable.value = true
}
watch(footerLogo, () => {
  footerLogoThumbnailFailed.value = false
  footerLogoUnavailable.value = false
})
function trustBadgeSrc(src: string): string {
  return CMS_UPLOAD_PHOTO.test(src) ? thumbUrl(src, 400, 152, 3) : src
}
function socialKind(label: string, href: string): 'facebook' | 'youtube' | 'other' {
  const value = `${label} ${href}`.toLocaleLowerCase('en')
  if (value.includes('facebook') || value.includes('fb.com')) return 'facebook'
  if (value.includes('youtube') || value.includes('youtu.be')) return 'youtube'
  return 'other'
}
const footer = computed(() => props.site.footer)
const { generic: footerHref, external: isExternalLink } = usePublicLinks()

function telHrefFor(value: string): string {
  return `tel:${value.replace(/[^+\d]/g, '')}`
}

const telHref = computed(() => telHrefFor(hotline.value))
</script>

<template>
  <footer class="border-t border-navy-900 bg-navy-950 pb-7 text-ftr-text">
    <div
      class="mx-auto grid w-full max-w-(--container-page) gap-8 px-4 max-[359px]:px-3 tb:px-(--spacing-gutter) pt-12 max-tb:gap-0 max-tb:pt-6
             grid-cols-1
             tb:grid-cols-2
             nav:grid-cols-[290px_minmax(280px,1fr)_minmax(460px,1.3fr)]
             wd:grid-cols-[290px_minmax(280px,1fr)_minmax(460px,1.3fr)]"
    >
      <!-- Cột 1: Biểu trưng thương hiệu -->
      <div class="flex flex-col items-center gap-4 max-tb:flex-row max-tb:justify-between tb:items-start">
        <a class="block w-full max-w-[300px] max-tb:max-w-[132px]" :href="footerHref('/')" :aria-label="`${brandName} - Trang chủ`">
          <img
            v-if="footerLogo && !footerLogoUnavailable"
            :src="footerLogoSrc"
            :alt="brandName"
            width="520"
            height="426"
            class="block h-auto w-full object-contain tb:max-w-[280px] wd:max-w-[300px]"
            @error="onFooterLogoError"
          >
          <span v-else class="text-lg font-black text-white">{{ brandName }}</span>
        </a>
        <a
          v-if="footer?.trustBadge"
          class="inline-flex min-h-11 max-w-[136px] items-center"
          :href="footer.trustBadge.href"
          target="_blank"
          rel="noopener noreferrer"
        >
          <img :src="trustBadgeSrc(footer.trustBadge.image)" alt="Đã thông báo Bộ Công Thương" width="400" height="152" class="block h-auto w-full object-contain">
        </a>
      </div>

      <!-- Cột 2: Pháp nhân doanh nghiệp và 2 đường dây nóng -->
      <div>
        <h2 class="foot-head mb-4 text-[16px] font-extrabold text-gold-400">
          <button type="button" class="foot-toggle" :aria-expanded="footOpen.company" @click="footOpen.company = !footOpen.company">{{ sentenceCase(footer?.companyName || 'Thông tin doanh nghiệp') }}</button>
        </h2>
        <span aria-hidden="true" class="-mt-2 mb-4 block h-0.5 w-9 bg-gold-500 max-tb:hidden" />

        <EditorialRichText v-if="footer?.contactText?.html" :content="footer.contactText" class="cms-content-on-dark mt-3 text-[14.5px] leading-[1.68]" :class="footOpen.company ? '' : 'max-tb:hidden'" />

        <div v-if="hotline || technicalHotline" class="mt-4 grid grid-cols-2 gap-2.5 max-tb:mb-3 max-tb:mt-1">
          <a
            v-if="hotline"
            class="flex min-w-0 items-start gap-2.5 rounded-btn border border-[#bfdbfe]/20 border-l-[3px]
                   border-l-gold-400 bg-white/[.045] px-3 py-2.5 text-[14.5px] leading-[1.4] no-underline
                   transition-colors hover:bg-white/[.09]"
            :href="telHref"
          >
            <svg class="mt-0.5 h-4 w-4 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.5 3.5 9 3l2 5-2 1.7a14 14 0 0 0 5.3 5.3L16 13l5 2-.5 2.5c-.3 1.5-1.7 2.5-3.2 2.2C10.8 18.5 5.5 13.2 4.3 6.7 4 5.2 5 3.8 6.5 3.5Z" /></svg>
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
            <svg class="mt-0.5 h-4 w-4 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a2 2 0 0 0-2 2v2.17a2 2 0 0 1-.59 1.42L7.59 9.41A2 2 0 0 0 7 10.83V13a2 2 0 0 0 2 2h.17a2 2 0 0 1 1.42.59l1.82 1.82a2 2 0 0 0 1.42.59H16a2 2 0 0 0 2-2v-2.17a2 2 0 0 1 .59-1.42L20 10.41" /></svg>
            <span class="flex min-w-0 flex-col gap-0.5">
              <small class="text-[11.5px] leading-[1.3] text-ftr-text">Hỗ trợ kỹ thuật</small>
              <strong class="whitespace-nowrap text-[15px] font-bold leading-[1.3] text-white">{{ technicalHotline }}</strong>
            </span>
          </a>
        </div>
      </div>

      <div class="grid min-w-0 max-tb:gap-0 grid-cols-1 gap-8 min-[768px]:grid-cols-[minmax(350px,1fr)_minmax(0,1fr)] min-[768px]:gap-12 tb:col-span-2 nav:col-span-1">
        <!-- Chính sách nằm cùng cột nội dung liên hệ như footer PHP. -->
        <nav v-if="footer?.policies.length" aria-label="Chính sách và hỗ trợ khách hàng">
          <h2 class="foot-head mb-4 text-[16px] font-extrabold text-white">
            <button type="button" class="foot-toggle" :aria-expanded="footOpen.policies" @click="footOpen.policies = !footOpen.policies">Chính sách &amp; Hỗ trợ khách hàng</button>
          </h2>
          <span aria-hidden="true" class="-mt-2 mb-4 block h-0.5 w-9 bg-gold-500 max-tb:hidden" />
          <ul class="m-0 list-none space-y-0.5 p-0" :class="footOpen.policies ? '' : 'max-tb:hidden'">
            <li v-for="policy in footer ? footer.policies : []" :key="policy.href">
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

        <!-- Địa điểm hiển thị thành danh sách phẳng, như .content_cn của PHP. -->
        <div v-if="footer?.locations.length">
          <h2 class="foot-head mb-4 text-[16px] font-extrabold text-white">
            <button type="button" class="foot-toggle" :aria-expanded="footOpen.locations" @click="footOpen.locations = !footOpen.locations">Hệ thống kho &amp; điểm giao dịch</button>
          </h2>
          <span aria-hidden="true" class="-mt-2 mb-4 block h-0.5 w-9 bg-gold-500 max-tb:hidden" />
          <div class="space-y-3" :class="footOpen.locations ? '' : 'max-tb:hidden'">
          <address
            v-for="location in footer.locations"
            :key="location.name + location.address"
            class="m-0 not-italic"
          >
            <component
              :is="location.mapUrl ? 'a' : 'div'"
              v-bind="location.mapUrl ? { href: location.mapUrl, target: '_blank', rel: 'noopener noreferrer' } : {}"
              class="block text-inherit no-underline transition-colors"
              :class="location.mapUrl ? 'hover:text-gold-400' : ''"
            >
              <span class="flex items-center gap-2 text-[14.5px] font-bold leading-[1.4] text-white">
                <svg class="h-4 w-4 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
                {{ location.name }}
              </span>
              <span class="mt-1.5 flex items-start gap-1.5 text-[13px] leading-[1.55] text-ftr-text">
                <svg class="mt-1 h-4 w-4 flex-none text-gold-400/80" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
                {{ location.address }}
              </span>
            </component>
            </address>
          </div>
        </div>
      </div>
    </div>

    <!-- Thanh bản quyền & Kết nối mạng xã hội -->
    <div class="mt-10 border-t border-white/10 pt-5 max-tb:mt-2">
      <div class="mx-auto flex w-full max-w-(--container-page) flex-col items-start justify-between gap-3 px-4 max-[359px]:px-3 tb:flex-row tb:items-center tb:px-(--spacing-gutter) text-[13px]">
        <p class="m-0 text-ftr-text/80">© {{ new Date().getFullYear() }} {{ brandName }}.</p>
        <ul v-if="footer?.socialLinks.length" class="m-0 flex list-none flex-wrap items-center gap-x-5 gap-y-1 p-0 tb:pr-14" aria-label="Kết nối mạng xã hội">
          <li v-for="social in footer.socialLinks" :key="social.href">
            <a
              class="inline-flex min-h-11 items-center text-[15px] font-semibold leading-[1.7] text-ftr-text no-underline transition-colors hover:text-gold-400"
              :href="social.href"
              :aria-label="social.label || 'Mạng xã hội'"
              :title="social.label || 'Mạng xã hội'"
              target="_blank"
              rel="noopener noreferrer"
            >
              <svg v-if="socialKind(social.label, social.href) === 'facebook'" class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073c0 6.019 4.388 11.022 10.125 11.927v-8.432H7.078v-3.495h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.49 0-1.956.931-1.956 1.886v2.268h3.328l-.532 3.495h-2.796V24C19.612 23.095 24 18.092 24 12.073Z" />
              </svg>
              <svg v-else-if="socialKind(social.label, social.href) === 'youtube'" class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.51 3.55 12 3.55 12 3.55s-7.51 0-9.38.51A3.02 3.02 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14c1.87.51 9.38.51 9.38.51s7.51 0 9.38-.51a3.02 3.02 0 0 0 2.12-2.14A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8ZM9.55 15.62V8.38L15.82 12l-6.27 3.62Z" />
              </svg>
              <UIcon v-else name="i-lucide-share-2" class="size-5" aria-hidden="true" />
            </a>
          </li>
        </ul>
      </div>
    </div>
  </footer>

  <SiteFloatingContact :site="site" />
  <MobileTabBar :site="site" />
</template>

<style scoped>
.foot-toggle { all: unset; }
@media (max-width: 820px) {
  .foot-head { margin: 0; border-top: 1px solid rgb(255 255 255 / 12%); }
  .foot-toggle { display: flex; width: 100%; min-height: 48px; align-items: center; justify-content: space-between; gap: 12px; cursor: pointer; }
  .foot-toggle::after { content: '+'; flex: none; color: var(--color-gold-400); font-size: 20px; font-weight: 500; }
  .foot-toggle[aria-expanded="true"]::after { content: '−'; }
  .foot-toggle:focus-visible { outline: 2px solid var(--color-gold-400); outline-offset: 2px; }
}
</style>

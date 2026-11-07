import { publicPhoneHref } from '#shared/contact-links'
import type { PublicSitePayload } from '#shared/contracts/public-site'

interface ContactLinks { hotline: string; phoneHref: string; zaloHref: string }

function usePublicContactState() {
  return useState<ContactLinks>('pts-contact-links', () => ({ hotline: '', phoneHref: '', zaloHref: '' }))
}

/** The footer holds the site data on every page and shares its contact channels with the phone bars. */
export function publishPublicContactLinks(site: PublicSitePayload) {
  const hotline = site.brand.hotline.trim()
  usePublicContactState().value = {
    hotline,
    phoneHref: publicPhoneHref(hotline),
    zaloHref: site.brand.zaloHref
      || site.footer?.socialLinks?.find((item) => /zalo\.me\//i.test(item.href) || item.label.trim().toLowerCase() === 'zalo')?.href
      || '',
  }
}

export function usePublicContactLinks() {
  const state = usePublicContactState()
  return {
    hotline: computed(() => state.value.hotline),
    phoneHref: computed(() => state.value.phoneHref),
    zaloHref: computed(() => state.value.zaloHref),
  }
}

/** A page with its own bottom action bar (product detail) takes the place of the tab bar. */
export function useMobileActionBar() {
  return useState('pts-mobile-action-bar', () => false)
}

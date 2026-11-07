import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

export const PUBLIC_SITE_ASYNC_KEY = 'site'

export const publicSiteFallback: PublicSitePayload = {
  schemaVersion: 1,
  brand: { name: 'Phúc Thịnh Solar', hotline: '' },
  productCategories: [],
}

/**
 * The site shell owns the single SSR request for the public site DTO.
 * The fallback contains shell identity only; it is not marketing content.
 */
export async function usePublicSite() {
  const { data, error, pending, refresh } = await useAsyncData<unknown>(
    PUBLIC_SITE_ASYNC_KEY,
    () => $fetch<unknown>('/_api/site'),
    {
      default: () => null,
      dedupe: 'defer',
      server: true,
    },
  )

  const hasSiteData = computed(() => isPublicSitePayload(data.value))
  const hasError = computed(() => Boolean(error.value) || (!pending.value && data.value !== null && !hasSiteData.value))
  const errorMessage = computed(() => error.value
    ? 'Không thể tải dữ liệu website từ CMS local.'
    : 'Dữ liệu website từ CMS không đúng hợp đồng public.'
  )

  return {
    site: computed<PublicSitePayload>(() => {
      const value = data.value
      return isPublicSitePayload(value) ? value : publicSiteFallback
    }),
    pending,
    hasSiteData,
    hasError,
    errorMessage,
    refresh,
  }
}

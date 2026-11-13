import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { canonicalPublicUrl, isSizedSocialImage, PUBLIC_SEO_SITE_NAME, PUBLIC_SOCIAL_IMAGE_SIZE, publicSocialImageUrl } from '#shared/public-seo'

type SocialMetaInput = {
  enabled?: MaybeRefOrGetter<boolean>
  title: MaybeRefOrGetter<string>
  description?: MaybeRefOrGetter<string>
  canonicalPath?: MaybeRefOrGetter<string>
  image?: MaybeRefOrGetter<string | undefined>
  type?: MaybeRefOrGetter<'website' | 'article' | 'product'>
}

/** Adds canonical/Open Graph/Twitter URLs using the configured trusted site origin. */
export function usePublicSocialMeta(input: SocialMetaInput): void {
  const route = useRoute()
  const config = useRuntimeConfig()
  const enabled = computed(() => input.enabled === undefined || toValue(input.enabled))
  const title = computed(() => toValue(input.title).trim() || PUBLIC_SEO_SITE_NAME)
  const description = computed(() => toValue(input.description ?? '').trim() || PUBLIC_SEO_SITE_NAME)
  const canonicalPath = computed(() => input.canonicalPath === undefined ? route.path : toValue(input.canonicalPath))
  const image = computed(() => toValue(input.image ?? ''))
  const type = computed(() => input.type === undefined ? 'website' : toValue(input.type))
  const canonical = computed(() => enabled.value ? canonicalPublicUrl(config.public.canonicalSiteOrigin, canonicalPath.value) : null)
  const absoluteImage = computed(() => enabled.value ? publicSocialImageUrl(config.public.canonicalSiteOrigin, image.value) : null)

  useHead({
    link: computed(() => canonical.value ? [{ rel: 'canonical', href: canonical.value }] : []),
  })
  useSeoMeta({
    // Nuxt's `ogType` type leaves out Open Graph's `product`, which is valid for product pages: cast it through.
    ogType: computed(() => enabled.value ? type.value as 'article' | 'website' : undefined),
    ogSiteName: computed(() => enabled.value ? PUBLIC_SEO_SITE_NAME : undefined),
    ogTitle: computed(() => enabled.value ? title.value : undefined),
    ogDescription: computed(() => enabled.value ? description.value : undefined),
    ogUrl: computed(() => canonical.value ?? undefined),
    ogImage: computed(() => absoluteImage.value ?? undefined),
    ogImageWidth: computed(() => isSizedSocialImage(absoluteImage.value) ? PUBLIC_SOCIAL_IMAGE_SIZE.width : undefined),
    ogImageHeight: computed(() => isSizedSocialImage(absoluteImage.value) ? PUBLIC_SOCIAL_IMAGE_SIZE.height : undefined),
    ogImageAlt: computed(() => absoluteImage.value ? title.value : undefined),
    twitterCard: computed(() => enabled.value ? 'summary_large_image' : undefined),
    twitterTitle: computed(() => enabled.value ? title.value : undefined),
    twitterDescription: computed(() => enabled.value ? description.value : undefined),
    twitterImage: computed(() => absoluteImage.value ?? undefined),
  })
}

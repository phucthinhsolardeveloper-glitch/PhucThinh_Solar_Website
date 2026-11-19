import type { PublicCatalogPricing } from './contracts/public-catalog.ts'
import type { PublicProductMerchant } from './contracts/public-product-detail.ts'

/**
 * schema.org Offer for a product page, or null when no public price is shown. The shop sells new equipment only.
 * Availability is optional for Product and merchant listing snippets, so it is stated only when staff verified it in
 * Admin (the field the verified Merchant feed uses); a price alone does not prove the item ships now.
 */
export function productOfferNode(pricing: PublicCatalogPricing | null | undefined, merchant: PublicProductMerchant | undefined, canonicalUrl: string, siteOrigin: string): Record<string, unknown> | null {
  if (pricing?.mode !== 'price') return null
  const price = pricing.salePrice ?? pricing.regularPrice
  if (!price) return null
  return {
    '@type': 'Offer',
    url: canonicalUrl,
    priceCurrency: pricing.currency,
    price,
    itemCondition: 'https://schema.org/NewCondition',
    ...(merchant?.availability === 'in_stock' ? { availability: 'https://schema.org/InStock' } : {}),
    seller: { '@id': `${siteOrigin}/#organization` },
  }
}

/** Manufacturer identifiers that passed the backend GTIN/MPN checks; nothing is derived from the internal SKU. */
export function productIdentifierFields(merchant: PublicProductMerchant | undefined): Record<string, string> {
  const fields: Record<string, string> = {}
  if (merchant?.gtin) fields.gtin = merchant.gtin
  if (merchant?.mpn) fields.mpn = merchant.mpn
  return fields
}

export const PRODUCT_CATEGORY_PUBLIC_PREFIX = '/danh-muc-san-pham/'

export function isRuntimeFlagEnabled(value: unknown): boolean {
  return value === true || value === '1' || value === 'true'
}

export function productDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9-]+$/.test(slug)) throw new Error('Invalid product slug')
  return `/san-pham/${slug}`
}

export function brandDetailPublicPath(slug: string): string {
  if (!/^[a-z0-9-]+$/.test(slug)) throw new Error('Invalid brand slug')
  return `/thuong-hieu/${slug}/`
}

export function productCategoryPublicPath(slugChain: string | readonly string[]): string {
  const chain = Array.isArray(slugChain) ? slugChain.join('/') : slugChain
  if (typeof chain !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/.test(chain)) {
    throw new Error('Invalid product category path')
  }
  return `${PRODUCT_CATEGORY_PUBLIC_PREFIX}${chain}/`
}

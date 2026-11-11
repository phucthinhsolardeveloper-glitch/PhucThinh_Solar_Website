/** Signed Admin link to a product page as saved (also while hidden); it works for 15 minutes. */
export interface ProductPreviewQuery { product: string, expires: string, signature: string }

export function productPreviewQuery(product: unknown, query: Record<string, unknown>): ProductPreviewQuery | null {
  const { expires, signature } = query
  return typeof product === 'string' && /^[1-9]\d{0,18}$/.test(product)
    && typeof expires === 'string' && /^\d{10}$/.test(expires)
    && typeof signature === 'string' && /^[a-f0-9]{64}$/.test(signature)
    ? { product, expires, signature }
    : null
}

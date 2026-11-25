const POSITIVE_PAGE = /^[1-9][0-9]*$/

function isPage(value: unknown): value is string {
  if (typeof value !== 'string' || !POSITIVE_PAGE.test(value)) return false
  const page = Number(value)
  return Number.isSafeInteger(page) && page <= 10_000
}

/** Accept the `page` query and the older `p` alias still found in links. */
export function publicPageQueryValue(page: unknown, legacyPage: unknown): string {
  if (isPage(page)) return page
  if (isPage(legacyPage)) return legacyPage
  return ''
}

/** A page past the end of the list does not exist. Page 1 of an empty list still does. */
export function isPageBeyondList(page: number, total: number, perPage: number): boolean {
  return page > 1 && perPage > 0 && page > Math.ceil(total / perPage)
}

/** A refused filter value or an unknown address is "not found"; anything else is a failing data source. */
export function publicListErrorStatus(status: unknown): 404 | 502 {
  return status === 400 || status === 404 ? 404 : 502
}

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

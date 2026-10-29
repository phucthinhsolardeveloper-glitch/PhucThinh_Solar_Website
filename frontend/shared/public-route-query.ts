/** Map only the query state supported by the legacy product search route. */
export function legacySearchFilterQuery(query: Record<string, unknown>): Record<string, string> {
  const output: Record<string, string> = {}
  const value = (key: string) => typeof query[key] === 'string' ? (query[key] as string).trim() : ''

  const page = value('page')
  if (/^[1-9][0-9]*$/.test(page) && Number(page) <= 10000) output.p = page

  const keyword = value('keyword')
  if (keyword !== '' && [...keyword].length <= 100 && !/[\u0000-\u001F\u007F]/.test(keyword)) {
    output.keyword = keyword
  }

  return output
}

function legacyPublicPage(query: Record<string, unknown>): Record<string, string> {
  const page = typeof query.p === 'string' ? query.p.trim() : ''
  return /^[1-9][0-9]*$/.test(page) && Number(page) <= 1000000 ? { p: page } : {}
}

function legacyEditorialCategory(query: Record<string, unknown>): Record<string, string> {
  const category = typeof query.category === 'string' ? query.category.trim() : ''
  return /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(category) ? { category } : {}
}

/** Preserve only the published news hub filters understood by PHP. */
export function legacyNewsFilterQuery(query: Record<string, unknown>): Record<string, string> {
  const output: Record<string, string> = { ...legacyPublicPage(query), ...legacyEditorialCategory(query) }
  const q = typeof query.q === 'string' ? query.q.trim() : ''
  if (q !== '' && [...q].length <= 120 && !/[\u0000-\u001F\u007F]/.test(q)) output.q = q
  return output
}

/** Preserve the project hub's public p/category query contract. */
export function legacyProjectsFilterQuery(query: Record<string, unknown>): Record<string, string> {
  return { ...legacyPublicPage(query), ...legacyEditorialCategory(query) }
}

/** Gallery and video hubs accept only the public p pagination parameter. */
export function legacyMediaPageQuery(query: Record<string, unknown>): Record<string, string> {
  return legacyPublicPage(query)
}

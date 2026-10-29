const MAX_PUBLIC_PAGE = 10000

export function projectCategoryPage(value: unknown): number {
  if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value)) return 1
  const page = Number(value)
  return Number.isSafeInteger(page) && page <= MAX_PUBLIC_PAGE ? page : 1
}

/** PHP canonicalizes project list categories to a bare slug without a slash. */
export function projectCategoryCanonicalPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid project category slug')
  return `/${slug}`
}

/** Preserve only the legacy PHP pagination parameter on category URL fallbacks. */
export function legacyProjectCategoryQuery(query: Record<string, unknown>): Record<string, string> {
  const page = typeof query.p === 'string' ? query.p.trim() : ''
  return /^[1-9][0-9]*$/.test(page) && Number(page) <= 1000000 ? { p: page } : {}
}

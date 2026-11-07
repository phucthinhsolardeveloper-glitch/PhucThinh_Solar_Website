const MAX_PUBLIC_PAGE = 10000

export function projectCategoryPage(value: unknown): number {
  if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value)) return 1
  const page = Number(value)
  return Number.isSafeInteger(page) && page <= MAX_PUBLIC_PAGE ? page : 1
}

/** Project list categories are canonical as a bare slug without a slash. */
export function projectCategoryCanonicalPath(slug: string): string {
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) throw new Error('Invalid project category slug')
  return `/${slug}`
}

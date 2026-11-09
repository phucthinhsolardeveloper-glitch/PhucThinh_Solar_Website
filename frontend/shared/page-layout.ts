/** Section order of Home and About as published in Admin ("Bố cục trang"); mirrors PageLayouts::PAGES. */
export const PAGE_SECTIONS = {
  home: ['hero', 'catalog', 'energy', 'campaigns', 'solutions', 'process', 'dealer', 'about', 'contact'],
  about: ['story', 'history', 'people', 'trust', 'brands', 'compass', 'certificates', 'contact'],
} as const

export type PageLayoutKey = keyof typeof PAGE_SECTIONS
export interface PageLayoutSection<P extends PageLayoutKey = PageLayoutKey> { type: (typeof PAGE_SECTIONS)[P][number]; variant: string }

/**
 * The sections to render, in order. Unknown types are skipped; a missing or broken layout means the
 * page as built (every section, default look), so an older backend still renders the full page.
 */
export function pageLayout<P extends PageLayoutKey>(page: P, value: unknown): PageLayoutSection<P>[] {
  const known = PAGE_SECTIONS[page] as readonly string[]
  if (!Array.isArray(value)) return known.map((type) => ({ type, variant: 'default' })) as PageLayoutSection<P>[]
  const seen = new Set<string>()
  const out: PageLayoutSection<P>[] = []
  for (const row of value) {
    if (!row || typeof row !== 'object') continue
    const { type, variant } = row as { type?: unknown, variant?: unknown }
    if (typeof type !== 'string' || !known.includes(type) || seen.has(type)) continue
    seen.add(type)
    out.push({ type, variant: typeof variant === 'string' && /^[a-z-]{1,20}$/.test(variant) ? variant : 'default' } as PageLayoutSection<P>)
  }
  return out
}

/** Signed link to a layout draft. */
export interface LayoutDraftQuery { layout: string, expires: string, signature: string }

export function layoutDraftQuery(layout: unknown, query: Record<string, unknown>): LayoutDraftQuery | null {
  const { expires, signature } = query
  return typeof layout === 'string' && /^[1-9]\d{0,18}$/.test(layout)
    && typeof expires === 'string' && /^\d{10}$/.test(expires)
    && typeof signature === 'string' && /^[a-f0-9]{64}$/.test(signature)
    ? { layout, expires, signature }
    : null
}

import type { PublicSanitizedRichText } from './contracts/editorial-rich-text.ts'

export interface EditorialTocItem { id: string; label: string; level: 1 | 2 | 3 }

export interface EditorialTocFragmentOptions {
  levels?: readonly (1 | 2 | 3)[]
  idPrefix?: string
}

type ProcessedFragments<T extends readonly PublicSanitizedRichText[]> = {
  [K in keyof T]: PublicSanitizedRichText
}

export function buildEditorialToc(content: PublicSanitizedRichText): {
  content: PublicSanitizedRichText
  toc: EditorialTocItem[]
} {
  const result = buildEditorialTocForFragments([content], {})
  return { content: result.contents[0] ?? content, toc: result.toc }
}

export function buildEditorialTocForFragments<T extends readonly PublicSanitizedRichText[]>(
  fragments: T,
  options: EditorialTocFragmentOptions = {},
): { contents: ProcessedFragments<T>; toc: EditorialTocItem[] } {
  const toc: EditorialTocItem[] = []
  const usedIds = new Set<string>(['toc-content'])
  for (const fragment of fragments) {
    const existingIds = [...fragment.html.matchAll(/\s+id=(?:"([^"]*)"|'([^']*)')/gi)]
      .map((match) => match[1] ?? match[2] ?? '')
      .filter(Boolean)
    for (const id of existingIds) usedIds.add(id)
  }

  const includedLevels = new Set(options.levels ?? [1, 2, 3])
  let headingIndex = 0
  const contents = fragments.map((fragment) => {
    const html = fragment.html.replace(/<h([1-3])\b([^>]*)>([\s\S]*?)<\/h\1>/gi, (heading, rawLevel: string, attributes: string, inner: string) => {
      const level = Number(rawLevel) as 1 | 2 | 3
      if (!includedLevels.has(level)) return heading

      headingIndex++
      const label = decodeHeadingText(inner)
      if (!label) return heading

      const idMatch = attributes.match(/\s+id=(?:"([^"]*)"|'([^']*)')/i)
      const explicitId = idMatch?.[1] ?? idMatch?.[2] ?? ''
      let id = /^[A-Za-z][A-Za-z0-9_.:-]*$/.test(explicitId) ? explicitId : ''
      if (!id) {
        const slug = legacyHeadingSlug(label)
        const base = options.idPrefix
          ? `${options.idPrefix}-${slug || 'heading'}-${headingIndex}`
          : (slug || 'section')
        id = base
        let suffix = 1
        while (usedIds.has(id)) id = `${base}_${suffix++}`
        usedIds.add(id)
      }

      toc.push({ id, label, level })
      if (id === explicitId) return heading
      const withoutId = attributes.replace(/\s+id=(?:"[^"]*"|'[^']*')/i, '')
      return `<h${rawLevel}${withoutId} id="${id}">${inner}</h${rawLevel}>`
    })
    return { ...fragment, html }
  }) as ProcessedFragments<T>

  return { contents, toc }
}

function legacyHeadingSlug(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/gi, 'd')
    .toLowerCase()
    .replace(/[\u0060~!@#|$%^&*()+=,.\/?<>':;"_]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-{2,}/g, '-')
    .replace(/^-|-$/g, '')
    .replace(/[^a-z0-9-]/g, '')
}

function decodeHeadingText(value: string): string {
  return value
    .replace(/<[^>]*>/g, '')
    .replace(/&(#x[\da-f]+|#\d+|amp|lt|gt|quot|apos|nbsp);/gi, (entity, code: string) => {
      if (code[0] === '#') {
        const point = code[1]?.toLowerCase() === 'x' ? Number.parseInt(code.slice(2), 16) : Number.parseInt(code.slice(1), 10)
        return Number.isFinite(point) && point > 0 && point <= 0x10ffff ? String.fromCodePoint(point) : ''
      }
      return ({ amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' } as Record<string, string>)[code.toLowerCase()] ?? entity
    })
    .replace(/\s+/g, ' ')
    .trim()
}

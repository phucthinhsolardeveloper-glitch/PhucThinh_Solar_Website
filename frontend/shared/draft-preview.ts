/** Signed link to an unpublished draft: Admin makes it, it works for 15 minutes. */
export interface DraftPreviewQuery { revision: string, expires: string, signature: string }

export const DRAFT_PREVIEW_KINDS = { 'giai-phap': 'solution', 'du-an': 'project', 'chinh-sach': 'policy' } as const

export function draftPreviewQuery(revision: unknown, query: Record<string, unknown>): DraftPreviewQuery | null {
  const { expires, signature } = query
  return typeof revision === 'string' && /^[1-9]\d{0,18}$/.test(revision)
    && typeof expires === 'string' && /^\d{10}$/.test(expires)
    && typeof signature === 'string' && /^[a-f0-9]{64}$/.test(signature)
    ? { revision, expires, signature }
    : null
}

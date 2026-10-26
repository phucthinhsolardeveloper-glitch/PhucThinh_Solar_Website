export interface PublicSanitizedRichText {
  format: 'sanitized-html'
  html: string
  sanitizedBy: 'pts_public_richtext'
  sanitizerVersion: 1
}

const MAX_RICH_TEXT_LENGTH = 512_000
const UNSAFE_MARKUP = /<(?:script|style|iframe|object|embed|svg|math|form|input|button|textarea|select|video|audio|source|track)\b|\bon[a-z][\w:-]*\s*=|(?:javascript|vbscript|data):/i

export function isPublicSanitizedRichText(value: unknown): value is PublicSanitizedRichText {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return false
  const record = value as Record<string, unknown>
  return record.format === 'sanitized-html'
    && typeof record.html === 'string'
    && record.html.length <= MAX_RICH_TEXT_LENGTH
    && !/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/.test(record.html)
    && !UNSAFE_MARKUP.test(record.html)
    && record.sanitizedBy === 'pts_public_richtext'
    && record.sanitizerVersion === 1
}

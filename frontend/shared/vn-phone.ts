/** The same rule as Laravel's LeadSubmission::normalizePhone: the 10 digits of a Vietnamese mobile number, or null. */
export function normalizeVnPhone(input: string): string | null {
  const text = input.trim()
  if (text.length > 24 || !/^\+?\d(?:[\d .\u00a0-]*\d)?$/.test(text)) return null
  let digits = text.replace(/\D/g, '')
  if (text.startsWith('+') && !digits.startsWith('84')) return null
  if (digits.length === 11 && digits.startsWith('84')) digits = `0${digits.slice(2)}`
  return /^0[35789]\d{8}$/.test(digits) ? digits : null
}

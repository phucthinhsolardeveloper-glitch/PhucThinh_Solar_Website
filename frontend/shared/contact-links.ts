export function publicPhoneHref(value: string): string {
  const digits = value.replace(/\D/g, '')
  return digits.length >= 7 && digits.length <= 15 ? `tel:${digits}` : ''
}

export function publicEmailHref(value: string): string {
  const email = value.trim()
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? `mailto:${email}` : ''
}

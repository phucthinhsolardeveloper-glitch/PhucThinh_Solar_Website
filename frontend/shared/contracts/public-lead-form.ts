export interface PublicLeadFormSecurity {
  schemaVersion: 1
  csrfToken: string
  cities: Array<{ id: number; name: string }>
  recaptcha: { active: boolean; siteKey: string }
}
export interface PublicLeadFormResult { ok: true; message: string }

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function safeText(value: unknown): value is string { return typeof value === 'string' && value.trim() !== '' && !/[\u0000-\u001F\u007F]/.test(value) }

export function isPublicLeadFormSecurity(value: unknown): value is PublicLeadFormSecurity {
  return record(value) && value.schemaVersion === 1 && typeof value.csrfToken === 'string' && /^[a-f0-9]{64}$/.test(value.csrfToken)
    && Array.isArray(value.cities) && value.cities.every((city) => record(city) && typeof city.id === 'number' && Number.isSafeInteger(city.id) && city.id > 0 && safeText(city.name))
    && record(value.recaptcha) && typeof value.recaptcha.active === 'boolean' && typeof value.recaptcha.siteKey === 'string'
    && (!value.recaptcha.active || /^[A-Za-z0-9_-]{20,100}$/.test(value.recaptcha.siteKey))
}
export function isPublicLeadFormResult(value: unknown): value is PublicLeadFormResult {
  return record(value) && value.ok === true && safeText(value.message)
}

import { isPublicLeadFormResult } from '../../../shared/contracts/public-lead-form'
import { fetchPhpSessionResponse } from '../../utils/migration-cart'

type LeadKind = 'contact' | 'dealer' | 'product'
type LeadBody = {
  kind?: LeadKind
  csrfToken?: string
  recaptchaToken?: string
  data?: Record<string, unknown>
}

function safeField(value: unknown, max: number, required = false): string | null {
  if (typeof value !== 'string') return required ? null : ''
  const text = value.trim()
  if ((required && !text) || text.length > max || /[\u0000-\u001F\u007F]/.test(text)) return null
  return text
}
function safeIdText(value: unknown, required = false): string | null {
  if (value === '' || value === undefined || value === null) return required ? null : ''
  const text = typeof value === 'number' && Number.isSafeInteger(value) ? String(value) : typeof value === 'string' ? value.trim() : ''
  if (!/^[1-9][0-9]*$/.test(text)) return null
  return text
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const body = await readBody<LeadBody>(event)
  const kind = body?.kind
  if (kind !== 'contact' && kind !== 'dealer' && kind !== 'product') throw createError({ statusCode: 400, statusMessage: 'Invalid lead form kind' })
  if (typeof body.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(body.csrfToken)
    || typeof body.recaptchaToken !== 'string' || body.recaptchaToken.length > 4096) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid lead form security data' })
  }
  const source = body.data ?? {}
  const data = kind === 'contact'
    ? {
        fullname: safeField(source.fullname, 100, true),
        phone: safeField(source.phone, 20, true),
        email: safeField(source.email, 150),
        address: safeField(source.address, 255),
        subject: safeField(source.subject, 180),
        content: safeField(source.content, 5000, true),
      }
    : kind === 'dealer'
      ? {
          fullname: safeField(source.fullname, 100, true),
          phone: safeField(source.phone, 20, true),
          email: safeField(source.email, 150, true),
          address: safeField(source.address, 180, true),
          company: safeField(source.company, 180, true),
        }
      : {
          fullname: safeField(source.fullname, 100, true),
          phone: safeField(source.phone, 20, true),
          email: safeField(source.email, 150),
          loaihinh: safeField(source.loaihinh, 180),
          congsuat: safeField(source.congsuat, 255),
          product_id: safeIdText(source.product_id, true),
          profile_key: safeField(source.profile_key, 32, true),
          need_code: safeField(source.need_code, 64),
          details: safeField(source.details, 1000),
          city_id: safeIdText(source.city_id),
          ward_id: safeIdText(source.ward_id),
        }
  if (Object.values(data).some((value) => value === null)) throw createError({ statusCode: 400, statusMessage: 'Invalid lead form fields' })

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_lead_form.php', {
      method: 'POST',
      headers: { 'content-type': 'application/json;charset=UTF-8' },
      body: JSON.stringify({ kind, csrfToken: body.csrfToken, recaptchaToken: body.recaptchaToken, data }),
    })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'Lead form submission unavailable' })
  }
  const payload: any = await response.json().catch(() => null)
  if (!response.ok) {
    const message = payload?.error?.message
    throw createError({ statusCode: response.status, statusMessage: typeof message === 'string' && message ? message : 'Lead form submission failed' })
  }
  if (!isPublicLeadFormResult(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid lead form result contract' })
  return payload
})

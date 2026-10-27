import { fetchPhpSessionResponse } from '../../utils/migration-cart'
import { migrationPhpEndpoint } from '../../utils/migration-api'

type CheckoutBody = {
  csrfToken?: string
  checkoutToken?: string
  fullname?: string
  phone?: string
  email?: string
  city?: number
  ward?: number
  address?: string
  requirements?: string
  payment?: number
}

function cleanText(value: unknown, max: number, required = true): string | null {
  if (typeof value !== 'string') return required ? null : ''
  const text = value.trim()
  if ((required && !text) || text.length > max || /[\u0000-\u001F\u007F]/.test(text)) return null
  return text
}
function positiveId(value: unknown): number | null {
  return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 ? value : null
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  const body = await readBody<CheckoutBody>(event)
  if (!body || typeof body.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(body.csrfToken)
    || typeof body.checkoutToken !== 'string' || !/^[a-f0-9]{64}$/.test(body.checkoutToken)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid checkout session' })
  }

  const fullname = cleanText(body.fullname, 150)
  const phone = cleanText(body.phone, 30)
  const email = cleanText(body.email, 190)
  const address = cleanText(body.address, 255)
  const requirements = cleanText(body.requirements ?? '', 1000, false)
  const city = positiveId(body.city)
  const ward = positiveId(body.ward)
  const payment = typeof body.payment === 'number' && Number.isSafeInteger(body.payment) && (body.payment === -1 || body.payment > 0) ? body.payment : null

  if (!fullname || !phone || !email || !address || requirements === null || city === null || ward === null || payment === null) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid checkout data' })
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) throw createError({ statusCode: 400, statusMessage: 'Invalid email' })

  const form = new URLSearchParams()
  form.set('form_csrf', body.csrfToken)
  form.set('checkout_token', body.checkoutToken)
  form.set('thanhtoan', '1')
  form.set('dataOrder[fullname]', fullname)
  form.set('dataOrder[phone]', phone)
  form.set('dataOrder[email]', email)
  form.set('dataOrder[city]', String(city))
  form.set('dataOrder[ward]', String(ward))
  form.set('dataOrder[address]', address)
  form.set('dataOrder[requirements]', requirements)
  form.set('dataOrder[payments]', String(payment))

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/gio-hang/', {
      method: 'POST',
      headers: { 'content-type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: form,
    })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP checkout unavailable' })
  }

  const location = response.headers.get('location')
  if (!location) {
    if (response.ok) throw createError({ statusCode: 502, statusMessage: 'Checkout returned an unexpected page response' })
    throw createError({ statusCode: 502, statusMessage: 'Checkout did not return a redirect' })
  }

  let redirect: URL
  try { redirect = new URL(location, migrationPhpEndpoint(event, '/gio-hang/')) }
  catch { throw createError({ statusCode: 502, statusMessage: 'Invalid checkout redirect' }) }

  const thanks = redirect.pathname.match(/^\/cam-on\/?$/)
  const token = redirect.searchParams.get('token')
  if (thanks && token && /^[a-f0-9]{48}$/.test(token)) return { ok: true, kind: 'success', token }

  const paymentHosts = new Set(['sandbox.vnpayment.vn', 'pay.vnpay.vn'])
  if (redirect.protocol === 'https:' && paymentHosts.has(redirect.hostname) && redirect.username === '' && redirect.password === '') {
    return { ok: true, kind: 'external', url: redirect.toString() }
  }

  if (/^\/lien-he\/?$/.test(redirect.pathname)) {
    throw createError({ statusCode: 409, statusMessage: 'Cart now requires a quotation' })
  }
  if (/^\/gio-hang\/?$/.test(redirect.pathname)) {
    throw createError({ statusCode: 422, statusMessage: 'Checkout validation failed' })
  }

  throw createError({ statusCode: 502, statusMessage: 'Unsupported checkout redirect' })
})

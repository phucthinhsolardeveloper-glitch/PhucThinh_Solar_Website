import { fetchPhpSessionResponse } from '../../utils/migration-cart'

type ReviewBody = {
  csrfToken?: string
  productId?: number
  star?: number
  title?: string
  content?: string
  fullname?: string
  phone?: string
  email?: string
  orderCode?: string
}
function clean(value: unknown, max: number, required = false): string | null {
  if (typeof value !== 'string') return required ? null : ''
  const text = value.trim()
  if ((required && !text) || text.length > max || /[\u0000-\u001F\u007F]/.test(text)) return null
  return text
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const body = await readBody<ReviewBody>(event)
  if (!body || typeof body.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(body.csrfToken)
    || typeof body.productId !== 'number' || !Number.isSafeInteger(body.productId) || body.productId <= 0
    || typeof body.star !== 'number' || !Number.isSafeInteger(body.star) || body.star < 1 || body.star > 5) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid review data' })
  }
  const data = {
    csrfToken: body.csrfToken,
    productId: body.productId,
    star: body.star,
    title: clean(body.title, 180, true),
    content: clean(body.content, 5000, true),
    fullname: clean(body.fullname, 100, true),
    phone: clean(body.phone, 20, true),
    email: clean(body.email, 150),
    orderCode: clean(body.orderCode, 40),
  }
  if (Object.values(data).some((value) => value === null)) throw createError({ statusCode: 400, statusMessage: 'Invalid review fields' })

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_review.php', {
      method: 'POST',
      headers: { 'content-type': 'application/json;charset=UTF-8' },
      body: JSON.stringify(data),
    })
  } catch { throw createError({ statusCode: 502, statusMessage: 'Review submission unavailable' }) }

  const payload: any = await response.json().catch(() => null)
  if (!response.ok) {
    const message = Array.isArray(payload?.errors) && typeof payload.errors[0] === 'string' ? payload.errors.join(' ') : 'Không thể gửi đánh giá.'
    throw createError({ statusCode: response.status, statusMessage: message })
  }
  if (!payload || payload.ok !== true || typeof payload.message !== 'string' || !payload.message.trim()) {
    throw createError({ statusCode: 502, statusMessage: 'Invalid review response' })
  }
  return { ok: true, message: payload.message }
})

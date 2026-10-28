import { fetchPhpSessionResponse } from '../../utils/migration-cart'

type CartAction = 'add' | 'update' | 'delete'
type CartMutationBody = {
  action?: CartAction
  csrfToken?: string
  productId?: number
  quantity?: number
  code?: string
  color?: number
  size?: number
}

const CODE = /^[a-f0-9]{32}$/
function boundedInt(value: unknown, min: number, max: number): number | null {
  return typeof value === 'number' && Number.isSafeInteger(value) && value >= min && value <= max ? value : null
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  const body = await readBody<CartMutationBody>(event)
  if (!body || typeof body.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(body.csrfToken)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid cart session' })
  }
  const action = body.action
  if (!action || !['add', 'update', 'delete'].includes(action)) throw createError({ statusCode: 400, statusMessage: 'Invalid cart action' })

  const payload: Record<string, unknown> = { action, csrfToken: body.csrfToken }
  if (action === 'add') {
    const productId = boundedInt(body.productId, 1, Number.MAX_SAFE_INTEGER)
    const quantity = boundedInt(body.quantity ?? 1, 1, 999)
    const color = boundedInt(body.color ?? 0, 0, Number.MAX_SAFE_INTEGER)
    const size = boundedInt(body.size ?? 0, 0, Number.MAX_SAFE_INTEGER)
    if (productId === null || quantity === null || color === null || size === null) throw createError({ statusCode: 400, statusMessage: 'Invalid cart item' })
    Object.assign(payload, { productId, quantity, color, size })
  } else if (action === 'update') {
    const productId = boundedInt(body.productId, 1, Number.MAX_SAFE_INTEGER)
    const quantity = boundedInt(body.quantity, 1, 999)
    if (productId === null || quantity === null || typeof body.code !== 'string' || !CODE.test(body.code)) throw createError({ statusCode: 400, statusMessage: 'Invalid cart update' })
    Object.assign(payload, { productId, quantity, code: body.code })
  } else {
    if (typeof body.code !== 'string' || !CODE.test(body.code)) throw createError({ statusCode: 400, statusMessage: 'Invalid cart delete' })
    Object.assign(payload, { code: body.code })
  }

  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/pts_v1_cart_mutate.php', {
      method: 'POST',
      headers: { 'content-type': 'application/json;charset=UTF-8' },
      body: JSON.stringify(payload),
    })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP cart mutation unavailable' })
  }

  const data: any = await response.json().catch(() => null)
  if (!response.ok) {
    const message = data?.error?.message
    throw createError({ statusCode: response.status, statusMessage: typeof message === 'string' && message ? message : 'Cart mutation failed' })
  }
  if (!data || data.ok !== true || typeof data.count !== 'number' || !Number.isSafeInteger(data.count) || data.count < 0) {
    throw createError({ statusCode: 502, statusMessage: 'Invalid cart mutation response' })
  }
  return data
})

import { fetchPhpSessionResponse } from '../../utils/migration-cart'

function id(value: unknown): number | null {
  return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 ? value : null
}
function record(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}
function safeMoney(value: unknown): number | null {
  return typeof value === 'number' && Number.isFinite(value) && value >= 0 && value <= Number.MAX_SAFE_INTEGER
    ? Math.round(value)
    : null
}

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const body = await readBody<{ city?: number; ward?: number }>(event)
  const city = id(body?.city)
  const ward = id(body?.ward)
  if (city === null || ward === null) throw createError({ statusCode: 400, statusMessage: 'Invalid shipping location' })

  const form = new URLSearchParams({ city: String(city), ward: String(ward) })
  let response: Response
  try {
    response = await fetchPhpSessionResponse(event, '/api/shipping_quote.php', {
      method: 'POST',
      headers: { 'content-type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: form,
    })
  } catch { throw createError({ statusCode: 502, statusMessage: 'Shipping quote unavailable' }) }

  const data: unknown = await response.json().catch(() => null)
  if (!record(data) || typeof data.ok !== 'boolean') throw createError({ statusCode: 502, statusMessage: 'Invalid shipping quote' })
  if (!data.ok) {
    const error = typeof data.error === 'string' && data.error.trim() ? data.error.trim() : 'Shipping quote unavailable'
    throw createError({ statusCode: response.status === 422 ? 422 : 502, statusMessage: error })
  }

  if (typeof data.priced !== 'boolean' || !record(data.selected) || data.selected.provider !== 'local'
    || data.selected.currency !== 'VND' || !record(data.selected.place)
    || data.selected.place.city_id !== city || data.selected.place.ward_id !== ward) {
    throw createError({ statusCode: 502, statusMessage: 'Invalid shipping quote contract' })
  }
  const amount = safeMoney(data.selected.amount)
  if (amount === null) throw createError({ statusCode: 502, statusMessage: 'Invalid shipping amount' })
  const subtotal = safeMoney(data.subtotal)
  if (subtotal === null) throw createError({ statusCode: 502, statusMessage: 'Invalid shipping subtotal' })

  return {
    ok: true,
    priced: data.priced,
    subtotal,
    selected: {
      provider: 'local',
      label: typeof data.selected.label === 'string' ? data.selected.label : 'Phí vận chuyển',
      amount,
      currency: 'VND',
    },
  }
})

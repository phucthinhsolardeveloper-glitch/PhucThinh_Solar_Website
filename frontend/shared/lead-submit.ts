/*
 * What a lead form does when Laravel refuses a request. Kept free of Nuxt so node tests can run it.
 */
export const LEAD_FALLBACK_MESSAGE = 'Chưa gửi được yêu cầu. Vui lòng thử lại hoặc gọi hotline để được hỗ trợ ngay.'

const statusMessages: Record<number, string> = {
  419: 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng tải lại trang rồi gửi lại.',
  429: 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.',
}

/** An error whose message is written for the visitor. */
export class LeadFormError extends Error {}

export function leadErrorStatus(reason: unknown): number | null {
  const error = reason as { statusCode?: unknown; status?: unknown } | null
  const status = error?.statusCode ?? error?.status
  return typeof status === 'number' ? status : null
}

/** Laravel's own message first, then one for the status; technical error text is never shown. */
export function toLeadFormError(reason: unknown): LeadFormError {
  if (reason instanceof LeadFormError) return reason
  const data = (reason as { data?: unknown } | null)?.data
  const message = data && typeof data === 'object' ? (data as { error?: { message?: unknown } }).error?.message : undefined
  if (typeof message === 'string' && message.trim()) return new LeadFormError(message)
  return new LeadFormError(statusMessages[leadErrorStatus(reason) ?? 0] ?? LEAD_FALLBACK_MESSAGE)
}

export function leadErrorMessage(reason: unknown): string {
  return reason instanceof LeadFormError ? reason.message : LEAD_FALLBACK_MESSAGE
}

/**
 * Runs `send`; when the form token is refused (419, a page left open past its lifetime) it refreshes the token
 * and sends once more. Laravel refuses the token before saving anything, so the retry cannot duplicate a lead.
 */
export async function sendWithFreshToken<T>(send: () => Promise<T>, refresh: () => Promise<unknown>): Promise<T> {
  try {
    return await send()
  } catch (reason) {
    if (leadErrorStatus(reason) !== 419) throw reason
    await refresh()
    return await send()
  }
}

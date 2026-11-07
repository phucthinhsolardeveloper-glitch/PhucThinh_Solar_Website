function record(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

export function cartErrorMessage(reason: unknown): string {
  if (!record(reason)) return 'Không thể cập nhật giỏ hàng.'
  const data = record(reason.data) ? reason.data : null
  const nestedError = data?.error
  const candidates = [
    typeof nestedError === 'string' ? nestedError : record(nestedError) ? nestedError.message : null,
    data?.statusMessage,
    reason.statusMessage,
    reason.message,
  ]
  const message = candidates.find((candidate): candidate is string => typeof candidate === 'string' && candidate.trim() !== '')
  return message ?? 'Không thể cập nhật giỏ hàng.'
}

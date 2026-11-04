import type { PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCartPayload } from '#shared/contracts/public-cart'
import { migrationCartErrorMessage } from './migrationCartError'

export type MigrationCartMutation =
  | { action: 'add'; productId: number; quantity: number; color?: number; size?: number }
  | { action: 'update'; productId: number; code: string; quantity: number }
  | { action: 'delete'; code: string }

export function useMigrationCart() {
  const cart = useState<PublicCartPayload | null>('migration-cart-payload', () => null)
  const loading = useState('migration-cart-loading', () => false)
  const error = useState<string | null>('migration-cart-error', () => null)

  async function refresh() {
    loading.value = true
    error.value = null
    try {
      const payload = await $fetch<unknown>('/_migration/cart')
      if (!isPublicCartPayload(payload)) throw new Error('Invalid cart contract')
      cart.value = payload
      return payload
    } catch (reason) {
      error.value = reason instanceof Error ? reason.message : 'Không thể tải giỏ hàng.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function mutate(body: MigrationCartMutation) {
    loading.value = true
    error.value = null
    try {
      let current = cart.value
      if (!current) current = await refresh()
      if (!current) throw new Error('Không thể khởi tạo phiên giỏ hàng.')
      try {
        await $fetch('/_migration/cart', { method: 'POST', body: { ...body, csrfToken: current.csrfToken } })
      } catch (reason) {
        // On a first visit, parallel PHP responses can each start a session and the last
        // cookie wins, leaving the cached token on another session (419). Re-read the
        // token for the cookie the browser now holds and retry once.
        if ((reason as { statusCode?: number })?.statusCode !== 419) throw reason
        current = await refresh()
        loading.value = true
        if (!current) throw reason
        await $fetch('/_migration/cart', { method: 'POST', body: { ...body, csrfToken: current.csrfToken } })
      }
    } catch (reason) {
      error.value = migrationCartErrorMessage(reason)
      throw reason
    } finally {
      loading.value = false
    }
    return refresh()
  }

  return { cart, loading, error, refresh, mutate }
}

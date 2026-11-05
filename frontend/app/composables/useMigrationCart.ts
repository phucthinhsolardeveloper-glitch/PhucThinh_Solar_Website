import type { PublicCartPayload } from '#shared/contracts/public-cart'
import { isPublicCartPayload } from '#shared/contracts/public-cart'
import { isCurrentCartRead } from '#shared/cart-refresh-guard'
import { migrationCartErrorMessage } from './migrationCartError'

export type MigrationCartMutation =
  | { action: 'add'; productId: number; quantity: number; color?: number; size?: number }
  | { action: 'update'; productId: number; code: string; quantity: number }
  | { action: 'delete'; code: string }

export function useMigrationCart() {
  const cart = useState<PublicCartPayload | null>('migration-cart-payload', () => null)
  const loading = useState('migration-cart-loading', () => false)
  const error = useState<string | null>('migration-cart-error', () => null)
  const cartRevision = useState('migration-cart-revision', () => 0)
  const refreshSequence = useState('migration-cart-refresh-sequence', () => 0)
  const pendingRequests = useState('migration-cart-pending-requests', () => 0)

  function beginRequest() {
    pendingRequests.value += 1
    loading.value = true
  }

  function finishRequest() {
    pendingRequests.value = Math.max(0, pendingRequests.value - 1)
    loading.value = pendingRequests.value > 0
  }

  async function refresh() {
    const readRevision = cartRevision.value
    const readSequence = ++refreshSequence.value
    beginRequest()
    try {
      const payload = await $fetch<unknown>('/_migration/cart')
      if (!isPublicCartPayload(payload)) throw new Error('Invalid cart contract')
      if (isCurrentCartRead(readRevision, cartRevision.value, readSequence, refreshSequence.value)) {
        cart.value = payload
        error.value = null
        return payload
      }
      return cart.value
    } catch (reason) {
      if (isCurrentCartRead(readRevision, cartRevision.value, readSequence, refreshSequence.value)) {
        error.value = reason instanceof Error ? reason.message : 'Không thể tải giỏ hàng.'
        return null
      }
      return cart.value
    } finally {
      finishRequest()
    }
  }

  async function mutate(body: MigrationCartMutation) {
    // Discard reads started before a mutation so an old response cannot roll
    // shared cart state back after a newer value has arrived.
    cartRevision.value += 1
    beginRequest()
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
        if (!current) throw reason
        await $fetch('/_migration/cart', { method: 'POST', body: { ...body, csrfToken: current.csrfToken } })
      }
      // Invalidate reads started while POST was in flight and keep the loading
      // state open until the authoritative post-mutation read completes.
      cartRevision.value += 1
      return await refresh()
    } catch (reason) {
      error.value = migrationCartErrorMessage(reason)
      throw reason
    } finally {
      finishRequest()
    }
  }

  return { cart, loading, error, refresh, mutate }
}

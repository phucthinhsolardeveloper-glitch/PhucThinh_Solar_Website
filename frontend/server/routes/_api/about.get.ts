import { isPublicAboutPayload } from '../../../shared/contracts/public-about'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/about',
    validate: isPublicAboutPayload,
    invalidMessage: 'Invalid About data contract',
    unavailableMessage: 'About data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})

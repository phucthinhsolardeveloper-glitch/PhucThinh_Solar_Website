import { isPublicSolarEstimatorForm } from '../../../shared/contracts/public-solar-estimator'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/solar-estimator',
    validate: isPublicSolarEstimatorForm,
    invalidMessage: 'Invalid solar estimator contract',
    unavailableMessage: 'solar estimator unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})

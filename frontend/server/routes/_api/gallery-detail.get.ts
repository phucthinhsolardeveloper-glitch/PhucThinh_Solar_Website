import { isPublicGalleryDetailPayload } from '../../../shared/contracts/public-gallery'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery detail query' })
  }
  const slug = query.slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery slug' })
  }
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/gallery-detail',
    query: { slug },
    validate: isPublicGalleryDetailPayload,
    invalidMessage: 'Invalid gallery detail contract',
    unavailableMessage: 'gallery detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})

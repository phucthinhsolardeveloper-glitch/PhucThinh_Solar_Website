import { isPublicGalleryDetailPayload } from '../../../shared/contracts/public-gallery'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery detail query' })
  }
  const slug = query.slug
  if (typeof slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery slug' })
  }
  return fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_gallery_detail.php',
    query: { slug },
    validate: isPublicGalleryDetailPayload,
    invalidMessage: 'Invalid PHP gallery detail contract',
    unavailableMessage: 'PHP gallery detail data source unavailable',
    passThroughStatuses: [404],
  })
})

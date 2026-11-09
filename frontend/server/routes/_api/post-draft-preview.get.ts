import { isPublicPolicyDetail } from '../../../shared/contracts/public-policy-detail'
import { isPublicProjectDetailPayload } from '../../../shared/contracts/public-project-detail'
import { isPublicSolutionDetailPayload } from '../../../shared/contracts/public-solution-detail'
import { draftPreviewQuery } from '../../../shared/draft-preview'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

// Draft of a solution, project or policy, in the same shape as its public detail. Never cached or indexed.
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  const preview = Object.keys(query).every((key) => ['revision', 'expires', 'signature'].includes(key)) ? draftPreviewQuery(query.revision, query) : null
  if (!preview) throw createError({ statusCode: 400, statusMessage: 'Invalid preview link' })

  const payload = await fetchBackendPayload(event, {
    endpoint: `/api/v1/post-draft-preview/${preview.revision}`,
    query: { expires: preview.expires, signature: preview.signature },
    validate: (value: unknown) => isPublicSolutionDetailPayload(value) || isPublicProjectDetailPayload(value) || isPublicPolicyDetail(value),
    invalidMessage: 'Invalid draft preview data',
    unavailableMessage: 'Draft preview unavailable',
    passThroughStatuses: [403, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  setHeader(event, 'Referrer-Policy', 'no-referrer')
  return payload
})

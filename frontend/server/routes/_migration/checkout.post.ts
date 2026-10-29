export default defineEventHandler((event) => {
  if (getMethod(event) !== 'POST') {
    setHeader(event, 'Allow', 'POST')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  // The migration checkout is intentionally read-only until a production
  // checkout rollout is explicitly authorized and verified.
  throw createError({ statusCode: 410, statusMessage: 'Checkout is disabled in migration preview' })
})

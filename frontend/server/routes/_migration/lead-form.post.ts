export default defineEventHandler((event) => {
  setHeader(event, 'Allow', 'GET')
  throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
})

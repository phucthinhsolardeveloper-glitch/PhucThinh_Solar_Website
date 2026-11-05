// Served here rather than from public/ because Safari only plays MP4 when byte ranges are honoured.
export default defineEventHandler(async (event) => {
  assertMethod(event, ['GET', 'HEAD'])
  const name = getRouterParam(event, 'name') ?? ''
  if (!/^[a-z0-9-]+\.mp4$/.test(name)) throw createError({ statusCode: 404, statusMessage: 'Video not found' })
  const raw = await useStorage('assets:server').getItemRaw<ArrayBuffer | Uint8Array>(`video:${name}`)
  if (!raw) throw createError({ statusCode: 404, statusMessage: 'Video not found' })

  const body = raw instanceof Uint8Array ? raw : new Uint8Array(raw)
  const total = body.byteLength
  setHeaders(event, { 'Content-Type': 'video/mp4', 'Accept-Ranges': 'bytes', 'Cache-Control': 'public, max-age=604800' })

  const range = /^bytes=(\d*)-(\d*)$/.exec(getRequestHeader(event, 'range') ?? '')
  if (!range || (!range[1] && !range[2])) {
    setHeader(event, 'Content-Length', total)
    return body
  }
  const start = range[1] ? Number(range[1]) : Math.max(0, total - Number(range[2]))
  const end = range[1] && range[2] ? Math.min(Number(range[2]), total - 1) : total - 1
  if (start > end || start >= total) {
    setResponseStatus(event, 416)
    setHeader(event, 'Content-Range', `bytes */${total}`)
    return ''
  }
  setResponseStatus(event, 206)
  setHeaders(event, { 'Content-Range': `bytes ${start}-${end}/${total}`, 'Content-Length': end - start + 1 })
  return body.subarray(start, end + 1)
})

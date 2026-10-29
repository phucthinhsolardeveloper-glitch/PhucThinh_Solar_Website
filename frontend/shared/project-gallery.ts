const YOUTUBE_ID = /^[A-Za-z0-9_-]{11}$/
const VIMEO_ID = /^\d{5,12}$/

export function projectVideoEmbedUrl(value: unknown): string {
  if (typeof value !== 'string') return ''
  let url: URL
  try { url = new URL(value) } catch { return '' }
  if (url.protocol !== 'https:' || url.username || url.password || url.port) return ''

  const host = url.hostname.toLowerCase()
  if (['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'].includes(host)) {
    const id = url.pathname === '/watch'
      ? url.searchParams.get('v') || ''
      : url.pathname.match(/^\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{11})(?:\/|$)/)?.[1] || ''
    return YOUTUBE_ID.test(id) ? `https://www.youtube-nocookie.com/embed/${id}` : ''
  }
  if (host === 'youtu.be') {
    const id = url.pathname.split('/').filter(Boolean)[0] || ''
    return YOUTUBE_ID.test(id) ? `https://www.youtube-nocookie.com/embed/${id}` : ''
  }
  if (['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'].includes(host)) {
    const id = url.pathname.match(/\/(?:video\/)?(\d{5,12})\/?$/)?.[1] || ''
    return VIMEO_ID.test(id) ? `https://player.vimeo.com/video/${id}` : ''
  }
  return ''
}

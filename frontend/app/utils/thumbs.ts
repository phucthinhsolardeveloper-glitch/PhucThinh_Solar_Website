const WEBP_RENDITION = /\.(?:jpe?g|png)$/i

// /thumbs serves a WebP rendition when ".webp" is appended to a JPG/PNG path
// (alpha kept); WebP originals and GIFs are requested as-is.
export function thumbUrl(src: string, width: number, height: number, mode: 1 | 2 | 3 = 1): string {
  return `/thumbs/${width}x${height}x${mode}${src}${WEBP_RENDITION.test(src) ? '.webp' : ''}`
}

// Uploads, the JPG/PNG site visuals and the product use-case pictures (also on the backend) can be resized by
// /thumbs; anything else (WebP art that only the frontend has,
// external URLs, empty) passes through.
const RESIZABLE = /^\/(?:upload\/(?:news|photo|product)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)|assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png)|assets\/images\/site-visuals\/pdp-usecase-v2\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp))$/i

// Fit mode keeps the original aspect ratio, so CSS framing (object-fit/position) is unchanged.
export function optimizedImage(src: string | null | undefined, width: number, height: number): string {
  const value = src || ''
  return RESIZABLE.test(value) ? thumbUrl(value, width, height, 3) : value
}

// Full-screen photos use a large rendition rather than the upload: originals reach 15 MB and look the
// same at this size. A phone or a 1080p screen stays at 1600px; sharper, larger screens get 2000px.
export function viewerImage(src: string | null | undefined): string {
  const pixels = import.meta.client ? Math.min(window.innerWidth, window.innerHeight) * (window.devicePixelRatio || 1) : 0
  const size = pixels > 1400 ? 2000 : 1600
  return optimizedImage(src, size, size)
}

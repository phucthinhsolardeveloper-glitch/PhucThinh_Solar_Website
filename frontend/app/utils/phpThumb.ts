const WEBP_RENDITION = /\.(?:jpe?g|png)$/i

// PHP createThumb serves a WebP rendition when ".webp" is appended to a JPG/PNG
// path (alpha kept); WebP originals and GIFs are requested as-is.
export function phpThumb(src: string, width: number, height: number, mode: 1 | 2 | 3 = 1): string {
  return `/thumbs/${width}x${height}x${mode}${src}${WEBP_RENDITION.test(src) ? '.webp' : ''}`
}

// Uploads and the raster site visuals shipped in both the PHP and Nuxt trees can be
// resized by PHP; anything else (Nuxt-only WebP art, external URLs, empty) passes through.
const PHP_RESIZABLE = /^\/(?:upload\/(?:news|photo|product)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)|assets\/images\/site-visuals\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png))$/i

// Fit mode keeps the original aspect ratio, so CSS framing (object-fit/position) is unchanged.
export function optimizedImage(src: string | null | undefined, width: number, height: number): string {
  const value = src || ''
  return PHP_RESIZABLE.test(value) ? phpThumb(value, width, height, 3) : value
}

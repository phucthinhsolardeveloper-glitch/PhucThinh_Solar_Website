import { createHash } from 'node:crypto'
import { mkdir, readFile, writeFile } from 'node:fs/promises'
import { tmpdir } from 'node:os'
import { join, resolve } from 'node:path'
import { validateLegacyMediaPath } from './legacy-preview-media'

const THUMB_PATH = /^\/thumbs\/(\d{1,5})x(\d{1,5})x(\d+)\//
const CACHE_DIR = join(tmpdir(), 'pts-preview-thumbs')
const WEBP_QUALITY = 80
const MAX_THUMB_EDGE = 4000

/**
 * Local preview only. If the configured media origin falls back from a missing PHP thumbnail to
 * its original upload, sharp recreates the requested size instead of decoding that full image.
 * Mode 1 crops to the box, other modes fit inside it, matching production output dimensions.
 */
export async function renderPreviewThumb(
  pathname: string,
  body: ArrayBuffer,
): Promise<{ body: Uint8Array, contentType: string } | null> {
  const match = THUMB_PATH.exec(pathname)
  if (!match) return null
  const width = Number(match[1])
  const height = Number(match[2])
  if (!width || !height || width > MAX_THUMB_EDGE || height > MAX_THUMB_EDGE) return null

  const cacheFile = join(CACHE_DIR, `${createHash('sha1').update(pathname).digest('hex')}.webp`)
  try {
    return { body: await readFile(cacheFile), contentType: 'image/webp' }
  } catch {
    // Not rendered yet.
  }

  try {
    const { default: sharp } = await import('sharp')
    const output = await sharp(Buffer.from(body))
      .resize(width, height, { fit: match[3] === '1' ? 'cover' : 'inside', withoutEnlargement: true })
      .webp({ quality: WEBP_QUALITY })
      .toBuffer()
    await mkdir(CACHE_DIR, { recursive: true })
    await writeFile(cacheFile, output)
    return { body: output, contentType: 'image/webp' }
  } catch {
    // sharp is a preview convenience; the original still renders without it.
    return null
  }
}

const LOCAL_LEGACY_ROOT = resolve(process.cwd(), '../domains/phucthinhsolar.com/public_html')

/**
 * Local preview only. If the configured media origin does not expose /assets, this reads the repo's
 * copy instead so local theme images still render.
 * Accepts /assets/... and /thumbs/WxHxM/assets/... ; the path passes the same validator as the proxy.
 */
export async function readLocalLegacyAsset(pathname: string, config: { previewMediaProxy: boolean | string | number }) {
  const enabled = config.previewMediaProxy === true || config.previewMediaProxy === 1 || config.previewMediaProxy === '1' || config.previewMediaProxy === 'true'
  if (!enabled) return null
  const assetPath = pathname.replace(/^\/thumbs\/\d{1,5}x\d{1,5}x\d+(?=\/assets\/)/, '').replace(/\.(jpe?g|png)\.webp$/i, '.$1')
  if (!assetPath.startsWith('/assets/')) return null
  const validated = validateLegacyMediaPath(assetPath)
  if (!validated) return null
  try {
    const file = await readFile(join(LOCAL_LEGACY_ROOT, validated.pathname))
    return { body: file.buffer.slice(file.byteOffset, file.byteOffset + file.byteLength) as ArrayBuffer, contentType: validated.contentType }
  } catch {
    return null
  }
}

import assert from 'node:assert/strict'
import { test } from 'node:test'
import { isPublicGalleryAlbum, isPublicGalleryDetailPayload, isPublicGalleryPayload } from '../shared/contracts/public-gallery.ts'
import { isPublicVideoItem, isPublicVideoPayload } from '../shared/contracts/public-video.ts'

const seo = (canonical) => ({ title: 'Media', description: '', keywords: '', canonical })
const album = { id: 7, title: 'Album thật', slug: 'album-that', url: '/album-that/', description: '', image: '/upload/product/cover.webp' }
const gallery = {
  schemaVersion: 1,
  locale: 'vi',
  path: '/thu-vien-anh/',
  title: 'Thư viện ảnh',
  seo: seo('/thu-vien-anh/'),
  albums: [album],
  pagination: { page: 1, perPage: 20, total: 1 },
}
const detail = {
  schemaVersion: 1,
  locale: 'vi',
  album,
  images: [{ id: 8, image: '/upload/product/gallery.webp', caption: 'Ảnh thật' }],
  seo: seo('/album-that/'),
}
const video = { id: 9, title: 'Video thật', youtubeId: 'dQw4w9WgXcQ', embedUrl: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', thumbnail: 'https://img.youtube.com/vi/dQw4w9WgXcQ/0.jpg' }
const videos = { schemaVersion: 1, locale: 'vi', path: '/video/', title: 'Video', seo: seo('/video/'), videos: [video], pagination: { page: 1, perPage: 10, total: 1 } }

test('accepts safe gallery list/detail contracts', () => {
  assert.equal(isPublicGalleryAlbum(album), true)
  assert.equal(isPublicGalleryPayload(gallery), true)
  assert.equal(isPublicGalleryDetailPayload(detail), true)
})

test('rejects unsafe gallery media and inconsistent canonical data', () => {
  assert.equal(isPublicGalleryPayload({ ...gallery, albums: [{ ...album, image: '/upload/product/../secret.php' }] }), false)
  assert.equal(isPublicGalleryDetailPayload({ ...detail, seo: seo('/different/') }), false)
  assert.equal(isPublicGalleryDetailPayload({ ...detail, images: Array.from({ length: 121 }, () => detail.images[0]) }), false)
})

test('accepts only the normalized nocookie YouTube video contract', () => {
  assert.equal(isPublicVideoItem(video), true)
  assert.equal(isPublicVideoPayload(videos), true)
})

test('rejects unsafe video hosts, IDs and pagination overflow', () => {
  assert.equal(isPublicVideoItem({ ...video, embedUrl: 'https://evil.test/embed/dQw4w9WgXcQ' }), false)
  assert.equal(isPublicVideoItem({ ...video, youtubeId: 'short', embedUrl: 'https://www.youtube-nocookie.com/embed/short' }), false)
  assert.equal(isPublicVideoPayload({ ...videos, videos: Array.from({ length: 11 }, () => video) }), false)
  assert.equal(isPublicVideoPayload({ ...videos, pagination: { page: 0, perPage: 10, total: 1 } }), false)
})

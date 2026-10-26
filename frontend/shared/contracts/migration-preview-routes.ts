export type MigrationPreviewRouteKey =
  | 'home'
  | 'catalog'
  | 'product'
  | 'solutions'
  | 'projects'
  | 'news'
  | 'about'
  | 'contact'
  | 'dealer'
  | 'shell'

export type MigrationPreviewStatus = 'preview' | 'php-form' | 'migrating'

export interface MigrationPreviewRoute {
  key: MigrationPreviewRouteKey
  title: string
  description: string
  previewPath: string
  phpPath: string
  status: MigrationPreviewStatus
}

/** Explicit registry only; it does not alter public routing or fallback behavior. */
export const MIGRATION_PREVIEW_ROUTES: readonly MigrationPreviewRoute[] = [
  { key: 'home', title: 'Trang chủ', description: 'Bản xem thử trang chủ product-first.', previewPath: '/migration-home-preview', phpPath: '/', status: 'preview' },
  { key: 'catalog', title: 'Danh mục sản phẩm', description: 'Bản xem thử cây danh mục và sản phẩm từ CMS.', previewPath: '/migration-catalog-preview', phpPath: '/san-pham/', status: 'preview' },
  { key: 'product', title: 'Chi tiết sản phẩm', description: 'Bản xem thử PDP qua slug sản phẩm thật.', previewPath: '/migration-product-preview', phpPath: '/san-pham/', status: 'migrating' },
  { key: 'solutions', title: 'Giải pháp', description: 'Bản xem thử landing Giải pháp từ CMS.', previewPath: '/migration-solutions-preview', phpPath: '/giai-phap/', status: 'preview' },
  { key: 'projects', title: 'Dự án', description: 'Bản xem thử danh sách dự án đã thực hiện.', previewPath: '/migration-projects-preview', phpPath: '/du-an-da-thuc-hien/', status: 'preview' },
  { key: 'news', title: 'Kiến thức', description: 'Bản xem thử hub bài viết và bộ lọc.', previewPath: '/migration-news-preview', phpPath: '/tin-tuc/', status: 'preview' },
  { key: 'about', title: 'Giới thiệu', description: 'Bản xem thử nội dung giới thiệu doanh nghiệp.', previewPath: '/migration-about-preview', phpPath: '/gioi-thieu/', status: 'preview' },
  { key: 'contact', title: 'Liên hệ', description: 'Bản xem thử nội dung; form vẫn do PHP xử lý.', previewPath: '/migration-contact-preview', phpPath: '/lien-he/', status: 'php-form' },
  { key: 'dealer', title: 'Đại lý', description: 'Bản xem thử nội dung; form đăng ký vẫn do PHP xử lý.', previewPath: '/migration-dealer-preview', phpPath: '/dai-ly/', status: 'php-form' },
  { key: 'shell', title: 'Header, menu và footer', description: 'Bản xem thử shell dùng chung của Nuxt.', previewPath: '/migration-shell-preview', phpPath: '/', status: 'preview' },
] as const

const LOCAL_PHP_ORIGIN = /^http:\/\/(?:localhost|127\.0\.0\.1)(?::\d{2,5})?$/
const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

function routeFor(key: MigrationPreviewRouteKey): MigrationPreviewRoute {
  const route = MIGRATION_PREVIEW_ROUTES.find((item) => item.key === key)
  if (!route) throw new Error('Unknown migration preview route')
  return route
}

export function previewHref(key: MigrationPreviewRouteKey, query: Record<string, string> = {}): string {
  const route = routeFor(key)
  const params = new URLSearchParams()
  for (const [name, value] of Object.entries(query)) {
    if (name !== 'slug' || !SLUG.test(value)) throw new Error('Unsupported preview query')
    params.set(name, value)
  }
  return route.previewPath + (params.size ? '?' + params.toString() : '')
}

export function phpHref(key: MigrationPreviewRouteKey, origin = 'http://localhost:8088'): string {
  const route = routeFor(key)
  if (!LOCAL_PHP_ORIGIN.test(origin)) throw new Error('PHP fallback must be local')
  return origin + route.phpPath
}

export function resolvePreviewLink(key: MigrationPreviewRouteKey, mode: 'nuxt' | 'php' = 'nuxt', query: Record<string, string> = {}): string {
  if (mode === 'php') return phpHref(key)
  if (mode !== 'nuxt') throw new Error('Unsupported preview link mode')
  return previewHref(key, query)
}


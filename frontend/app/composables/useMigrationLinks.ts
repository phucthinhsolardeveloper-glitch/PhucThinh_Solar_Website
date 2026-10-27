const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

export function useMigrationLinks() {
  const route = useRoute()
  const active = computed(() => route.path.startsWith('/migration-'))

  function generic(href: string): string {
    if (!active.value) return href
    if (!href || href.startsWith('#') || /^(?:mailto:|tel:|https?:\/\/)/i.test(href)) return href

    let url: URL
    try { url = new URL(href, 'http://preview.local') } catch { return href }
    const path = url.pathname
    const hash = url.hash
    const staticMap: Record<string, string> = {
      '/': '/migration-home-preview',
      '/gioi-thieu/': '/migration-about-preview',
      '/giai-phap/': '/migration-solutions-preview',
      '/du-an-da-thuc-hien/': '/migration-projects-preview',
      '/tin-tuc/': '/migration-news-preview',
      '/dai-ly/': '/migration-dealer-preview',
      '/lien-he/': '/migration-contact-preview',
      '/san-pham/': '/migration-catalog-preview',
      '/gio-hang/': '/migration-cart-preview',
      '/tim-kiem/': '/migration-search-preview',
    }
    if (staticMap[path]) return staticMap[path] + hash

    const category = path.match(/^\/danh-muc-san-pham\/([a-z0-9-]+(?:\/[a-z0-9-]+){0,3})\/$/)
    if (category) return '/migration-catalog-preview?category=' + encodeURIComponent(category[1]!)

    const productMatch = path.match(/^\/san-pham\/([a-z0-9]+(?:-[a-z0-9]+)*)\/?$/)
    if (productMatch) return product(productMatch[1]!)

    const solutionMatch = path.match(/^\/giai-phap\/([a-z0-9]+(?:-[a-z0-9]+)*)\/$/)
    if (solutionMatch) return solution(solutionMatch[1]!)

    const policy = path.match(/^\/ho-tro\/([a-z0-9]+(?:-[a-z0-9]+)*)\/$/)
    if (policy) return '/migration-policy-preview?slug=' + encodeURIComponent(policy[1]!)

    return href
  }

  function product(slug: string): string {
    return active.value && SLUG.test(slug) ? '/migration-product-preview?slug=' + encodeURIComponent(slug) : '/san-pham/' + slug
  }
  function solution(slug: string): string {
    return active.value && SLUG.test(slug) ? '/migration-solution-detail-preview?slug=' + encodeURIComponent(slug) : '/giai-phap/' + slug + '/'
  }
  function news(slug: string): string {
    return active.value && SLUG.test(slug) ? '/migration-news-detail-preview?slug=' + encodeURIComponent(slug) : '/' + slug + '/'
  }
  function project(slug: string): string {
    return active.value && SLUG.test(slug) ? '/migration-project-detail-preview?slug=' + encodeURIComponent(slug) : '/' + slug + '/'
  }

  return { active, generic, product, solution, news, project }
}

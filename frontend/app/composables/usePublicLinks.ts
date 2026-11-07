const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/

/** Public URL builders shared by the components, so every link follows the site's canonical paths. */
export function usePublicLinks() {
  const generic = (href: string): string => href
  const external = (href: string): boolean => /^(?:https?:\/\/|mailto:|tel:)/i.test(href)
  const product = (slug: string): string => '/san-pham/' + slug
  const solution = (slug: string): string => '/giai-phap/' + slug + '/'
  const news = (slug: string): string => '/' + slug + '/'
  const project = (slug: string): string => '/' + slug + '/'
  const gallery = (slug: string): string => '/' + slug + '/'
  const brand = (slug: string): string => (SLUG.test(slug) ? '/thuong-hieu/' + slug + '/' : '/thuong-hieu/')

  return { generic, external, product, solution, news, project, gallery, brand }
}

import type { RouterConfig } from '@nuxt/schema'

const HEADER_OFFSET = 112

function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function pageParam(query: Record<string, unknown>): string {
  return String(query.page ?? query.p ?? '1')
}

// Scroll rules: back/forward restores; #hash lands below the sticky header; a new
// page of results glides to the list; filter/sort changes keep the reader in place;
// any other page change starts at the top.
export default <RouterConfig>{
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash && to.path === from.path) {
      return { el: to.hash, top: HEADER_OFFSET, behavior: prefersReducedMotion() ? 'auto' : 'smooth' }
    }
    if (to.path === from.path) {
      if (pageParam(to.query) !== pageParam(from.query) && typeof document !== 'undefined') {
        const results = document.querySelector('[data-results]')
        if (results) return { el: results, top: HEADER_OFFSET, behavior: prefersReducedMotion() ? 'auto' : 'smooth' }
      }
      return false
    }
    if (to.hash) return { el: to.hash, top: HEADER_OFFSET }
    return { top: 0 }
  },
}

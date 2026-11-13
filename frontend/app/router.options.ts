import type { RouterConfig } from '@nuxt/schema'
import { useNuxtApp } from '#app/nuxt'
import { useRouter } from '#app/composables/router'

const HEADER_OFFSET = 112

function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function pageParam(query: Record<string, unknown>): string {
  return String(query.page ?? query.p ?? '1')
}

type ScrollTarget = Parameters<NonNullable<RouterConfig['scrollBehavior']>>[2] | { el?: string | Element; top?: number; behavior?: ScrollBehavior } | false

/**
 * Moves the scroll only once the new page is on screen. Scrolling at the click showed the top of the OLD page
 * for as long as the next page took to load (0.5-1.5 s on a mid phone), then swapped: the page seemed to jump twice.
 * Resolved inside page:finish (DOM already patched, before the browser paints), so swap and scroll land in one frame.
 */
function afterPageSwap(fullPath: string, position: ScrollTarget): Promise<ScrollTarget> {
  const nuxtApp = useNuxtApp()
  const router = useRouter()
  return new Promise((resolve) => {
    let settled = false
    const done = () => {
      if (settled) return
      settled = true
      resolve(router.currentRoute.value.fullPath === fullPath ? position : false)
    }
    nuxtApp.hooks.hookOnce('page:finish', done)
    // A page that keeps its component (no Suspense round) never fires page:finish; loading still ends.
    nuxtApp.hooks.hookOnce('page:loading:end', () => { requestAnimationFrame(done) })
  })
}

// Scroll rules: back/forward restores; #hash lands below the sticky header; a new
// page of results glides to the list; filter/sort changes keep the reader in place;
// any other page change starts at the top.
export default <RouterConfig>{
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return to.path === from.path ? savedPosition : afterPageSwap(to.fullPath, savedPosition)
    // First load: the browser already placed the page, and the reader may have started scrolling.
    if (!from.matched.length) return to.hash ? { el: to.hash, top: HEADER_OFFSET } : false
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
    if (to.path.startsWith('/ho-tro/') && from.path.startsWith('/ho-tro/')) {
      return { top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' }
    }
    return afterPageSwap(to.fullPath, to.hash ? { el: to.hash, top: HEADER_OFFSET } : { top: 0 })
  },
}

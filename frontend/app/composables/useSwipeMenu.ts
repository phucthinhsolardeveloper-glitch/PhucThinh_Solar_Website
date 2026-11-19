type SwipeMenuOptions = {
  isOpen: () => boolean
  canStart: () => boolean
  open: () => void
  close: () => void
}

const excludedStart = [
  'input', 'select', 'textarea', '[contenteditable="true"]',
  'iframe', '[data-no-swipe]', '.m-rail', '[data-thumb]',
  '[data-hero-carousel]', '[data-carousel]', '[data-slider]', '[data-map]', '[data-3d]',
  '[aria-roledescription="carousel"]', 'canvas', '.lightbox-strip', '.pdp-studio-stage',
  '.swiper', '.splide', '.slick-slider', '.product-gallery', '.product-detail-gallery',
].join(',')
const otherModal = '[role="dialog"][aria-modal="true"]:not(#ptsMobileMenu)'

function startsInExcludedRegion(target: EventTarget | null): boolean {
  if (!(target instanceof Element)) return true
  if (target.closest(excludedStart)) return true

  for (let element: Element | null = target; element; element = element.parentElement) {
    const style = getComputedStyle(element)
    const allowsHorizontalScroll = /auto|scroll/.test(style.overflowX)
    if (allowsHorizontalScroll && element.scrollWidth > element.clientWidth + 1) return true
  }

  return false
}

/** Adds a non-blocking phone gesture for the shared navigation drawer. */
export function useSwipeMenu(options: SwipeMenuOptions) {
  let pointerQuery: MediaQueryList | null = null
  let widthQuery: MediaQueryList | null = null
  let touch: { id: number; x: number; y: number; startedAt: number } | null = null

  function enabled(): boolean {
    return Boolean(pointerQuery?.matches && widthQuery?.matches)
  }

  function onTouchStart(event: TouchEvent) {
    touch = null
    if (!enabled() || !options.canStart() || document.querySelector(otherModal) || event.touches.length !== 1 || startsInExcludedRegion(event.target)) return
    const point = event.touches[0]
    if (!point) return
    touch = { id: point.identifier, x: point.clientX, y: point.clientY, startedAt: performance.now() }
  }

  function onTouchMove(event: TouchEvent) {
    if (event.touches.length !== 1) touch = null
  }

  function onTouchEnd(event: TouchEvent) {
    const start = touch
    touch = null
    if (!start || !enabled() || document.querySelector(otherModal) || (!options.canStart() && !options.isOpen()) || performance.now() - start.startedAt > 700) return
    const point = [...event.changedTouches].find((item) => item.identifier === start.id)
    if (!point) return

    const dx = point.clientX - start.x
    const dy = point.clientY - start.y
    if (Math.abs(dx) < 64 || Math.abs(dx) <= 1.6 * Math.abs(dy)) return

    if (dx > 0 && !options.isOpen()) options.open()
    else if (dx < 0 && options.isOpen()) options.close()
  }

  function onTouchCancel() {
    touch = null
  }

  onMounted(() => {
    pointerQuery = window.matchMedia('(pointer: coarse)')
    widthQuery = window.matchMedia('(max-width: 1320px)')
    document.addEventListener('touchstart', onTouchStart, { passive: true })
    document.addEventListener('touchmove', onTouchMove, { passive: true })
    document.addEventListener('touchend', onTouchEnd, { passive: true })
    document.addEventListener('touchcancel', onTouchCancel, { passive: true })
  })

  onBeforeUnmount(() => {
    document.removeEventListener('touchstart', onTouchStart)
    document.removeEventListener('touchmove', onTouchMove)
    document.removeEventListener('touchend', onTouchEnd)
    document.removeEventListener('touchcancel', onTouchCancel)
    touch = null
    pointerQuery = null
    widthQuery = null
  })
}

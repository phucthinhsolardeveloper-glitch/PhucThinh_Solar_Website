import type { Ref } from 'vue'

// Chip rails share one pill that glides to the chosen chip the moment it is tapped, while the rail centres
// that chip. Both run on one clock, and a page change often rebuilds the rail halfway, so the motion is kept
// outside the component and simply carries on in the new rail: one movement, never two.
interface Flight {
  from: { x: number, width: number }
  to: { x: number, width: number }
  scrollFrom: number
  scrollTo: number
  startedAt: number
}

const flights = new Map<string, Flight>()
const DURATION_MS = 460
const EASING = 'cubic-bezier(0.22, 1, 0.36, 1)'

// The same curve as the pill's, solved here for scrolling: with one curve the pill's path on screen never doubles back.
function easeOut(t: number) {
  const curve = (a: number, b: number, u: number) => 3 * (1 - u) * (1 - u) * u * a + 3 * (1 - u) * u * u * b + u * u * u
  let low = 0
  let high = 1
  for (let i = 0; i < 18; i += 1) {
    const middle = (low + high) / 2
    if (curve(0.22, 0.36, middle) < t) low = middle
    else high = middle
  }
  return curve(1, 1, (low + high) / 2)
}

export function useSlidingPill(railRef: Ref<HTMLElement | null>, key: string, options: { chip?: string, tone?: 'navy' | 'gold' } = {}) {
  const chipSelector = options.chip ?? '.public-filter-chip'
  let pill: HTMLElement | null = null
  let rail: HTMLElement | null = null
  let current: HTMLElement | null = null
  let observer: MutationObserver | undefined
  let resizeObserver: ResizeObserver | undefined
  let scrollFrame = 0

  const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const activeChip = () => rail?.querySelector<HTMLElement>(`${chipSelector}[aria-current]`) ?? null

  function centredScroll(chip: HTMLElement) {
    if (!rail) return 0
    const left = chip.offsetLeft + chip.offsetWidth / 2 - rail.clientWidth / 2
    return Math.min(Math.max(0, rail.scrollWidth - rail.clientWidth), Math.max(0, left))
  }

  function stopScroll() {
    if (scrollFrame) cancelAnimationFrame(scrollFrame)
    scrollFrame = 0
  }

  function runScroll(flight: Flight) {
    stopScroll()
    const step = () => {
      if (!rail) return
      const progress = Math.min(1, (performance.now() - flight.startedAt) / DURATION_MS)
      rail.scrollLeft = flight.scrollFrom + (flight.scrollTo - flight.scrollFrom) * easeOut(progress)
      scrollFrame = progress < 1 ? requestAnimationFrame(step) : 0
    }
    step()
  }

  function runPill(flight: Flight) {
    if (!pill) return
    const animation = pill.animate([
      { transform: `translateX(${flight.from.x}px) scaleX(${flight.from.width / flight.to.width})` },
      { transform: `translateX(${flight.to.x}px) scaleX(1)` },
    ], { duration: DURATION_MS, easing: EASING })
    animation.currentTime = performance.now() - flight.startedAt
  }

  function drawn() {
    if (!pill?.offsetWidth) return null
    const matrix = new DOMMatrixReadOnly(getComputedStyle(pill).transform)
    return { x: matrix.e, width: pill.offsetWidth * matrix.a }
  }

  function settle(target: HTMLElement) {
    if (!rail || !pill) return
    rail.querySelectorAll('[data-pill-on]').forEach((chip) => { if (chip !== target) chip.removeAttribute('data-pill-on') })
    target.setAttribute('data-pill-on', '')
    current = target
    pill.style.opacity = '1'
    pill.style.top = `${target.offsetTop}px`
    pill.style.height = `${target.offsetHeight}px`
    pill.style.width = `${target.offsetWidth}px`
    pill.style.borderRadius = getComputedStyle(target).borderRadius
    pill.style.transform = `translateX(${target.offsetLeft}px)`
  }

  function glideTo(target: HTMLElement) {
    if (!rail || !pill || target === current) return
    const from = drawn()
    pill.getAnimations().forEach((animation) => animation.cancel())
    settle(target)
    const to = { x: target.offsetLeft, width: target.offsetWidth }
    const scrollTo = centredScroll(target)
    if (!from || reducedMotion()) {
      rail.scrollLeft = scrollTo
      return
    }
    const flight = { from, to, scrollFrom: rail.scrollLeft, scrollTo, startedAt: performance.now() }
    flights.set(key, flight)
    runPill(flight)
    runScroll(flight)
  }

  function onClick(event: MouseEvent) {
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) return
    const chip = (event.target as Element | null)?.closest<HTMLElement>(chipSelector)
    if (chip && rail?.contains(chip)) glideTo(chip)
  }

  function follow() {
    const target = activeChip()
    if (target) glideTo(target)
  }

  function refit() {
    if (current && rail?.contains(current)) {
      settle(current)
      flights.delete(key)
    }
  }

  function attach(element: HTMLElement) {
    rail = element
    rail.style.scrollBehavior = 'auto'
    pill = document.createElement('span')
    pill.className = 'rail-pill'
    pill.setAttribute('aria-hidden', 'true')
    if (options.tone) pill.dataset.tone = options.tone
    rail.prepend(pill)
    rail.setAttribute('data-pill-rail', '')
    rail.addEventListener('click', onClick)
    rail.addEventListener('pointerdown', stopScroll, { passive: true })
    rail.addEventListener('wheel', stopScroll, { passive: true })

    const target = activeChip()
    if (target) {
      settle(target)
      const flight = flights.get(key)
      const inFlight = flight && performance.now() - flight.startedAt < DURATION_MS
      if (flight && inFlight && !reducedMotion() && Math.abs(flight.to.x - target.offsetLeft) < 2) {
        flight.scrollTo = centredScroll(target)
        runPill(flight)
        runScroll(flight)
      } else {
        rail.scrollLeft = centredScroll(target)
      }
    }
    observer = new MutationObserver(follow)
    observer.observe(rail, { subtree: true, attributes: true, attributeFilter: ['aria-current'] })
    if ('ResizeObserver' in window) {
      resizeObserver = new ResizeObserver(refit)
      resizeObserver.observe(rail)
    }
  }

  function detach() {
    stopScroll()
    observer?.disconnect()
    resizeObserver?.disconnect()
    rail?.removeEventListener('click', onClick)
    rail?.removeEventListener('pointerdown', stopScroll)
    rail?.removeEventListener('wheel', stopScroll)
    rail?.removeAttribute('data-pill-rail')
    pill?.remove()
    pill = null
    rail = null
    current = null
  }

  watch(railRef, (element) => {
    detach()
    if (element) attach(element)
  }, { flush: 'post' })
  onMounted(() => { if (railRef.value && !rail) attach(railRef.value) })
  onBeforeUnmount(detach)
}

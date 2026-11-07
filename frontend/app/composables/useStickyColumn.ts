import type { Ref } from 'vue'

// A column taller than the screen scrolls with the page and stops at its bottom edge going down and
// its top edge going up, so every option is reachable without a second scrollbar inside the page.
export function useStickyColumn(column: Ref<HTMLElement | null>, gap = 20) {
  let top = gap
  let lastScrollY = 0
  let frame = 0
  let observer: ResizeObserver | undefined

  function place() {
    frame = 0
    const element = column.value
    if (!element) return
    const scrollY = Math.max(window.scrollY, 0)
    const lowest = Math.min(gap, window.innerHeight - element.offsetHeight - gap)
    top = Math.min(gap, Math.max(lowest, top - (scrollY - lastScrollY)))
    lastScrollY = scrollY
    element.style.top = `${top}px`
  }

  function schedule() {
    if (!frame) frame = requestAnimationFrame(place)
  }

  onMounted(() => {
    lastScrollY = Math.max(window.scrollY, 0)
    window.addEventListener('scroll', schedule, { passive: true })
    window.addEventListener('resize', schedule, { passive: true })
    if (column.value && 'ResizeObserver' in window) {
      observer = new ResizeObserver(schedule)
      observer.observe(column.value)
    }
    place()
  })

  onBeforeUnmount(() => {
    window.removeEventListener('scroll', schedule)
    window.removeEventListener('resize', schedule)
    observer?.disconnect()
    if (frame) cancelAnimationFrame(frame)
  })
}

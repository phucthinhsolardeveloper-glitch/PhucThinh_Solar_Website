function hasScrollableParent(target: EventTarget | null, deltaY: number): boolean {
  let element = target instanceof HTMLElement ? target : null

  while (element && element !== document.body) {
    const style = window.getComputedStyle(element)
    const scrollable = /(auto|scroll|overlay)/.test(style.overflowY) && element.scrollHeight > element.clientHeight
    if (scrollable) {
      const canScrollUp = element.scrollTop > 0
      const canScrollDown = element.scrollTop + element.clientHeight < element.scrollHeight - 1
      return deltaY < 0 ? canScrollUp : canScrollDown
    }
    element = element.parentElement
  }

  return false
}

/** Keep wheel scrolling comfortable without hijacking touch, trackpad or inner panels. */
export function usePageScrollExperience() {
  function onWheel(event: WheelEvent) {
    if (!import.meta.client || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey) return
    if (event.deltaX !== 0 || Math.abs(event.deltaY) < 50 || hasScrollableParent(event.target, event.deltaY)) return

    event.preventDefault()
    const delta = event.deltaMode === WheelEvent.DOM_DELTA_LINE ? event.deltaY * 16 : event.deltaY
    window.scrollBy({ top: delta * 1.6, behavior: 'smooth' })
  }

  onMounted(() => window.addEventListener('wheel', onWheel, { passive: false }))
  onBeforeUnmount(() => window.removeEventListener('wheel', onWheel))
}

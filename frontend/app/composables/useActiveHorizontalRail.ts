export function useActiveHorizontalRail(railRef: Ref<HTMLElement | null | undefined>) {
  let observer: MutationObserver | null = null
  let frame: number | null = null
  let firstAlignment = true

  function alignActiveItem() {
    if (!import.meta.client || !railRef.value) return
    const rail = railRef.value
    const active = rail.querySelector<HTMLElement>('[aria-current="page"], [aria-current="location"]')
    if (!active) return

    const maxScrollLeft = Math.max(0, rail.scrollWidth - rail.clientWidth)
    const railRect = rail.getBoundingClientRect()
    const activeRect = active.getBoundingClientRect()
    const visibleLeft = railRect.left + rail.clientLeft + 12
    const visibleRight = railRect.left + rail.clientLeft + rail.clientWidth - 12
    let targetLeft = rail.scrollLeft
    if (activeRect.left < visibleLeft) targetLeft += activeRect.left - visibleLeft
    else if (activeRect.right > visibleRight) targetLeft += activeRect.right - visibleRight
    targetLeft = Math.min(maxScrollLeft, Math.max(0, targetLeft))
    if (Math.abs(targetLeft - rail.scrollLeft) < 1) {
      firstAlignment = false
      return
    }
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
    rail.scrollTo({ left: targetLeft, behavior: firstAlignment || reducedMotion ? 'auto' : 'smooth' })
    firstAlignment = false
  }

  function scheduleAlignment() {
    if (frame !== null) return
    frame = window.requestAnimationFrame(() => {
      frame = null
      alignActiveItem()
    })
  }

  const stopWatchingRail = watch(railRef, (rail) => {
    if (!observer || !rail) return
    observer.disconnect()
    observer.observe(rail, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-current'] })
    scheduleAlignment()
  }, { flush: 'post' })

  onMounted(() => {
    observer = new MutationObserver(scheduleAlignment)
    if (railRef.value) observer.observe(railRef.value, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-current'] })
    scheduleAlignment()
    window.addEventListener('resize', scheduleAlignment, { passive: true })
  })

  onBeforeUnmount(() => {
    stopWatchingRail()
    observer?.disconnect()
    observer = null
    window.removeEventListener('resize', scheduleAlignment)
    if (frame !== null) window.cancelAnimationFrame(frame)
    frame = null
  })
}

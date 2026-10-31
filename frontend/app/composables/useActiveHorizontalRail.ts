export function useActiveHorizontalRail(railRef: Ref<HTMLElement | null | undefined>) {
  let observer: MutationObserver | null = null
  let frame: number | null = null
  let firstAlignment = true

  function alignActiveItem() {
    if (!import.meta.client || !railRef.value) return
    const rail = railRef.value
    const active = rail.querySelector<HTMLElement>('[aria-current="page"]')
    if (!active) return

    const maxScrollLeft = Math.max(0, rail.scrollWidth - rail.clientWidth)
    const targetLeft = Math.min(
      maxScrollLeft,
      Math.max(0, active.offsetLeft - (rail.clientWidth - active.offsetWidth) / 2),
    )
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

  onMounted(() => {
    nextTick(alignActiveItem)
    observer = new MutationObserver(scheduleAlignment)
    observer.observe(railRef.value ?? document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['aria-current'] })
    window.addEventListener('resize', scheduleAlignment, { passive: true })
  })

  onBeforeUnmount(() => {
    observer?.disconnect()
    observer = null
    window.removeEventListener('resize', scheduleAlignment)
    if (frame !== null) window.cancelAnimationFrame(frame)
    frame = null
  })
}

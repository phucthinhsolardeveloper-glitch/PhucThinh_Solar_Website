import type { Ref } from 'vue'

// Which section is the reader in? The last one whose top has passed the line under the sticky rail. It is worked
// out from real positions on every frame, so a fast scroll cannot skip a section, sections that sit inside others
// cannot pull the highlight backwards, and content that opens or loads later simply corrects itself.
export function useSectionSpy(ids: () => string[], active: Ref<string>, rail?: Ref<HTMLElement | null>) {
  let frame = 0
  let lockedUntil = 0
  let lockTimer: ReturnType<typeof setTimeout> | undefined
  let resizeObserver: ResizeObserver | undefined

  function update() {
    frame = 0
    if (performance.now() < lockedUntil) return
    const railBottom = rail?.value?.getBoundingClientRect().bottom ?? 0
    const line = Math.min(Math.max(railBottom, 80), window.innerHeight * 0.4) + 16
    let best = ''
    let bestTop = -Infinity
    let firstId = ''
    for (const id of ids()) {
      const element = document.getElementById(id)
      if (!element) continue
      firstId ||= id
      const top = element.getBoundingClientRect().top
      if (top <= line && top >= bestTop) {
        best = id
        bestTop = top
      }
    }
    const next = best || firstId
    if (next && next !== active.value) active.value = next
  }

  function schedule() {
    if (!frame) frame = requestAnimationFrame(update)
  }

  function unlock() {
    lockedUntil = 0
    clearTimeout(lockTimer)
    schedule()
  }

  // A tapped chip stays lit while the page travels to it, without lighting up everything in between.
  function lockTo(id: string) {
    active.value = id
    lockedUntil = performance.now() + 1400
    clearTimeout(lockTimer)
    lockTimer = setTimeout(unlock, 1400)
    window.addEventListener('scrollend', unlock, { once: true })
  }

  onMounted(() => {
    window.addEventListener('scroll', schedule, { passive: true })
    window.addEventListener('resize', schedule, { passive: true })
    window.addEventListener('wheel', unlock, { passive: true })
    window.addEventListener('touchstart', unlock, { passive: true })
    if ('ResizeObserver' in window) {
      resizeObserver = new ResizeObserver(schedule)
      resizeObserver.observe(document.body)
    }
    nextTick(update)
  })
  onBeforeUnmount(() => {
    window.removeEventListener('scroll', schedule)
    window.removeEventListener('resize', schedule)
    window.removeEventListener('wheel', unlock)
    window.removeEventListener('touchstart', unlock)
    window.removeEventListener('scrollend', unlock)
    resizeObserver?.disconnect()
    clearTimeout(lockTimer)
    if (frame) cancelAnimationFrame(frame)
  })

  return { lockTo, refresh: schedule }
}

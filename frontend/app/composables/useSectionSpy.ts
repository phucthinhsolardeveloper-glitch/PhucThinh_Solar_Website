import type { Ref } from 'vue'

// Which section is the reader in? The last one whose top has passed the line under the sticky rail. It is worked
// out from real positions on every frame, so a fast scroll cannot skip a section, sections that sit inside others
// cannot pull the highlight backwards, and content that opens or loads later simply corrects itself.
export function useSectionSpy(ids: () => string[], active: Ref<string>, rail?: Ref<HTMLElement | null>) {
  let frame = 0
  let lockedUntil = 0
  let lockTimer: ReturnType<typeof setTimeout> | undefined
  let tappedId = ''
  let resizeObserver: ResizeObserver | undefined

  function update() {
    frame = 0
    if (performance.now() < lockedUntil) return
    const railBottom = rail?.value?.getBoundingClientRect().bottom ?? 0
    const line = Math.min(Math.max(railBottom, 80), window.innerHeight * 0.4) + 16
    const scrollPadding = Number.parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop) || 0
    let best = ''
    let bestTop = -Infinity
    let firstId = ''
    for (const id of ids()) {
      const element = document.getElementById(id)
      if (!element) continue
      firstId ||= id
      const top = element.getBoundingClientRect().top
      // A tapped section parks at its own scroll margin, which can sit below the line. It still counts as reached.
      const margin = (Number.parseFloat(getComputedStyle(element).scrollMarginTop) || 0) + scrollPadding
      if (top <= Math.max(line, margin + 40) && top >= bestTop) {
        best = id
        bestTop = top
      }
    }
    // A section near the end may be unable to scroll up to the line, so a tapped one stays lit at the bottom.
    const atEnd = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2
    const next = (atEnd && tappedId) || best || firstId
    if (next && next !== active.value) active.value = next
  }

  function schedule() {
    if (!frame) frame = requestAnimationFrame(update)
  }

  function release() {
    tappedId = ''
    unlock()
  }

  function unlock() {
    lockedUntil = 0
    clearTimeout(lockTimer)
    schedule()
  }

  // A tapped chip stays lit while the page travels to it, without lighting up everything in between.
  function lockTo(id: string) {
    active.value = id
    tappedId = id
    lockedUntil = performance.now() + 1400
    clearTimeout(lockTimer)
    lockTimer = setTimeout(unlock, 1400)
    window.addEventListener('scrollend', unlock, { once: true })
  }

  onMounted(() => {
    window.addEventListener('scroll', schedule, { passive: true })
    window.addEventListener('resize', schedule, { passive: true })
    window.addEventListener('wheel', release, { passive: true })
    window.addEventListener('touchstart', release, { passive: true })
    if ('ResizeObserver' in window) {
      resizeObserver = new ResizeObserver(schedule)
      resizeObserver.observe(document.body)
    }
    nextTick(update)
  })
  onBeforeUnmount(() => {
    window.removeEventListener('scroll', schedule)
    window.removeEventListener('resize', schedule)
    window.removeEventListener('wheel', release)
    window.removeEventListener('touchstart', release)
    window.removeEventListener('scrollend', unlock)
    resizeObserver?.disconnect()
    clearTimeout(lockTimer)
    if (frame) cancelAnimationFrame(frame)
  })

  return { lockTo, refresh: schedule }
}

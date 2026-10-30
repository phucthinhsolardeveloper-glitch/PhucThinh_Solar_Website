import type { Ref } from 'vue'

export interface UseTocScrollspyOptions {
  contentSelector?: string
  headingSelector?: string
  tocContainerRef?: Ref<HTMLElement | null | undefined>
  headerOffset?: number
}

/**
 * TOC Scrollspy:
 * - Theo dõi các heading (h2, h3) trong content.
 * - Cập nhật activeHeadingId theo vị trí cuộn.
 * - Hỗ trợ scrollIntoView cho active item trong TOC list.
 * - Hỗ trợ smooth scrolling có tính header offset (100px).
 */
export function useTocScrollspy(options: UseTocScrollspyOptions = {}) {
  const contentSelector = options.contentSelector ?? '#solution-content'
  const headingSelector = options.headingSelector ?? 'h2[id], h3[id]'
  const headerOffset = options.headerOffset ?? 100

  const activeHeadingId = ref<string>('')
  let isClickScrolling = false
  let clickScrollTimer: ReturnType<typeof setTimeout> | null = null
  let rafId: number | null = null

  function getHeadings(): HTMLElement[] {
    if (typeof document === 'undefined') return []
    const content = document.querySelector(contentSelector)
    if (!content) return []
    return Array.from(content.querySelectorAll<HTMLElement>(headingSelector))
  }

  function updateActiveHeading() {
    if (isClickScrolling || typeof window === 'undefined') return

    const headings = getHeadings()
    if (!headings.length) return

    const scrollY = window.scrollY

    // Về gần đầu trang thì kích hoạt heading đầu tiên
    if (scrollY < 120 && headings[0]) {
      activeHeadingId.value = headings[0].id
      return
    }

    // Cuộn đến cuối trang thì kích hoạt heading cuối cùng
    const isAtBottom = window.innerHeight + scrollY >= document.documentElement.scrollHeight - 50
    if (isAtBottom) {
      const last = headings[headings.length - 1]
      if (last) {
        activeHeadingId.value = last.id
        return
      }
    }

    // Tìm heading gần nhất phía trên ngưỡng headerOffset
    let currentId = headings[0]?.id || ''
    for (const heading of headings) {
      const top = heading.getBoundingClientRect().top
      if (top <= headerOffset + 25) {
        currentId = heading.id
      } else {
        break
      }
    }

    if (currentId && currentId !== activeHeadingId.value) {
      activeHeadingId.value = currentId
    }
  }

  function onScroll() {
    if (rafId !== null) return
    rafId = window.requestAnimationFrame(() => {
      rafId = null
      updateActiveHeading()
    })
  }

  function onUserInterrupt() {
    if (isClickScrolling) {
      isClickScrolling = false
      if (clickScrollTimer !== null) {
        clearTimeout(clickScrollTimer)
        clickScrollTimer = null
      }
      updateActiveHeading()
    }
  }

  function scrollToHeading(id: string) {
    if (typeof window === 'undefined' || typeof document === 'undefined') return
    const target = document.getElementById(id)
    if (!target) return

    activeHeadingId.value = id
    isClickScrolling = true

    const elementPosition = target.getBoundingClientRect().top
    const offsetPosition = elementPosition + window.scrollY - headerOffset

    window.scrollTo({
      top: Math.max(0, Math.round(offsetPosition)),
      behavior: 'smooth',
    })

    if (window.history.pushState) {
      window.history.pushState(null, '', `#${id}`)
    }

    if (clickScrollTimer !== null) {
      clearTimeout(clickScrollTimer)
    }
    clickScrollTimer = setTimeout(() => {
      isClickScrolling = false
      clickScrollTimer = null
    }, 850)
  }

  // Tự động cuộn TOC item vào khung nhìn nếu danh sách mục lục dài
  watch(activeHeadingId, (newId) => {
    if (!newId || typeof window === 'undefined') return
    nextTick(() => {
      const container = options.tocContainerRef?.value
      const activeEl = container
        ? container.querySelector<HTMLElement>(`[data-toc-id="${newId}"]`)
        : document.querySelector<HTMLElement>(`[data-toc-id="${newId}"]`)

      if (activeEl && typeof activeEl.scrollIntoView === 'function') {
        activeEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
      }
    })
  })

  onMounted(() => {
    nextTick(() => {
      const hash = window.location.hash.slice(1)
      if (hash && document.getElementById(hash)) {
        activeHeadingId.value = hash
      } else {
        updateActiveHeading()
      }
    })

    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll, { passive: true })
    window.addEventListener('wheel', onUserInterrupt, { passive: true })
    window.addEventListener('touchmove', onUserInterrupt, { passive: true })
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('scroll', onScroll)
      window.removeEventListener('resize', onScroll)
      window.removeEventListener('wheel', onUserInterrupt)
      window.removeEventListener('touchmove', onUserInterrupt)
      if (rafId !== null) {
        window.cancelAnimationFrame(rafId)
        rafId = null
      }
      if (clickScrollTimer !== null) {
        clearTimeout(clickScrollTimer)
        clickScrollTimer = null
      }
    }
  })

  return {
    activeHeadingId,
    scrollToHeading,
    updateActiveHeading,
  }
}

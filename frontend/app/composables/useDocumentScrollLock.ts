interface OriginalBodyStyle {
  overflow: string
  paddingRight: string
}

/** Share one reference-counted body lock across menus and media dialogs. */
export function useDocumentScrollLock() {
  const owner = `pts-scroll-lock-${useId()}`
  const owners = useState<string[]>('pts-document-scroll-lock-owners', () => [])
  const originalStyle = useState<OriginalBodyStyle | null>('pts-document-scroll-lock-original-style', () => null)

  function setLocked(locked: boolean) {
    if (!import.meta.client) return
    const alreadyOwnsLock = owners.value.includes(owner)

    if (locked) {
      if (alreadyOwnsLock) return
      if (owners.value.length === 0) {
        originalStyle.value = {
          overflow: document.body.style.overflow,
          paddingRight: document.body.style.paddingRight,
        }
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth
        if (scrollbarWidth > 0) {
          const currentPadding = Number.parseFloat(getComputedStyle(document.body).paddingRight) || 0
          document.body.style.paddingRight = `${currentPadding + scrollbarWidth}px`
        }
      }
      owners.value = [...owners.value, owner]
      document.body.style.overflow = 'hidden'
      return
    }

    if (!alreadyOwnsLock) return
    owners.value = owners.value.filter((activeOwner) => activeOwner !== owner)
    if (owners.value.length === 0) {
      document.body.style.overflow = originalStyle.value?.overflow ?? ''
      document.body.style.paddingRight = originalStyle.value?.paddingRight ?? ''
      originalStyle.value = null
    }
  }

  onScopeDispose(() => setLocked(false))
  return setLocked
}

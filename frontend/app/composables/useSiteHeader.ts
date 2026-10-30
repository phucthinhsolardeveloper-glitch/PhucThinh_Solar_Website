export function useSiteHeader() {
  const isHeaderHidden = useState<boolean>('site-header-hidden', () => false)
  const isScrolled = useState<boolean>('site-header-scrolled', () => false)

  function showHeader() {
    isHeaderHidden.value = false
  }

  function hideHeader() {
    isHeaderHidden.value = true
  }

  return {
    isHeaderHidden,
    isScrolled,
    showHeader,
    hideHeader,
  }
}

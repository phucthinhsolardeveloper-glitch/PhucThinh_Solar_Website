export function useStorefrontModal() {
  const isConsultationOpen = useState<boolean>('storefront-consultation-modal-open', () => false)
  const isDealerOpen = useState<boolean>('storefront-dealer-modal-open', () => false)
  const consultationSubject = useState<string>('storefront-consultation-subject', () => '')
  const quoteCartMode = useState<boolean>('storefront-consultation-quote-cart', () => false)

  function openConsultation(subject?: string) {
    // Every opening resets prior context, so a stale cart is never attached
    // to a regular consultation launched from another page.
    quoteCartMode.value = false
    consultationSubject.value = subject ?? ''
    isConsultationOpen.value = true
  }

  function openCartQuote() {
    openConsultation('Giỏ hàng: Yêu cầu báo giá')
    quoteCartMode.value = true
  }

  function closeConsultation() {
    isConsultationOpen.value = false
    quoteCartMode.value = false
  }

  function openDealer() {
    isDealerOpen.value = true
  }

  function closeDealer() {
    isDealerOpen.value = false
  }

  return {
    isConsultationOpen,
    isDealerOpen,
    consultationSubject,
    quoteCartMode,
    openConsultation,
    openCartQuote,
    closeConsultation,
    openDealer,
    closeDealer,
  }
}

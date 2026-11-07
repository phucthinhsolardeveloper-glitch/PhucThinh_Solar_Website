export function useStorefrontModal() {
  const isConsultationOpen = useState<boolean>('storefront-consultation-modal-open', () => false)
  const isDealerOpen = useState<boolean>('storefront-dealer-modal-open', () => false)
  const consultationSubject = useState<string>('storefront-consultation-subject', () => '')

  function openConsultation(subject?: string) {
    // Every opening states its own subject, so an earlier page's topic cannot carry over.
    consultationSubject.value = subject ?? ''
    isConsultationOpen.value = true
  }

  function closeConsultation() {
    isConsultationOpen.value = false
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
    openConsultation,
    closeConsultation,
    openDealer,
    closeDealer,
  }
}

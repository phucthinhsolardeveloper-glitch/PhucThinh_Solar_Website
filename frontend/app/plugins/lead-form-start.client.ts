// The enquiry funnel the server cannot see: lead_form_start when the visitor first types, chooses or taps a choice
// in a lead form, and lead_form_submit + lead_form_error (validation) when the browser refuses a send because a
// field is missing or malformed. Sends that reach the server are reported by useLeadForm. Only which form and which
// page: nothing that was typed.
import { leadFormRefused, leadFormStart } from '#shared/lead-analytics'

export default defineNuxtPlugin(() => {
  const started = new WeakSet<object>()
  const attempts = new WeakMap<object, number>()
  const used = (event: Event) => {
    // Values filled in by the page itself are not the visitor starting the form.
    if (!event.isTrusted) return
    const form = leadFormStart(event.target, started)
    if (form) pushDataLayer('lead_form_start', { ...form, page_path: window.location.pathname })
  }
  document.addEventListener('input', used, { capture: true, passive: true })
  document.addEventListener('change', used, { capture: true, passive: true })
  // Choice chips are buttons: a click is the visitor's first answer.
  document.addEventListener('click', (event) => {
    if (!(event.target as Element | null)?.closest?.('button, [role="button"], label')) return
    used(event)
  }, { capture: true, passive: true })

  // A send starts with a submit button or Enter in a field; the browser then checks every field.
  const tried = (event: Event) => {
    if (!event.isTrusted) return
    const target = event.target as Element | null
    const isSubmit = event.type === 'click' ? !!target?.closest?.('button[type="submit"], input[type="submit"]')
      : (event as KeyboardEvent).key === 'Enter' && target?.tagName === 'INPUT'
    const form = isSubmit ? target?.closest?.('form[data-lead-form]') : null
    if (form) attempts.set(form, Date.now())
  }
  document.addEventListener('click', tried, { capture: true, passive: true })
  document.addEventListener('keydown', tried, { capture: true, passive: true })
  // "invalid" does not bubble, so it is caught on the way down; one report per attempt.
  document.addEventListener('invalid', (event) => {
    const form = leadFormRefused(event.target, attempts, Date.now())
    if (!form) return
    const funnel = { ...form, page_path: window.location.pathname }
    pushDataLayer('lead_form_submit', funnel)
    pushDataLayer('lead_form_error', { ...funnel, error_type: 'validation' })
  }, { capture: true })
})

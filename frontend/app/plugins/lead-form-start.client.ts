// lead_form_start: the visitor typed or chose something in a lead form. With lead_form_submit, lead_form_error and
// generate_lead it shows where enquiries are lost. Only which form and which page: nothing that was typed.
import { leadFormStart } from '#shared/lead-analytics'

export default defineNuxtPlugin(() => {
  const started = new WeakSet<object>()
  const used = (event: Event) => {
    // Values filled in by the page itself are not the visitor starting the form.
    if (!event.isTrusted) return
    const form = leadFormStart(event.target, started)
    if (form) pushDataLayer('lead_form_start', { ...form, page_path: window.location.pathname })
  }
  document.addEventListener('input', used, { capture: true, passive: true })
  document.addEventListener('change', used, { capture: true, passive: true })
})

/** Which form produced a GA4 generate_lead. Fixed values only: nothing the visitor typed. */
export type LeadFormSource =
  | 'home'
  | 'contact_page'
  | 'consultation_popup'
  | 'cart_quote'
  | 'solar_estimator'
  | 'product_page'
  | 'dealer_page'
  | 'dealer_popup'

const formSources: readonly string[] = ['home', 'contact_page', 'consultation_popup', 'cart_quote', 'solar_estimator', 'product_page', 'dealer_page', 'dealer_popup'] satisfies LeadFormSource[]
const formKinds: readonly string[] = ['contact', 'dealer', 'product']

/** Why a lead_form_error happened, from the HTTP status alone: never the message, which can quote the input. */
export function leadErrorType(status: number | null): 'validation' | 'session' | 'rate_limit' | 'server' | 'network' | 'other' {
  if (status === null) return 'network'
  if (status === 422) return 'validation'
  if (status === 419) return 'session'
  if (status === 429) return 'rate_limit'
  return status >= 500 ? 'server' : 'other'
}

interface FormElement { getAttribute(name: string): string | null }

/**
 * The lead form a visitor has just started filling in, once per form on screen; null for anything else.
 * A form names itself with data-lead-form (the source) and data-lead-kind; only the fixed values count.
 */
export function leadFormStart(target: unknown, started: WeakSet<object>): { form_kind: string, form_source: string } | null {
  const element = target as { closest?: (selector: string) => FormElement | null } | null
  const form = element?.closest?.('form[data-lead-form]')
  if (!form || started.has(form)) return null
  const source = form.getAttribute('data-lead-form') ?? ''
  const kind = form.getAttribute('data-lead-kind') ?? ''
  if (!formSources.includes(source) || !formKinds.includes(kind)) return null
  started.add(form)
  return { form_kind: kind, form_source: source }
}

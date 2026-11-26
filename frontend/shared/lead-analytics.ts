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

/** The lead form an element belongs to, when that form names itself with fixed values; null for anything else. */
export function leadFormOf(target: unknown): { form: FormElement, form_kind: string, form_source: string } | null {
  const element = target as { closest?: (selector: string) => FormElement | null } | null
  const form = element?.closest?.('form[data-lead-form]')
  if (!form) return null
  const source = form.getAttribute('data-lead-form') ?? ''
  const kind = form.getAttribute('data-lead-kind') ?? ''
  if (!formSources.includes(source) || !formKinds.includes(kind)) return null
  return { form, form_kind: kind, form_source: source }
}

/**
 * The lead form a visitor has just started using (typed, chose, or tapped a choice), once per form on screen;
 * null for anything else.
 */
export function leadFormStart(target: unknown, started: WeakSet<object>): { form_kind: string, form_source: string } | null {
  const found = leadFormOf(target)
  if (!found || started.has(found.form)) return null
  started.add(found.form)
  return { form_kind: found.form_kind, form_source: found.form_source }
}

/**
 * A send the browser refused because a field is missing or malformed: the form never reaches the server, so this
 * is the only trace of it. One report per attempt, however many fields are wrong: `attempts` holds the time of the
 * visitor's last send per form, and a report clears it.
 */
export function leadFormRefused(target: unknown, attempts: WeakMap<object, number>, now: number): { form_kind: string, form_source: string } | null {
  const found = leadFormOf(target)
  const at = found ? attempts.get(found.form) : undefined
  if (!found || at === undefined || now - at > 1500) return null
  attempts.delete(found.form)
  return { form_kind: found.form_kind, form_source: found.form_source }
}

import type { PublicLeadFormSecurity, PublicLeadFormResult } from '#shared/contracts/public-lead-form'
import { isPublicLeadFormSecurity, isPublicLeadFormResult } from '#shared/contracts/public-lead-form'
import { leadSourceFromUrl } from '#shared/lead-source'
import { leadErrorStatus, sendWithFreshToken, toLeadFormError } from '#shared/lead-submit'
import { leadErrorType, type LeadFormSource } from '#shared/lead-analytics'

type LeadKind = 'contact' | 'dealer' | 'product'

async function loadRecaptcha(siteKey: string): Promise<any> {
  if (!import.meta.client) throw new Error('reCAPTCHA is client-only')
  const w = window as any
  if (w.grecaptcha?.execute) return w.grecaptcha

  const id = 'pts-recaptcha-v3'
  let script = document.getElementById(id) as HTMLScriptElement | null
  if (!script) {
    script = document.createElement('script')
    script.id = id
    script.async = true
    script.defer = true
    script.src = 'https://www.google.com/recaptcha/api.js?render=' + encodeURIComponent(siteKey)
    document.head.appendChild(script)
  }
  await new Promise<void>((resolve, reject) => {
    if ((window as any).grecaptcha?.execute) return resolve()
    const onLoad = () => resolve()
    const onError = () => reject(new Error('Không thể tải reCAPTCHA.'))
    script!.addEventListener('load', onLoad, { once: true })
    script!.addEventListener('error', onError, { once: true })
    setTimeout(() => (window as any).grecaptcha?.execute ? resolve() : reject(new Error('reCAPTCHA quá thời gian chờ.')), 10000)
  })
  return (window as any).grecaptcha
}

async function recaptchaToken(security: PublicLeadFormSecurity, action: string): Promise<string> {
  if (!security.recaptcha.active) return ''
  const grecaptcha = await loadRecaptcha(security.recaptcha.siteKey)
  await new Promise<void>((resolve) => grecaptcha.ready(resolve))
  const token = await grecaptcha.execute(security.recaptcha.siteKey, { action })
  if (typeof token !== 'string' || !token) throw new Error('Không thể xác thực chống spam.')
  return token
}

export function useLeadForm() {
  const security = useState<PublicLeadFormSecurity | null>('lead-form-security', () => null)
  const loadingSecurity = useState('lead-form-security-loading', () => false)

  async function ensureSecurity() {
    if (security.value) return security.value
    loadingSecurity.value = true
    try {
      const payload = await $fetch<unknown>('/_api/lead-form')
      if (!isPublicLeadFormSecurity(payload)) throw new Error('Invalid lead form security contract')
      security.value = payload
      return payload
    } finally {
      loadingSecurity.value = false
    }
  }

  async function refreshSecurity() {
    security.value = null
    return ensureSecurity()
  }

  async function post(kind: LeadKind, data: Record<string, string | number>) {
    const meta = await ensureSecurity()
    const action = kind === 'contact' ? 'contact' : kind === 'product' ? 'Newsletter' : 'Newsletter2'
    // A reCAPTCHA token works once, so a retry asks for a new one.
    const token = await recaptchaToken(meta, action)
    let source = ''
    if (import.meta.client) {
      const campaign = rememberedLeadCampaign()
      source = leadSourceFromUrl(window.location.href, campaign)
    }
    // Keep this rate-limited mutation on the browser-to-Caddy path so Apache
    // can restore the real client IP from X-Forwarded-For; a Nitro proxy hop
    // would make every visitor share the Nuxt container's rate-limit key.
    return $fetch<unknown>('/api/v1/lead-form', {
      method: 'POST',
      credentials: 'same-origin',
      body: { kind, csrfToken: meta.csrfToken, recaptchaToken: token, data: { ...data, source_url: source } },
    })
  }

  /** `formSource` names the form for analytics only; it is not sent to Laravel. */
  async function submit(kind: LeadKind, data: Record<string, string | number>, formSource: LeadFormSource) {
    const page = import.meta.client ? window.location.pathname : ''
    const funnel = { form_kind: kind, form_source: formSource, page_path: page }
    // One submit per attempt (a silent token retry is the same attempt), then exactly one outcome.
    pushDataLayer('lead_form_submit', { ...funnel })
    let payload: unknown
    try {
      payload = await sendWithFreshToken(() => post(kind, data), refreshSecurity)
    } catch (reason) {
      pushDataLayer('lead_form_error', { ...funnel, error_type: leadErrorType(leadErrorStatus(reason)) })
      throw toLeadFormError(reason)
    }
    if (!isPublicLeadFormResult(payload)) {
      pushDataLayer('lead_form_error', { ...funnel, error_type: 'other' })
      throw toLeadFormError(null)
    }
    // GA4 generate_lead: which form and which page only, never what the visitor typed.
    pushDataLayer('generate_lead', { form_kind: kind, form_source: formSource, page_path: import.meta.client ? window.location.pathname : '' })
    return payload
  }

  return { security, loadingSecurity, ensureSecurity, refreshSecurity, submit }
}

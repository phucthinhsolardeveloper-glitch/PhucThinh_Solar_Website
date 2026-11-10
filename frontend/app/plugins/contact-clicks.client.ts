// Every hotline and Zalo link on the site (header, footer, floating buttons, buy bar, forms) reports a click to
// Google Tag Manager as click_call / click_zalo, with the page it came from. One listener, no per-button wiring.
export default defineNuxtPlugin(() => {
  document.addEventListener('click', (event) => {
    const link = (event.target as Element | null)?.closest?.('a[href]') as HTMLAnchorElement | null
    if (!link) return
    const href = link.getAttribute('href') || ''
    const kind = /^tel:/i.test(href) ? 'click_call' : /^https?:\/\/(?:[a-z0-9-]+\.)*zalo\.me\b|^zalo:/i.test(href) ? 'click_zalo' : ''
    if (!kind) return
    pushDataLayer(kind, { link_url: href.slice(0, 120), page_path: window.location.pathname })
  }, { capture: true, passive: true })
})

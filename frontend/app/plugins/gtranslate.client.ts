const widgetUrl = 'https://cdn.gtranslate.net/widgets/latest/popup.js'

export default defineNuxtPlugin(() => {
  let loading = false

  function mountWidget() {
    const wrappers = document.querySelectorAll<HTMLElement>('.gtranslate_wrapper')
    if (!wrappers.length || ![...wrappers].some(wrapper => !wrapper.querySelector('.gt_switcher-popup')) || loading) return

    loading = true
    document.querySelectorAll('style.gtranslate_css').forEach(style => style.remove())
    const script = document.createElement('script')
    script.dataset.ptsGtranslate = 'true'
    script.src = widgetUrl
    script.defer = true
    script.addEventListener('load', () => { loading = false }, { once: true })
    script.addEventListener('error', () => { loading = false }, { once: true })
    document.head.append(script)
  }

  // This observer sees every DOM change on the site, so it only ever queues one idle check.
  let queued = false
  function queueMount() {
    if (queued) return
    queued = true
    const run = () => {
      queued = false
      mountWidget()
    }
    if ('requestIdleCallback' in window) window.requestIdleCallback(run, { timeout: 2000 })
    else setTimeout(run, 300)
  }

  const observer = new MutationObserver(queueMount)
  onNuxtReady(() => {
    observer.observe(document.body, { childList: true, subtree: true })
    queueMount()
  })
})

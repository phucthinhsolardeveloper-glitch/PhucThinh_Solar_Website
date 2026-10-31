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

  const observer = new MutationObserver(mountWidget)
  onNuxtReady(() => {
    observer.observe(document.body, { childList: true, subtree: true })
    mountWidget()
  })
})

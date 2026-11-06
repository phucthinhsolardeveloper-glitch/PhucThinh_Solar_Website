// Progressive disclosure for phones. Any element marked data-fold="420" is clipped by CSS at phone
// width and gets one "Xem thêm" button. The full content stays in the HTML for search and keyboards.
function prepare(el: HTMLElement) {
  el.dataset.foldReady = 'true'
  if (window.innerWidth <= 820 && el.scrollHeight <= el.clientHeight + 90) {
    el.classList.add('m-fold-open')
    return
  }
  const button = document.createElement('button')
  button.type = 'button'
  button.className = 'm-fold-more'
  button.setAttribute('aria-expanded', 'false')
  button.innerHTML = '<span>Xem thêm</span>'
  button.addEventListener('click', () => {
    if (el.classList.contains('m-fold-open')) close(el)
    else open(el)
  })
  el.append(button)
  el.addEventListener('focusin', (event) => { if (event.target !== button) open(el) })
}

function setLabel(el: HTMLElement, expanded: boolean) {
  const button = el.querySelector<HTMLElement>(':scope > .m-fold-more')
  if (!button) return
  button.setAttribute('aria-expanded', String(expanded))
  button.firstElementChild!.textContent = expanded ? 'Thu gọn' : 'Xem thêm'
}

function open(el: HTMLElement) {
  el.classList.add('m-fold-open')
  setLabel(el, true)
}

function close(el: HTMLElement) {
  el.classList.remove('m-fold-open')
  setLabel(el, false)
  // Folding removes height above the reader, so bring them back to the start of the block.
  const top = el.getBoundingClientRect().top
  if (top < 0) window.scrollBy({ top: top - 72 })
}

export default defineNuxtPlugin((nuxtApp) => {
  let queued = false
  const scan = () => {
    queued = false
    document.querySelectorAll<HTMLElement>('[data-fold]:not([data-fold-ready])').forEach(prepare)
  }
  const queue = () => {
    if (queued) return
    queued = true
    window.setTimeout(scan, 250)
  }
  nuxtApp.hook('app:mounted', queue)
  nuxtApp.hook('page:finish', queue)
  new MutationObserver(queue).observe(document.documentElement, { childList: true, subtree: true })
  // In-page links must reach content that is still folded.
  document.addEventListener('click', (event) => {
    const link = (event.target as Element | null)?.closest?.('a[href*="#"]')
    const id = link?.getAttribute('href')?.split('#')[1]
    const target = id ? document.getElementById(decodeURIComponent(id)) : null
    const fold = target?.closest<HTMLElement>('[data-fold]:not(.m-fold-open)')
    if (fold && fold !== target) open(fold)
  }, { capture: true })
})

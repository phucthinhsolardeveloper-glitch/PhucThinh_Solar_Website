import { VI_WORD_PAIRS } from '#shared/vi-word-pairs'

// Keeps two-syllable words on one line and stops a paragraph ending on a single word, using non-breaking spaces.
// It runs in the browser only, so the page source and search engines keep the plain text.
const NBSP = ' '
const PAIR = new RegExp(`(^|[^\\p{L}\\p{N}])(${VI_WORD_PAIRS.map((pair) => pair.replace(' ', '[ ]')).join('|')})(?=$|[^\\p{L}\\p{N}])`, 'giu')
const SKIP = 'script,style,noscript,textarea,input,select,option,code,pre,svg,[contenteditable="true"],.notranslate,.gtranslate_wrapper'
const ENDING_BLOCKS = 'p,li,h1,h2,h3,h4,h5,h6,figcaption,dd,blockquote,.hero-caption span'
const ROOTS = ['main', 'header', 'footer', '.pts-drawer', '[role="dialog"]']

function keepPairs(text: string) {
  return text.includes(' ') ? text.replace(PAIR, (_, before: string, pair: string) => before + pair.replace(' ', NBSP)) : text
}

function fixTextNode(node: Text) {
  // Some CMS text is stored decomposed, which the word list would not match.
  const next = keepPairs(node.data.normalize('NFC'))
  if (next !== node.data) node.data = next
}

// The last word stays with the one before it, unless the line would become too long to fit a phone.
function fixEnding(block: Element) {
  const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT)
  let last: Text | null = null
  while (walker.nextNode()) {
    const node = walker.currentNode as Text
    if (node.data.trim()) last = node
  }
  if (!last || last.parentElement?.closest(SKIP)) return
  const text = last.data
  const trimmed = text.trimEnd()
  const space = trimmed.lastIndexOf(' ')
  // A last word kept as a pair already fills its line; joining more would make a run too long for narrow cards.
  if (space <= 0 || trimmed.slice(space + 1).includes(NBSP) || trimmed.slice(0, space).endsWith(NBSP)) return
  if (trimmed.length - space > 14 || (block.textContent ?? '').trim().split(/\s+/).length < 4) return
  last.data = `${text.slice(0, space)}${NBSP}${text.slice(space + 1)}`
}

// Long running text gets straight edges on both sides, and centred copy or short lines keep their alignment.
// A paragraph is only justified if no word gap stretches too far: a large hole reads worse than an uneven edge.
const JUSTIFY_MIN_WORDS = 12
const JUSTIFY_MIN_WIDTH = 260
const JUSTIFY_MAX_GAP_EM = 0.8
const JUSTIFY_BLOCKS = 'main p, main li, article p, article li'
const JUSTIFY_SKIP = 'nav,button,[role="listbox"],[data-no-justify],.sug,.m-chunk-skip'

function fixAlignment(block: Element) {
  if (!block.matches(JUSTIFY_BLOCKS) || block.closest(JUSTIFY_SKIP)) return
  block.classList.remove('text-justified')
  const style = getComputedStyle(block)
  const left = style.textAlign === 'start' || style.textAlign === 'left'
  const wide = (block as HTMLElement).clientWidth >= JUSTIFY_MIN_WIDTH
  const long = (block.textContent ?? '').trim().split(/\s+/).length >= JUSTIFY_MIN_WORDS
  if (left && wide && long) {
    block.classList.add('text-justified')
    if (widestGap(block) > JUSTIFY_MAX_GAP_EM * parseFloat(style.fontSize)) block.classList.remove('text-justified')
  }
}

function widestGap(block: Element) {
  const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT)
  const range = document.createRange()
  let widest = 0
  while (walker.nextNode()) {
    const node = walker.currentNode as Text
    for (let index = node.data.indexOf(' '); index !== -1; index = node.data.indexOf(' ', index + 1)) {
      range.setStart(node, index)
      range.setEnd(node, index + 1)
      const box = range.getBoundingClientRect()
      if (box.height > 0 && box.width > widest) widest = box.width
    }
  }
  return widest
}

function process(root: Element) {
  if (root.closest(SKIP)) return
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    acceptNode: (node) => (node as Text).data.trim() && !node.parentElement?.closest(SKIP) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT,
  })
  const nodes: Text[] = []
  while (walker.nextNode()) nodes.push(walker.currentNode as Text)
  nodes.forEach(fixTextNode)
  if (root.matches(ENDING_BLOCKS)) fixEnding(root)
  root.querySelectorAll(ENDING_BLOCKS).forEach(fixEnding)
  if (root.matches(JUSTIFY_BLOCKS)) fixAlignment(root)
  root.querySelectorAll(JUSTIFY_BLOCKS).forEach(fixAlignment)
}

export default defineNuxtPlugin((nuxtApp) => {
  const pending = new Set<Element>()
  let scheduled = false
  let observer: MutationObserver | undefined

  function flush() {
    scheduled = false
    observer?.disconnect()
    pending.forEach((element) => { if (element.isConnected) process(element) })
    pending.clear()
    watchDom()
  }

  function queue(element: Element | null | undefined) {
    if (!element) return
    pending.add(element)
    if (!scheduled) {
      scheduled = true
      requestAnimationFrame(flush)
    }
  }

  function watchDom() {
    observer?.observe(document.body, { subtree: true, childList: true, characterData: true })
  }

  nuxtApp.hook('app:mounted', () => {
    observer = new MutationObserver((records) => {
      for (const record of records) {
        const target = record.target.nodeType === Node.TEXT_NODE ? record.target.parentElement : record.target as Element
        const block = target?.closest(ENDING_BLOCKS) ?? target
        if (block && block !== document.body) queue(block)
        else record.addedNodes.forEach((node) => { if (node.nodeType === Node.ELEMENT_NODE) queue(node as Element) })
      }
    })
    ROOTS.forEach((selector) => document.querySelectorAll(selector).forEach(queue))
    watchDom()
    let resizeTimer: ReturnType<typeof setTimeout> | undefined
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimer)
      resizeTimer = setTimeout(() => document.querySelectorAll(JUSTIFY_BLOCKS).forEach(fixAlignment), 200)
    }, { passive: true })
  })
})

// Turns the sanitized solution-hub CMS HTML into chapters so each one can get its own layout.
// Any shape it does not recognise stays as raw HTML ('prose'); no headings at all -> [] (caller falls back).

export interface StoryFit { label: string; text: string }
export interface StoryItem { id: string; title: string; html: string; fit: StoryFit | null }
export type StoryKind = 'match' | 'ecosystem' | 'checklist' | 'process' | 'pillars' | 'prose'
export interface StoryChapter { id: string; title: string; lead: string; items: StoryItem[]; list: string[]; quote: string; kind: StoryKind }

const entities: Record<string, string> = { '&amp;': '&', '&quot;': '"', '&#39;': "'", '&lt;': '<', '&gt;': '>', '&nbsp;': ' ' }
export function decodeText(html: string): string {
  return html.replace(/<[^>]+>/g, '').replace(/&(?:amp|quot|#39|lt|gt|nbsp);/g, (m) => entities[m] ?? m).replace(/\s+/g, ' ').trim()
}

// CMS editors sometimes type a whole heading in capitals; show it in sentence case.
function sentence(text: string): string {
  if (text !== text.toLocaleUpperCase('vi') || text === text.toLocaleLowerCase('vi')) return text
  const lower = text.toLocaleLowerCase('vi')
  return lower.charAt(0).toLocaleUpperCase('vi') + lower.slice(1)
}

function heading(chunk: string, tag: 'h2' | 'h3') {
  const match = chunk.match(new RegExp(`^<${tag}\\b([^>]*)>([\\s\\S]*?)</${tag}>`))
  if (!match) return null
  const id = match[1]?.match(/\bid="([^"]*)"/)?.[1] || ''
  return { id, title: sentence(decodeText(match[2] || '')), rest: chunk.slice(match[0].length) }
}

const fitPattern = /<p>\s*<strong>([^<]+?):?\s*<\/strong>:?\s*([\s\S]*?)<\/p>/

function parseItem(chunk: string): StoryItem | null {
  const head = heading(chunk, 'h3')
  if (!head) return null
  const fitMatch = head.rest.match(fitPattern)
  const fit = fitMatch ? { label: decodeText(fitMatch[1] || '').replace(/:$/, ''), text: decodeText(fitMatch[2] || '') } : null
  const html = (fitMatch ? head.rest.replace(fitMatch[0], '') : head.rest).trim()
  return { id: head.id, title: head.title, html, fit }
}

function classify(chapter: Omit<StoryChapter, 'kind'>): StoryKind {
  const { items, list } = chapter
  if (items.length && items.every((item) => /^\d+[.)]\s/.test(item.title))) return 'process'
  if (items.some((item) => item.fit && /giải pháp/i.test(item.fit.label))) return 'match'
  if (items.some((item) => item.fit)) return 'ecosystem'
  if (!items.length && list.length) return 'checklist'
  if (items.length >= 2) return 'pillars'
  return 'prose'
}

export function parseSolutionStory(html: string): StoryChapter[] {
  const source = html.replace(/>\s+</g, '><').trim()
  if (!/<h2\b/.test(source)) return []
  const chapters: StoryChapter[] = []
  for (const chunk of source.split(/(?=<h2\b)/)) {
    const head = heading(chunk, 'h2')
    if (!head) continue
    // A zero-width split never yields a leading empty part, so the first h3 can land in slot 0.
    const parts = head.rest.split(/(?=<h3\b)/)
    if (parts[0]?.startsWith('<h3')) parts.unshift('')
    const [leadPart = '', ...itemParts] = parts
    const items = itemParts.map(parseItem).filter((item): item is StoryItem => item !== null)
    let lead = leadPart
    const list: string[] = []
    lead = lead.replace(/<ul>([\s\S]*?)<\/ul>/, (_, inner: string) => {
      for (const li of inner.matchAll(/<li>([\s\S]*?)<\/li>/g)) list.push(decodeText(li[1] || '').replace(/\.$/, ''))
      return ''
    })
    let quote = ''
    lead = lead.replace(/<blockquote>([\s\S]*?)<\/blockquote>/, (_, inner: string) => { quote = decodeText(inner); return '' })
    const chapter = { id: head.id, title: head.title, lead: lead.trim(), items, list, quote }
    chapters.push({ ...chapter, kind: classify(chapter) })
  }
  return chapters
}

export function stripStepNumber(title: string): string {
  return title.replace(/^\d+[.)]\s*/, '')
}

// Icon chosen from the item's own words; generic sun when nothing matches.
const iconRules: Array<[RegExp, string]> = [
  [/tiếp nhận/i, 'i-lucide-message-square-text'],
  [/khảo sát/i, 'i-lucide-scan-search'],
  [/phân tích|thiết kế/i, 'i-lucide-drafting-compass'],
  [/đề xuất/i, 'i-lucide-file-check'],
  [/thi công|nghiệm thu/i, 'i-lucide-hard-hat'],
  [/giám sát|đồng hành/i, 'i-lucide-activity'],
  [/bess|doanh nghiệp|nhà xưởng|nhà máy/i, 'i-lucide-factory'],
  [/lưu trữ|dự phòng|pin/i, 'i-lucide-battery-charging'],
  [/chiếu sáng|đèn/i, 'i-lucide-lamp'],
  [/bơm|nước|tưới/i, 'i-lucide-droplets'],
  [/mái|gia đình|nhà ở/i, 'i-lucide-house'],
  [/chi phí|tiền điện|giảm/i, 'i-lucide-trending-down'],
  [/nhu cầu thực tế|bắt đầu/i, 'i-lucide-target'],
  [/nguồn gốc|sản phẩm/i, 'i-lucide-badge-check'],
  [/thiết kế|công trình/i, 'i-lucide-drafting-compass'],
  [/đội ngũ|kỹ thuật/i, 'i-lucide-hard-hat'],
  [/mở rộng/i, 'i-lucide-expand'],
]
export function storyIcon(title: string): string {
  return iconRules.find(([pattern]) => pattern.test(title))?.[1] ?? 'i-lucide-sun'
}

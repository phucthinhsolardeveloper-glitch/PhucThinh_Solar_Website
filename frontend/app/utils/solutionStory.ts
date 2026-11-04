import { sentenceCase } from './sentenceCase.ts'

// Turns sanitized solution CMS HTML into chapters so each one can get its own layout.
// Any shape it does not recognise stays as raw HTML ('prose'); no h2 at all -> [] (caller falls back).

export interface StoryFit { label: string; text: string; list: string[] }
export interface StoryItem { id: string; title: string; html: string; fit: StoryFit | null }
export type StoryKind = 'match' | 'ecosystem' | 'checklist' | 'process' | 'options' | 'pillars' | 'faq' | 'prose'
export interface StoryChapter {
  id: string
  title: string
  lead: string
  items: StoryItem[]
  list: string[]
  ordered: boolean
  quote: string
  kind: StoryKind
}

const entities: Record<string, string> = { '&amp;': '&', '&quot;': '"', '&#39;': "'", '&lt;': '<', '&gt;': '>', '&nbsp;': ' ' }
export function decodeText(html: string): string {
  return html.replace(/<[^>]+>/g, '').replace(/&(?:amp|quot|#39|lt|gt|nbsp);/g, (m) => entities[m] ?? m).replace(/\s+/g, ' ').trim()
}

function heading(chunk: string, tag: 'h2' | 'h3') {
  const match = chunk.match(new RegExp(`^<${tag}\\b([^>]*)>([\\s\\S]*?)</${tag}>`))
  if (!match) return null
  const id = match[1]?.match(/\bid="([^"]*)"/)?.[1] || ''
  return { id, title: sentenceCase(decodeText(match[2] || '')), rest: chunk.slice(match[0].length) }
}

function listItems(inner: string): string[] {
  return [...inner.matchAll(/<li\b[^>]*>([\s\S]*?)<\/li>/g)].map((li) => decodeText(li[1] || '').replace(/\.$/, ''))
}

// "<p><strong>Phù hợp:</strong> text</p>" or "<p><strong>Phù hợp khi:</strong></p><ul>…</ul>"
const fitPattern = /<p>\s*<strong>([^<]{2,40}?):?\s*<\/strong>:?\s*([\s\S]*?)<\/p>(\s*<ul>([\s\S]*?)<\/ul>)?/

function parseItem(chunk: string): StoryItem | null {
  const head = heading(chunk, 'h3')
  if (!head) return null
  const fitMatch = head.rest.match(fitPattern)
  let fit: StoryFit | null = null
  if (fitMatch && /phù hợp/i.test(fitMatch[1] || '')) {
    const text = decodeText(fitMatch[2] || '')
    const list = fitMatch[4] ? listItems(fitMatch[4]) : []
    if (text || list.length) fit = { label: decodeText(fitMatch[1] || '').replace(/:$/, ''), text, list }
  }
  const html = (fit && fitMatch ? head.rest.replace(fitMatch[0], '') : head.rest).trim()
  return { id: head.id, title: head.title, html, fit }
}

const numbered = /^\d+[.)]\s/

function classify(chapter: Omit<StoryChapter, 'kind'>): StoryKind {
  const { items, list, ordered, title } = chapter
  if (items.length && /^(faq|câu hỏi)/i.test(title)) return 'faq'
  if (!items.length && list.length) return ordered ? 'process' : 'checklist'
  if (items.length && items.every((item) => numbered.test(item.title))) return items.length >= 4 ? 'process' : 'options'
  if (items.some((item) => item.fit && /giải pháp/i.test(item.fit.label) && item.fit.text)) return 'match'
  if (items.some((item) => item.fit?.text)) return 'ecosystem'
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
    let quote = ''
    lead = lead.replace(/<blockquote>([\s\S]*?)<\/blockquote>/, (_, inner: string) => { quote = decodeText(inner); return '' })
    // Quotes written after the last h3 still belong to the chapter.
    for (const item of items) item.html = item.html.replace(/<blockquote>([\s\S]*?)<\/blockquote>/, (_, inner: string) => { quote ||= decodeText(inner); return '' })
    // Only a chapter made of a single list is restaged as a list; lists beside h3s stay in their prose.
    const list: string[] = []
    let ordered = false
    if (!items.length) {
      lead = lead.replace(/<(ul|ol)>([\s\S]*?)<\/\1>/, (_, tag: string, inner: string) => {
        ordered = tag === 'ol'
        list.push(...listItems(inner))
        return ''
      })
    }
    const chapter = { id: head.id, title: head.title, lead: lead.trim(), items, list, ordered, quote }
    chapters.push({ ...chapter, kind: classify(chapter) })
  }
  return chapters
}

export function stripStepNumber(title: string): string {
  return title.replace(/^\d+[.)]\s*/, '')
}

// Icon chosen from the item's own words; generic sun when nothing matches.
const iconRules: Array<[RegExp, string]> = [
  [/tiếp nhận|thu thập|nhận bản vẽ/i, 'i-lucide-message-square-text'],
  [/khảo sát/i, 'i-lucide-scan-search'],
  [/ước tính|tính toán|phân tích/i, 'i-lucide-calculator'],
  [/thiết kế/i, 'i-lucide-drafting-compass'],
  [/đề xuất|báo giá/i, 'i-lucide-file-check'],
  [/thi công|nghiệm thu|lắp đặt/i, 'i-lucide-hard-hat'],
  [/giám sát|đồng hành|theo dõi|dữ liệu/i, 'i-lucide-activity'],
  [/ban ngày/i, 'i-lucide-sun'],
  [/ban đêm|buổi tối/i, 'i-lucide-moon-star'],
  [/mất điện/i, 'i-lucide-zap-off'],
  [/bess|doanh nghiệp|nhà xưởng|nhà máy|kho bãi/i, 'i-lucide-factory'],
  [/biến tần|inverter/i, 'i-lucide-cpu'],
  [/lưu trữ|dự phòng|pin|bms|dung lượng|kwh|sạc/i, 'i-lucide-battery-charging'],
  [/chiếu sáng|đèn/i, 'i-lucide-lamp'],
  [/bơm|nước|tưới/i, 'i-lucide-droplets'],
  [/đường|giao thông|khu dân cư/i, 'i-lucide-route'],
  [/công viên|resort|sân vườn|cảnh quan/i, 'i-lucide-trees'],
  [/nông thôn|vùng xa/i, 'i-lucide-mountain'],
  [/khung/i, 'i-lucide-frame'],
  [/tủ điện|bảo vệ|an toàn/i, 'i-lucide-shield-check'],
  [/ems|quản lý năng lượng/i, 'i-lucide-gauge'],
  [/kw\b|công suất/i, 'i-lucide-zap'],
  [/mái|gia đình|nhà ở/i, 'i-lucide-house'],
  [/chi phí|tiền điện|giảm/i, 'i-lucide-trending-down'],
  [/nhu cầu thực tế|bắt đầu|mục tiêu/i, 'i-lucide-target'],
  [/nguồn gốc|sản phẩm/i, 'i-lucide-badge-check'],
  [/đội ngũ|kỹ thuật/i, 'i-lucide-hard-hat'],
  [/mở rộng/i, 'i-lucide-expand'],
  [/tự động|linh hoạt/i, 'i-lucide-sparkles'],
  [/bền vững/i, 'i-lucide-leaf'],
]
export function storyIcon(title: string): string {
  return iconRules.find(([pattern]) => pattern.test(title))?.[1] ?? 'i-lucide-sun'
}

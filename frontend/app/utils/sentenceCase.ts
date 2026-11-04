// CMS editors often type headings in capitals. Shown in sentence case, with known
// proper nouns and acronyms restored; mixed-case text is returned untouched.
const properNouns: Array<[RegExp, string]> = [
  [/phúc thịnh solar/g, 'Phúc Thịnh Solar'],
  [/blue carbon/g, 'Blue Carbon'],
  [/việt nam/g, 'Việt Nam'],
  [/nhật bản/g, 'Nhật Bản'],
  [/\bsharp\b/g, 'Sharp'],
  [/\bled\b/g, 'LED'],
  [/\bcree\b/g, 'CREE'],
  [/\bbess\b/g, 'BESS'],
  [/\bems\b/g, 'EMS'],
  [/\bbms\b/g, 'BMS'],
  [/\bfaq\b/g, 'FAQ'],
]

export function sentenceCase(text: string): string {
  const value = text.trim()
  if (!value || value !== value.toLocaleUpperCase('vi') || value === value.toLocaleLowerCase('vi')) return value
  if (/^[A-Z]{2,5}$/.test(value)) return value
  let lower = value.toLocaleLowerCase('vi')
  for (const [pattern, replacement] of properNouns) lower = lower.replace(pattern, replacement)
  // First letter of the sentence and of a quoted or post-colon clause.
  lower = lower.replace(/^([^\p{L}\p{N}]*)(\p{L})/u, (_, lead: string, ch: string) => lead + ch.toLocaleUpperCase('vi'))
  lower = lower.replace(/([:!?]\s*["“]?\s*)(\p{L})/gu, (_, lead: string, ch: string) => lead + ch.toLocaleUpperCase('vi'))
  return lower
}

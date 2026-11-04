// CMS paragraphs written as "line<br>- item<br>- item" become real paragraphs and lists.
// Only rearranges tags the sanitizer already allows (p, ul, li); text is untouched.
export function restructureLineBreaks(html: string): string {
  return html.replace(/<p>([\s\S]*?)<\/p>/g, (whole, inner: string) => {
    if (!/<br\s*\/?>/i.test(inner)) return whole
    const lines = inner.split(/<br\s*\/?>/i).map((line) => line.replace(/\s+/g, ' ').trim()).filter(Boolean)
    let out = ''
    let items: string[] = []
    const flush = () => {
      if (items.length) out += `<ul>${items.map((item) => `<li>${item}</li>`).join('')}</ul>`
      items = []
    }
    for (const line of lines) {
      const bullet = line.match(/^[-–•+]\s*(.+)$/)
      if (bullet) items.push(bullet[1]!)
      else { flush(); out += `<p>${line}</p>` }
    }
    flush()
    return out
  })
}

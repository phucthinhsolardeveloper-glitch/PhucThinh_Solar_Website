#!/usr/bin/env node
// How are long passages aligned, and how wide do the word gaps get if they are justified?
// Counts paragraphs by alignment and width, and reports the widest stretched gap per page. Read only.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const routes = (process.env.AUDIT_ROUTES || '/,/gioi-thieu/,/giai-phap/,/giai-phap/he-thong-luu-tru-bess/,/du-an-da-thuc-hien/,/du-an-dien-nlmt-ho-anh-quan/,/tin-tuc/,/dai-ly/,/lien-he/,/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-sys-lv-d150-pro').split(',');
const widths = (process.env.AUDIT_WIDTHS || '360,412,1440').split(',').map(Number);
function scan() {
  const out = { long: 0, justified: 0, centered: 0, narrow: 0, worstGap: 0, worstText: '', over: 0 };
  document.querySelectorAll('main p, main li, article p').forEach((element) => {
    if (!element.getClientRects().length) return;
    const text = element.innerText.trim();
    if (text.split(/\s+/).length < 12) return;
    const style = getComputedStyle(element);
    out.long += 1;
    if (style.textAlign === 'center') out.centered += 1;
    if (style.textAlign === 'justify') out.justified += 1;
    if (element.getBoundingClientRect().width < 300) out.narrow += 1;
    if (style.textAlign !== 'justify') return;
    const fontSize = parseFloat(style.fontSize);
    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
    const range = document.createRange();
    let worst = 0;
    while (walker.nextNode()) {
      const node = walker.currentNode;
      for (let index = 0; index < node.data.length; index += 1) {
        if (node.data[index] !== ' ') continue;
        range.setStart(node, index);
        range.setEnd(node, index + 1);
        const rect = range.getBoundingClientRect();
        if (rect.width > worst && rect.height > 0) worst = rect.width;
      }
    }
    const ratio = worst / fontSize;
    if (ratio > 0.75) out.over += 1;
    if (ratio > out.worstGap) { out.worstGap = Number(ratio.toFixed(2)); out.worstText = text.slice(0, 40); }
  });
  return out;
}
(async () => {
  const browser = await chromium.launch();
  for (const width of widths) {
    const phone = width < 821;
    const context = await browser.newContext({ viewport: { width, height: 900 }, isMobile: phone, hasTouch: phone, locale: 'vi-VN' });
    await context.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await context.route('**/pts-living-energy/index.html*', (route) => route.abort());
    await context.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    const page = await context.newPage();
    const total = { long: 0, justified: 0, centered: 0, narrow: 0, over: 0, worstGap: 0, worstText: '' };
    for (const route of routes) {
      await page.goto(base + route, { waitUntil: 'load' }).catch(() => {});
      await page.waitForTimeout(700);
      const result = await page.evaluate(scan);
      for (const key of ['long', 'justified', 'centered', 'narrow', 'over']) total[key] += result[key];
      if (result.worstGap > total.worstGap) { total.worstGap = result.worstGap; total.worstText = `${route} ${result.worstText}`; }
    }
    console.log(width, JSON.stringify(total));
    await context.close();
  }
  await browser.close();
})();

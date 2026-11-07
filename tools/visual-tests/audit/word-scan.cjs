#!/usr/bin/env node
// Finds words on the rendered public pages, for example retired facts. Usage: word-scan.cjs "2019" "gạch" ...
// Crawls from the sitemap-free list below plus every internal link found on those pages, one level deep, up to 80 pages.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const words = process.argv.slice(2);
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  const seeds = ['/', '/gioi-thieu/', '/giai-phap/', '/du-an-da-thuc-hien/', '/tin-tuc/', '/lien-he/', '/he-thong-dai-ly/', '/dai-ly/', '/san-pham/'];
  const queue = [...seeds];
  const seen = new Set();
  while (queue.length && seen.size < 80) {
    const route = queue.shift();
    if (seen.has(route)) continue;
    seen.add(route);
    const response = await page.goto(base + route, { waitUntil: 'domcontentloaded' }).catch(() => null);
    if (!response || response.status() >= 400) continue;
    await page.waitForTimeout(300);
    const { text, links } = await page.evaluate(() => ({ text: document.body.textContent.replace(/\s+/g, ' '), links: [...document.querySelectorAll('a[href^="/"]')].map((a) => a.getAttribute('href').split('#')[0]) }));
    for (const word of words) {
      const pattern = new RegExp(`.{0,50}${word}.{0,50}`, 'gi');
      for (const match of (text.match(pattern) || []).slice(0, 3)) console.log(route, '|', match);
    }
    if (seeds.includes(route)) links.forEach((link) => { if (link && !seen.has(link) && !/\.(pdf|jpg|png|webp)$/i.test(link)) queue.push(link); });
  }
  console.log('pages scanned', seen.size);
  await browser.close();
})();

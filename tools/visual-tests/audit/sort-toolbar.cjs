#!/usr/bin/env node
// Measures the sort and filter toolbar on catalog, search and brand pages at the widths where it used to squash.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const routes = process.argv.slice(2).length ? process.argv.slice(2) : ['/san-pham/', '/tim-kiem/?keyword=pin', '/thuong-hieu/'];
(async () => {
  const browser = await chromium.launch();
  for (const width of [412, 700, 740, 769, 770, 820, 900, 960, 1001, 1002, 1280]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    for (const route of routes) {
      await page.goto(base + route, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(500);
      const row = await page.evaluate(() => {
        const select = [...document.querySelectorAll('main button[role="combobox"]')].find((el) => el.offsetWidth);
        if (!select) return null;
        const filter = [...document.querySelectorAll('main button')].find((el) => el.offsetWidth && /^Bộ lọc/.test(el.textContent.trim()));
        const label = [...document.querySelectorAll('main span')].find((el) => el.offsetWidth && el.textContent.trim() === 'Sắp xếp');
        return { select: select.offsetWidth, filter: filter ? filter.offsetWidth : 0, labelHeight: label ? label.offsetHeight : 0, clipped: select.querySelector('span')?.scrollWidth > select.querySelector('span')?.clientWidth, overflow: document.documentElement.scrollWidth > innerWidth };
      });
      console.log(width, route, JSON.stringify(row));
    }
    await page.close();
  }
  await browser.close();
})();

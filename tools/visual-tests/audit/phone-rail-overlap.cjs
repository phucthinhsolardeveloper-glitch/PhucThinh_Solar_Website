#!/usr/bin/env node
// On phones, scrolls each page with a chip rail, taps the next chip, then checks nothing covers the chosen chip and
// that the pill lines up with it and stays inside the rail. Takes a screenshot of the top of the screen for each page.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 412, height: 860 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
  await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  const product = await page.locator('article a[href]').first().getAttribute('href');
  for (const [name, route] of [['about', '/gioi-thieu/'], ['solutions', '/giai-phap/'], ['solution', '/giai-phap/dien-mat-troi-ho-gia-dinh/'], ['product', product], ['projects', '/du-an-da-thuc-hien/'], ['news', '/tin-tuc/']]) {
    await page.goto(new URL(route, base).href, { waitUntil: 'load' });
    await page.waitForTimeout(1000);
    const rail = page.locator('[data-pill-rail]').first();
    await rail.evaluate((el) => el.scrollIntoView({ behavior: 'instant', block: 'center' }));
    await page.evaluate(() => window.scrollBy({ top: 500, behavior: 'instant' }));
    await page.waitForTimeout(700);
    const result = await rail.evaluate(async (el) => {
      const pill = el.querySelector('.rail-pill');
      const read = () => {
        const on = el.querySelector('[data-pill-on]');
        if (!on) return { on: null };
        on.scrollIntoView({ behavior: 'instant', inline: 'center', block: 'nearest' });
        const a = on.getBoundingClientRect(); const p = pill.getBoundingClientRect(); const r = el.getBoundingClientRect();
        const hit = document.elementFromPoint(a.x + a.width / 2, a.y + a.height / 2);
        return { chip: on.textContent.trim().slice(0, 18), chipH: Math.round(a.height), pillH: Math.round(p.height), railH: Math.round(r.height), offX: Math.round(p.x - a.x), offY: Math.round(p.y - a.y), inRail: p.top >= r.top - 1 && p.bottom <= r.bottom + 1, covered: !(on === hit || on.contains(hit)) ? `${hit?.tagName}.${String(hit?.className).slice(0, 30)}` : 'no', top: Math.round(a.y), color: getComputedStyle(on).color };
      };
      return read();
    });
    console.log(name, JSON.stringify(result));
    await page.waitForTimeout(600);
    await page.screenshot({ path: `${out}/overlap-${name}.png`, clip: { x: 0, y: 0, width: 412, height: 240 } });
  }
  await browser.close();
})();

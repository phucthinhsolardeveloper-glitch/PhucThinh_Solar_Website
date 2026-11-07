#!/usr/bin/env node
// Hovers every visible gold or call-to-action button on key pages and reports any that move, resize or add a filter
// on hover. Movement or a filter under the pointer is what makes a button flicker as the mouse passes over it.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
  await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  const product = await page.locator('article a[href]').first().getAttribute('href');
  let problems = 0;
  for (const route of ['/', '/san-pham/', product, '/giai-phap/', '/giai-phap/dien-mat-troi-ho-gia-dinh/', '/du-an-da-thuc-hien/', '/gioi-thieu/', '/lien-he/', '/gio-hang/']) {
    await page.goto(new URL(route, base).href, { waitUntil: 'load' });
    await page.waitForTimeout(800);
    const count = await page.evaluate(() => {
      const isGold = (el) => /gold|e0b654|c9962e|224, 182, 84/.test(getComputedStyle(el).backgroundImage + getComputedStyle(el).backgroundColor) || /bg-gold|gold-fill|primary/.test(el.className);
      const list = [...document.querySelectorAll('a, button')].filter((el) => el.offsetWidth > 60 && el.offsetHeight > 30 && isGold(el));
      list.forEach((el, index) => el.setAttribute('data-hover-probe', String(index)));
      return list.length;
    });
    for (let index = 0; index < count; index += 1) {
      const target = page.locator(`[data-hover-probe="${index}"]`);
      if (!(await target.isVisible().catch(() => false))) continue;
      await target.scrollIntoViewIfNeeded().catch(() => {});
      const before = await target.evaluate((el) => { const r = el.getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; });
      await target.hover({ force: true, timeout: 2000 }).catch(() => {});
      await page.waitForTimeout(350);
      const after = await target.evaluate((el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return { x: r.x, y: r.y, w: r.width, h: r.height, filter: s.filter, transform: s.transform, text: el.textContent.trim().slice(0, 28) }; });
      const moved = Math.abs(after.x - before.x) > 0.5 || Math.abs(after.y - before.y) > 0.5 || Math.abs(after.w - before.w) > 0.5 || Math.abs(after.h - before.h) > 0.5;
      if (moved || after.filter !== 'none') { problems += 1; console.log(route.slice(0, 40), '|', after.text, '| moved', moved, '| filter', after.filter); }
      await page.mouse.move(2, 2);
    }
    console.log(route.slice(0, 40), 'gold buttons checked', count);
  }
  console.log('problems', problems);
  await browser.close();
})();

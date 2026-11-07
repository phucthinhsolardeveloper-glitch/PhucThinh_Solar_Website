#!/usr/bin/env node
// Screenshots the gliding chip rails on each kind of page and prints the colours of the chosen chip and of the pill.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  for (const [label, options] of [['desktop', { viewport: { width: 1440, height: 800 } }], ['phone', { viewport: { width: 412, height: 800 }, isMobile: true, hasTouch: true }]]) {
    const context = await browser.newContext(options);
    const page = await context.newPage();
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    for (const [name, route] of [['solution', '/giai-phap/dien-mat-troi-ho-gia-dinh/'], ['solutions', '/giai-phap/'], ['projects', '/du-an-da-thuc-hien/'], ['news', '/tin-tuc/'], ['about', '/gioi-thieu/'], ['search', '/tim-kiem/?keyword=pin']]) {
      await page.goto(base + route, { waitUntil: 'load' });
      await page.waitForTimeout(1000);
      const rail = page.locator('[data-pill-rail]').first();
      if (!(await rail.count())) { console.log(label, name, 'no rail'); continue; }
      await rail.evaluate((el) => el.scrollIntoView({ behavior: 'instant', block: 'center' }));
      await page.waitForTimeout(800);
      const info = await rail.evaluate((el) => {
        const pill = el.querySelector('.rail-pill');
        const on = el.querySelector('[data-pill-on]');
        return { pill: getComputedStyle(pill).backgroundImage.slice(0, 42), opacity: getComputedStyle(pill).opacity, chip: on?.textContent.trim().slice(0, 22), text: on ? getComputedStyle(on).color : '' };
      });
      console.log(label, name, JSON.stringify(info));
      await rail.screenshot({ path: `${out}/rail-${name}-${label}.png` });
    }
    await context.close();
  }
  await browser.close();
})();

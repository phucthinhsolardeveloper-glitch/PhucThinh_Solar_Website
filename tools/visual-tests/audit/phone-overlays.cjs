#!/usr/bin/env node
// Phone checks for floating layers: the add-to-cart card stays on screen above the dock, a folded section's
// "Xem thêm" never paints over the buy bar, and every card in the About mission rail is visible when swiped to.
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
  await page.waitForTimeout(1200);
  const add = page.locator('article button').filter({ hasNot: page.locator('[aria-hidden="false"]') }).last();
  if (await add.count()) {
    await add.scrollIntoViewIfNeeded();
    await add.click();
    await page.waitForTimeout(1800);
    const card = await page.evaluate(() => { const c = document.querySelector('.cart-card'); if (!c) return null; const r = c.getBoundingClientRect(); return { top: Math.round(r.top), bottom: Math.round(r.bottom), onScreen: r.top >= 0 && r.bottom <= innerHeight }; });
    console.log('cart card', JSON.stringify(card));
    await page.screenshot({ path: `${out}/overlay-cart.png` });
  } else console.log('cart card: no add button found');

  const href = await page.locator('article a[href]').first().getAttribute('href');
  await page.goto(new URL(href, base).href, { waitUntil: 'load' });
  await page.waitForTimeout(1500);
  const fold = page.locator('[data-fold] > .m-fold-more span').first();
  if (await fold.count()) {
    await fold.evaluate((el) => el.scrollIntoView({ block: 'end' }));
    await page.evaluate(() => window.scrollBy(0, 40));
    await page.waitForTimeout(700);
    console.log('fold under buy bar', await page.evaluate(() => {
      const button = document.querySelector('[data-fold] > .m-fold-more span').getBoundingClientRect();
      const bar = document.querySelector('.buy-island');
      if (!bar) return 'no bar';
      const b = bar.getBoundingClientRect();
      if (button.bottom < b.top || button.top > b.bottom) return 'not overlapping here';
      const hit = document.elementFromPoint(button.x + button.width / 2, Math.max(b.top + 4, button.y + button.height / 2));
      return hit && bar.contains(hit) ? 'bar on top' : `fold on top (${hit?.className})`;
    }));
    await page.screenshot({ path: `${out}/overlay-fold.png` });
  }

  await page.goto(`${base}/gioi-thieu/`, { waitUntil: 'load' });
  await page.waitForTimeout(1000);
  for (let y = 0; y < 7000; y += 600) { await page.evaluate((v) => window.scrollTo(0, v), y); await page.waitForTimeout(120); }
  const rail = page.locator('.about-compass-rail');
  await rail.evaluate((el) => el.scrollIntoView({ block: 'center' }));
  await rail.evaluate((el) => { el.scrollLeft = el.scrollWidth; });
  await page.waitForTimeout(900);
  console.log('mission cards opacity', await rail.evaluate((el) => [...el.children].map((c) => getComputedStyle(c).opacity).join(',')));
  await browser.close();
})();

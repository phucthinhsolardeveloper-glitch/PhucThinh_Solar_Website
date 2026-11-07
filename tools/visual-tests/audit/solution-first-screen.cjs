#!/usr/bin/env node
// For each solution page on short and tall phones, reports whether the solution switcher is on the first screen
// and whether the hero photo's bottom edge falls inside the text without the dark wash over it.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  const probe = await browser.newPage();
  await probe.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await probe.goto(`${base}/giai-phap/`, { waitUntil: 'load' });
  const routes = [...new Set(await probe.locator('a[href*="/giai-phap/"]').evaluateAll((links) => links.map((a) => new URL(a.href).pathname).filter((p) => /^\/giai-phap\/[^/]+\/$/.test(p))))];
  await probe.close();
  for (const [width, height] of [[360, 640], [412, 740], [412, 860]]) {
    const context = await browser.newContext({ viewport: { width, height }, isMobile: true, hasTouch: true });
    const page = await context.newPage();
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    for (const route of routes) {
      await page.goto(base + route, { waitUntil: 'load' });
      await page.waitForTimeout(700);
      const row = await page.evaluate(() => {
        const rail = document.querySelector('.solution-rail');
        const image = document.querySelector('.solution-hero__image');
        const shade = document.querySelector('.solution-hero__shade');
        const railTop = rail ? Math.round(rail.getBoundingClientRect().top) : -1;
        return { railTop, railVisible: railTop >= 0 && railTop + 40 <= innerHeight - 90, shadeCoversImage: !image || !shade || shade.getBoundingClientRect().bottom >= image.getBoundingClientRect().bottom + 2 };
      });
      console.log(`${width}x${height}`, route, JSON.stringify(row));
      if (width === 412 && height === 740) await page.screenshot({ path: `${out}/sol-first-${route.split('/')[2]}.png` });
    }
    await context.close();
  }
  await browser.close();
})();

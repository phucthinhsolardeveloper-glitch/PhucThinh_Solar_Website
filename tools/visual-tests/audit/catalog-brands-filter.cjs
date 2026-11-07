#!/usr/bin/env node
// Product landing: counts masthead brand logos (all partners on /san-pham/, only stocked brands on category pages),
// then on a phone ticks two brands in the filter drawer and checks the drawer stays open and both stay ticked.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  const desk = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await desk.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await desk.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  await desk.waitForTimeout(800);
  console.log('landing logos', await desk.locator('.masthead-brands .masthead-brand img').count(), 'chips', await desk.locator('.masthead-brands .masthead-brand').count());
  await desk.locator('.masthead').screenshot({ path: `${out}/masthead-landing.png` });
  const categoryHref = await desk.locator('.catalog-category-chip').nth(1).getAttribute('href');
  await desk.goto(new URL(categoryHref, base).href, { waitUntil: 'load' });
  await desk.waitForTimeout(800);
  console.log('category chips', await desk.locator('.masthead-brands .masthead-brand').count());
  const context = await browser.newContext({ viewport: { width: 412, height: 900 }, isMobile: true, hasTouch: true });
  const phone = await context.newPage();
  await phone.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await phone.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  await phone.waitForTimeout(1000);
  await phone.locator('button[aria-controls="catalog-filter-drawer"]').first().click();
  await phone.waitForTimeout(600);
  const brands = phone.locator('#catalog-filter-drawer section[aria-labelledby$="brand-filter-title"] a');
  for (const index of [0, 1]) {
    await brands.nth(index).click();
    await phone.waitForTimeout(1500);
    console.log('after tick', index + 1, 'drawer open', await phone.locator('#catalog-filter-drawer').isVisible(), 'ticked', await brands.evaluateAll((list) => list.filter((a) => a.getAttribute('aria-current') === 'true').length));
  }
  console.log('footer', await phone.locator('#catalog-filter-drawer button.m-gold-cta').innerText());
  await phone.screenshot({ path: `${out}/drawer-two-brands.png` });
  await browser.close();
})();

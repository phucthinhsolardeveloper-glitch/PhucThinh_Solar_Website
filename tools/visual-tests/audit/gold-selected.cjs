#!/usr/bin/env node
// Clicks a choice in each form and picker (never submits) and reports the chosen control's background and text colour.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
const read = (el) => { const s = getComputedStyle(el); return { text: el.textContent.trim().slice(0, 24), bg: s.backgroundImage.slice(0, 30), color: s.color }; };
(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 412, height: 924 }, isMobile: true, hasTouch: true });
  const page = await context.newPage();
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
  await page.goto(`${base}/`, { waitUntil: 'load' });
  for (let step = 0; step < 14; step += 1) { await page.evaluate(() => window.scrollBy({ top: 800, behavior: 'instant' })); await page.waitForTimeout(250); }
  const need = page.locator('.need').nth(1);
  if (await need.count()) { await need.scrollIntoViewIfNeeded(); await need.evaluate((el) => el.click()); await page.waitForTimeout(400); console.log('home form', JSON.stringify(await need.evaluate(read))); await page.screenshot({ path: `${out}/gold-home-form.png` }); }
  const scene = page.locator('.scene-item').nth(1);
  if (await scene.count()) { await scene.scrollIntoViewIfNeeded(); await scene.evaluate((el) => el.click()); await page.waitForTimeout(500); console.log('home scenes', JSON.stringify(await page.locator('.scene-item.is-active').first().evaluate(read))); await page.screenshot({ path: `${out}/gold-home-scenes.png` }); }
  await page.goto(`${base}/lien-he/`, { waitUntil: 'load' });
  await page.waitForTimeout(1000);
  const option = page.locator('form#yeu-cau-tu-van button[aria-pressed]').nth(1);
  if (await option.count()) { await option.scrollIntoViewIfNeeded(); await option.click(); await page.waitForTimeout(400); console.log('contact form', JSON.stringify(await page.locator('form#yeu-cau-tu-van button[aria-pressed="true"]').first().evaluate(read))); await page.screenshot({ path: `${out}/gold-contact-form.png` }); }
  const place = page.locator('button.group[aria-pressed]').nth(1);
  if (await place.count()) { await place.scrollIntoViewIfNeeded(); await place.click(); await page.waitForTimeout(400); console.log('contact places', JSON.stringify(await page.locator('button.group[aria-pressed="true"]').first().evaluate(read))); await page.screenshot({ path: `${out}/gold-contact-place.png` }); }
  await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  const href = await page.locator('article a[href]').first().getAttribute('href');
  await page.goto(new URL(href, base).href, { waitUntil: 'load' });
  await page.waitForTimeout(1000);
  const pick = page.locator('#pts-pdp-consultation button[aria-pressed]').nth(0);
  if (await pick.count()) { await pick.scrollIntoViewIfNeeded(); await pick.click(); await page.waitForTimeout(400); console.log('product form', JSON.stringify(await pick.evaluate(read))); await page.screenshot({ path: `${out}/gold-product-form.png` }); }
  await browser.close();
})();

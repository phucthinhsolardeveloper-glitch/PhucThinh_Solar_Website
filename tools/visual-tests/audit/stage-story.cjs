#!/usr/bin/env node
// Walks the homepage 3D story on desktop and phone: arrival, every chapter, using the model, returning.
// Saves screenshots to the folder given as the first argument (default audit/out). Needs WebGL (SwiftShader here).
const { chromium } = require('playwright');
const fs = require('node:fs');
const out = process.argv[2] || 'audit/out';
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
fs.mkdirSync(out, { recursive: true });
(async () => {
  const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const only = process.env.AUDIT_ONLY;
  for (const [name, options] of [['desktop', { viewport: { width: 1600, height: 900 } }], ['phone', { viewport: { width: 412, height: 924 }, isMobile: true, hasTouch: true }]]) {
    if (only && only !== name) continue;
    const context = await browser.newContext({ ...options, locale: 'vi-VN', timezoneId: 'Asia/Ho_Chi_Minh' });
    const page = await context.newPage();
    page.setDefaultTimeout(120000); // software WebGL makes screenshots slow
    await page.clock.setFixedTime(new Date('2026-11-07T09:00:00+07:00'));
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    await page.goto(`${base}/`, { waitUntil: 'load' });
    await page.locator('.energy-scene-frame').evaluate((element) => element.scrollIntoView({ behavior: 'instant', block: 'center' }));
    await page.waitForTimeout(700);
    await page.screenshot({ path: `${out}/story-${name}-0-arrive.png` });
    await page.waitForFunction(() => document.querySelector('.energy-scene-frame iframe.is-ready'), null, { timeout: 40000 });
    const chips = page.locator('.stage-story__rail button');
    await page.waitForTimeout(1500);
    for (let index = 0; index < 4; index += 1) {
      await chips.nth(index).click();
      await page.waitForTimeout(4200);
      console.log(name, 'chapter', index + 1, await chips.nth(index).evaluate((el) => `${el.textContent.trim()} text ${getComputedStyle(el).color} current ${el.getAttribute('aria-current')}`));
      await page.screenshot({ path: `${out}/story-${name}-${index + 1}.png` });
    }
    await page.locator('.stage-story__explore').click();
    // Counted before the screenshot: software rendering makes a shot slow enough for the idle return to fire.
    console.log(name, 'story hidden while exploring', await page.locator('.stage-story.is-away').count(), 'back button', await page.locator('.stage-back').count());
    await page.waitForTimeout(1800);
    await page.screenshot({ path: `${out}/story-${name}-explore.png` });
    if (!(await page.locator('.stage-back').count())) await page.locator('.stage-story__explore').click();
    // The idle return may already have fired under software rendering, so this tolerates a missing button.
    await page.locator('.stage-back').evaluateAll((list) => list.forEach((button) => button.click()));
    await page.waitForTimeout(800);
    console.log(name, 'story back', await page.locator('.stage-story:not(.is-away)').count());
    await context.close();
  }
  await browser.close();
})();

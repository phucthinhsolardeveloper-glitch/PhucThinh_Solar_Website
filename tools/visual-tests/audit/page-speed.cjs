#!/usr/bin/env node
// Records long tasks (blocks of 50ms or more) while loading a page, opening the catalog menu and scrolling, at 4x CPU slowdown.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
(async () => {
  const browser = await chromium.launch();
  for (const route of ['/san-pham/', '/gioi-thieu/', '/']) {
    const page = await browser.newPage({ viewport: { width: 1536, height: 864 } });
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    const cdp = await page.context().newCDPSession(page);
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    await page.addInitScript(() => { window.__long = []; try { new PerformanceObserver((list) => { for (const entry of list.getEntries()) window.__long.push(Math.round(entry.duration)); }).observe({ entryTypes: ['longtask'] }); } catch {} try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    await page.goto(base + route, { waitUntil: 'load' });
    await page.waitForTimeout(2500);
    const take = async () => page.evaluate(() => { const list = window.__long.splice(0); return { tasks: list.length, total: list.reduce((a, b) => a + b, 0), max: Math.max(0, ...list) }; });
    console.log(route, 'load', JSON.stringify(await take()));
    const button = page.locator('header [aria-expanded]').filter({ hasText: 'Danh mục sản phẩm' }).first();
    await button.hover(); await page.waitForTimeout(700);
    console.log(route, 'open menu', JSON.stringify(await take()));
    await page.mouse.move(700, 800); await page.waitForTimeout(700); await take();
    for (let step = 0; step < 16; step += 1) { await page.mouse.wheel(0, 300); await page.waitForTimeout(120); }
    await page.waitForTimeout(500);
    console.log(route, 'scroll', JSON.stringify(await take()));
    await page.close();
  }
  await browser.close();
})();

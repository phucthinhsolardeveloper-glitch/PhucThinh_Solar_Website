#!/usr/bin/env node
// Measures the desktop header and catalog menu across widths: overflow, menu scrolling, blank space, and open speed.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  for (const [width, height] of [[1024, 700], [1280, 720], [1366, 768], [1440, 900], [1536, 864], [1661, 838], [1681, 900], [1720, 900], [1800, 950], [1920, 1080], [2560, 1300]]) {
    const page = await browser.newPage({ viewport: { width, height } });
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    await page.waitForTimeout(700);
    const head = await page.evaluate(() => {
      const header = document.querySelector('header');
      let right = 0;
      header.querySelectorAll('a,button').forEach((el) => { const r = el.getBoundingClientRect(); if (r.width && r.right > right) right = r.right; });
      return { right: Math.round(right), docOverflow: document.documentElement.scrollWidth - innerWidth };
    });
    const button = page.locator('header [aria-expanded]').filter({ hasText: 'Danh mục sản phẩm' }).first();
    if (!(await button.isVisible().catch(() => false))) { console.log(width, height, 'header', JSON.stringify(head), 'no catalog button'); await page.close(); continue; }
    const started = Date.now();
    await button.hover();
    await page.waitForTimeout(500);
    const menu = await page.evaluate(() => {
      const panel = document.getElementById('ptsCatalogMenu');
      if (!panel) return null;
      const box = panel.getBoundingClientRect();
      let low = 0;
      panel.querySelectorAll('a,button,h3').forEach((el) => { const r = el.getBoundingClientRect(); if (r.width && r.bottom > low) low = r.bottom; });
      return { panelH: Math.round(box.height), contentH: panel.scrollHeight, scrolls: panel.scrollHeight - panel.clientHeight, blankBelow: Math.round(box.bottom - low), bottomGap: Math.round(innerHeight - box.bottom) };
    });
    const flyoutItem = page.locator('#ptsCatalogMenu li.group\\/item:has(.catalog-flyout)').last();
    let flyout = null;
    if (menu && await flyoutItem.count()) {
      await flyoutItem.hover();
      await page.waitForTimeout(350);
      flyout = await page.evaluate(() => {
        const open = document.querySelector('#ptsCatalogMenu .is-flyout-open .catalog-flyout');
        if (!open) return 'none';
        const panel = document.getElementById('ptsCatalogMenu');
        const r = open.getBoundingClientRect();
        return { bottomInView: r.bottom <= innerHeight, leftInView: r.left >= 0, rightInView: r.right <= innerWidth, menuScroll: panel.scrollHeight - panel.clientHeight };
      });
    }
    console.log(width, height, 'header', JSON.stringify(head), 'menu', JSON.stringify(menu), 'flyout', JSON.stringify(flyout), `${Date.now() - started - 500}ms`);
    if (width === 1661) await page.screenshot({ path: `${out}/menu-1661.png` });
    await page.close();
  }
  await browser.close();
})();

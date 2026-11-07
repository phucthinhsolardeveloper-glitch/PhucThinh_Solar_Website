#!/usr/bin/env node
// Opens the certificate viewer, a product photo, the mega menu on a short screen and the phone search panel,
// and saves screenshots to the folder given as the first argument (default audit/out).
const { chromium } = require('playwright');
const fs = require('node:fs');
const out = process.argv[2] || 'audit/out';
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
fs.mkdirSync(out, { recursive: true });
(async () => {
  const browser = await chromium.launch();
  const open = async (options) => {
    const context = await browser.newContext({ ...options, locale: 'vi-VN' });
    await context.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await context.route('**/pts-living-energy/index.html*', (route) => route.abort());
    await context.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    return context.newPage();
  };
  const desktop = await open({ viewport: { width: 1600, height: 900 } });
  await desktop.goto(`${base}/gioi-thieu/`, { waitUntil: 'load' });
  await desktop.waitForTimeout(700);
  const certificate = desktop.locator('#about-certificates button:has(img), #about-certificates a:has(img)').first();
  await certificate.scrollIntoViewIfNeeded();
  await certificate.click();
  await desktop.waitForTimeout(700);
  await desktop.screenshot({ path: `${out}/certificate.png` });
  console.log('certificate dialog open', await desktop.locator('.about-certificate-dialog[open]').count());

  await desktop.goto(`${base}/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-8300l-anh-sang-vang`, { waitUntil: 'load' }).catch(() => {});
  const stage = desktop.locator('.pdp-studio-stage');
  if (!(await stage.count())) {
    await desktop.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    await desktop.goto(base + await desktop.locator('article a[href*="/san-pham/"]').first().getAttribute('href'), { waitUntil: 'load' });
  }
  await desktop.waitForTimeout(800);
  await desktop.locator('.pdp-studio-stage').click();
  await desktop.mouse.move(2, 2);
  await desktop.waitForTimeout(1200);
  await desktop.screenshot({ path: `${out}/product-viewer.png` });
  console.log('viewer image background', await desktop.locator('.photo-viewer img').first().evaluate((image) => getComputedStyle(image).backgroundColor));

  const short = await open({ viewport: { width: 1600, height: 640 } });
  await short.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  await short.waitForTimeout(600);
  await short.locator('header li').filter({ hasText: /Danh\s+mục\s+sản\s+phẩm/ }).first().hover();
  await short.waitForTimeout(500);
  const items = short.locator('#ptsCatalogMenu li:has(> .catalog-flyout)');
  let cut = 0;
  const count = await items.count();
  for (let index = 0; index < count; index += 1) {
    if (!(await items.nth(index).isVisible())) continue;
    await items.nth(index).scrollIntoViewIfNeeded().catch(() => {});
    await items.nth(index).hover({ timeout: 3000 }).catch(() => {});
    await short.waitForTimeout(260);
    const over = await short.evaluate(() => {
      const flyout = document.querySelector('.is-flyout-open > .catalog-flyout');
      const menu = document.getElementById('ptsCatalogMenu');
      if (!flyout || !menu) return 0;
      return flyout.getBoundingClientRect().bottom - Math.min(menu.getBoundingClientRect().bottom, innerHeight);
    });
    if (over > 1) cut += 1;
    if (index === count - 1) await short.screenshot({ path: `${out}/menu-short-screen.png` });
  }
  console.log('flyouts', count, 'cut off', cut);

  const phone = await open({ viewport: { width: 412, height: 924 }, isMobile: true, hasTouch: true });
  await phone.goto(`${base}/san-pham/`, { waitUntil: 'load' });
  await phone.waitForTimeout(600);
  await phone.locator('header button[aria-label="Mở tìm kiếm"]').first().tap();
  await phone.waitForTimeout(600);
  await phone.screenshot({ path: `${out}/search-phone.png` });
  await browser.close();
})();

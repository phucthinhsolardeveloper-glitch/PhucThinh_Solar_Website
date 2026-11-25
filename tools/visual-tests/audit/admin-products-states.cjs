#!/usr/bin/env node
// Walks the product list the way staff do it (tabs, bulk hide, undo, price menu, quick edit) and photographs each
// step. It CHANGES data: run it only against a local preview, never production.
//
//   ADMIN_USER=… ADMIN_PASS=… ADMIN_BASE=http://127.0.0.1:8092 node audit/admin-products-states.cjs <out dir> <category id>
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.ADMIN_BASE || 'http://127.0.0.1:8092';
const out = process.argv[2] || path.join(__dirname, 'out', 'admin-products-states');
const category = process.argv[3];
if (!/^http:\/\/(127\.0\.0\.1|localhost)[:/]/.test(base)) throw new Error('Local preview only');

(async () => {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: Number(process.env.WIDTH || 1710), height: 960 }, colorScheme: 'dark' });
  const page = await context.newPage();
  const problems = [];
  page.on('response', (r) => { if (r.status() >= 500) problems.push(`${r.status()} ${r.url()}`); });
  page.on('pageerror', (e) => problems.push(`JS ${e.message}`));
  const shot = async (name) => { await page.waitForTimeout(700); await page.screenshot({ path: path.join(out, `${name}.png`) }); console.log('saved', name); };
  const settle = () => page.waitForTimeout(1800);

  await page.goto(`${base}/admin/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('[wire\\:model="data.username"]').fill(process.env.ADMIN_USER);
  await page.locator('[wire\\:model\\.fill="data.password"]').fill(process.env.ADMIN_PASS);
  await Promise.all([page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 30000 }), page.locator('button[type="submit"]').first().click()]);

  const url = `${base}/admin/products${category ? `?filters[category_id][value]=${category}` : ''}`;
  await page.goto(url, { waitUntil: 'load' });
  await settle();
  await shot('1-list');
  const counts = () => page.locator('.fi-tabs-item').allInnerTexts().then((all) => all.map((t) => t.replace(/\s+/g, ' ').trim()).join(' | '));
  console.log('tabs:', await counts());
  const overflow = await page.evaluate(() => { const t = document.querySelector('.fi-ta-content, .fi-ta-content-ctn'); return t ? [t.scrollWidth, t.clientWidth] : null; });
  console.log('table width scroll/client:', JSON.stringify(overflow));

  const tickAll = () => page.locator('thead input[type="checkbox"]:visible').first().click();
  await tickAll();
  await settle();
  await shot('2-selected');
  // The group trigger is the toolbar button carrying the group's name.
  await page.locator('button:visible', { hasText: 'Hiện / Ẩn' }).first().click();
  await shot('2-menu-show-hide');
  await page.locator('button:visible', { hasText: 'Ẩn khỏi website' }).first().click();
  await settle();
  await shot('3-confirm-hide');
  await page.locator('.fi-modal-window:visible button[type="submit"]').click();
  await settle();
  await shot('4-after-hide');
  console.log('tabs:', await counts());
  await page.getByRole('button', { name: 'Hoàn tác' }).click();
  await settle();
  await shot('5-after-undo');
  console.log('tabs:', await counts());

  await tickAll();
  await settle();
  await page.locator('button:visible', { hasText: /^\s*Giá\s*$/ }).first().click();
  await shot('6-menu-price');
  await page.locator('button:visible', { hasText: 'Ẩn giá (Liên hệ báo giá)' }).first().click();
  await settle();
  await shot('7-confirm-hide-price');
  await page.keyboard.press('Escape');
  await settle();

  await page.goto(url, { waitUntil: 'load' });
  await settle();
  await page.locator('tbody tr').nth(1).getByText('Sửa nhanh').click();
  await settle();
  await shot('8-quick-edit');
  await page.keyboard.press('Escape');
  await settle();
  await page.locator('.fi-tabs-item:visible', { hasText: 'Đang ẩn' }).first().click();
  await settle();
  await shot('9-tab-hidden');

  console.log(problems.length ? `PROBLEMS:\n- ${problems.join('\n- ')}` : 'no server or JS errors');
  await browser.close();
})().catch((e) => { console.error(e.message); process.exit(1); });

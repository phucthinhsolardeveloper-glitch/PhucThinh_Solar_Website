#!/usr/bin/env node
// Signs in to the Filament Admin and opens every screen at desktop and phone width: reports server
// errors, JS errors and horizontal overflow, and saves a screenshot of each screen. Read-only: it
// never presses Save or Delete.
//
//   ADMIN_BASE=http://127.0.0.1:8090 ADMIN_USER=… ADMIN_PASS=… node audit/admin-screens.cjs [output dir]
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.ADMIN_BASE || 'http://127.0.0.1:8090';
const out = process.argv[2] || path.join(__dirname, 'out', 'admin');
const screens = ['', 'products', 'products/create', 'categories', 'brands', 'articles', 'solutions', 'projects', 'policies', 'post-categories',
  'page-items', 'banners', 'albums', 'videos', 'orders', 'wards', 'payment-methods', 'leads', 'reviews', 'thong-tin-website',
  'store-locations', 'seo-pages', 'users'];

(async () => {
  if (!process.env.ADMIN_USER || !process.env.ADMIN_PASS) throw new Error('Set ADMIN_USER and ADMIN_PASS');
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  const report = [];
  // One sign-in for both widths: Filament throttles repeated logins.
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const problems = [];
  page.on('response', (response) => { if (response.status() >= 500) problems.push(`${response.status()} ${response.url()}`); });
  page.on('pageerror', (error) => problems.push(`JS ${error.message}`));
  await page.goto(`${base}/admin/login`, { waitUntil: 'networkidle' });
  await page.locator('input[autocomplete="username"]').fill(process.env.ADMIN_USER);
  await page.locator('input[autocomplete="current-password"]').fill(process.env.ADMIN_PASS);
  await page.getByRole('button', { name: /Đăng nhập/ }).click();
  await page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15000 });
  for (const [label, viewport] of [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
    await page.setViewportSize(viewport);
    for (const screen of screens) {
      problems.length = 0;
      const response = await page.goto(`${base}/admin/${screen}`, { waitUntil: 'networkidle' });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      const file = path.join(out, `${label}-${screen.replace(/\//g, '_') || 'dashboard'}.png`);
      await page.screenshot({ path: file, fullPage: false });
      report.push({ viewport: label, screen: screen || 'dashboard', status: response.status(), overflow: overflow > 1 ? overflow : 0, problems: [...problems] });
    }
  }
  await browser.close();
  const bad = report.filter((row) => row.status !== 200 || row.overflow || row.problems.length);
  console.log(JSON.stringify({ screens: report.length, issues: bad }, null, 2));
  process.exitCode = bad.length ? 1 : 0;
})();

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
  'store-locations', 'seo-pages', 'users', 'media-files', 'audit-events', 'ban-nhap', 'mau-email', 'tich-hop', 'redirects'];
// With DEEP=1 the first record's edit page and the create page of each list are opened too.
const deep = process.env.DEEP === '1';

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
    const queue = [...screens];
    const seen = new Set();
    while (queue.length) {
      const screen = queue.shift();
      if (seen.has(screen)) continue;
      seen.add(screen);
      problems.length = 0;
      const response = await page.goto(`${base}/admin/${screen}`, { waitUntil: 'networkidle' });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
      // A table wider than its card hides the row actions behind a horizontal scroll.
      const tableOverflow = await page.evaluate(() => Math.max(0, ...[...document.querySelectorAll('.fi-ta-content, .fi-ta-content-ctn')]
        .map((el) => el.scrollWidth - el.clientWidth)));
      const file = path.join(out, `${label}-${screen.replace(/\//g, '_') || 'dashboard'}.png`);
      await page.screenshot({ path: file, fullPage: false });
      report.push({ viewport: label, screen: screen || 'dashboard', status: response.status(), overflow: overflow > 1 ? overflow : 0, tableOverflow: label === 'desktop' && tableOverflow > 1 ? tableOverflow : 0, problems: [...problems] });
      if (deep && label === 'desktop' && screen && !screen.includes('/')) {
        const links = await page.evaluate((list) => [...document.querySelectorAll('a[href]')].map((a) => new URL(a.href).pathname)
          .filter((href) => href.startsWith(`/admin/${list}/`) && (/\/\d+\/edit$/.test(href) || href.endsWith('/create'))), screen);
        const edit = links.find((href) => href.endsWith('/edit'));
        const create = links.find((href) => href.endsWith('/create'));
        for (const href of [edit, create].filter(Boolean)) queue.push(href.replace('/admin/', ''));
        if (!edit) {
          // Simple resources edit in a modal: open the first row's edit/view action.
          const button = page.locator('.fi-ta-row .fi-ta-actions').first().getByRole('button', { name: /^(Sửa|Xem|Chỉnh sửa)$/ }).first();
          if (await button.count()) {
            problems.length = 0;
            await button.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(800);
            const modal = await page.locator('.fi-modal-window:visible').count();
            await page.screenshot({ path: path.join(out, `${label}-${screen}-modal.png`), fullPage: false });
            report.push({ viewport: label, screen: `${screen} (modal)`, status: modal ? 200 : 0, overflow: 0, problems: [...problems] });
            await page.keyboard.press('Escape');
          }
        }
      }
    }
  }
  await browser.close();
  const bad = report.filter((row) => row.status !== 200 || row.overflow || row.tableOverflow || row.problems.length);
  console.log(JSON.stringify({ screens: report.length, issues: bad }, null, 2));
  process.exitCode = bad.length ? 1 : 0;
})();

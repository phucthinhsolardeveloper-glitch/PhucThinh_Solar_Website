#!/usr/bin/env node
// Signs in to Admin and photographs the screens you name, so a UI change can be judged on the real
// rendered page instead of a description. Read-only: it never presses Save or Delete.
//
//   ADMIN_USER=… ADMIN_PASS=… node audit/admin-shot.cjs <output dir> <slug> [slug…]
//
// A slug is what follows /admin/, e.g. `post-categories` or `thong-tin-website`. Add `#open-select:<id
// fragment>` to a slug to open that select before the shot, e.g. `thong-tin-website#open-select:bank_bin`.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.ADMIN_BASE || 'http://127.0.0.1:8090';
const out = process.argv[2] || path.join(__dirname, 'out', 'admin-shot');
const targets = process.argv.slice(3);
const width = Number(process.env.WIDTH || 1440);

(async () => {
  if (!process.env.ADMIN_USER || !process.env.ADMIN_PASS) throw new Error('Set ADMIN_USER and ADMIN_PASS');
  if (targets.length === 0) throw new Error('Name at least one screen, e.g. post-categories');
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width, height: 950 } });
  const problems = [];
  page.on('response', (r) => { if (r.status() >= 500) problems.push(`${r.status()} ${r.url()}`); });
  page.on('pageerror', (e) => problems.push(`JS ${e.message}`));

  // Livewire keeps a connection open, so 'networkidle' never settles; wait for the page's own markers.
  await page.goto(`${base}/admin/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('[wire\\:model="data.username"]').fill(process.env.ADMIN_USER);
  await page.locator('[wire\\:model\\.fill="data.password"]').fill(process.env.ADMIN_PASS);
  await Promise.all([page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 20000 }), page.locator('button[type="submit"]').first().click()]);

  for (const target of targets) {
    const [slug, directive] = target.split('#');
    const name = (slug || 'dashboard').replace(/[^a-z0-9-]+/gi, '-') + (directive ? '-' + directive.replace(/[^a-z0-9-]+/gi, '-') : '');
    await page.goto(`${base}/admin/${slug}`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load');
    await page.waitForTimeout(1200);
    if (directive && directive.startsWith('open-select:')) {
      const field = directive.slice('open-select:'.length);
      // Filament renders a searchable select as a button that opens a listbox.
      const trigger = page.locator(`[id*="${field}"]`).first();
      await trigger.scrollIntoViewIfNeeded();
      await trigger.click({ timeout: 5000 });
      await page.waitForTimeout(800);
    }
    await page.screenshot({ path: path.join(out, `${name}.png`), fullPage: !directive });
    console.log('saved', path.join(out, `${name}.png`));
  }

  console.log(problems.length ? `PROBLEMS:\n- ${problems.join('\n- ')}` : 'no server or JS errors');
  await browser.close();
})();

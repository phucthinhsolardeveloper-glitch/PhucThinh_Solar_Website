#!/usr/bin/env node
// Brand and project-category links, and the buy / quote bar on a phone. Read-only: nothing is submitted.
//
//   AUDIT_BASE=http://127.0.0.1:3130 AUDIT_SHOTS=/some/folder node audit/growth-links.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const shots = process.env.AUDIT_SHOTS || '';

(async () => {
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
  await context.route(/^https?:\/\/([a-z0-9-]+\.)*(googletagmanager\.com|google-analytics\.com|gtranslate\.net|google\.com)\//, (route) => route.abort());
  const page = await context.newPage();
  const serverErrors = [];
  page.on('response', (response) => { if (response.status() >= 500) serverErrors.push(`${response.status()} ${response.url()}`); });
  const checks = {};
  const notes = {};
  const open = (path) => page.goto(`${base}${path}`, { waitUntil: 'networkidle' });

  const catalog = await (await fetch(`${base}/_api/catalog?per_page=48`)).json();
  const priced = catalog.products.find((item) => item.pricing.mode === 'price');
  const quoted = catalog.products.find((item) => item.pricing.mode !== 'price')
    || (await (await fetch(`${base}/_api/catalog?category=den-nlmt/den-duong&per_page=48`)).json()).products.find((item) => item.pricing.mode !== 'price');

  for (const [name, product] of [['priced', priced], ['quoted', quoted]]) {
    if (!product) { notes[name] = 'NOT RUN: no such product in this database'; continue; }
    await open(product.url);
    // The cookie notice appears a moment after the page; it covers the lower part until answered.
    await page.getByRole('region', { name: 'Thông báo cookie' }).getByRole('button', { name: 'Từ chối' }).click({ timeout: 6000 }).catch(() => {});
    // Scrolled past the main buttons, the bar at the bottom is the way to buy or ask.
    await page.mouse.wheel(0, 2600);
    await page.waitForTimeout(900);
    const found = await page.evaluate(() => {
      const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < innerHeight && s.visibility !== 'hidden' && s.opacity !== '0'; };
      const label = /Mua ngay|Nhận báo giá|Liên hệ nhận báo giá|Thêm vào giỏ/;
      // Labels use non-breaking spaces; compare plain text.
      const text = (el) => (el.textContent || '').normalize('NFC').replace(/\s+/g, ' ').trim();
      const buttons = [...document.querySelectorAll('button, a')].filter((el) => label.test(text(el)) && visible(el));
      // What a finger at the middle of each button would actually touch.
      const reachable = buttons.filter((el) => { const r = el.getBoundingClientRect(); const hit = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2); return hit && (el === hit || el.contains(hit)); });
      return { buttons: buttons.map((el) => text(el).slice(0, 40)), reachable: reachable.length, overflow: document.documentElement.scrollWidth - innerWidth };
    });
    notes[name] = { url: product.url, ...found };
    checks[`${name}ActionReachable`] = found.reachable > 0 && found.overflow <= 0;
    if (shots) await page.screenshot({ path: `${shots}/pdp-${name}.png` });
  }

  await open(priced.url);
  const brand = page.locator('main a[href^="/thuong-hieu/"]').first();
  const brandHref = await brand.count() ? await brand.getAttribute('href') : null;
  if (brandHref) {
    await brand.click();
    await page.waitForURL(`**${brandHref}`);
    // The old page stays in the document until the new one has its data.
    await page.waitForFunction(() => (document.querySelector('h1')?.textContent || '').normalize('NFC').replace(/\s+/g, ' ').trim().startsWith('Sản phẩm'), null, { timeout: 15000 }).catch(() => {});
  }
  notes.brand = { href: brandHref, heading: ((await page.locator('h1').first().textContent()) || '').normalize('NFC').replace(/\s+/g, ' ').trim() };
  checks.brandLink = !!brandHref && new URL(page.url()).pathname === brandHref && notes.brand.heading.startsWith('Sản phẩm');

  await open('/gioi-thieu/');
  const wall = await page.locator('.about-brand-cell a').evaluateAll((links) => links.map((a) => a.getAttribute('href')));
  notes.aboutBrands = wall;
  checks.aboutBrandsLinkToBrandPages = wall.length > 0 && wall.every((href) => /^\/thuong-hieu\/[a-z0-9-]+\/$/.test(href));

  await open('/du-an-da-thuc-hien/');
  const chip = page.locator('nav a.public-filter-chip').nth(1);
  const chipHref = await chip.getAttribute('href');
  await chip.click();
  await page.waitForURL(`**${chipHref}`);
  await page.waitForFunction((href) => document.querySelector('nav a.public-filter-chip[aria-current="page"]')?.getAttribute('href') === href, chipHref, { timeout: 15000 }).catch(() => {});
  const current = await page.locator('nav a.public-filter-chip[aria-current="page"]').getAttribute('href');
  notes.projects = { chipHref, current, heading: await page.locator('h1').first().textContent() };
  checks.projectChip = /^\/[a-z0-9-]+$/.test(chipHref) && current === chipHref;

  checks.noServerErrors = serverErrors.length === 0;
  console.log(JSON.stringify({ notes, serverErrors }, null, 2));
  console.log(JSON.stringify(checks));
  await browser.close();
  process.exit(Object.values(checks).every(Boolean) ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });

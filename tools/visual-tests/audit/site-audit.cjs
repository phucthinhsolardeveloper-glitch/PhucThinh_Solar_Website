#!/usr/bin/env node
// Scans the public site for problems a person would notice: sideways scroll, clipped text, broken images,
// server errors, console errors and leftover dark overlays. Run it after any visible change and add new checks
// here rather than writing a new script. Read only: it never submits a form or an order.
//
//   node audit/site-audit.cjs                 local preview on :3100
//   AUDIT_BASE=https://phucthinhsolar.com node audit/site-audit.cjs
//   AUDIT_ROUTES=/,/lien-he/ AUDIT_WIDTHS=412 node audit/site-audit.cjs
const { chromium } = require('playwright');
const fs = require('node:fs');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const widths = (process.env.AUDIT_WIDTHS || '360,412,820,1440').split(',').map(Number);
const routes = (process.env.AUDIT_ROUTES || [
  '/', '/gioi-thieu/', '/giai-phap/', '/giai-phap/dien-mat-troi-ho-gia-dinh/', '/giai-phap/he-thong-luu-tru-bess/',
  '/san-pham/', '/danh-muc-san-pham/den-nlmt/', '/tim-kiem/?keyword=den+pha', '/tim-kiem/?keyword=zzzzzz',
  '/du-an-da-thuc-hien/', '/danh-muc-du-an/du-an-doanh-nghiep/', '/du-an-dien-nlmt-ho-anh-quan/',
  '/tin-tuc/', '/tin-truyen-thong', '/dai-ly/', '/lien-he/', '/gio-hang/', '/video/', '/thu-vien-anh/',
].join(',')).split(',');

function scanPage() {
  const issues = [];
  const doc = document.documentElement;
  if (doc.scrollWidth > window.innerWidth + 1) issues.push(['overflow-x', `page is ${doc.scrollWidth}px wide on ${window.innerWidth}px`]);
  for (const image of document.images) {
    if (image.complete && image.naturalWidth === 0 && image.getAttribute('src') && image.getClientRects().length) issues.push(['broken-image', image.getAttribute('src').slice(0, 80)]);
  }
  const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  const seen = new Set();
  while (walker.nextNode()) {
    const node = walker.currentNode;
    if (!node.data.trim()) continue;
    const element = node.parentElement;
    if (!element || seen.has(element) || element.closest('script,style,noscript,.sr-only,[class*="sr-only"],[aria-hidden="true"]')) continue;
    seen.add(element);
    const style = getComputedStyle(element);
    if (style.visibility === 'hidden' || !element.getClientRects().length || style.textOverflow === 'ellipsis' || style.webkitLineClamp !== 'none') continue;
    const range = document.createRange();
    range.selectNodeContents(node);
    const text = range.getBoundingClientRect();
    if (!text.width) continue;
    for (let parent = element, depth = 0; parent && depth < 6; parent = parent.parentElement, depth += 1) {
      const box = getComputedStyle(parent);
      if (!['hidden', 'clip'].includes(box.overflowX)) { if (['auto', 'scroll'].includes(box.overflowX)) break; continue; }
      const edge = parent.getBoundingClientRect();
      if (edge.width > 0 && (text.right > edge.right + 2 || text.left < edge.left - 2)) {
        issues.push(['clipped-text', `${node.data.trim().slice(0, 44)} <${element.tagName.toLowerCase()}.${String(element.className).slice(0, 24)}>`]);
      }
      break;
    }
  }
  return issues;
}

(async () => {
  const browser = await chromium.launch();
  const all = [];
  for (const width of widths) {
    const phone = width < 821;
    const context = await browser.newContext({ viewport: { width, height: phone ? 844 : 900 }, isMobile: phone, hasTouch: phone, locale: 'vi-VN' });
    await context.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await context.route('**/pts-living-energy/index.html*', (route) => route.abort());
    await context.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    const page = await context.newPage();
    let current = '';
    const add = (kind, detail) => all.push({ width, route: current, kind, detail });
    page.on('pageerror', (error) => add('js-error', error.message.slice(0, 140)));
    page.on('response', (response) => { if (response.status() >= 500) add('server-error', `${response.status()} ${response.url().replace(base, '').slice(0, 90)}`); });
    for (const route of routes) {
      current = route;
      const response = await page.goto(base + route, { waitUntil: 'load' }).catch((error) => { add('load-failed', error.message.slice(0, 100)); return null; });
      if (response && response.status() >= 400) add('status', String(response.status()));
      await page.waitForTimeout(500);
      const height = await page.evaluate(() => document.documentElement.scrollHeight);
      for (let y = 0; y < height; y += 800) { await page.evaluate((top) => window.scrollTo({ top, behavior: 'instant' }), y); await page.waitForTimeout(40); }
      await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
      await page.waitForTimeout(250);
      for (const [kind, detail] of await page.evaluate(scanPage)) add(kind, detail);
    }
    // Panels that only exist once opened.
    current = 'header search panel';
    await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    await page.waitForTimeout(500);
    await page.locator('header button[aria-label="Mở tìm kiếm"]').first().click();
    await page.waitForTimeout(500);
    for (const [kind, detail] of await page.evaluate(scanPage)) add(kind, detail);
    if (!phone) {
      current = 'mega menu flyouts';
      await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
      await page.waitForTimeout(500);
      await page.locator('header li').filter({ hasText: /Danh\s+mục\s+sản\s+phẩm/ }).first().hover({ timeout: 8000 }).catch(() => add('audit-step', 'could not open the product menu'));
      await page.waitForTimeout(500);
      const items = page.locator('#ptsCatalogMenu li:has(> .catalog-flyout)');
      // The menu closes when the pointer leaves it, so each item is reached from inside.
      for (let index = 0, count = await items.count(); index < count; index += 1) {
        if (!(await items.nth(index).isVisible())) continue;
        await items.nth(index).hover({ timeout: 4000 }).catch(() => {});
        await page.waitForTimeout(220);
        const cut = await page.evaluate(() => {
          const flyout = document.querySelector('.is-flyout-open > .catalog-flyout');
          const menu = document.getElementById('ptsCatalogMenu');
          if (!flyout || !menu) return null;
          const box = flyout.getBoundingClientRect();
          const limit = Math.min(menu.getBoundingClientRect().bottom, window.innerHeight);
          return box.bottom > limit + 1 ? `${flyout.getAttribute('aria-label')} ends ${Math.round(box.bottom - limit)}px below the panel` : null;
        });
        if (cut) add('cut-off-menu', cut);
      }
    }
    await context.close();
  }
  await browser.close();
  const unique = [...new Map(all.map((item) => [`${item.width}|${item.route}|${item.kind}|${item.detail}`, item])).values()];
  fs.mkdirSync('audit/out', { recursive: true });
  fs.writeFileSync('audit/out/site-audit.json', JSON.stringify(unique, null, 2));
  const counts = {};
  for (const item of unique) counts[item.kind] = (counts[item.kind] ?? 0) + 1;
  console.log(`${unique.length} issues on ${routes.length} routes at ${widths.join(', ')}px`, counts);
  for (const item of unique.slice(0, Number(process.env.AUDIT_SHOW || 60))) console.log(`${item.width}\t${item.route}\t${item.kind}\t${item.detail}`);
})();

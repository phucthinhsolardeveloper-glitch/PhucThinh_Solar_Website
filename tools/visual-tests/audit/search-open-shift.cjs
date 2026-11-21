#!/usr/bin/env node
// Opens the header search with a CLASSIC scrollbar (as on Windows) and reports what moves: page width, header and hero edges,
// layout shifts, style recalculation count. Read only.
//   AUDIT_BASE=https://phucthinhsolar.com AUDIT_ROUTE=/ node audit/search-open-shift.cjs
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const route = process.env.AUDIT_ROUTE || '/';
(async () => {
  const browser = await chromium.launch({ ignoreDefaultArgs: ['--hide-scrollbars'], args: ['--disable-features=OverlayScrollbar'] });
  const ctx = await browser.newContext({ viewport: { width: 1536, height: 864 }, locale: 'vi-VN' });
  await ctx.route(/cdn\.gtranslate\.net/, (r) => r.abort());
  const page = await ctx.newPage();
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} window.__shifts = []; try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__shifts.push({ v: +e.value.toFixed(4), src: (e.sources || []).map((s) => (s.node ? s.node.nodeName + '.' + String(s.node.className?.baseVal ?? s.node.className ?? '').slice(0, 30) : '')).slice(0, 2) }); }).observe({ type: 'layout-shift' }); } catch {} });
  await cdp0(page);
  await page.goto(base + route, { waitUntil: 'load' });
  await page.waitForTimeout(2500);
  const snap = () => page.evaluate(() => {
    const r = (sel) => { const e = document.querySelector(sel); if (!e) return null; const b = e.getBoundingClientRect(); return [Math.round(b.left), Math.round(b.right), Math.round(b.width)]; };
    return { innerWidth, clientWidth: document.documentElement.clientWidth, scrollbar: innerWidth - document.documentElement.clientWidth, capsule: r('.pts-header-capsule'), firstSection: r('main section'), main: r('main'), bodyPadRight: document.body.style.paddingRight, recalc: performance.getEntriesByType('measure').length };
  });
  const before = await snap();
  const t0 = Date.now();
  await page.locator('button[aria-controls="ptsHeaderSearch"]').first().click();
  await page.waitForSelector('#ptsHeaderSearchInput', { state: 'visible' });
  await page.waitForTimeout(1000);
  const after = await snap();
  const shifts = await page.evaluate(() => window.__shifts);
  console.log('before', JSON.stringify(before)); console.log('after ', JSON.stringify(after));
  console.log('layout shifts on open:', JSON.stringify(shifts.filter((s) => s.v > 0.0005)), 'total', shifts.reduce((a, s) => a + s.v, 0).toFixed(4));
  await browser.close();
})();
async function cdp0() {}

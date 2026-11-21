#!/usr/bin/env node
// A/B: which part of the homepage costs the most while scrolling? Each variant injects CSS that switches one thing off,
// then wheel-scrolls the whole page and reports frame times (p50 / p95 / worst / frames over 50 ms) and long tasks. Read only.
//   AUDIT_BASE=https://phucthinhsolar.com AUDIT_CPU=2 node audit/home-scroll-ab.cjs
//   AUDIT_ONLY='baseline,no iframe 3D'   run only these variants
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const route = process.env.AUDIT_ROUTE || '/';
const cpu = Number(process.env.AUDIT_CPU || 2);
const variants = {
  baseline: '',
  'no backdrop-filter': '*{backdrop-filter:none!important;-webkit-backdrop-filter:none!important}',
  'no iframe 3D': 'iframe{display:none!important}',
  'no animations': '*,*::before,*::after{animation:none!important}',
  'no map section': '.dealer{display:none!important}',
  'no filters': '*{filter:none!important}',
  'no sticky header': 'header{display:none!important}',
};
(async () => {
  const browser = await chromium.launch();
  const only = (process.env.AUDIT_ONLY || '').split(',').filter(Boolean);
  for (const [name, css] of Object.entries(variants)) {
    if (only.length && !only.includes(name)) continue;
    const ctx = await browser.newContext({ viewport: { width: 1536, height: 864 }, locale: 'vi-VN' });
    await ctx.route(/cdn\.gtranslate\.net/, (r) => r.abort());
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page); await cdp.send('Emulation.setCPUThrottlingRate', { rate: cpu });
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} window.__f = []; window.__long = 0; try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__long += e.duration; }).observe({ type: 'longtask' }); } catch {} });
    await page.goto(base + route, { waitUntil: 'load' });
    if (css) await page.addStyleTag({ content: css });
    await page.waitForTimeout(2500);
    const total = await page.evaluate(() => document.documentElement.scrollHeight);
    await page.evaluate(() => { window.__f = []; window.__long = 0; let l = performance.now(); const t = (n) => { window.__f.push(n - l); l = n; requestAnimationFrame(t); }; requestAnimationFrame(t); });
    for (let y = 0; y < total - 864; y += 300) { await page.mouse.wheel(0, 300); await page.waitForTimeout(60); }
    await page.waitForTimeout(300);
    const r = await page.evaluate(() => { const f = window.__f.slice(1).sort((a, b) => a - b); const p = (q) => Math.round(f[Math.min(f.length - 1, Math.floor(f.length * q))]); return { frames: f.length, p50: p(0.5), p95: p(0.95), worst: Math.round(f[f.length - 1]), over50: f.filter((x) => x > 50).length, longMs: Math.round(window.__long) }; });
    console.log(name.padEnd(20), JSON.stringify(r));
    await ctx.close();
  }
  await browser.close();
})();

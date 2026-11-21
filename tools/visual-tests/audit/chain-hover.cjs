#!/usr/bin/env node
// Smoothness of the homepage "energy chain" (five expanding cards): hovers across the cards in order, twice,
// and reports frame times and long tasks. Variants inject CSS to see what each part costs. Read only.
//   AUDIT_BASE=https://phucthinhsolar.com AUDIT_CPU=2 AUDIT_ONLY='baseline' node audit/chain-hover.cjs
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const cpu = Number(process.env.AUDIT_CPU || 2);
const only = (process.env.AUDIT_ONLY || '').split(',').filter(Boolean);
const variants = {
  baseline: '',
  'no map animation': '.dealer-map *{animation:none!important}',
  'image layer': '.chain-image{will-change:transform,filter}',
  'contain cards': '.chain-link{contain:layout style}.chain-card{contain:layout paint style}',
  'no filter': '.chain-image{filter:none!important}',
  'fixed image width': '.chain-track{container-type:inline-size}.chain-image{inset:0 auto 0 50%!important;width:calc((100cqw - 40px) * 4.2 / 8.2)!important;max-width:none!important;translate:-50% 0}',
  'no radius': '.chain-card{border-radius:0!important}',
  'no isolate+clip': '.chain-card{isolation:auto!important;overflow:visible!important}.chain-link{overflow:hidden}',
  'no image transform': '.chain-image{transform:none!important;transition:none!important}',
  'no inner transitions': '.chain-body,.chain-step{transition:none!important}',
  'no veil': '.chain-veil{display:none!important}',
  'no wire': '.chain-wire{display:none!important}',
  'all but fixed width': '.chain-image{will-change:transform,filter}.chain-link{contain:layout style}.chain-card{contain:layout paint style}',
};
(async () => {
  const browser = await chromium.launch();
  for (const [name, css] of Object.entries(variants)) {
    if (only.length && !only.includes(name)) continue;
    const ctx = await browser.newContext({ viewport: { width: 1536, height: 864 }, locale: 'vi-VN' });
    await ctx.route(/cdn\.gtranslate\.net/, (r) => r.abort());
    const page = await ctx.newPage();
    const cdp = await ctx.newCDPSession(page); await cdp.send('Emulation.setCPUThrottlingRate', { rate: cpu });
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} window.__long = 0; try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__long += e.duration; }).observe({ type: 'longtask' }); } catch {} });
    await page.goto(base + '/', { waitUntil: 'load' });
    if (css) await page.addStyleTag({ content: css });
    await page.evaluate(() => document.querySelector('#danh-muc-thiet-bi')?.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(2500);
    const cards = await page.locator('.chain-link').all();
    await page.evaluate(() => { window.__f = []; window.__long = 0; let l = performance.now(); const t = (n) => { window.__f.push(n - l); l = n; requestAnimationFrame(t); }; requestAnimationFrame(t); });
    for (let round = 0; round < 2; round++) for (const c of cards) { await c.hover(); await page.waitForTimeout(900); }
    const r = await page.evaluate(() => { const f = window.__f.slice(1).sort((a, b) => a - b); const p = (q) => Math.round(f[Math.min(f.length - 1, Math.floor(f.length * q))]); return { frames: f.length, p50: p(0.5), p95: p(0.95), worst: Math.round(f[f.length - 1]), over33: f.filter((x) => x > 33).length, longMs: Math.round(window.__long) }; });
    console.log(name.padEnd(20), JSON.stringify(r));
    await ctx.close();
  }
  await browser.close();
})();

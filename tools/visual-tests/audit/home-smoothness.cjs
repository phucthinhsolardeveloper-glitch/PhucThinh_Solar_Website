#!/usr/bin/env node
// Smoothness of the homepage on a desktop at normal speed and 4x CPU slowdown. Read only.
//   load      long tasks, blocking time, LCP, CLS
//   scroll    frame times while wheel-scrolling the whole page, long tasks, how many animations run at once
//   search    click on the header search: page width change (lost scrollbar), layout shifts, long tasks, frame times
//
//   AUDIT_BASE=https://phucthinhsolar.com node audit/home-smoothness.cjs
//   AUDIT_ROUTE=/ AUDIT_CPU=4 AUDIT_VIEW=1536x864
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const route = process.env.AUDIT_ROUTE || '/';
const [width, height] = (process.env.AUDIT_VIEW || '1536x864').split('x').map(Number);
const cpuRates = (process.env.AUDIT_CPU || '1,4').split(',').map(Number);

const probe = () => {
  window.__long = [];
  window.__shifts = [];
  window.__lcp = 0;
  window.__frames = [];
  try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__long.push({ at: Math.round(e.startTime), d: Math.round(e.duration) }); }).observe({ type: 'longtask', buffered: true }); } catch {}
  try { new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = Math.round(e.startTime); }).observe({ type: 'largest-contentful-paint', buffered: true }); } catch {}
  try { new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__shifts.push({ v: +e.value.toFixed(4), at: Math.round(e.startTime), src: (e.sources || []).map((s) => (s.node ? s.node.nodeName + '.' + String(s.node.className || '').slice(0, 40) : '')).slice(0, 2) }); }).observe({ type: 'layout-shift', buffered: true }); } catch {}
  try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {}
  let last = performance.now();
  const tick = (t) => { window.__frames.push(Math.round(t - last)); last = t; requestAnimationFrame(tick); };
  requestAnimationFrame(tick);
};

const take = (page) => page.evaluate(() => {
  const long = window.__long.splice(0);
  const frames = window.__frames.splice(0).filter((f) => f > 0);
  const sorted = [...frames].sort((a, b) => a - b);
  const pct = (p) => sorted.length ? sorted[Math.min(sorted.length - 1, Math.floor(sorted.length * p))] : 0;
  const shifts = window.__shifts.splice(0);
  return {
    longTasks: long.length, longTotalMs: long.reduce((a, b) => a + b.d, 0), longMaxMs: Math.max(0, ...long.map((l) => l.d)),
    frames: frames.length, p50: pct(0.5), p95: pct(0.95), maxFrame: sorted[sorted.length - 1] || 0, over50: frames.filter((f) => f > 50).length,
    cls: +shifts.reduce((a, s) => a + s.v, 0).toFixed(4), shifts: shifts.filter((s) => s.v > 0.002).slice(0, 4),
  };
});

(async () => {
  const browser = await chromium.launch();
  for (const rate of cpuRates) {
    const context = await browser.newContext({ viewport: { width, height }, locale: 'vi-VN' });
    await context.route(/cdn\.gtranslate\.net/, (r) => r.abort());
    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);
    await cdp.send('Emulation.setCPUThrottlingRate', { rate });
    await page.addInitScript(probe);
    await page.goto(base + route, { waitUntil: 'load', timeout: 120000 });
    await page.waitForTimeout(2500);
    const lcp = await page.evaluate(() => window.__lcp);
    console.log(`\n=== ${route} ${width}x${height} CPU x${rate}`);
    console.log('load  ', JSON.stringify({ lcp, ...(await take(page)) }));

    const total = await page.evaluate(() => document.documentElement.scrollHeight);
    await take(page);
    let maxAnimations = 0;
    for (let y = 0; y < total - height; y += 400) {
      await page.mouse.wheel(0, 400);
      await page.waitForTimeout(90);
      maxAnimations = Math.max(maxAnimations, await page.evaluate(() => document.getAnimations().filter((a) => a.playState === 'running').length));
    }
    await page.waitForTimeout(400);
    console.log('scroll', JSON.stringify({ pageHeight: total, maxRunningAnimations: maxAnimations, ...(await take(page)) }));
    await page.evaluate(() => scrollTo(0, 0));
    await page.waitForTimeout(900);
    await take(page);

    const trigger = page.locator('button[aria-controls="ptsHeaderSearch"]').first();
    const before = await page.evaluate(() => ({ w: document.documentElement.clientWidth, sb: innerWidth - document.documentElement.clientWidth }));
    const t0 = Date.now();
    await trigger.click();
    await page.waitForSelector('#ptsHeaderSearchInput', { state: 'visible', timeout: 10000 });
    const visibleMs = Date.now() - t0;
    await page.waitForTimeout(1200);
    const after = await page.evaluate(() => ({ w: document.documentElement.clientWidth, sb: innerWidth - document.documentElement.clientWidth }));
    console.log('search', JSON.stringify({ openToVisibleMs: visibleMs, widthBefore: before.w, widthAfter: after.w, scrollbarBefore: before.sb, scrollbarAfter: after.sb, ...(await take(page)) }));
    await context.close();
  }
  await browser.close();
})();

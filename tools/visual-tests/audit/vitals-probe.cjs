#!/usr/bin/env node
// Phone on slow 4G (1.6 Mbps, 150 ms) and 4x CPU: LCP element and time, CLS during load and a scroll down,
// bytes transferred, plus samples of small text and small tap targets. Read only.
//
//   AUDIT_BASE=http://127.0.0.1:3110 node audit/vitals-probe.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const routes = (process.env.AUDIT_ROUTES || '/,/giai-phap/dien-mat-troi-ho-gia-dinh/,/san-pham/,/den-pha-nang-luong-mat-troi-200w-blue-carbon-bct-flrp20/,/du-an-dien-nlmt-ho-anh-quan/,/tin-tuc/').split(',');

(async () => {
  const browser = await chromium.launch();
  for (const path of routes) {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, locale: 'vi-VN' });
    await context.route(/gtranslate\.net/, (route) => route.abort());
    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6e6 / 8, uploadThroughput: 750e3 / 8 });
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
    let bytes = 0;
    cdp.on('Network.loadingFinished', (e) => { bytes += e.encodedDataLength; });
    await page.addInitScript(() => {
      window.__lcp = null; window.__cls = 0; window.__shifts = [];
      new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__lcp = { t: Math.round(e.startTime), el: e.element ? (e.element.tagName + '.' + String(e.element.className).slice(0, 50)) : '', url: (e.url || '').slice(-60), fp: e.element?.getAttribute?.('fetchpriority'), loading: e.element?.loading }; }).observe({ type: 'largest-contentful-paint', buffered: true });
      new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) { window.__cls += e.value; if (e.value > 0.01) window.__shifts.push({ v: +e.value.toFixed(3), at: Math.round(e.startTime), src: (e.sources || []).map((s) => s.node ? (s.node.tagName + '.' + String(s.node.className).slice(0, 40)) : '').slice(0, 2) }); } }).observe({ type: 'layout-shift', buffered: true });
      try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {}
    });
    const t0 = Date.now();
    await page.goto(base + path, { waitUntil: 'load', timeout: 120000 });
    const loadMs = Date.now() - t0;
    await page.waitForTimeout(1500);
    for (let i = 0; i < 6; i++) { await page.mouse.wheel(0, 500); await page.waitForTimeout(250); }
    await page.waitForTimeout(800);
    const r = await page.evaluate(() => {
      const visible = (el) => { const b = el.getBoundingClientRect(); return b.width > 0 && b.height > 0; };
      const small = [...document.querySelectorAll('p')].filter(visible).filter((p) => (p.textContent || '').trim().length > 60 && parseFloat(getComputedStyle(p).fontSize) < 15)
        .map((p) => getComputedStyle(p).fontSize + ' ' + (p.closest('[class]')?.className || '').toString().slice(0, 30) + ' «' + p.textContent.trim().slice(0, 30) + '»').slice(0, 4);
      scrollTo(0, 0);
      const taps = [...document.querySelectorAll('a,button')].filter(visible).filter((el) => { const b = el.getBoundingClientRect(); return b.top >= 0 && b.top < innerHeight * 3 && (b.height < 32 || b.width < 32); })
        .map((el) => ((el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 22)) + ' ' + Math.round(el.getBoundingClientRect().width) + 'x' + Math.round(el.getBoundingClientRect().height)).slice(0, 6);
      return { lcp: window.__lcp, cls: +window.__cls.toFixed(3), shifts: window.__shifts.slice(0, 3), small, taps };
    });
    console.log(JSON.stringify({ path, loadMs, kb: Math.round(bytes / 1024), ...r }));
    await context.close();
  }
  await browser.close();
})();

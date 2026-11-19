#!/usr/bin/env node
// Measures /uoc-tinh-dien-mat-troi/ on phones, tablet and desktop: page width vs viewport, card boxes,
// a right gutter of at least 8px, and any element sticking out of a card. The estimate answer is a fixed sample served by the browser
// (no request reaches Laravel), so the result card is measured on any site. Never sends the lead form.
// ESTIMATOR_FIXTURE=big uses large business figures to stress the widest numbers.
// Local Nuxt dev only shows the page when the estimator is switched on in the local API.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const shots = process.argv[2];
const small = { kwp: 3.1, panels: 5, panelWatt: 620, roofM2: 15, limitedByRoof: false, batteryKwh: 0, outputKwh: 400, bill: 2000000, saving: 987000, newBill: 1013000, savingPercent: 49, cost: { low: 28600000, high: 36500000 }, payback: { low: 2.4, high: 3.1 }, co2Tonnes: 3.2 };
const big = { kwp: 812.4, panels: 1310, panelWatt: 620, roofM2: 5895, limitedByRoof: true, batteryKwh: 1215, outputKwh: 104800, bill: 300000000, saving: 187650000, newBill: 112350000, savingPercent: 63, cost: { low: 9748800000, high: 12186000000 }, payback: { low: 4.3, high: 5.4 }, co2Tonnes: 842.6 };
const result = { type: 'household', typeLabel: 'Hộ gia đình', region: 'Tây Nguyên', habit: 'Dùng nhiều ban ngày', battery: false, usageKwh: 625, basis: 'Mẫu đo bố cục.', summary: 'Mẫu đo bố cục', ...(process.env.ESTIMATOR_FIXTURE === 'big' ? big : small) };
(async () => {
  const browser = await chromium.launch();
  let failed = 0;
  for (const width of [320, 360, 375, 390, 412, 430, 540, 768, 1024, 1440]) {
    const context = await browser.newContext({ viewport: { width, height: 900 }, isMobile: width < 800, hasTouch: width < 800, locale: 'vi-VN' });
    const page = await context.newPage();
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.route('**/api/v1/solar-estimate', (route) => route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, result }) }));
    await page.goto(`${base}/uoc-tinh-dien-mat-troi/`, { waitUntil: 'networkidle' });
    await page.locator('.est-hero').waitFor({ timeout: 15000 });
    const box = await page.evaluate(() => {
      const card = (s) => { const r = document.querySelector(s)?.getBoundingClientRect(); return r && { width: Math.round(r.width), right: Math.round(r.right) }; };
      const outside = [];
      for (const el of document.querySelectorAll('.est-card')) {
        const edge = el.getBoundingClientRect().right;
        for (const node of el.querySelectorAll('*:not(.sr-only)')) {
          const r = node.getBoundingClientRect();
          if (r.width && r.right > edge + 0.5) outside.push(`${node.tagName.toLowerCase()}.${[...node.classList].join('.')}`);
        }
      }
      return { scrollWidth: document.documentElement.scrollWidth, viewport: document.documentElement.clientWidth, form: card('.est-form'), result: card('.est-result'), outside: outside.slice(0, 5) };
    });
    const ok = box.scrollWidth <= box.viewport && box.form.right <= box.viewport - 8 && box.result.right <= box.viewport - 8 && !box.outside.length;
    if (!ok) failed++;
    console.log(ok ? 'OK  ' : 'FAIL', width, JSON.stringify(box));
    if (shots) await page.screenshot({ path: `${shots}/estimator-${width}.png`, fullPage: true });
    await context.close();
  }
  await browser.close();
  process.exitCode = failed ? 1 : 0;
})();

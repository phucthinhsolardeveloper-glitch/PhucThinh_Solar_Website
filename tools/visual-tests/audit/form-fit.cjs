#!/usr/bin/env node
// Opens each form on small phones and reports whether it fits without scrolling, and any leftover "không bắt buộc" text.
// Only opens and measures. It never fills in or submits a form.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const shots = process.argv[2];
(async () => {
  const browser = await chromium.launch();
  for (const [width, height] of [[360, 640], [412, 780], [412, 924], [1440, 900]]) {
    const context = await browser.newContext({ viewport: { width, height }, isMobile: width < 800, hasTouch: width < 800, locale: 'vi-VN' });
    const page = await context.newPage();
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    const measure = async (name, panel) => {
      const box = await panel.evaluate((el) => {
        const scroller = [el, ...el.querySelectorAll('*')].find((node) => node.scrollHeight > node.clientHeight + 1 && /auto|scroll/.test(getComputedStyle(node).overflowY));
        return { height: Math.round(el.getBoundingClientRect().height), needsScroll: scroller ? scroller.scrollHeight - scroller.clientHeight : 0, optionalText: /không bắt buộc/i.test(el.textContent) };
      });
      console.log(`${width}x${height}`, name, JSON.stringify(box));
      if (shots) await page.screenshot({ path: `${shots}/form-${name}-${width}x${height}.png` });
    };
    await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    await page.waitForTimeout(600);
    const quote = page.locator('article :is(a,button)', { hasText: 'Liên hệ báo giá' }).first();
    if (await quote.count()) {
      await quote.click();
      const dialog = page.locator('[role="dialog"]:has(form [name="fullname"])');
      await dialog.waitFor();
      await page.waitForTimeout(500);
      await measure('consultation', dialog);
    } else console.log(`${width}x${height}`, 'consultation', 'no quote-only product');
    // The dealer form opens from the product purchase panel.
    await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    const productHref = await page.locator('article a[href]').first().getAttribute('href');
    await page.goto(new URL(productHref, base).href, { waitUntil: 'load' });
    await page.waitForTimeout(1200);
    // No visible trigger on every dataset, so this sets the same shared state the button sets.
    const opened = await page.evaluate(() => {
      const nuxt = document.querySelector('#__nuxt')?.__vue_app__?.$nuxt;
      const state = nuxt?.payload?.state;
      const key = state && Object.keys(state).find((name) => /dealer/i.test(name) && /open/i.test(name));
      if (!key) return false;
      state[key] = true;
      return true;
    });
    if (opened) {
      const dealer = page.locator('[role="dialog"]:has(form [name="company"])');
      await dealer.waitFor();
      await page.waitForTimeout(500);
      await measure('dealer', dealer);
    } else console.log(`${width}x${height}`, 'dealer', 'no button');
    for (const route of ['/gio-hang/', '/lien-he/']) {
      await page.goto(base + route, { waitUntil: 'load' });
      const hits = await page.locator('main').evaluate((el) => (el.textContent.match(/không bắt buộc/gi) || []).length);
      console.log(`${width}x${height}`, route, 'optional text', hits);
    }
    await context.close();
  }
  await browser.close();
})();

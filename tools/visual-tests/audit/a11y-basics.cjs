#!/usr/bin/env node
// Accessibility basics on a phone, cold cache, for the buying and enquiry pages: names for controls, a visible
// focus while tabbing, text contrast on plain backgrounds, sideways overflow, and lab load figures.
// Nothing is submitted. The figures are from this machine and say nothing about real visitors (CWV).
//
//   AUDIT_BASE=http://127.0.0.1:3130 node audit/a11y-basics.cjs
//
// Contrast is skipped where a picture or gradient sits behind the text; those need a person's eye.
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';

(async () => {
  const browser = await chromium.launch();
  const catalog = await (await fetch(`${base}/_api/catalog?per_page=48`)).json();
  const priced = catalog.products.find((item) => item.pricing.mode === 'price');
  const pages = [['home', '/'], ['catalog', '/san-pham/'], ['category', '/danh-muc-san-pham/den-nlmt/'], ['product', priced.url],
    ['cart', '/gio-hang/', true], ['checkout', '/thanh-toan/', true], ['contact', '/lien-he/']];
  const report = {};

  for (const [name, path, needsCart] of pages) {
    const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
    await context.route(/^https?:\/\/([a-z0-9-]+\.)*(googletagmanager\.com|google-analytics\.com|gtranslate\.net|google\.com)\//, (route) => route.abort());
    await context.addInitScript(() => {
      try { localStorage.setItem('pts-consent', 'denied'); } catch { /* none */ }
      window.__vitals = { lcp: 0, cls: 0 };
      new PerformanceObserver((list) => { for (const e of list.getEntries()) window.__vitals.lcp = e.startTime; }).observe({ type: 'largest-contentful-paint', buffered: true });
      new PerformanceObserver((list) => { for (const e of list.getEntries()) if (!e.hadRecentInput) window.__vitals.cls += e.value; }).observe({ type: 'layout-shift', buffered: true });
    });
    const page = await context.newPage();
    if (needsCart) {
      await page.goto(`${base}${priced.url}`, { waitUntil: 'networkidle' });
      await page.getByRole('button', { name: 'Thêm vào giỏ' }).first().click();
      await page.waitForTimeout(1500);
    }
    const response = await page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1200);
    const load = await page.evaluate(() => ({ lcpMs: Math.round(window.__vitals.lcp), cls: Number(window.__vitals.cls.toFixed(3)),
      kb: Math.round(performance.getEntriesByType('resource').reduce((sum, r) => sum + (r.transferSize || 0), 0) / 1024) }));

    const dom = await page.evaluate(() => {
      const shown = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 1 && r.height > 1 && s.visibility !== 'hidden' && s.display !== 'none' && !el.closest('[aria-hidden="true"],[inert]'); };
      const describe = (el) => `${el.tagName.toLowerCase()}${el.name ? `[name=${el.name}]` : ''}.${String(el.className || '').split(' ')[0]}`.slice(0, 70);
      const label = (el) => (el.labels && [...el.labels].map((l) => l.textContent).join(' ').trim()) || el.getAttribute('aria-label')
        || (el.getAttribute('aria-labelledby') || '').split(' ').map((id) => document.getElementById(id)?.textContent || '').join('').trim() || el.title;
      const controls = [...document.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]), select, textarea')].filter(shown);
      const named = (el) => (el.textContent || '').trim() || el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || el.title || el.querySelector('img[alt]:not([alt=""])') || el.value;
      // Colours may be written in any CSS syntax; a canvas reads them back as RGBA.
      const canvas = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
      const rgba = (value) => { canvas.clearRect(0, 0, 1, 1); canvas.fillStyle = '#000'; canvas.fillStyle = value; canvas.fillRect(0, 0, 1, 1); const [r, g, b, a] = canvas.getImageData(0, 0, 1, 1).data; return [r, g, b, a / 255]; };
      const over = (top, under) => [0, 1, 2].map((i) => top[i] * top[3] + under[i] * (1 - top[3])).concat(1);
      const light = (c) => { const [r, g, b] = c.slice(0, 3).map((v) => { const x = v / 255; return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4; }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
      const low = [];
      let measured = 0;
      let skipped = 0;
      for (const el of document.querySelectorAll('body *')) {
        const text = [...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent.trim()).join(' ').trim();
        // Punctuation that only decorates (a breadcrumb ellipsis) is not text to read.
        if (!/[\p{L}\p{N}]/u.test(text) || !shown(el) || el.closest('[disabled],[aria-disabled="true"]')) continue;
        const layers = [];
        let node = el;
        let picture = false;
        while (node) {
          const s = getComputedStyle(node);
          if (s.backgroundImage !== 'none' || node.matches('picture, video') || (node !== el && node.querySelector(':scope > img, :scope > picture, :scope > video'))) { picture = true; break; }
          const bg = rgba(s.backgroundColor);
          if (bg[3] > 0) layers.push(bg);
          if (bg[3] >= 1) break;
          node = node.parentElement;
        }
        if (picture) { skipped++; continue; }
        const back = layers.reverse().reduce((under, layer) => over(layer, under), [255, 255, 255, 1]);
        const style = getComputedStyle(el);
        const front = over(rgba(style.color), back);
        const [a, b] = [light(front), light(back)].sort((x, y) => y - x);
        const ratio = (a + 0.05) / (b + 0.05);
        const size = parseFloat(style.fontSize);
        const need = size >= 24 || (size >= 18.66 && Number(style.fontWeight) >= 700) ? 3 : 4.5;
        measured++;
        if (ratio < need - 0.05) low.push({ text: text.slice(0, 40), ratio: Number(ratio.toFixed(2)), need, size: Math.round(size), el: describe(el) });
      }
      low.sort((x, y) => x.ratio - y.ratio);
      return {
        lang: document.documentElement.lang,
        h1: [...document.querySelectorAll('h1')].length,
        unnamedControls: controls.filter((el) => !label(el)).map((el) => `${describe(el)} placeholder=${el.placeholder || ''}`),
        unnamedActions: [...document.querySelectorAll('a[href], button')].filter((el) => shown(el) && !named(el)).map(describe),
        imagesWithoutAlt: [...document.querySelectorAll('img:not([alt])')].length,
        overflow: document.documentElement.scrollWidth - innerWidth,
        contrast: { measured, skippedOverPictures: skipped, below: low.length, worst: low.slice(0, 8) },
      };
    });

    // Tab through the first stops: each must be on screen and look different from the unfocused state.
    const focus = { stops: 0, invisible: [], unmarked: [] };
    await page.evaluate(() => { document.activeElement?.blur?.(); window.scrollTo(0, 0); });
    for (let i = 0; i < 30; i++) {
      await page.keyboard.press('Tab');
      // Focus styles ease in and out; read them once they have settled.
      await page.waitForTimeout(350);
      const stop = await page.evaluate(async () => {
        const el = document.activeElement;
        // Inside a frame the browser draws the focus in the framed page, which cannot be read from here.
        if (!el || el === document.body || el.tagName === 'IFRAME') return null;
        const look = (node) => { const s = getComputedStyle(node); return [s.outlineStyle === 'none' ? '' : `${s.outlineStyle} ${s.outlineWidth} ${s.outlineColor}`, s.boxShadow, s.borderColor, s.backgroundColor, s.color, s.textDecorationLine, s.transform, s.opacity].join('|'); };
        // The marker may sit on a wrapper (focus-within) as well as on the control.
        const chain = [el, el.parentElement, el.parentElement?.parentElement].filter(Boolean);
        const focused = chain.map(look);
        const rect = el.getBoundingClientRect();
        el.blur();
        await new Promise((resolve) => setTimeout(resolve, 350));
        const plain = chain.map(look);
        el.focus({ preventScroll: true, focusVisible: false });
        return { el: `${el.tagName.toLowerCase()} "${(el.getAttribute('aria-label') || el.textContent || el.name || '').trim().slice(0, 30)}"`,
          onScreen: rect.width > 0 && rect.height > 0 && rect.bottom > 0 && rect.top < innerHeight && rect.right > 0 && rect.left < innerWidth,
          marked: focused.some((value, index) => value !== plain[index]) };
      });
      if (!stop) continue;
      focus.stops++;
      if (!stop.onScreen) focus.invisible.push(stop.el);
      else if (!stop.marked) focus.unmarked.push(stop.el);
    }
    report[name] = { status: response.status(), load, ...dom, focus };
    await context.close();
  }

  console.log(JSON.stringify(report, null, 2));
  const summary = Object.fromEntries(Object.entries(report).map(([name, r]) => [name, `controls ${r.unnamedControls.length}, actions ${r.unnamedActions.length}, noalt ${r.imagesWithoutAlt}, overflow ${r.overflow}, contrast ${r.contrast.below}/${r.contrast.measured}, tab ${r.focus.stops} offscreen ${r.focus.invisible.length} unmarked ${r.focus.unmarked.length}, lcp ${r.load.lcpMs}ms cls ${r.load.cls} ${r.load.kb}KB`]));
  console.log(JSON.stringify(summary, null, 2));
  await browser.close();
  // Names, alt text and overflow are pass or fail; contrast and focus findings are read by a person.
  process.exit(Object.values(report).every((r) => r.status === 200 && r.unnamedControls.length === 0 && r.unnamedActions.length === 0 && r.imagesWithoutAlt === 0 && r.overflow <= 0) ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });

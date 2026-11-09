#!/usr/bin/env node
// Measures the points of a generic UX/SEO review (topbar links, headings, images, forms, animations,
// maps, schema, canonical, Open Graph, tap targets, text size) on real pages. Read only: never submits.
//
//   AUDIT_BASE=http://127.0.0.1:3110 node audit/ux-seo-checklist.cjs
//   AUDIT_BASE=https://phucthinhsolar.com node audit/ux-seo-checklist.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const routes = (process.env.AUDIT_ROUTES || [
  '/', '/gioi-thieu/', '/giai-phap/dien-mat-troi-ho-gia-dinh/', '/san-pham/', '/danh-muc-san-pham/den-nlmt/',
  '/danh-muc-san-pham/den-nlmt/?page=2', '/du-an-da-thuc-hien/', '/du-an-dien-nlmt-ho-anh-quan/', '/tin-tuc/', '/lien-he/', '/dai-ly/',
].join(',')).split(',');
const product = process.env.AUDIT_PRODUCT || '/den-pha-nang-luong-mat-troi-200w-blue-carbon-bct-flrp20/';

function scan() {
  const r = {};
  const visible = (el) => { const b = el.getBoundingClientRect(); const s = getComputedStyle(el); return b.width > 0 && b.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
  r.overflow = document.documentElement.scrollWidth > innerWidth + 1;
  const phoneText = [...document.querySelectorAll('body *')].filter((el) => el.children.length === 0 && /(\d[\s.]?){9,}/.test(el.textContent || '') && /^(0|\+84)/.test((el.textContent || '').trim().replace(/\s/g, '')) && visible(el));
  r.phonesUnlinked = phoneText.filter((el) => !el.closest('a[href^="tel:"]')).map((el) => el.textContent.trim().slice(0, 20));
  r.tel = document.querySelectorAll('a[href^="tel:"]').length;
  r.mailto = document.querySelectorAll('a[href^="mailto:"]').length;
  const hs = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].filter(visible).map((h) => +h.tagName[1]);
  r.h1 = [...document.querySelectorAll('h1')].filter(visible).length;
  r.headingSkips = hs.filter((l, i) => i > 0 && l > hs[i - 1] + 1).length;
  const imgs = [...document.images].filter(visible);
  r.imgsNoSize = imgs.filter((i) => !(i.getAttribute('width') && i.getAttribute('height')) && getComputedStyle(i).aspectRatio === 'auto' && !i.closest('[style*="aspect"],[class*="aspect"]')).map((i) => (i.getAttribute('src') || '').slice(0, 60)).slice(0, 4);
  r.imgsNoSizeCount = imgs.filter((i) => !(i.getAttribute('width') && i.getAttribute('height')) && getComputedStyle(i).aspectRatio === 'auto' && !i.closest('[style*="aspect"],[class*="aspect"]')).length;
  const formats = {};
  for (const i of imgs) { const u = (i.currentSrc || i.src).split('?')[0]; const e = (u.match(/\.(\w+)$/) || [, 'other'])[1].toLowerCase(); formats[e] = (formats[e] || 0) + 1; }
  r.formats = formats;
  r.srcset = imgs.filter((i) => i.srcset || i.closest('picture')).length + '/' + imgs.length;
  const first = imgs.find((i) => i.getBoundingClientRect().top < innerHeight);
  r.heroImg = first ? { loading: first.loading, fetchpriority: first.getAttribute('fetchpriority'), w: first.naturalWidth } : null;
  r.belowFoldEager = imgs.filter((i) => i.getBoundingClientRect().top > innerHeight * 1.5 && i.loading !== 'lazy').length;
  r.infiniteAnimations = document.getAnimations().filter((a) => a.effect?.getTiming().iterations === Infinity && a.playState === 'running').map((a) => (a.animationName || a.constructor.name) + '@' + (a.effect.target?.className?.toString?.() || '').slice(0, 40)).slice(0, 5);
  r.iframesEager = [...document.querySelectorAll('iframe')].filter((f) => f.loading !== 'lazy').map((f) => (f.src || '').slice(0, 60));
  r.inputs = [...document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=search])')].filter(visible).map((i) => ({ n: i.name || i.id || i.placeholder, t: i.type, im: i.inputMode, ac: i.autocomplete }))
    .filter((i) => /phone|sdt|tel|đt|điện/i.test(i.n + '') ? !(i.t === 'tel' && i.ac) : /mail/i.test(i.n + '') ? i.t !== 'email' : /name|tên/i.test(i.n + '') ? !i.ac : false);
  r.schema = [...document.querySelectorAll('script[type="application/ld+json"]')].flatMap((s) => { try { const j = JSON.parse(s.textContent); return (Array.isArray(j) ? j : j['@graph'] || [j]).map((x) => [].concat(x['@type']).join('+')); } catch { return ['INVALID']; } });
  r.canonical = document.querySelector('link[rel=canonical]')?.getAttribute('href') || null;
  r.robots = document.querySelector('meta[name=robots]')?.content || null;
  r.ogImage = document.querySelector('meta[property="og:image"]')?.content || null;
  r.ogSize = [document.querySelector('meta[property="og:image:width"]')?.content, document.querySelector('meta[property="og:image:height"]')?.content].join('x');
  r.description = (document.querySelector('meta[name=description]')?.content || '').length;
  const taps = [...document.querySelectorAll('a,button,[role=button],input,select,summary')].filter(visible).filter((el) => { const b = el.getBoundingClientRect(); return b.top >= 0 && (b.height < 32 || b.width < 32) && !el.closest('p,li p,.prose,article'); });
  r.smallTaps = taps.length;
  r.smallTapSample = taps.slice(0, 4).map((el) => (el.textContent || el.getAttribute('aria-label') || el.tagName).trim().slice(0, 24) + ' ' + Math.round(el.getBoundingClientRect().width) + 'x' + Math.round(el.getBoundingClientRect().height));
  const paras = [...document.querySelectorAll('p')].filter(visible).filter((p) => (p.textContent || '').trim().length > 60);
  r.smallBody = paras.filter((p) => parseFloat(getComputedStyle(p).fontSize) < 15).length + '/' + paras.length;
  r.lineHeight = [...new Set(paras.slice(0, 40).map((p) => (parseFloat(getComputedStyle(p).lineHeight) / parseFloat(getComputedStyle(p).fontSize)).toFixed(2)))].slice(0, 5);
  r.fonts = [...new Set([...document.querySelectorAll('body *')].slice(0, 3000).map((el) => getComputedStyle(el).fontFamily.split(',')[0].trim()))];
  r.tables = [...document.querySelectorAll('table')].filter(visible).map((t) => t.getBoundingClientRect().width > innerWidth ? (getComputedStyle(t.parentElement).overflowX === 'auto' || getComputedStyle(t.parentElement).overflowX === 'scroll' ? 'scrolls' : 'OVERFLOWS') : 'fits');
  r.thirdParty = [...new Set(performance.getEntriesByType('resource').map((e) => new URL(e.name).host).filter((h) => h && h !== location.host))];
  r.domNodes = document.getElementsByTagName('*').length;
  return r;
}

(async () => {
  const browser = await chromium.launch();
  for (const [label, viewport, mobile] of [['phone', { width: 390, height: 844 }, true], ['desktop', { width: 1440, height: 900 }, false]]) {
    const context = await browser.newContext({ viewport, isMobile: mobile, hasTouch: mobile, locale: 'vi-VN' });
    await context.route(/gtranslate\.net/, (route) => route.abort());
    const page = await context.newPage();
    for (const path of [...routes, product]) {
      const response = await page.goto(base + path, { waitUntil: 'networkidle', timeout: 60000 }).catch((e) => ({ status: () => 'ERR ' + e.message.slice(0, 40) }));
      await page.waitForTimeout(800);
      const r = await page.evaluate(scan);
      console.log(JSON.stringify({ view: label, path, status: response.status(), ...r }));
    }
    // Drawer: does the page behind still scroll?
    if (mobile) {
      await page.goto(base + '/', { waitUntil: 'networkidle' });
      const opener = page.locator('button[aria-label*="enu" i], button[aria-label*="danh mục" i]').first();
      if (await opener.count()) {
        await opener.click();
        await page.waitForTimeout(500);
        const lock = await page.evaluate(() => ({ body: getComputedStyle(document.body).overflow, html: getComputedStyle(document.documentElement).overflow, before: scrollY, close: [...document.querySelectorAll('[role=dialog] button, aside button')].filter((b) => /đóng|close/i.test(b.getAttribute('aria-label') || b.textContent || '')).map((b) => Math.round(b.getBoundingClientRect().width) + 'x' + Math.round(b.getBoundingClientRect().height)) }));
        await page.mouse.wheel(0, 600);
        await page.waitForTimeout(300);
        lock.after = await page.evaluate(() => scrollY);
        console.log(JSON.stringify({ view: 'phone', check: 'drawer', ...lock }));
      } else console.log(JSON.stringify({ view: 'phone', check: 'drawer', error: 'menu button not found' }));
    }
    await context.close();
  }
  await browser.close();
})();

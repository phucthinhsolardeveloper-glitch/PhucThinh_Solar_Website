#!/usr/bin/env node
// Measures phone navigation reachability and fixed-layer collisions on public routes. Read only.
// Run from tools/visual-tests with PLAYWRIGHT_BROWSERS_PATH=$PWD/.browsers.
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'https://phucthinhsolar.com';
const output = process.argv[2] || path.resolve(__dirname, '../../../output/mobile-ux');
const routes = [
  ['home', '/'],
  ['category', '/danh-muc-san-pham/den-nlmt/'],
  ['product', '/san-pham/den-pha-nang-luong-mat-troi-blue-carbon-bct-wr20-100w'],
  ['article', process.env.ARTICLE_ROUTE || '/tin-tuc/'],
  ['about', '/gioi-thieu/'],
  ['cart', '/gio-hang/'],
  ['checkout', '/thanh-toan/'],
  ['contact', '/lien-he/'],
];

(async () => {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch();
  const context = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, locale: 'vi-VN' });
  await context.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  const page = await context.newPage();
  page.setDefaultTimeout(20000);
  const results = [];

  for (const [name, route] of routes) {
    const errors = [];
    page.removeAllListeners('pageerror');
    page.on('pageerror', (error) => errors.push(error.message));
    let responseStatus = null;
    try {
      const response = await page.goto(new URL(route, base).href, { waitUntil: 'domcontentloaded', timeout: 45000 });
      responseStatus = response?.status() ?? null;
      await page.waitForTimeout(900);
      let actualRoute = route;
      if (name === 'article' && route === '/tin-tuc/') {
        const articleHref = await page.locator('main a[href], article a[href]').evaluateAll((links) => links.map((link) => link.getAttribute('href')).find((href) => href && /^\/(?!tin-tuc\/?$|category\/|tag\/|danh-muc-san-pham\/|san-pham\/|gio-hang\/|lien-he\/|gioi-thieu\/)[^?#]+/.test(href)) || null).catch(() => null);
        if (articleHref) {
          await page.goto(new URL(articleHref, base).href, { waitUntil: 'domcontentloaded', timeout: 45000 });
          await page.waitForTimeout(700);
          actualRoute = articleHref;
        }
      }

      const positions = [];
      const maxY = await page.evaluate(() => Math.max(0, document.documentElement.scrollHeight - innerHeight));
      for (const [state, y] of [['middle', Math.round(maxY * 0.52)], ['end', maxY]]) {
        await page.evaluate((scrollY) => window.scrollTo(0, scrollY), y);
        await page.waitForTimeout(280);
        const sample = await page.evaluate((stateName) => {
          const rect = (el) => { const r = el.getBoundingClientRect(); return { left: r.left, top: r.top, right: r.right, bottom: r.bottom, width: r.width, height: r.height }; };
          const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none'; };
          const inViewport = (el) => { if (!visible(el)) return false; const r = el.getBoundingClientRect(); return r.bottom > 0 && r.top < innerHeight && r.right > 0 && r.left < innerWidth; };
          const menu = [...document.querySelectorAll('button')].find((el) => /mở danh mục điều hướng/i.test(el.getAttribute('aria-label') || ''));
          const search = [...document.querySelectorAll('button')].find((el) => /tìm kiếm/i.test(el.getAttribute('aria-label') || el.textContent || ''));
          const cart = document.querySelector('[data-cart-target],a[href*="gio-hang"]');
          const top = [...document.querySelectorAll('button,a')].find((el) => /lên đầu trang/i.test(el.getAttribute('aria-label') || el.getAttribute('title') || ''));
          const filter = [...document.querySelectorAll('button,a')].find((el) => /lọc|bộ lọc|sắp xếp/i.test(el.getAttribute('aria-label') || el.textContent || ''));
          const pick = (el) => el ? { visible: inViewport(el), rect: rect(el), label: (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 36), font: getComputedStyle(el).fontSize } : null;
          const layerSelectors = ['.dock', '.buy-island', '.buy-bar', '.contact-above-checkout', '.cookie-notice', '[class*="cookie"]', '.cmp-tray', '.cart-card', '[role="dialog"]'];
          const layers = layerSelectors.flatMap((selector) => [...document.querySelectorAll(selector)].filter(visible).map((el) => ({ selector, rect: rect(el) })));
          const collisions = [];
          for (let i = 0; i < layers.length; i++) for (let j = i + 1; j < layers.length; j++) {
            const a = layers[i], b = layers[j];
            if (a.selector !== b.selector && a.rect.width && b.rect.width && a.rect.height && b.rect.height && a.rect.left < b.rect.right && a.rect.right > b.rect.left && a.rect.top < b.rect.bottom && a.rect.bottom > b.rect.top) collisions.push([a.selector, b.selector]);
          }
          const main = document.querySelector('main') || document.body;
          const smallText = [...main.querySelectorAll('p,li,label')].filter(visible).filter((el) => (el.textContent || '').trim().length > 55 && parseFloat(getComputedStyle(el).fontSize) < 14).slice(0, 5).map((el) => ({ px: getComputedStyle(el).fontSize, text: el.textContent.trim().slice(0, 44) }));
          const smallTargets = [...main.querySelectorAll('a,button,input,select,textarea')].filter(visible).filter((el) => { const r = el.getBoundingClientRect(); return r.width < 44 || r.height < 44; }).slice(0, 8).map((el) => ({ label: (el.getAttribute('aria-label') || el.textContent || '').trim().slice(0, 30), size: `${Math.round(el.getBoundingClientRect().width)}x${Math.round(el.getBoundingClientRect().height)}` }));
          return { state: stateName, scrollY: Math.round(scrollY), menu: pick(menu), search: pick(search), cart: pick(cart), backToTop: pick(top), filter: pick(filter), fixedLayerCollisions: collisions, smallText, smallTargets };
        }, state);
        sample.scrollY = await page.evaluate(() => Math.round(scrollY));
        positions.push(sample);
        await page.screenshot({ path: path.join(output, `${name}-${state}.png`) });
      }
      results.push({ name, route: actualRoute, status: responseStatus, documentHeight: maxY + 844, positions, pageErrors: errors });
    } catch (error) {
      results.push({ name, route, status: responseStatus, error: error.message, pageErrors: errors });
    }
  }

  fs.writeFileSync(path.join(output, 'measurements.json'), JSON.stringify({ base, viewport: '390x844', measuredAt: new Date().toISOString(), results }, null, 2));
  console.log(JSON.stringify(results, null, 2));
  await browser.close();
})().catch((error) => { console.error(error); process.exitCode = 1; });

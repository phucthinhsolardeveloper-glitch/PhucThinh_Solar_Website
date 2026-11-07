#!/usr/bin/env node
// Clicks every chip of each sticky section rail with a real pointer, from every starting chip to the next one and the
// one after, and reports whether the page actually arrived at that section. Sequential clicks are the case owners hit.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const RAILS = { '/gioi-thieu/': 'nav', '/giai-phap/': 'nav' };
(async () => {
  const browser = await chromium.launch();
  for (const [label, options] of [['desktop', { viewport: { width: 1440, height: 900 } }], ['phone', { viewport: { width: 412, height: 860 }, isMobile: true, hasTouch: true }]]) {
    const context = await browser.newContext({ ...options, locale: 'vi-VN' });
    const page = await context.newPage();
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    await page.goto(`${base}/san-pham/`, { waitUntil: 'load' });
    const hrefs = await page.locator('article a[href]').evaluateAll((links) => [...new Set(links.map((a) => a.getAttribute('href')))].slice(0, 1));
    const routes = [...Object.keys(RAILS), ...hrefs, ...(process.argv.slice(2))];
    for (const route of routes) {
      await page.goto(new URL(route, base).href, { waitUntil: 'load' });
      await page.waitForTimeout(1200);
      const railHandle = await page.evaluateHandle(() => {
        const chips = [...document.querySelectorAll('a[href^="#"], button[data-section], nav a[href*="#"], nav button')].filter((el) => el.offsetWidth);
        const counts = new Map();
        chips.forEach((chip) => { const rail = chip.parentElement?.closest('nav, ul, ol, div'); if (rail) counts.set(rail, (counts.get(rail) || 0) + 1); });
        let best = null; let top = 2;
        counts.forEach((count, rail) => { const pos = getComputedStyle(rail.closest('.sticky') || rail).position; if (count > top && !rail.closest('header')) { best = rail; top = count; } });
        return best;
      });
      const rail = railHandle.asElement();
      if (!rail) { console.log(label, route, 'no rail'); continue; }
      const count = await rail.evaluate((el) => [...el.querySelectorAll('a,button')].filter((b) => b.offsetWidth).length);
      const names = await rail.evaluate((el) => [...el.querySelectorAll('a,button')].filter((b) => b.offsetWidth).map((b) => b.textContent.trim()));
      const failures = [];
      const clickAndCheck = async (index) => {
        const chip = (await rail.$$('a,button')).filter(Boolean)[index];
        const info = await chip.evaluate((el) => {
          el.scrollIntoView({ behavior: 'instant', inline: 'center', block: el.getBoundingClientRect().top > innerHeight * 0.5 ? 'center' : 'nearest' });
          const box = el.getBoundingClientRect();
          const hit = document.elementFromPoint(box.x + box.width / 2, box.y + box.height / 2);
          return { x: box.x + box.width / 2, y: box.y + box.height / 2, hit: el === hit || el.contains(hit), hitName: hit ? `${hit.tagName}.${String(hit.className).slice(0, 40)}` : '', href: el.getAttribute('href') };
        });
        const before = await page.evaluate(() => scrollY);
        await page.mouse.click(info.x, info.y);
        await page.waitForTimeout(1100);
        const after = await page.evaluate((href) => {
          const id = href ? href.split('#')[1] : '';
          const target = id ? document.getElementById(decodeURIComponent(id)) : null;
          return { y: scrollY, top: target ? Math.round(target.getBoundingClientRect().top) : null, targetHeight: target ? target.offsetHeight : 0, maxed: scrollY + innerHeight >= document.documentElement.scrollHeight - 2 };
        }, info.href);
        const current = await rail.evaluate((el) => [...el.querySelectorAll('[aria-current]')].map((b) => b.textContent.trim()).join('|'));
        const arrived = after.top !== null && Math.abs(after.top) < 260;
        if (!info.hit || (!arrived && !after.maxed) || current !== names[index]) failures.push(`${names[index]} from ${Math.round(before)} to ${Math.round(after.y)} top ${after.top} covered ${!info.hit ? info.hitName : 'no'} lit ${current}`);
      };
      for (let index = 0; index < count; index += 1) await clickAndCheck(index);
      for (let index = count - 1; index >= 0; index -= 1) await clickAndCheck(index);
      for (let index = 0; index < count - 2; index += 2) { await clickAndCheck(index); await clickAndCheck(index + 2); }
      console.log(label, route.slice(0, 44), `${count} chips`, failures.length ? `FAIL ${failures.length}` : 'ok');
      failures.slice(0, 8).forEach((line) => console.log('   ', line));
    }
    await context.close();
  }
  await browser.close();
})();

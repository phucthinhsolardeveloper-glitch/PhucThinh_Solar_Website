#!/usr/bin/env node
// Scrolls through the About history, reports the period lit at each step and whether content overflows the pinned
// stage. Also checks the search toolbar sticks on phones. Screenshots go to the folder given as the first argument.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const out = process.argv[2] || 'audit/out';
(async () => {
  const browser = await chromium.launch();
  for (const [width, height] of [[1440, 900], [1920, 1080], [1366, 768]]) {
    const page = await browser.newPage({ viewport: { width, height } });
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await page.goto(`${base}/gioi-thieu/`, { waitUntil: 'load' });
    await page.waitForTimeout(800);
    const info = await page.evaluate(() => { const s = document.getElementById('lich-su-phat-trien'); return { top: s.getBoundingClientRect().top + scrollY, height: s.offsetHeight, pinned: s.classList.contains('is-pinned') }; });
    console.log(width, height, JSON.stringify(info));
    for (let step = 0; step < 5; step += 1) {
      const y = info.top - 60 + (info.height - (height - 60)) * (step + 0.5) / 5;
      await page.evaluate((top) => scrollTo({ top, behavior: 'instant' }), y);
      await page.waitForTimeout(900);
      const row = await page.evaluate(() => {
        const stage = document.querySelector('.about-history__stage');
        const main = document.querySelector('.about-history__main');
        return { open: document.querySelector('.about-history__stop.is-open .about-history__year')?.textContent, stageH: stage.clientHeight, mainH: main.offsetHeight, side: document.querySelector('.about-history__side').offsetHeight, fill: document.querySelector('.about-history__line').style.getPropertyValue('--fill') };
      });
      console.log(' ', step, JSON.stringify(row));
      if (width === 1440) await page.screenshot({ path: `${out}/hist-scroll-${step}.png` });
    }
    await page.close();
  }
  const context = await browser.newContext({ viewport: { width: 412, height: 860 }, isMobile: true, hasTouch: true });
  const phone = await context.newPage();
  await phone.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await phone.goto(`${base}/tim-kiem/?keyword=pin`, { waitUntil: 'load' });
  await phone.waitForTimeout(800);
  await phone.evaluate(() => scrollTo({ top: 1500, behavior: 'instant' }));
  await phone.waitForTimeout(500);
  console.log('search tools top after scroll', await phone.locator('.m-sticky-tools').evaluate((el) => Math.round(el.getBoundingClientRect().top)));
  await phone.screenshot({ path: `${out}/search-sticky.png` });
  await browser.close();
})();

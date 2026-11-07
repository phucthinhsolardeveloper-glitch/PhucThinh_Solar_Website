#!/usr/bin/env node
// Does the home hero keep advancing by itself? Tests idle, after tapping a bar, after a swipe, after the page is
// scrolled away and back, and with reduced motion. Prints the slide number sampled over time.
const { chromium } = require('playwright');
const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const activeSlide = (page) => page.evaluate(() => [...document.querySelectorAll('.hero-steps button')].findIndex((button) => button.hasAttribute('aria-current')));
async function sample(page, seconds) {
  const seen = [];
  for (let second = 0; second < seconds; second += 1) { seen.push(await activeSlide(page)); await page.waitForTimeout(1000); }
  return seen.join('');
}
(async () => {
  const browser = await chromium.launch();
  const open = async (options = {}) => {
    const context = await browser.newContext({ viewport: { width: 412, height: 924 }, isMobile: true, hasTouch: true, locale: 'vi-VN', ...options });
    await context.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    await context.route('**/pts-living-energy/index.html*', (route) => route.abort());
    await context.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
    const page = await context.newPage();
    await page.goto(`${base}/`, { waitUntil: 'load' });
    await page.waitForTimeout(800);
    return page;
  };
  let page = await open();
  console.log('idle            ', await sample(page, 14));
  page = await open();
  await page.locator('.hero-steps button').nth(1).tap();
  console.log('after bar tap   ', await sample(page, 14));
  page = await open();
  const client = await page.context().newCDPSession(page);
  const touch = (type, x) => client.send('Input.dispatchTouchEvent', { type, touchPoints: type === 'touchEnd' ? [] : [{ x, y: 400 }] });
  await touch('touchStart', 320); await touch('touchMove', 200); await touch('touchMove', 100); await touch('touchEnd', 100);
  console.log('after swipe     ', await sample(page, 14));
  page = await open();
  await page.locator('.hero-copy a').last().tap().catch(() => {});
  await page.waitForTimeout(800);
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  console.log('after button tap', await sample(page, 14));
  page = await open();
  await page.locator('.hero-copy-layer').dispatchEvent('touchstart', { touches: [{ identifier: 1, clientX: 200, clientY: 400 }] }).catch(() => {});
  await page.evaluate(() => {
    const hero = document.querySelector('.hero-copy-layer');
    const touch = new Touch({ identifier: 1, target: hero, clientX: 200, clientY: 400 });
    hero.dispatchEvent(new TouchEvent('touchstart', { bubbles: true, touches: [touch], changedTouches: [touch] }));
    hero.dispatchEvent(new TouchEvent('touchcancel', { bubbles: true, touches: [], changedTouches: [touch] }));
  });
  console.log('after touchcancel', await sample(page, 14));
  page = await open({ reducedMotion: 'reduce' });
  console.log('reduced motion  ', await sample(page, 10));
  const desktop = await open({ viewport: { width: 1440, height: 900 }, isMobile: false, hasTouch: false });
  await desktop.mouse.move(700, 400);
  console.log('mouse on hero   ', await desktop.locator('.hero-nav b').innerText().catch(() => '?'));
  await browser.close();
})();

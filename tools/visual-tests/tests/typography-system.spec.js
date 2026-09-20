const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    try {
      const original = window.sessionStorage.getItem.bind(window.sessionStorage);
      window.sessionStorage.getItem = key => key === 'pts-popup-campaign' ? 'current' : original(key);
    } catch (error) {}
  });
});

const fontSize = locator => locator.evaluate(element => Number.parseFloat(getComputedStyle(element).fontSize));

test('public typography scale is readable and responsive', async ({ page, isMobile }, testInfo) => {
  const viewport = isMobile ? { width: 390, height: 844 } : { width: 1440, height: 950 };
  await page.setViewportSize(viewport);

  let response = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });

  expect(await fontSize(page.locator('body'))).toBe(isMobile ? 17 : 18);
  expect(await fontSize(page.locator('.pts-ecosystem-name').first())).toBeGreaterThanOrEqual(isMobile ? 14.5 : 15.5);
  expect(await fontSize(page.locator('.product-card .caption .name').first())).toBeGreaterThanOrEqual(isMobile ? 15.5 : 17);
  expect(await fontSize(page.locator('.footer-link a').first())).toBeGreaterThanOrEqual(isMobile ? 14.5 : 15);
  const logoWidth = await page.locator('.pts-header-v3 .logo-header img').evaluate(image => image.getBoundingClientRect().width);
  expect(logoWidth).toBeGreaterThanOrEqual(isMobile ? 155 : 210);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  await page.screenshot({ path: testInfo.outputPath(`typography-home-${viewport.width}.png`), fullPage: false, animations: 'disabled' });

  response = await page.goto('/tin-tuc/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  await expect(page.locator('.nh-heading h1')).toBeVisible();
  await expect(page.locator('.nh-card h2').first()).toBeVisible();
  expect(await fontSize(page.locator('.nh-heading h1'))).toBeGreaterThanOrEqual(32);
  expect(await fontSize(page.locator('.nh-card h2').first())).toBeGreaterThanOrEqual(18);
  expect(await fontSize(page.locator('.nh-card p').first())).toBeGreaterThanOrEqual(15.5);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  await page.screenshot({ path: testInfo.outputPath(`typography-news-${viewport.width}.png`), fullPage: false, animations: 'disabled' });
});

test('wide 2K desktop uses the large-screen reading scale', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Dự án mobile không cần lặp lại viewport 2K.');
  await page.setViewportSize({ width: 2560, height: 1440 });
  const response = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();

  expect(await fontSize(page.locator('body'))).toBeGreaterThanOrEqual(20);
  expect(await fontSize(page.locator('.pts-header-nav .menu-catalog > a'))).toBeGreaterThanOrEqual(17);
  expect(await fontSize(page.locator('.pts-ecosystem-name').first())).toBeGreaterThanOrEqual(17.5);
  expect(await fontSize(page.locator('.product-card .caption .name').first())).toBeGreaterThanOrEqual(19);
  expect(await fontSize(page.locator('.product-card .price-product .price-new').first())).toBeGreaterThanOrEqual(21);
  expect(await fontSize(page.locator('.footer-link a').first())).toBeGreaterThanOrEqual(17);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

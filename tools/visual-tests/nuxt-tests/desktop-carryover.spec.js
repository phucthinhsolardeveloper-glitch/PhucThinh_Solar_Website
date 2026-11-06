const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop only.');
  await page.route('**/pts-living-energy/index.html*', (route) => route.abort());
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
});

test('buy island appears only after the main buttons are passed, even on a long jump', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const href = await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href');
  await page.goto(href, { waitUntil: 'load' });
  await page.waitForTimeout(800);
  const island = page.locator('.buy-island');
  await expect(island).toBeHidden();
  await page.evaluate(() => scrollTo(0, 2000));
  await expect(island).toBeVisible();
  await page.evaluate(() => scrollTo(0, 0));
  await expect(island).toBeHidden();
});

test('catalog filters stay beside the results', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.evaluate(() => scrollTo(0, 1500));
  await page.waitForTimeout(500);
  const box = await page.locator('.catalog-sidebar-sticky').boundingBox();
  expect(box.y).toBeGreaterThanOrEqual(0);
  expect(box.y).toBeLessThanOrEqual(100);
});

test('brand logos sit on a white plate', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const plate = await page.locator('.masthead-brands a').first().evaluate((e) => getComputedStyle(e).backgroundColor);
  expect(plate).toMatch(/255, 255, 255/);
});

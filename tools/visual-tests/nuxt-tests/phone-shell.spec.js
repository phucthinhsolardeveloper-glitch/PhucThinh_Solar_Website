const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Phone shell only.');
  await page.route('**/pts-living-energy/index.html*', (route) => route.abort());
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
});

test('menu opens as a square-edged navy sheet with the close button on the right', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('.pts-mobile-nav-trigger').first().click();
  const panel = page.locator('#ptsMobileMenu');
  await expect(panel).toBeVisible();
  const box = await panel.boundingBox();
  expect(await panel.evaluate((e) => getComputedStyle(e).borderTopLeftRadius)).toBe('0px');
  const close = await panel.getByRole('button', { name: 'Đóng danh mục điều hướng' }).boundingBox();
  expect(close.x).toBeGreaterThan(box.x + box.width / 2);
  await panel.getByRole('button', { name: 'Đóng danh mục điều hướng' }).click();
  await expect(panel).toBeHidden();
});

test('filter drawer shows the count and returns to results', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('button', { hasText: 'Bộ lọc' }).first().click();
  const drawer = page.locator('#catalog-filter-drawer');
  await expect(drawer).toBeVisible();
  await drawer.getByRole('button', { name: 'Xem sản phẩm' }).click();
  await expect(drawer).toBeHidden();

  await page.goto('/san-pham/?brand=57', { waitUntil: 'load' });
  await expect(page.locator('.filter-count').first()).toHaveText('1');
});

test('filter and sort stick while the list scrolls', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.evaluate(() => scrollTo(0, 1500));
  await page.waitForTimeout(500);
  const box = await page.locator('.m-sticky-tools').boundingBox();
  expect(box.y).toBeLessThanOrEqual(16);
  expect(box.y).toBeGreaterThanOrEqual(0);
});

test('product page uses the buying island and a short form', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const href = await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href');
  await page.goto(href, { waitUntil: 'load' });
  await expect(page.locator('.buy-island')).toBeVisible();
  await expect(page.locator('.dock')).toHaveCount(0);
  const form = page.locator('#pts-pdp-consultation form');
  await expect(form.locator('[name="phone"]')).toBeVisible();
  await expect(form.locator('[name="email"]')).toBeHidden();
  await form.getByRole('button', { name: /Thêm chi tiết/ }).click();
  await expect(form.locator('[name="email"]')).toBeVisible();
});

test('dock, sidebar and island keep one tint when transparency is off', async ({ page, context }) => {
  const cdp = await context.newCDPSession(page);
  await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-transparency', value: 'reduce' }] });
  const paint = (e) => getComputedStyle(e).backgroundImage;
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const dock = await page.locator('.dock').evaluate(paint);
  await page.locator('.pts-mobile-nav-trigger').first().click();
  const sidebar = await page.locator('#ptsMobileMenu').evaluate(paint);
  expect(sidebar).toBe(dock);
  expect(dock).not.toContain('0.');
});

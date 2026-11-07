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

test('catalog filters follow the page without an inner scrollbar or touching the banner', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 800 });
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const column = page.locator('.catalog-sidebar-sticky');
  const scrollTo = async (y) => { await page.evaluate((target) => window.scrollTo(0, target), y); await page.waitForTimeout(300); };
  expect(await column.evaluate((e) => e.scrollHeight <= e.clientHeight)).toBe(true);

  await scrollTo(1400);
  let box = await column.boundingBox();
  expect(box.y + box.height).toBeLessThanOrEqual(800);
  expect(box.y + box.height).toBeGreaterThanOrEqual(760);

  await page.evaluate(() => window.scrollBy(0, -5000));
  await scrollTo(1800);
  await scrollTo(1000);
  box = await column.boundingBox();
  expect(box.y).toBeGreaterThanOrEqual(0);

  const banner = page.locator('.cta-band--catalog');
  await banner.scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);
  box = await column.boundingBox();
  const bannerBox = await banner.boundingBox();
  expect(box.y + box.height).toBeLessThanOrEqual(bannerBox.y);
});

test('product photo zooms under the mouse and resets when the mouse leaves', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.goto(await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href'), { waitUntil: 'load' });
  const photo = page.locator('.pdp-zoom-image').first();
  await page.locator('.pdp-studio-stage').hover();
  await expect.poll(() => photo.evaluate((e) => e.style.transform)).toBe('scale(2.2)');
  await page.mouse.move(2, 2);
  await expect.poll(() => photo.evaluate((e) => e.style.transform)).toBe('scale(1)');
});

test('a loading 3D viewer never takes the wheel from the page', async ({ page }) => {
  await page.goto('/', { waitUntil: 'load' });
  await page.locator('.energy-scene-frame').scrollIntoViewIfNeeded();
  const frame = page.locator('.energy-scene-frame iframe');
  await expect(frame).toHaveCount(1, { timeout: 8000 });
  expect(await frame.evaluate((e) => getComputedStyle(e).pointerEvents)).toBe('none');
  await expect(page.locator('.energy-scene-frame picture img')).toBeVisible();
});

test('filter pill glides to the chosen chip and lands on it', async ({ page }) => {
  await page.goto('/du-an-da-thuc-hien/', { waitUntil: 'load' });
  const pill = page.locator('.rail-pill');
  await expect(pill).toHaveCount(1);
  const chips = page.locator('nav .public-filter-chip');
  const before = await pill.boundingBox();
  await chips.nth(1).click();
  const during = await pill.evaluate((e) => e.getAnimations().length);
  expect(during).toBeGreaterThan(0);
  await page.waitForURL(/danh-muc-du-an/);
  await page.waitForTimeout(800);
  const after = await pill.boundingBox();
  const chip = await page.locator('nav .public-filter-chip[aria-current="page"]').boundingBox();
  expect(Math.abs(after.x - chip.x)).toBeLessThan(2);
  expect(Math.abs(after.width - chip.width)).toBeLessThan(2);
  expect(after.x).toBeGreaterThan(before.x);
});

test('product category rail glides in gold and lands on the chosen chip', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'load' });
  const rail = page.locator('.catalog-chip-rail');
  const pill = rail.locator('.rail-pill');
  await expect(pill).toHaveCount(1);
  await rail.locator('.catalog-category-chip').nth(2).click();
  expect(await pill.evaluate((e) => e.getAnimations().length)).toBeGreaterThan(0);
  await page.waitForTimeout(1200);
  const box = await rail.locator('.rail-pill').boundingBox();
  const chip = await rail.locator('.catalog-category-chip[aria-current="page"]').boundingBox();
  expect(Math.abs(box.x - chip.x)).toBeLessThan(3);
  expect(Math.abs(box.width - chip.width)).toBeLessThan(3);
});

test('viewers sit on light glass and product photos keep their own shape', async ({ page }) => {
  await page.goto('/gioi-thieu/', { waitUntil: 'load' });
  const certificate = page.locator('#about-certificates button:has(img), #about-certificates a:has(img)').first();
  await certificate.scrollIntoViewIfNeeded();
  await certificate.click();
  const dialog = page.locator('.about-certificate-dialog[open]');
  await expect(dialog).toHaveCount(1);
  const backdrop = await dialog.evaluate((element) => getComputedStyle(element, '::backdrop').backgroundColor);
  expect(backdrop).toMatch(/240, 245, 252/);
  await page.getByRole('button', { name: 'Đóng xem chứng nhận' }).click();

  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.goto(await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href'), { waitUntil: 'load' });
  await page.locator('.pdp-studio-stage').click();
  const photo = page.locator('.photo-viewer img').first();
  await expect(photo).toBeVisible();
  expect(await photo.evaluate((element) => getComputedStyle(element).backgroundColor)).toBe('rgba(0, 0, 0, 0)');
});

test('menu flyouts stay inside the panel on a short screen', async ({ page }) => {
  await page.setViewportSize({ width: 1600, height: 640 });
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('header li').filter({ hasText: /Danh\s+mục\s+sản\s+phẩm/ }).first().hover();
  const items = page.locator('#ptsCatalogMenu li:has(> .catalog-flyout)');
  await expect(items.first()).toBeVisible();
  for (let index = 0, count = await items.count(); index < count; index += 1) {
    if (!(await items.nth(index).isVisible())) continue;
    await items.nth(index).hover({ timeout: 3000 }).catch(() => {});
    await page.waitForTimeout(260);
    const overflow = await page.evaluate(() => {
      const flyout = document.querySelector('.is-flyout-open > .catalog-flyout');
      const menu = document.getElementById('ptsCatalogMenu');
      return flyout && menu ? flyout.getBoundingClientRect().bottom - Math.min(menu.getBoundingClientRect().bottom, innerHeight) : 0;
    });
    expect(overflow).toBeLessThanOrEqual(1);
  }
});

test('brand logos sit on a white plate', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const plate = await page.locator('.masthead-brands a').first().evaluate((e) => getComputedStyle(e).backgroundColor);
  expect(plate).toMatch(/255, 255, 255/);
});

const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('Project hub matches the new gallery layout and keeps real routes', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const response = await page.goto('/du-an-da-thuc-hien/', { waitUntil: 'domcontentloaded' });
  expect(response.status()).toBe(200);
  const hub = page.locator('.pts-project-hub');
  await expect(hub).toBeVisible();
  await expect(hub.locator('h1')).toHaveCount(1);
  await expect(hub.locator('.pts-ph-filter')).toHaveCount(3); // All + two existing Admin groups.
  await expect(hub.locator('.pts-ph-featured')).toHaveCount(1);
  await expect(hub.locator('.pts-ph-card')).toHaveCount(3);
  await expect(hub.locator('.date_content, .author_post')).toHaveCount(0);
  const hrefs = await hub.locator('.pts-ph-featured-cta, .pts-ph-card').evaluateAll(nodes => nodes.map(node => node.getAttribute('href')));
  expect(hrefs.every(href => href && !href.includes('store-manager'))).toBe(true);
  for(const href of hrefs) expect((await page.request.get(href)).status(), href).toBe(200);
  for(const image of await hub.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate(el => el.complete && el.naturalWidth > 0)).toBe(true);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  if(testInfo.project.name.includes('mobile')) await expect(hub.locator('.pts-ph-card').first()).toHaveCSS('display', 'block');
  await hub.locator('.pts-ph-consult').click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
  expect(errors).toEqual([]);
  await page.goto(hrefs[0], { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#inf_single_project')).toBeVisible();
  await expect(page.locator('.pts-ph-contact .pts-ph-consult')).toBeVisible();
});

test('Project category routes share filters, cards and empty state', async ({ page }) => {
  await page.goto('/danh-muc-du-an/du-an-ca-nhan/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pts-project-hub .pts-ph-card')).toHaveCount(4);
  await expect(page.locator('.pts-ph-filter.is-active')).toContainText('Cá nhân');
  await page.goto('/danh-muc-du-an/du-an-doanh-nghiep/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pts-project-hub .pts-ph-card')).toHaveCount(0);
  await expect(page.locator('.pts-ph-empty')).toBeVisible();
  await expect(page.locator('.pts-ph-filter.is-active')).toContainText('Doanh nghiệp');
});

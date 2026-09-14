const { test, expect } = require('@playwright/test');

test('Solution hub supports choices, equipment and consultation without layout errors', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
  const hub = page.locator('.pts-solution-hub');
  await expect(hub).toBeVisible();
  await expect(hub.locator('h1')).toHaveCount(1);
  await expect(hub.locator('.pts-sh-choice')).toHaveCount(2); // Existing local content; no fake solutions.
  await expect(hub.locator('.pts-sh-equipment-link')).toHaveCount(4);
  await expect(hub.locator('.date_content, .author_post')).toHaveCount(0);
  const links = await hub.locator('.pts-sh-choice, .pts-sh-equipment-link').evaluateAll(nodes => nodes.map(node => node.href));
  for(const link of links) {
    const response = await page.request.get(link);
    expect(response.status(), link).toBe(200);
  }
  for(const image of await hub.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate(el => el.complete && el.naturalWidth > 0)).toBe(true);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  if(testInfo.project.name.includes('mobile')) {
    const order = await hub.evaluate(el => ({copy:el.querySelector('.pts-sh-intro-copy').getBoundingClientRect().top,image:el.querySelector('.pts-sh-intro-image').getBoundingClientRect().top}));
    expect(order.copy).toBeLessThan(order.image);
  }
  await expect(hub.locator('.pts-sh-hotline')).toHaveAttribute('href', 'tel:0944555957');
  await hub.locator('.pts-sh-consult').click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
  expect(errors).toEqual([]);
});

test('Existing business and household solution routes share the upgraded design', async ({ page }) => {
  for(const route of ['/doanh-nghiep', '/danh-muc-giai-phap/ho-gia-dinh/']) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
    expect(response.status(), route).toBe(200);
    const category = page.locator('.pts-solution-category');
    await expect(category).toBeVisible();
    await expect(category.locator('h1')).toHaveCount(1);
    await expect(category.locator('.pts-sh-category-nav a')).toHaveCount(2);
    await expect(category.locator('.pts-sh-category-card')).toHaveCount(1);
    await expect(category.locator('.date_content, .author_post')).toHaveCount(0);
    await expect(category.locator('.pts-sh-category-card')).toHaveAttribute('href', /solution\//);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  }
});

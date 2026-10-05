const { test, expect } = require('@playwright/test');

test('first visible hero image is not natively lazy loaded', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const firstImage = page.locator('[data-home-hero-slide="0"] img').first();
  await expect(firstImage).toBeAttached();
  expect(await firstImage.getAttribute('loading')).not.toBe('lazy');
  expect(await firstImage.evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
});

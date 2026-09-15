const { test, expect } = require('@playwright/test');

test('header and product quote phone icons render', async ({ page }) => {
  const browserErrors = [];
  page.on('pageerror', error => browserErrors.push(error.message));

  await page.goto('/', { waitUntil: 'domcontentloaded' });
  // Utility bar đã tắt theo quyết định product-first; Header V3 dùng headset
  // làm icon tư vấn chính ở cả desktop và mobile.
  const headerIcon = page.locator('#headerscroll .hdr-call-ic .fa-headset').first();
  await expect(headerIcon).toBeVisible();
  await expect(headerIcon).toHaveCSS('font-family', /Font Awesome/);
  expect(await headerIcon.evaluate(element => getComputedStyle(element, '::before').content)).not.toBe('none');

  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const cardIcon = page.locator('.pts-card-ask .fa-phone-alt').first();
  await expect(cardIcon).toBeVisible();
  await expect(cardIcon).toHaveCSS('font-family', /Font Awesome/);
  expect(await cardIcon.evaluate(element => getComputedStyle(element, '::before').content)).not.toBe('none');
  expect(browserErrors).toEqual([]);
});

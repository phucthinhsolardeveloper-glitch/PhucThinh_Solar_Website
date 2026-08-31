const { test, expect } = require('@playwright/test');

test('header and product quote phone icons render', async ({ page }) => {
  const browserErrors = [];
  page.on('pageerror', error => browserErrors.push(error.message));

  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const headerIcon = page.locator('.top-utility-left .fa-phone-alt').first();
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

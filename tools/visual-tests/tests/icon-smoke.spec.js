const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('header and product quote phone icons render', async ({ page, isMobile }) => {
  const browserErrors = [];
  page.on('pageerror', error => browserErrors.push(error.message));

  await page.goto('/', { waitUntil: 'domcontentloaded' });
  // Header V4 dùng icon điện thoại cạnh hotline ở desktop và mobile.
  const headerIcon = page.locator('#headerscroll .hdr-call-ic .fa-phone-alt').first();
  await expect(headerIcon).toBeAttached();
  if (isMobile) await expect(page.locator('#headerscroll .hdr-call')).toBeHidden();
  else await expect(headerIcon).toBeVisible();
  await expect(headerIcon).toHaveCSS('font-family', /Font Awesome/);
  expect(await headerIcon.evaluate(element => getComputedStyle(element, '::before').content)).not.toBe('none');

  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const cardIcon = page.locator('.pts-card-ask .fa-phone-alt').first();
  await expect(cardIcon).toBeVisible();
  await expect(cardIcon).toHaveCSS('font-family', /Font Awesome/);
  expect(await cardIcon.evaluate(element => getComputedStyle(element, '::before').content)).not.toBe('none');
  expect(browserErrors).toEqual([]);
});

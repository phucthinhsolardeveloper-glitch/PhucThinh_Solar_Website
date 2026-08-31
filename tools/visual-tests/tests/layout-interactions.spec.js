const { test, expect } = require('@playwright/test');

test('header, menu, breadcrumb and footer remain usable', async ({ page, isMobile }) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));

  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#headerscroll')).toBeVisible();
  await expect(page.locator('.breadCrumbs')).toBeVisible();

  if (isMobile) {
    const hamburger = page.locator('#hamburger');
    await expect(hamburger).toBeVisible();
    await hamburger.click();
    await expect(page.locator('#menu')).toBeVisible();
    await page.keyboard.press('Escape');
  } else {
    await expect(page.locator('#headerscroll .menu').first()).toBeVisible();
  }

  await page.locator('footer').scrollIntoViewIfNeeded();
  await expect(page.locator('footer')).toBeVisible();
  await expect(page.locator('#footer_logo img')).toBeVisible();
  expect(pageErrors).toEqual([]);
});

test('product detail keeps gallery and quote-only CTA', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const productLink = page.locator('.grid-product a[href]').filter({ has: page.locator('img') }).first();
  await expect(productLink).toBeVisible();
  const href = await productLink.getAttribute('href');
  expect(href).toBeTruthy();

  const response = await page.goto(href, { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  await expect(page.locator('.breadCrumbs')).toBeVisible();
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  await expect(page.locator('.right-pro-detail > .title-pro-detail')).toBeVisible();
  await expect(page.locator('.btn-contact-price')).toBeVisible();
  await expect(page.locator('.btn-contact-price a[href^="tel:"]')).toBeVisible();
  await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
});

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
    const catalogMenu = page.locator('#headerscroll .menu-catalog');
    const catalogSubmenu = catalogMenu.locator(':scope > ul');
    await expect(catalogSubmenu).toBeAttached();
    await catalogMenu.hover();
    await expect(catalogSubmenu).toBeVisible();
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

test('quote request CTA opens the protected consultation form', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const href = await page.locator('.grid-product a[href]').filter({ has: page.locator('img') }).first().getAttribute('href');
  expect(href).toBeTruthy();

  await page.goto(href, { waitUntil: 'domcontentloaded' });
  const modal = page.locator('#exampleModal');
  await expect(modal).not.toBeVisible();
  await page.locator('.btn-contact-price [data-target="#exampleModal"]').click();
  await expect(modal).toBeVisible();
  await expect(modal.locator('#quoteRequestModalLabel')).toContainText('Tư vấn & báo giá');

  const form = modal.locator('form.validation-newsletter');
  await expect(form.locator('input[name="form_csrf"]')).toHaveCount(1);
  await expect(form.locator('[name="dataNewsletter[fullname]"]')).toBeVisible();
  await expect(form.locator('[name="dataNewsletter[phone]"]')).toBeVisible();
  await expect(form.locator('[name="submit-newsletter"]')).toBeVisible();

  await modal.locator('[data-dismiss="modal"]').click();
  await expect(modal).not.toBeVisible();
});

test('home exposes every featured category and switches its product grid', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const categories = page.locator('.pts-home-category-grid > li');
  expect(await categories.count()).toBeGreaterThanOrEqual(9);
  await expect(categories).toHaveCount(await categories.count());

  for (const category of await categories.all()) {
    await category.scrollIntoViewIfNeeded();
    await expect(category).toBeVisible();
    await expect(category.locator('.pts-home-category-image').locator('img, i')).toBeVisible();
  }

  const secondCategory = categories.nth(1);
  const listId = await secondCategory.locator('a').getAttribute('data-list');
  expect(listId).toBeTruthy();
  const productResponse = page.waitForResponse(response => response.url().includes(`api/product2.php?perpage=16&idList=${listId}`) && response.ok());
  await secondCategory.locator('a').click();
  await productResponse;
  await expect(secondCategory).toHaveClass(/uk-active/);
});

test('product category sidebar keeps child categories only in the type filter', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });

  await expect(page.locator('.pts-filter-card .filter-cat li > ul')).toHaveCount(0);
  await expect(page.locator('.pts-filter-card .filter-cat > .aside-content > ul > li').first()).toBeVisible();
  await expect(page.locator('input[name="car_cat"]')).not.toHaveCount(0);
  await expect(page.locator('input[name="car_cat"]').first()).toBeAttached();
});

test('mobile product filters open as an accessible drawer and close after filtering', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The filter drawer is a mobile/tablet interaction.');
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });

  const trigger = page.locator('#opensideBar-product');
  const drawer = page.locator('#product-filter-drawer');
  await expect(trigger).toBeVisible();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(drawer).not.toHaveClass(/show/);
  await expect(drawer).toHaveAttribute('aria-hidden', 'true');

  await trigger.click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(drawer).toHaveClass(/show/);
  await expect(drawer).toHaveAttribute('aria-hidden', 'false');
  await expect(page.locator('body')).toHaveClass(/pts-filter-drawer-open/);
  await expect(drawer.locator('.pts-filter-drawer-close')).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(trigger).toBeFocused();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(drawer).not.toHaveClass(/show/);

  await trigger.click();
  await expect(drawer).toHaveClass(/show/);
  const firstBrand = drawer.locator('input[name="car_brand"]').first();
  await expect(firstBrand).toBeAttached();
  const filteredResponse = page.waitForResponse(response => response.url().includes('/api/fillter_service.php?') && response.ok());
  await firstBrand.evaluate(input => {
    input.checked = true;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await filteredResponse;
  await expect(drawer).not.toHaveClass(/show/);
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(page.locator('body')).not.toHaveClass(/pts-filter-drawer-open/);
});

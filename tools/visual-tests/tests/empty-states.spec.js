const { test, expect } = require('@playwright/test');

// Keep the DOM in Vietnamese: the GTranslate widget rewrites nodes asynchronously and would race assertions.
test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

// Shared browser-health probe: page errors, failed local requests, broken images, horizontal overflow.
async function collectHealth(page, baseURL, action) {
  const pageErrors = [];
  const failedLocalResponses = [];
  const baseOrigin = new URL(baseURL).origin;
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('response', response => {
    const url = response.url();
    if (url.startsWith(baseOrigin) && response.status() >= 400) {
      failedLocalResponses.push(`${response.status()} ${url}`);
    }
  });

  await action();

  await page.evaluate(async () => {
    const step = Math.max(400, Math.floor(window.innerHeight * 0.8));
    for (let position = 0; position < document.body.scrollHeight; position += step) {
      window.scrollTo(0, position);
      await new Promise(resolve => setTimeout(resolve, 25));
    }
    window.scrollTo(0, 0);
  });

  const brokenImages = await page.locator('img').evaluateAll(images => images
    .filter(image => image.currentSrc && image.complete && image.naturalWidth === 0)
    .map(image => image.currentSrc));
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

  expect(pageErrors).toEqual([]);
  expect(failedLocalResponses).toEqual([]);
  expect(brokenImages).toEqual([]);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
}

test('empty cart page renders its empty state', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/gio-hang/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  const emptyCart = page.locator('.empty-cart');
  await expect(emptyCart).toBeVisible();
  await expect(emptyCart).toContainText('Chưa có sản phẩm');
  await expect(emptyCart.locator('i.fa-cart-plus')).toBeVisible();
  await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
  await expect(page.locator('footer')).toBeVisible();
});

test('entrance popup appears once per tab and resets in a new tab', async ({ page, context }) => {
  const firstResponse = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(firstResponse && firstResponse.ok()).toBeTruthy();
  await expect(page.locator('#popup')).toBeVisible();

  const campaignKey = await page.locator('#popup').getAttribute('data-campaign-key');
  expect(campaignKey).toMatch(/^[a-f0-9]{20}$/);
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('#popup')).not.toBeVisible();

  const newTab = await context.newPage();
  const newTabResponse = await newTab.goto('/', { waitUntil: 'domcontentloaded' });
  expect(newTabResponse && newTabResponse.ok()).toBeTruthy();
  await expect(newTab.locator('#popup')).toBeVisible();
  await newTab.close();
});

test('search with no match shows the not-found notice', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/tim-kiem/?keyword=zzzznothingfound999', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toContainText('Không tìm thấy kết quả');
  await expect(page.locator('.product-item')).toHaveCount(0);
  await expect(page.locator('.breadCrumbs')).toBeVisible();
});

test('empty product category keeps navigation, filters and consultation entry points', async ({ page, baseURL, isMobile }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/den-ac/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toContainText('Không tìm thấy kết quả');
  await expect(page.locator('.product-item')).toHaveCount(0);
  await expect(page.locator('.filter_product')).toBeVisible();
  await expect(page.locator('.breadCrumbs')).toBeVisible();
  if (!isMobile) {
    await expect(page.locator('.sidebar-consult-box')).toBeVisible();
    await expect(page.locator('.sidebar-consult-box .sc-btn')).toHaveAttribute('href', /lien-he\/?$/);
  }
});

test('search with matches lists product cards', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/tim-kiem/?keyword=ORIANA', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toHaveCount(0);
  expect(await page.locator('.product-item').count()).toBeGreaterThan(0);
  // Quote-only mode: cards must not expose add-to-cart controls.
  await expect(page.locator('.product-card .addcart')).toHaveCount(0);

  const filteredResponse = page.waitForResponse(response => response.url().includes('/api/fillter_service.php?') && response.ok());
  await page.locator('select[name="orderby"]').selectOption('new');
  await filteredResponse;
  const filteredUrl = new URL(page.url());
  expect(filteredUrl.pathname).toBe('/tim-kiem/');
  expect(filteredUrl.searchParams.get('keyword')).toBe('ORIANA');
  expect(await page.locator('.product-item').count()).toBeGreaterThan(0);
});

test('search preserves the promotional frame layered over the product image', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/tim-kiem/?keyword=JD-200NEW', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });

  const promotedCard = page.locator('.product-card', { hasText: 'JD-200NEW' }).first();
  await expect(promotedCard).toBeVisible();
  await expect(promotedCard.locator('.pts-product-photo')).toBeVisible();
  await expect(promotedCard.locator('.pts-promo-frame')).toBeVisible();

  const layers = await promotedCard.locator('.product-card-img').evaluate(container => {
    const base = container.querySelector('.pts-product-photo');
    const overlay = container.querySelector('.pts-promo-frame');
    return {
      sameParent: Boolean(base && overlay && base.parentElement === overlay.parentElement),
      baseZ: base ? getComputedStyle(base).zIndex : null,
      overlayZ: overlay ? getComputedStyle(overlay).zIndex : null,
    };
  });
  expect(layers.sameParent).toBeTruthy();
  expect(Number(layers.overlayZ)).toBeGreaterThan(Number(layers.baseZ));
});

test('contact page serves a CSRF-protected lead form wired to the PHP handler', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  // The live form carries WordPress-era class names but posts to sources/contact.php.
  const form = page.locator('form:has([name="dataContact[fullname]"])');
  await expect(form).toHaveCount(1);
  await expect(form.locator('input[name="form_csrf"]')).toHaveCount(1);
  await expect(form.locator('input[name="submit-contact"]')).toBeVisible();
  await expect(form.locator('[name="dataContact[email]"]')).toHaveAttribute('required', '');
  // Empty submit is blocked by native constraint validation — still on /lien-he/.
  await form.locator('input[name="submit-contact"]').click();
  expect(new URL(page.url()).pathname.replace(/\/$/, '')).toBe('/lien-he');
});

test('imageless imported product detail renders without broken media or bad requests', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/san-pham/den-pha-nang-luong-mat-troi-oriana-db60100-100w', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.right-pro-detail > .title-pro-detail')).toBeVisible();
  await expect(page.locator('.btn-contact-price a[href^="tel:"]')).toBeVisible();
  await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
  // No photo -> the MagicZoom trigger must fall back to the placeholder, never request the bare upload dir.
  await expect(page.locator('#Zoom-1')).not.toHaveAttribute('href', /upload\/product\/?$/);
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  // Review section shell is present in the DOM even with zero reviews (inside an inactive tab).
  await expect(page.locator('[name="dataReview[fullname]"]')).toBeAttached();
});

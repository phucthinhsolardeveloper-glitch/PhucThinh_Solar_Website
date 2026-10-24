const { test, expect } = require('@playwright/test');

test.use({
  launchOptions: {
    executablePath: process.env.PTS_CHROMIUM_PATH || '/home/admin01/.cache/ms-playwright/chromium_headless_shell-1247/chrome-headless-shell-linux64/chrome-headless-shell'
  }
});

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    try {
      const originalGetItem = window.sessionStorage.getItem.bind(window.sessionStorage);
      window.sessionStorage.getItem = key => {
        if (key === 'pts-popup-campaign') {
          const popup = document.querySelector('#popup');
          return popup ? (popup.getAttribute('data-campaign-key') || 'current') : 'current';
        }
        return originalGetItem(key);
      };
    } catch (error) {}
  });
});

async function dismissPopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    const close = popup.locator('[data-dismiss="modal"]').first();
    if (await close.isVisible()) await close.click();
  }
}

test('header search opens with focus and closes by keyboard at 390px', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissPopup(page);

  const header = page.locator('.pts-header-v4');
  const toggle = header.locator('.pts-header-search-toggle');
  const panel = header.locator('#ptsHeaderSearch');
  const input = header.locator('#keyword-res');
  await toggle.click();
  await expect(panel).toBeVisible();
  await expect(input).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(panel).toBeHidden();
  await expect(toggle).toBeFocused();
});

test('desktop catalogue has a real empty state, clears, and returns focus', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Mega menu requires a fine-pointer desktop context.');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissPopup(page);

  const trigger = page.locator('.menu-catalog-trigger');
  const overlay = page.locator('#ptsCatalogOverlay');
  const search = overlay.locator('#ptsCatalogKeyword');
  await trigger.hover();
  await expect(overlay.getByRole('dialog')).toBeVisible();
  await search.fill('__no_catalog_match__');
  await expect(overlay.locator('#ptsCatalogEmpty')).toBeVisible();
  await expect(overlay.locator('.pts-catalog-group:visible')).toHaveCount(0);
  await overlay.locator('#ptsCatalogKeywordClear').click();
  await expect(overlay.locator('#ptsCatalogEmpty')).toBeHidden();
  await expect(overlay.locator('.pts-catalog-list-item:visible').first()).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(overlay).toBeHidden();
  await expect(trigger).toBeFocused();
});

test('mobile product filter drawer restores focus, updates status, and becomes desktop sidebar after resize', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.route('**/api/fillter_service.php*', route => route.fulfill({
    status: 200,
    contentType: 'text/html',
    body: '<div class="grid-product grid-product-3 lv-grid"></div>'
  }));
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  await dismissPopup(page);

  const drawer = page.locator('#product-filter-drawer');
  const trigger = page.locator('#opensideBar-product');
  const close = drawer.locator('.pts-filter-drawer-close');
  await trigger.click();
  await expect(drawer).toHaveClass(/show/);
  await expect(close).toBeFocused();
  await page.locator('.mask-sidebar').click({ position: { x: 8, y: 120 } });
  await expect(drawer).not.toHaveClass(/show/);
  await expect(trigger).toBeFocused();

  await trigger.click();
  await page.keyboard.press('Escape');
  await expect(drawer).not.toHaveClass(/show/);
  await expect(trigger).toBeFocused();

  await trigger.click();
  await drawer.locator('input[name="car_brand"]').first().check();
  await expect(page.locator('[data-product-filter-status]')).toHaveText('Đã cập nhật danh sách sản phẩm');
  await expect(drawer).not.toHaveClass(/show/);
  await expect(trigger).toBeFocused();
  await expect(page.locator('#show-product')).not.toHaveAttribute('role', 'status');

  await page.setViewportSize({ width: 1440, height: 900 });
  await expect(drawer).toHaveAttribute('aria-hidden', 'false');
  await expect(drawer).not.toHaveAttribute('inert', '');
  await expect(drawer).not.toHaveClass(/show/);
});

test('homepage consultation form reports native invalid field without submitting', async ({ page }) => {
  let postRequests = 0;
  page.on('request', request => {
    if (request.method() === 'POST') postRequests += 1;
  });
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/#yeu-cau-tu-van', { waitUntil: 'domcontentloaded' });
  await dismissPopup(page);

  const form = page.locator('.pts-home-contact-form');
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('#ptsHomeFormStatus')).toBeVisible();
  await expect(page.locator('#ptsHomeFormStatus')).toContainText('Vui lòng điền Họ và tên');
  await expect(form.locator('#ptsHomeFullname')).toBeFocused();
  expect(postRequests).toBe(0);
});

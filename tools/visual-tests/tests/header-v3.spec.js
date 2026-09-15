const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function dismissEntrancePopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    await popup.locator('[data-dismiss="modal"]').click();
    await expect(popup).not.toBeVisible();
  }
}

test('header v3 keeps navigation and search usable', async ({ page, isMobile }, testInfo) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  if (!isMobile) await page.setViewportSize({ width: 1904, height: 950 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });

  const header = page.locator('#headerscroll.pts-header-v3');
  await expect(header).toBeVisible();
  await expect(header.locator('.logo-header img')).toBeVisible();
  expect(await header.locator('.logo-header img').evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
  await page.screenshot({ path: testInfo.outputPath('header-v3.png'), fullPage: false });

  if (isMobile) {
    await expect(header.locator('.hdr-search-zone')).toBeHidden();
    await expect(header.locator('.pts-header-nav')).toBeHidden();
    await header.locator('.hdr-mobile-search .icon-search').click();
    const mobileInput = header.locator('#keyword-res-mobile');
    await expect(mobileInput).toBeVisible();
    await mobileInput.fill('đèn đường');
    await Promise.all([
      page.waitForURL(/tim-kiem\?keyword=/),
      header.locator('.hdr-mobile-search .search-grid button').click()
    ]);
  } else {
    await expect(header.locator('.hdr-search-zone')).toBeVisible();
    await expect(header.locator('.pts-header-nav')).toBeVisible();
    await expect(header.locator('.hdr-quick-links a').first()).toBeVisible();
    const catalogX = await header.locator('.menu-catalog').evaluate(element => element.getBoundingClientRect().x);
    const homeX = await header.locator('.menu-home').evaluate(element => element.getBoundingClientRect().x);
    expect(catalogX).toBeLessThan(homeX);
    const input = header.locator('#keyword-res');
    await input.fill('tấm pin');
    await Promise.all([
      page.waitForURL(/tim-kiem\?keyword=/),
      header.locator('.hdr-search-submit').click()
    ]);
  }

  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
  expect(pageErrors).toEqual([]);
});

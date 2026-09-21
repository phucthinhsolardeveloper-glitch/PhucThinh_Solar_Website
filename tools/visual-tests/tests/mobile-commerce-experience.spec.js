const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('mobile product names expand instead of being clipped by the legacy two-line card', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Kiểm tra này dành cho lưới hai cột mobile.');
  await page.setViewportSize({ width: 390, height: 844 });
  // Trang chủ V4 không còn lưới sản phẩm; kiểm tra card trên danh mục đang bán.
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });

  const names = page.locator('.product-card .caption .name');
  expect(await names.count()).toBeGreaterThan(0);
  const metrics = await names.evaluateAll(nodes => nodes.slice(0, 12).map(node => {
    const style = getComputedStyle(node);
    return {
      height: node.getBoundingClientRect().height,
      scrollHeight: node.scrollHeight,
      clamp: style.webkitLineClamp,
      overflow: style.overflow,
    };
  }));
  for (const metric of metrics) {
    expect(metric.clamp).toBe('none');
    expect(metric.height).toBeGreaterThanOrEqual(metric.scrollHeight - 1);
    expect(metric.overflow).toBe('visible');
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('mobile language choices survive the Mmenu clone and set the translation cookie', async ({ page, context, isMobile }) => {
  test.skip(!isMobile, 'Bộ đổi ngôn ngữ này nằm trong drawer mobile.');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const languageSettings = await page.evaluate(() => window.gtranslateSettings);
  expect(languageSettings.default_language).toBe('vi');
  expect(languageSettings.detect_browser_language).toBe(true);
  await page.evaluate(() => document.querySelector('#hamburger').click());

  const languageBox = page.locator('.mm-menu:visible .mm-lang-links').first();
  await expect(languageBox).toBeVisible();
  await expect(languageBox.locator('[data-gtlang]')).toHaveCount(5);

  const navigation = page.waitForNavigation({ waitUntil: 'domcontentloaded' });
  await languageBox.locator('[data-gtlang="en"]').evaluate(link => link.click());
  await navigation;
  const cookie = (await context.cookies()).find(item => item.name === 'googtrans');
  expect(cookie && cookie.value).toBe('/vi/en');
});

test('mobile footer finishes compactly and floating actions yield to footer content', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Khoảng trống footer 152px chỉ từng áp dụng trên mobile.');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => scrollTo(0, document.documentElement.scrollHeight));
  await page.waitForTimeout(250);

  const footer = page.locator('.footer_bottom');
  expect(await footer.evaluate(node => node.getBoundingClientRect().height)).toBeLessThanOrEqual(100);
  await expect(page.locator('body')).toHaveClass(/pts-footer-in-view/);
  await expect(page.locator('#button-contact-vr')).toHaveCSS('opacity', '0');
  await expect(page.locator('.progress-wrap')).toHaveCSS('opacity', '0');
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('mobile product detail keeps price and purchase controls inside the buy box', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Kiểm tra buy box mobile.');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });

  await expect(page.locator('.right-pro-detail')).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
  const controls = page.locator('.btn-product-detail a');
  if (await controls.count()) {
    const box = await page.locator('.right-pro-detail').boundingBox();
    for (const control of await controls.all()) {
      const rect = await control.boundingBox();
      expect(rect.x).toBeGreaterThanOrEqual(box.x - 1);
      expect(rect.x + rect.width).toBeLessThanOrEqual(box.x + box.width + 1);
      expect(rect.height).toBeGreaterThanOrEqual(48);
    }
  }
  const thumbnails = page.locator('.gallery-thumb-pro img');
  if (await thumbnails.count()) {
    const src = await thumbnails.first().getAttribute('src');
    expect(src).toMatch(/600x600x[24]/);
  }
});

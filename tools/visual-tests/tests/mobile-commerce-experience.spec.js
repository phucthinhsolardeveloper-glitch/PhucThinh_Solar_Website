const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('mobile product names clamp to three lines with a fixed card height', async ({ page, isMobile }) => {
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
      clamp: style.webkitLineClamp,
      overflow: style.overflow,
    };
  }));
  /* 2026-09-22: đổi lại thành giới hạn 3 dòng + chiều cao cố định (4.44em) để
     ảnh, thông số, giá và CTA thẳng hàng giữa các card trên cùng một hàng lưới
     - đánh đổi có chủ đích, tên quá dài sẽ hiện dấu ... Test trước kiểm tra
     hành vi cũ (không giới hạn dòng); cập nhật theo thiết kế đã duyệt. */
  const heights = metrics.map(metric => metric.height);
  for (const metric of metrics) {
    expect(metric.clamp).toBe('3');
    expect(metric.overflow).toBe('hidden');
  }
  for (const height of heights) expect(Math.abs(height - heights[0])).toBeLessThanOrEqual(1);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('mobile hides the language switcher entirely (desktop keeps it)', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Chủ ý ẩn công tắc ngôn ngữ chỉ áp dụng cho mobile.');
  /* 2026-09-22: chủ project chủ ý ẩn hoàn toàn chức năng đổi ngôn ngữ trên
     mobile (cả header lẫn drawer mmenu), không phải bug. Test trước kiểm tra
     hành vi ngược lại (mong đợi thấy được và đổi được ngôn ngữ); cập nhật
     theo đúng chủ ý đã xác nhận. */
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.flex-search-lang>.gtranslate_wrapper')).toBeHidden();
  await page.evaluate(() => document.querySelector('#hamburger').click());
  await expect(page.locator('.mm-menu .mm-lang').first()).toBeHidden();
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

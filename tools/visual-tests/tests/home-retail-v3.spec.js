const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  /* Chặn popup chào mừng tất định - xem chú thích đầy đủ ở
     layout-interactions.spec.js. Popup bung lên sau khi apps.js chạy sẽ phủ
     backdrop lên rail danh mục, làm click vào ô danh mục treo tới timeout 30s. */
  await page.addInitScript(() => {
    try {
      const original = window.sessionStorage.getItem.bind(window.sessionStorage);
      window.sessionStorage.getItem = key => {
        if (key === 'pts-popup-campaign') {
          const popup = document.querySelector('#popup');
          return popup ? (popup.getAttribute('data-campaign-key') || 'current') : 'current';
        }
        return original(key);
      };
    } catch (error) {}
  });
});

async function dismissEntrancePopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    await popup.locator('[data-dismiss="modal"]').click();
    await expect(popup).not.toBeVisible();
  }
}

async function settleLazyImages(page) {
  await page.evaluate(async () => {
    const height = document.body.scrollHeight;
    for (let y = 0; y < height; y += Math.max(500, window.innerHeight * 0.8)) {
      window.scrollTo(0, y);
      await new Promise(resolve => setTimeout(resolve, 35));
    }
    window.scrollTo(0, 0);
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });
  await page.waitForTimeout(500);
}

test('homepage v3 is product-first and keeps its retail interactions working', async ({ page, isMobile }, testInfo) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  if (!isMobile) await page.setViewportSize({ width: 1440, height: 950 });

  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  await expect(page.locator('.top-utility-bar')).toBeHidden();
  await expect(page.locator('.wap-slider')).toHaveCount(0);
  await expect(page.locator('.pts-retail-campaign-card')).toHaveCount(3);
  await expect(page.locator('.pts-product-family')).toHaveCount(3);
  expect(await page.locator('.pts-product-family .product-card').count()).toBeGreaterThanOrEqual(24);

  const categoryRail = page.locator('.pts-ecosystem-grid');
  await expect(categoryRail).toBeVisible();
  const categoryLayout = await categoryRail.evaluate(element => ({
    display: getComputedStyle(element).display,
    wrap: getComputedStyle(element).flexWrap,
    scrolls: element.scrollWidth > element.clientWidth
  }));
  expect(categoryLayout.display).toBe('flex');
  expect(categoryLayout.wrap).toBe('nowrap');
  expect(categoryLayout.scrolls).toBe(isMobile);
  expect(await categoryRail.evaluate(element => getComputedStyle(element, '::-webkit-scrollbar').display)).toBe('none');
  await expect(page.locator('.pts-ecosystem .section-title > p')).toHaveCount(0);
  expect(await page.locator('.pts-ecosystem .section-title h3').evaluate(element => getComputedStyle(element, '::after').display)).toBe('none');

  const firstCategory = page.locator('.get-product-category').first();
  const firstCategorySurface = await firstCategory.evaluate(element => ({
    border: getComputedStyle(element).borderTopWidth,
    background: getComputedStyle(element).backgroundColor,
    shadow: getComputedStyle(element).boxShadow
  }));
  expect(firstCategorySurface).toEqual({ border: '0px', background: 'rgba(0, 0, 0, 0)', shadow: 'none' });
  await expect(page.locator('.paging-product-category .product-card').first()).toBeVisible();
  if (!isMobile) {
    const nextControl = page.locator('.pts-rail-next');
    await expect(nextControl).toBeHidden();
  }

  // Các nội dung hồ sơ doanh nghiệp đã được trả về đúng hub chuyên biệt.
  await expect(page.locator('.pts-about-teaser')).toHaveCount(0);
  await expect(page.locator('section.home-2')).toHaveCount(0);
  await expect(page.locator('section.home-7')).toHaveCount(0);
  await expect(page.locator('section.home-6')).toHaveCount(0);
  await expect(page.locator('.pts-customer-stories')).toHaveCount(0);

  await expect(page.locator('.pts-brands h3')).toHaveText('THƯƠNG HIỆU HỢP TÁC');
  await expect(page.locator('.home-8 .pts-news-card').first()).toBeVisible();

  // Danh mục vẫn tải sản phẩm qua API cũ sau khi đổi tab.
  const categoryTabs = page.locator('.get-product-category');
  expect(await categoryTabs.count()).toBeGreaterThan(1);
  const secondCategoryId = await categoryTabs.nth(1).getAttribute('data-list');
  const productResponse = page.waitForResponse(response => response.url().includes('api/product2.php') && response.url().includes(`idList=${secondCategoryId}`));
  await categoryTabs.nth(1).click();
  await productResponse;
  await expect(page.locator('.paging-product-category .product-card').first()).toBeVisible();

  await settleLazyImages(page);
  const firstFamilyImage = page.locator('.pts-product-family .pts-product-photo').first();
  await expect(firstFamilyImage).toBeVisible();
  expect(await firstFamilyImage.evaluate(image => image.naturalWidth)).toBeGreaterThan(0);

  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
  expect(pageErrors).toEqual([]);
  await page.screenshot({ path: testInfo.outputPath('home-retail-v3.png'), fullPage: true });
});

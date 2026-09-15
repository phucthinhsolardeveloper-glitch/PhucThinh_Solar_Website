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
  await expect(header.locator('.menu-home')).toHaveCount(0);
  await expect(page.locator('#menu a').filter({ hasText: 'Trang chủ' })).toHaveCount(0);
  await expect(page.locator('a[href="ve-chung-toi/"]').filter({ hasText: 'Giới thiệu' }).first()).toBeAttached();
  const expectedPrimaryNavigation = ['Danh mục sản phẩm', 'Giới thiệu', 'Giải pháp', 'Dự án', 'Kiến thức', 'Hợp tác'];
  const primaryNavigation = await header.locator('.pts-header-nav .menu > div > ul > li > a').evaluateAll(links => links.map(link => link.childNodes[link.childNodes.length - 1].textContent.trim()));
  expect(primaryNavigation).toEqual(expectedPrimaryNavigation);
  await expect(header.locator('.menu-knowledge > ul a[href="tin-tuc/"]').first()).toContainText('Tất cả bài viết');
  await expect(header.locator('.menu-cooperation > ul a[href="dai-ly#dang-ky-dai-ly"]')).toContainText('Đăng ký đại lý');
  await expect(header.locator('.menu-about > ul a[href="lien-he/#he-thong-chi-nhanh"]')).toContainText('Hệ thống chi nhánh');
  await expect(header.locator('.pts-header-nav .menu > div > ul > li.menu-dealer, .pts-header-nav .menu > div > ul > li.menu-contact')).toHaveCount(0);
  await expect(page.locator('#menu .menu-knowledge')).toBeAttached();
  await expect(page.locator('#menu .menu-cooperation')).toBeAttached();
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
    for (const menuClass of ['menu-about', 'menu-knowledge', 'menu-cooperation']) {
      const group = header.locator(`.${menuClass}`);
      await group.hover();
      await expect(group.locator(':scope > ul')).toBeVisible();
    }
    const catalogX = await header.locator('.menu-catalog').evaluate(element => element.getBoundingClientRect().x);
    const introX = await header.locator('.pts-header-nav a[href="ve-chung-toi/"]').evaluate(element => element.getBoundingClientRect().x);
    expect(catalogX).toBeLessThan(introX);
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

test('các nhóm menu gộp trỏ đến đúng landing và section hiện hữu', async ({ page }) => {
  for (const [path, selector] of [
    ['/ve-chung-toi/#ve-phuc-thinh', '#ve-phuc-thinh'],
    ['/ve-chung-toi/#lich-su-phat-trien', '#lich-su-phat-trien'],
    ['/ve-chung-toi/#tam-nhin-su-menh', '#tam-nhin-su-menh'],
    ['/ve-chung-toi/#about-certificates', '#about-certificates'],
    ['/lien-he/#he-thong-chi-nhanh', '#he-thong-chi-nhanh'],
    ['/lien-he/#yeu-cau-tu-van', '#yeu-cau-tu-van'],
    ['/dai-ly#dang-ky-dai-ly', '#dang-ky-dai-ly'],
    ['/dai-ly#nang-luc-phan-phoi', '#nang-luc-phan-phoi'],
    ['/dai-ly#ho-tro-dai-ly', '#ho-tro-dai-ly'],
  ]) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    // Chuyển giữa hai anchor cùng document không tạo HTTP response mới.
    if (response) expect(response.ok(), path).toBeTruthy();
    await expect(page.locator(selector), path).toHaveCount(1);
  }

  await page.goto('/tin-tuc/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pts-news-hub h1')).toHaveText('Kiến thức');
});

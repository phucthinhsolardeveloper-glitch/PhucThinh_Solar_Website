const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  /* Chặn popup chào mừng tất định - xem chú thích đầy đủ ở
     layout-interactions.spec.js. Popup bung lên sau khi apps.js chạy sẽ chắn
     tương tác và làm test timeout ngẫu nhiên. */
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

test('header v3 keeps navigation and search usable', async ({ page, isMobile }, testInfo) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  if (!isMobile) await page.setViewportSize({ width: 1904, height: 950 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
  });

  const header = page.locator('#headerscroll.pts-header-v3.pts-header-v4');
  await expect(header).toBeVisible();
  if (!isMobile) {
    const headerHeight = await header.evaluate(element => element.getBoundingClientRect().height);
    expect(headerHeight).toBeGreaterThanOrEqual(150);
  }
  await expect(header.locator('.logo-header img')).toBeVisible();
  expect(await header.locator('.logo-header img').evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
  const logoSurface = await header.locator('.logo-header').evaluate(logo => ({
    background: getComputedStyle(logo).backgroundColor,
    border: getComputedStyle(logo).borderTopWidth,
    shadow: getComputedStyle(logo).boxShadow,
  }));
  expect(logoSurface.background).toBe('rgba(0, 0, 0, 0)');
  expect(logoSurface.border).toBe('0px');
  expect(logoSurface.shadow).toBe('none');
  await expect(header.locator('.menu-home')).toHaveCount(0);
  await expect(page.locator('#menu a').filter({ hasText: 'Trang chủ' })).toHaveCount(0);
  await expect(page.locator('a[href="gioi-thieu/"]').filter({ hasText: 'Giới thiệu' }).first()).toBeAttached();
  const expectedPrimaryNavigation = ['Danh mục sản phẩm', 'Giới thiệu', 'Giải pháp', 'Dự án', 'Kiến thức', 'Hợp tác'];
  const primaryNavigation = await header.locator('.pts-header-nav .menu > div > ul > li > a, .pts-header-nav .menu > div > ul > li > button').evaluateAll(links => links.map(link => link.textContent.trim()));
  expect(primaryNavigation).toEqual(expectedPrimaryNavigation);
  await expect(header.locator('.menu-knowledge > ul a[href="/tin-tuc/"]').first()).toContainText('Tất cả bài viết');
  await expect(header.locator('.menu-cooperation > ul a[href="dai-ly#dang-ky-dai-ly"]')).toContainText('Đăng ký đại lý');
  await expect(header.locator('.menu-about > ul a[href="lien-he/#he-thong-chi-nhanh"]')).toContainText('Hệ thống chi nhánh');
  await expect(header.locator('.pts-header-nav .menu > div > ul > li.menu-dealer, .pts-header-nav .menu > div > ul > li.menu-contact')).toHaveCount(0);
  await expect(page.locator('#menu .menu-knowledge')).toBeAttached();
  await expect(page.locator('#menu .menu-cooperation')).toBeAttached();
  await page.screenshot({ path: testInfo.outputPath('header-v3.png'), fullPage: false });

  if (isMobile) {
    await expect(header.locator('.hdr-search-zone')).toBeHidden();
    await expect(header.locator('.pts-header-nav')).toBeHidden();
    await expect(header.locator('.pts-header-actions>.gtranslate_wrapper')).toBeHidden();
    await header.locator('.pts-header-search-toggle').click();
    const mobileInput = header.locator('#keyword-res');
    await expect(mobileInput).toBeVisible();
    await mobileInput.fill('đèn đường');
    await Promise.all([
      page.waitForURL(/tim-kiem\?keyword=/),
      header.locator('.hdr-search-submit').click()
    ]);
  } else {
    await expect(header.locator('.hdr-search-zone')).toBeHidden();
    await expect(header.locator('.pts-header-nav')).toBeVisible();
    await header.locator('.pts-header-search-toggle').click();
    await expect(header.locator('.hdr-search-zone')).toBeVisible();
    await expect(header.locator('.hdr-quick-links a').first()).toBeVisible();
    const aboutMenuWhileSearching = header.locator('.menu-about');
    await aboutMenuWhileSearching.hover();
    const aboutSubmenuWhileSearching = aboutMenuWhileSearching.locator(':scope > ul');
    const aboutLinksWhileSearching = aboutSubmenuWhileSearching.locator('a');
    await expect(aboutSubmenuWhileSearching).toBeVisible();
    const searchPanelBounds = await header.locator('.hdr-search-zone').boundingBox();
    const overlappedLinkIndex = await aboutLinksWhileSearching.evaluateAll((links, panel) => links.findIndex(link => {
      const bounds = link.getBoundingClientRect();
      return bounds.top < panel.y + panel.height && bounds.bottom > panel.y;
    }), searchPanelBounds);
    expect(overlappedLinkIndex).toBeGreaterThanOrEqual(0);
    await aboutLinksWhileSearching.nth(overlappedLinkIndex).hover();
    await expect(aboutSubmenuWhileSearching).toBeVisible();
    await expect(header.locator('.hdr-search-zone')).toBeVisible();
    await header.locator('.pts-header-search-toggle').click();
    await expect(header.locator('.hdr-search-zone')).toBeHidden();
    for (const menuClass of ['menu-about', 'menu-knowledge', 'menu-cooperation']) {
      const group = header.locator(`.${menuClass}`);
      await group.hover();
      const submenu = group.locator(':scope > ul');
      await expect(submenu).toBeVisible();
      if (menuClass === 'menu-about') {
        const firstLink = submenu.locator('a').first();
        const submenuMetrics = await submenu.evaluate(element => {
          const firstItem = element.querySelector('a');
          const itemStyle = getComputedStyle(firstItem);
          return {
            width: element.getBoundingClientRect().width,
            itemHeight: firstItem.getBoundingClientRect().height,
            fontSize: parseFloat(itemStyle.fontSize),
          };
        });
        expect(submenuMetrics.width).toBeGreaterThanOrEqual(300);
        expect(submenuMetrics.itemHeight).toBeGreaterThanOrEqual(48);
        expect(submenuMetrics.fontSize).toBeGreaterThanOrEqual(15);
        await page.screenshot({ path: testInfo.outputPath('header-v3-submenu.png'), fullPage: false });
        const target = await firstLink.boundingBox();
        expect(target).toBeTruthy();
        await page.mouse.move(target.x + target.width / 2, target.y + target.height / 2, { steps: 12 });
        await expect(submenu).toBeVisible();
        await expect(firstLink).toBeVisible();
      }
    }
    const catalogX = await header.locator('.menu-catalog').evaluate(element => element.getBoundingClientRect().x);
    const introX = await header.locator('.pts-header-nav a[href="gioi-thieu/"]').evaluate(element => element.getBoundingClientRect().x);
    expect(catalogX).toBeLessThan(introX);
    await header.locator('.pts-header-search-toggle').click();
    await expect(header.locator('.hdr-search-zone')).toBeVisible();
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

test('header scrolls away naturally with the page', async ({ page }) => {
  await page.setViewportSize({ width: 1920, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const header = page.locator('#headerscroll.pts-header-v4');
  await page.evaluate(() => {
    document.documentElement.style.setProperty('scroll-behavior', 'auto', 'important');
    document.body.style.setProperty('scroll-behavior', 'auto', 'important');
    window.scrollTo(0, 0);
  });
  await page.evaluate(() => window.scrollTo(0, 700));
  await expect.poll(() => header.evaluate(element => element.getBoundingClientRect().bottom)).toBeLessThanOrEqual(0);
  await expect(header).not.toHaveClass(/fixed|is-scroll-hidden/);
  expect(await header.evaluate(element => element.style.top)).toBe('');

  await page.evaluate(() => window.scrollTo(0, 400));
  await expect.poll(() => header.evaluate(element => element.getBoundingClientRect().bottom)).toBeLessThanOrEqual(0);

  await page.evaluate(() => window.scrollTo(0, 0));
  await expect.poll(() => header.evaluate(element => element.getBoundingClientRect().top)).toBeGreaterThanOrEqual(-1);
});

test('desktop logo never collides with the category navigation', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop header collision check.');
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const layouts = [];
  for (const width of [1321, 1366, 1440, 1600, 1904, 2200, 2300, 2560]) {
    await page.setViewportSize({ width, height: 900 });
    layouts.push(await page.evaluate(() => {
      const logo = document.querySelector('#headerscroll .logo-header img').getBoundingClientRect();
      const category = document.querySelector('#headerscroll .menu-catalog-trigger').getBoundingClientRect();
      const items = document.querySelectorAll('#headerscroll .pts-header-nav .menu > div > ul > li');
      const lastItem = items[items.length - 1].getBoundingClientRect();
      const actions = document.querySelector('#headerscroll .pts-header-actions').getBoundingClientRect();
      return {
        width: window.innerWidth,
        logoGap: category.left - logo.right,
        navigationGap: actions.left - lastItem.right,
        documentOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
      };
    }));
  }
  for (const layout of layouts) {
    expect(layout.logoGap, `${layout.width}px logo/category gap: ${JSON.stringify(layouts)}`).toBeGreaterThanOrEqual(8);
    expect(layout.navigationGap, `${layout.width}px navigation/actions gap: ${JSON.stringify(layouts)}`).toBeGreaterThanOrEqual(0);
    expect(layout.documentOverflow, `${layout.width}px horizontal overflow`).toBeLessThanOrEqual(1);
  }
});

test('các nhóm menu gộp trỏ đến đúng landing và section hiện hữu', async ({ page }) => {
  for (const [path, selector] of [
    ['/gioi-thieu/#ve-phuc-thinh', '#ve-phuc-thinh'],
    ['/gioi-thieu/#lich-su-phat-trien', '#lich-su-phat-trien'],
    ['/gioi-thieu/#tam-nhin-su-menh', '#tam-nhin-su-menh'],
    ['/gioi-thieu/#about-certificates', '#about-certificates'],
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

test('desktop product mega-menu follows the selected 4/4/2 layout and supports search', async ({ page, isMobile }, testInfo) => {
  test.skip(isMobile, 'Desktop product catalogue overlay.');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  const trigger = page.locator('.menu-catalog-trigger');
  const overlay = page.locator('#ptsCatalogOverlay');
  await expect(trigger).toHaveAttribute('href', '/san-pham/');
  await trigger.hover();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(overlay.getByRole('dialog')).toBeVisible();
  await expect(overlay.locator('.pts-catalog-group')).toHaveCount(3);
  for (const [index, count] of [[0, 4], [1, 4], [2, 2]]) {
    await expect(overlay.locator('.pts-catalog-group').nth(index).locator('.pts-catalog-list-item')).toHaveCount(count);
  }
  await expect(overlay.locator('.pts-catalog-sublist:visible')).toHaveCount(0);
  await expect(overlay.locator('.pts-catalog-help-card')).toBeVisible();
  await expect(overlay.locator('.pts-catalog-footer a[href="/san-pham/"]')).toBeVisible();
  await expect(overlay.locator('.pts-catalog-footer-advice')).toBeVisible();
  const categoryPaths = await overlay.locator('.pts-catalog-list-link').evaluateAll(links => links.map(link => link.getAttribute('href')));
  expect(new Set(categoryPaths).size).toBe(10);
  for (const path of categoryPaths) {
    const response = await page.request.get(new URL(path, page.url()).href);
    expect(response.ok(), path).toBeTruthy();
  }
  await overlay.locator('.pts-catalog-panel').screenshot({ path: testInfo.outputPath('desktop-catalog-1440.png'), scale: 'css' });
  await overlay.locator('a[href="/danh-muc-san-pham/den-san-vuon/"]').hover();
  await overlay.locator('.pts-catalog-panel').screenshot({ path: testInfo.outputPath('desktop-catalog-garden-hover-1440.png'), scale: 'css' });

  await overlay.locator('#ptsCatalogKeyword').fill('Hybrid');
  await expect(overlay.locator('.pts-catalog-list-item:visible')).toHaveCount(1);
  await expect(overlay.locator('.pts-catalog-group:visible')).toHaveCount(1);
  await page.keyboard.press('Escape');
  await expect(overlay).toBeHidden();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(trigger).toBeFocused();
});

test('desktop product mega-menu scales with wide viewports without horizontal overflow', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop product catalogue overlay.');
  for (const width of [1360, 1920]) {
    await page.setViewportSize({ width, height: 1000 });
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await dismissEntrancePopup(page);
    const trigger = page.locator('.menu-catalog-trigger');
    const overlay = page.locator('#ptsCatalogOverlay');
    await trigger.hover();
    const metrics = await overlay.locator('.pts-catalog-panel').evaluate(element => {
      const box = element.getBoundingClientRect();
      return { width: box.width, viewport: window.innerWidth, overflow: element.scrollWidth - element.clientWidth };
    });
    expect(Math.abs(metrics.width - Math.min(1200, width * 0.85))).toBeLessThanOrEqual(1);
    expect(metrics.overflow).toBeLessThanOrEqual(1);
  }
});

test('product catalogue trigger opens the landing page when activated', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop product catalogue landing link.');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await expect(page.locator('.menu-catalog-trigger')).toHaveAttribute('href', '/san-pham/');
  await page.locator('.menu-catalog-trigger').click();
  await expect(page).toHaveURL(/\/san-pham\/$/);
  await expect(page.locator('h1')).toBeVisible();
});

test('desktop catalogue reveals real child categories on hover and keeps leaf rows plain', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop category flyout.');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.locator('.menu-catalog-trigger').hover();

  const overlay = page.locator('#ptsCatalogOverlay');
  const garden = overlay.locator('.pts-catalog-list-item').filter({ has: page.locator('a[href="/danh-muc-san-pham/den-san-vuon/"]') });
  await garden.locator('.pts-catalog-list-link').hover();
  const children = garden.locator('.pts-catalog-sublist');
  await expect(children).toBeVisible();
  await expect(children.getByRole('link', { name: 'Đèn trụ cổng' })).toHaveAttribute('href', 'danh-muc-san-pham/den-san-vuon/den-tru-cong/');
  await children.getByRole('link', { name: 'Đèn trụ cổng' }).hover();
  await expect(children).toBeVisible();
  const panel = overlay.locator('.pts-catalog-panel');
  const geometry = await panel.evaluate(element => {
    const flyout = element.querySelector('.pts-catalog-list-item:hover > .pts-catalog-sublist').getBoundingClientRect();
    const bounds = element.getBoundingClientRect();
    return { left: flyout.left, right: flyout.right, top: flyout.top, bottom: flyout.bottom, panelLeft: bounds.left, panelRight: bounds.right, panelTop: bounds.top, panelBottom: bounds.bottom, overflow: element.scrollWidth - element.clientWidth };
  });
  expect(geometry.left).toBeGreaterThanOrEqual(geometry.panelLeft);
  expect(geometry.right).toBeLessThanOrEqual(geometry.panelRight);
  expect(geometry.top).toBeGreaterThanOrEqual(geometry.panelTop);
  expect(geometry.bottom).toBeLessThanOrEqual(geometry.panelBottom);
  expect(geometry.overflow).toBeLessThanOrEqual(1);

  await overlay.locator('#ptsCatalogKeyword').hover();
  await garden.locator('.pts-catalog-list-link').focus();
  await expect(children).toBeVisible();
  await page.keyboard.press('Tab');
  await expect(children.getByRole('link', { name: 'Đèn trụ cổng' })).toBeFocused();

  await overlay.locator('#ptsCatalogKeyword').focus();
  for (const row of await overlay.locator('.pts-catalog-list-item').all()) {
    await overlay.locator('#ptsCatalogTitle').hover();
    await row.locator('.pts-catalog-list-link').hover();
    const flyout = row.locator('.pts-catalog-sublist');
    if (await row.locator('.pts-catalog-row-arrow').count()) {
      await expect(flyout).toBeVisible();
      const position = await flyout.evaluate(element => {
        const flyoutBounds = element.getBoundingClientRect();
        const panelBounds = element.closest('.pts-catalog-panel').getBoundingClientRect();
        return { left: flyoutBounds.left, right: flyoutBounds.right, top: flyoutBounds.top, bottom: flyoutBounds.bottom, panelLeft: panelBounds.left, panelRight: panelBounds.right, panelTop: panelBounds.top, panelBottom: panelBounds.bottom };
      });
      expect(position.left).toBeGreaterThanOrEqual(position.panelLeft);
      expect(position.right).toBeLessThanOrEqual(position.panelRight);
      expect(position.top).toBeGreaterThanOrEqual(position.panelTop);
      expect(position.bottom).toBeLessThanOrEqual(position.panelBottom);
    } else {
      await expect(flyout).toHaveCount(0);
    }
  }

  const fan = overlay.locator('.pts-catalog-list-item').filter({ hasText: 'Quạt năng lượng mặt trời' });
  await expect(fan.locator('.pts-catalog-row-arrow')).toHaveCount(0);
  await expect(fan.locator('.pts-catalog-sublist')).toHaveCount(0);
});

test('desktop catalogue advice card opens the existing consultation form', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop catalogue overlay.');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  /* The catalogue trigger remains a real landing link. Hover opens the
     discovery overlay; activating the advice card is tested from that state. */
  await page.locator('.menu-catalog-trigger').hover();
  await expect(page.locator('#ptsCatalogOverlay')).toBeVisible();
  await page.locator('#ptsCatalogOverlay .pts-catalog-help-card').click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
  await expect(page.locator('#ptsContactModal form')).toBeVisible();
});

test('desktop product mega-menu opens on hover without flicker and remains keyboard accessible', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Desktop hover interaction.');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  const trigger = page.locator('.menu-catalog-trigger');
  const overlay = page.locator('#ptsCatalogOverlay');
  const panel = overlay.locator('.pts-catalog-panel');
  const search = overlay.locator('#ptsCatalogKeyword');

  const triggerXBefore = await trigger.evaluate(element => element.getBoundingClientRect().x);
  const bodyStylesBefore = await page.locator('body').evaluate(element => ({overflow: element.style.overflow, paddingRight: element.style.paddingRight}));
  await trigger.hover();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(overlay.getByRole('dialog')).toBeVisible();
  await expect(search).not.toBeFocused();
  const triggerXAfter = await trigger.evaluate(element => element.getBoundingClientRect().x);
  expect(Math.abs(triggerXAfter - triggerXBefore)).toBeLessThan(1);

  const triggerBox = await trigger.boundingBox();
  const panelBox = await panel.boundingBox();
  expect(triggerBox).toBeTruthy();
  expect(panelBox).toBeTruthy();
  const startX = triggerBox.x + triggerBox.width / 2;
  const startY = triggerBox.y + triggerBox.height / 2;
  const endX = panelBox.x + 150;
  const endY = panelBox.y + 30;
  await page.mouse.move(startX, startY);
  for (let step = 1; step <= 8; step += 1) {
    await page.mouse.move(startX + (endX - startX) * step / 8, startY + (endY - startY) * step / 8);
    await page.waitForTimeout(35);
  }
  await expect(overlay.getByRole('dialog')).toBeVisible();

  await page.mouse.move(12, 700);
  await expect(overlay).toBeHidden({ timeout: 1200 });
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect.poll(() => page.locator('body').evaluate(element => ({overflow: element.style.overflow, paddingRight: element.style.paddingRight}))).toEqual(bodyStylesBefore);

  /* The trigger is intentionally a real landing link: hover is the
     progressive desktop preview, click and Enter keep the landing reachable. */
  await trigger.click();
  await expect(page).toHaveURL(/\/san-pham\/$/);
  await expect(page.locator('h1')).toBeVisible();
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await trigger.focus();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/\/san-pham\/$/);
});

test('mobile drawer keeps product links and submits product search', async ({ page, isMobile }, testInfo) => {
  test.skip(!isMobile, 'Mobile drawer.');
  await page.setViewportSize({ width: 390, height: 895 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.locator('#hamburger').click();

  const drawer = page.locator('#menu');
  const search = drawer.locator('#ptsMobileMenuSearch');
  await expect(search).toBeVisible();
  await expect(drawer.getByRole('link', { name: 'Danh mục sản phẩm' })).toBeVisible();
  const productRoot = drawer.locator('.mm-panel_opened > .mm-listview > .mmenu-product-root');
  await expect(productRoot).toHaveClass(/mm-listitem_opened/);
  await expect(productRoot.locator('.pts-mobile-product-category').first()).toBeVisible();
  const firstCategory = productRoot.locator('.pts-mobile-product-category').first();
  await expect(firstCategory).not.toHaveClass(/mm-listitem_opened/);
  await expect(drawer.locator('.pts-mmenu-advice')).toBeVisible();
  await expect(drawer.locator('.mm-lang')).toHaveCount(0);
  await expect(drawer.locator('.menu-about')).toBeVisible();
  await drawer.screenshot({ path: testInfo.outputPath('mobile-drawer-390x895.png'), scale: 'css' });
  await firstCategory.locator(':scope > .mm-listitem__btn').click();
  await expect(firstCategory).toHaveClass(/mm-listitem_opened/);
  await firstCategory.locator(':scope > .mm-listitem__btn').click();
  await expect(firstCategory).not.toHaveClass(/mm-listitem_opened/);
  await page.setViewportSize({ width: 320, height: 740 });
  expect(Math.round((await drawer.boundingBox()).width)).toBe(296);
  await drawer.screenshot({ path: testInfo.outputPath('mobile-drawer-320x740.png'), scale: 'css' });
  await drawer.locator('.mm-panel_opened').first().evaluate(panel => { panel.scrollTop = panel.scrollHeight; });
  const adviceBounds = await drawer.locator('.pts-mmenu-advice').boundingBox();
  const cooperationBounds = await drawer.locator('.menu-cooperation').boundingBox();
  expect(cooperationBounds.y + cooperationBounds.height).toBeLessThanOrEqual(adviceBounds.y);
  await page.setViewportSize({ width: 390, height: 895 });
  await firstCategory.locator(':scope > .mm-listitem__btn').click();
  await expect(firstCategory).toHaveClass(/mm-listitem_opened/);
  await page.mouse.click(365, 180);
  await expect(drawer).not.toHaveClass(/mm-menu_opened/);
  await page.locator('#hamburger').click();
  await expect(firstCategory).not.toHaveClass(/mm-listitem_opened/);
  const closeButton = drawer.locator('.pts-mmenu-close').first();
  await expect(closeButton).toBeVisible();
  const closeBounds = await closeButton.boundingBox();
  const drawerBounds = await drawer.boundingBox();
  expect(closeBounds.x).toBeGreaterThanOrEqual(drawerBounds.x);
  expect(closeBounds.x + closeBounds.width).toBeLessThanOrEqual(drawerBounds.x + drawerBounds.width);
  await closeButton.click();
  await expect(page.locator('body')).not.toHaveClass(/mm-wrapper_opened/);
  await expect(drawer).not.toHaveClass(/mm-menu_opened/);
  await page.locator('#hamburger').click();
  await expect(search).toBeVisible();
  await productRoot.locator(':scope > .mm-listitem__btn').click();
  await expect(productRoot).not.toHaveClass(/mm-listitem_opened/);
  await productRoot.locator(':scope > .mm-listitem__btn').click();
  await expect(productRoot).toHaveClass(/mm-listitem_opened/);
  await drawer.locator('.pts-mmenu-close').first().click();
  await expect(page.locator('body')).not.toHaveClass(/mm-wrapper_opened/);
  await expect(drawer).not.toHaveClass(/mm-menu_opened/);
  await page.locator('#hamburger').click();
  await expect(search).toBeVisible();
  await search.fill('đèn đường');
  await Promise.all([
    page.waitForURL(/tim-kiem\?keyword=/),
    search.press('Enter')
  ]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
});

test('mobile category label opens its landing instead of only toggling the accordion', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Mobile drawer.');
  await page.setViewportSize({ width: 390, height: 895 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.locator('#hamburger').click();
  const category = page.locator('#menu .pts-mobile-product-category').first();
  const href = await category.locator(':scope > .mm-listitem__text').getAttribute('href');
  expect(href).toMatch(/^\/danh-muc-san-pham\//);
  await Promise.all([
    page.waitForURL(url => url.pathname === href),
    category.locator(':scope > .mm-listitem__text').click()
  ]);
  await expect(page.locator('h1').first()).toBeVisible();
});

test('tablet drawer uses the same compact accordion and keeps its advice CTA visible', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Tablet width in desktop browser context.');
  await page.setViewportSize({ width: 768, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await page.locator('#hamburger').click();
  const drawer = page.locator('#menu');
  expect(Math.round((await drawer.boundingBox()).width)).toBe(360);
  await expect(drawer.locator('.mmenu-product-root')).toHaveClass(/mm-listitem_opened/);
  await expect(drawer.locator('.pts-mobile-product-category').first()).not.toHaveClass(/mm-listitem_opened/);
  await expect(drawer.locator('.pts-mmenu-advice')).toBeVisible();
  await expect(drawer.locator('.mm-lang')).toHaveCount(0);
});

const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    try { window.sessionStorage.setItem('pts-popup-campaign', 'current'); } catch (error) {}
  });
});

test('homepage v4 giữ đủ mạch nội dung và hero là một slider bốn ảnh', async ({ page, isMobile }, testInfo) => {
  test.skip(isMobile, 'Đo khung hero toàn màn hình chỉ áp dụng cho trình duyệt desktop.');
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.setViewportSize({ width: 1920, height: 950 });
  const response = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(response.ok()).toBeTruthy();
  await page.evaluate(async () => { if (document.fonts && document.fonts.ready) await document.fonts.ready; });

  const slider = page.locator('[data-home-hero-slider]');
  const slides = slider.locator('[data-home-hero-slide]');
  await expect(slider).toBeVisible();
  await expect(slides).toHaveCount(4);
  expect(await slides.evaluateAll(items => items.map(item => item.getAttribute('data-home-hero-slide-key')))).toEqual(['street-light', 'inverter', 'panel', 'storage']);
  await expect(slider.locator('[data-home-hero-slide].is-active')).toHaveCount(1);
  await expect(slider.locator('[data-home-hero-current]')).toHaveText('01');
  await slider.locator('[data-home-hero-next]').click();
  await expect(slider.locator('[data-home-hero-current]')).toHaveText('02');
  await expect(slides.nth(1)).toHaveClass(/is-active/);

  const heroMetrics = await page.locator('.pts-home-hero').evaluate(hero => {
    const grid = hero.querySelector('.pts-home-hero-grid').getBoundingClientRect();
    const title = hero.querySelector('h1');
    const logo = document.querySelector('.logo-header img').getBoundingClientRect();
    return {
      gridWidth: grid.width,
      titleSize: parseFloat(getComputedStyle(title).fontSize),
      logoWidth: logo.width,
      heroBottom: hero.getBoundingClientRect().bottom,
      viewportHeight: window.innerHeight,
    };
  });
  expect(heroMetrics.gridWidth).toBeGreaterThan(1450);
  expect(heroMetrics.titleSize).toBeGreaterThanOrEqual(55);
  expect(heroMetrics.logoWidth).toBeGreaterThanOrEqual(360);
  expect(heroMetrics.heroBottom).toBeLessThan(heroMetrics.viewportHeight + 80);
  expect(await page.locator('.pts-home-catalog h2').evaluate(title => title.getBoundingClientRect().top)).toBeGreaterThan(heroMetrics.viewportHeight);

  await expect(page.locator('.pts-home-category')).toHaveCount(await page.locator('.pts-home-category').count());
  expect(await page.locator('.pts-home-category').count()).toBeGreaterThanOrEqual(5);
  expect(await page.locator('.pts-home-category').evaluateAll(items => items.map(item => item.getAttribute('data-home-category-key')))).toEqual(['lighting', 'panel', 'inverter', 'storage', 'pump']);
  await expect(page.locator('.pts-home-category-copy small').first()).not.toBeEmpty();
  await expect(page.locator('.pts-home-tab')).toHaveCount(4);
  await expect(page.locator('.pts-home-tab').first()).toHaveText('Doanh nghiệp và nhà xưởng');
  const businessChips = page.locator('.pts-home-solution-chips').first();
  await expect(businessChips).toBeVisible();
  await expect(businessChips.locator('a')).toHaveCount(3);
  await expect(businessChips.locator('a').first()).toHaveAttribute('href', 'giai-phap/dien-mat-troi-doanh-nghiep/');
  await expect(page.locator('.pts-home-tab-panel').first().locator('.pts-home-btn-primary')).toHaveAttribute('href', 'giai-phap/dien-mat-troi-doanh-nghiep/');
  await expect(page.locator('.pts-home-solution-visual').first()).toBeVisible();
  const solutionVisualMetrics = await page.locator('.pts-home-solution-visual').first().evaluate(visual => {
    const bounds = visual.getBoundingClientRect();
    return { width: bounds.width, height: bounds.height };
  });
  expect(solutionVisualMetrics.width).toBeGreaterThanOrEqual(600);
  expect(solutionVisualMetrics.height).toBeGreaterThanOrEqual(350);
  await page.locator('.pts-home-solutions').screenshot({ path: testInfo.outputPath('homepage-v4-solutions.png') });
  await expect(page.locator('.pts-home-solution-visual img')).toHaveCount(4);
  const brandColors = await page.evaluate(() => ({
    token: getComputedStyle(document.querySelector('.pts-home-v4')).getPropertyValue('--home-navy').trim(),
    sectionSurfaces: Array.from(document.querySelectorAll('.pts-home-lighting, .pts-home-services, .pts-home-contact')).map(section => getComputedStyle(section).backgroundColor),
    dealerSurface: getComputedStyle(document.querySelector('.pts-home-dealer')).backgroundColor,
    heading: getComputedStyle(document.querySelector('.pts-home-lighting h2')).color,
  }));
  expect(brandColors.token).toBe('#061537');
  expect(brandColors.sectionSurfaces).toEqual(['rgb(243, 245, 247)', 'rgb(243, 245, 247)', 'rgb(243, 245, 247)']);
  expect(brandColors.dealerSurface).toBe('rgb(6, 21, 55)');
  expect(brandColors.heading).toBe('rgb(6, 21, 55)');
  await expect(page.locator('.pts-home-steps li')).toHaveCount(5);
  await expect(page.locator('.pts-home-dealer')).toBeVisible();
  await expect(page.locator('.pts-home-dealer img')).toHaveCount(0);
  expect(await page.locator('.pts-home-dealer').evaluate(section => getComputedStyle(section).backgroundColor)).toBe('rgb(6, 21, 55)');
  const dealer = page.locator('.pts-home-dealer');
  /* 2026-09-22: tiêu đề rút gọn "TRỞ THÀNH ĐẠI LÝ PHÚC THỊNH SOLAR" -> "ĐĂNG KÝ
     ĐẠI LÝ" và bỏ span .pts-home-dealer-brand theo bản chỉnh copy mới nhất -
     nội dung, không phải lỗi; cập nhật assertion theo copy đã duyệt. */
  await expect(dealer.locator('h2')).toHaveText('ĐĂNG KÝ ĐẠI LÝ');
  await expect(dealer.locator('p')).toHaveText('Cùng phát triển kinh doanh thiết bị năng lượng mặt trời với nguồn hàng đa dạng, chính sách hợp tác rõ ràng và sự đồng hành của đội ngũ Phúc Thịnh Solar.');
  await expect(dealer.locator('li')).toHaveCount(3);
  await expect(dealer.locator('.pts-home-btn-primary')).toHaveAttribute('href', '#yeu-cau-tu-van');
  await expect(dealer.locator('.pts-home-btn-primary')).toHaveAttribute('data-home-quote', 'Hợp tác đại lý');
  await dealer.locator('.pts-home-btn-primary').click();
  await expect(page.locator('#ptsHomeInterest')).toHaveValue('Hợp tác đại lý');
  expect(await page.locator('.pts-home-category-shade').first().evaluate(shade => getComputedStyle(shade).backgroundImage)).toBe('none');
  expect(await page.locator('.pts-home-category-rail .pts-home-category-copy').first().evaluate(copy => getComputedStyle(copy).backgroundImage)).toContain('linear-gradient');
  const about = page.locator('.pts-home-about');
  await expect(about).toBeVisible();
  await expect(about.locator('h2')).toHaveText('Cung cấp thiết bị và thi công năng lượng mặt trời');
  await expect(about.locator('p')).toHaveText('Phúc Thịnh Solar cung cấp đèn năng lượng mặt trời, tấm pin, biến tần, pin lưu trữ và thiết bị bơm nước. Chúng tôi tư vấn lựa chọn thiết bị, thiết kế và thi công hệ thống phù hợp với nhu cầu sử dụng và ngân sách của khách hàng.');
  await expect(about.locator('.pts-home-text-link')).toContainText('Tìm hiểu về Phúc Thịnh Solar');
  await expect(about.locator('.pts-home-about-points li')).toHaveCount(4);
  await expect(about.locator('.pts-home-about-points strong')).toHaveText([
    'Sản phẩm chính hãng',
    'Tư vấn và thi công',
    'Hỗ trợ sau bàn giao',
    'Hệ thống kho ba khu vực',
  ]);
  await expect(page.locator('#yeu-cau-tu-van form input[name="form_csrf"]')).toHaveCount(1);
  const contactFooterMetrics = await page.evaluate(() => {
    const panel = document.querySelector('.pts-home-contact-panel');
    const contact = document.querySelector('.pts-home-contact');
    const footer = document.querySelector('footer');
    return {
      gap: footer.getBoundingClientRect().top - panel.getBoundingClientRect().bottom,
      panelBottom: panel.getBoundingClientRect().bottom,
      contactBottom: contact.getBoundingClientRect().bottom,
      footerTop: footer.getBoundingClientRect().top,
      paddingBottom: getComputedStyle(contact).paddingBottom,
      footerCount: document.querySelectorAll('footer').length,
      matchingRules: Array.from(document.styleSheets).flatMap(sheet => {
        try {
          return Array.from(sheet.cssRules)
            .filter(rule => rule.selectorText && rule.selectorText.includes('pts-home-contact'))
            .map(rule => ({ href: sheet.href, selector: rule.selectorText, paddingBottom: rule.style.paddingBottom }));
        } catch (error) { return []; }
      }),
    };
  });
  expect(contactFooterMetrics.gap, JSON.stringify(contactFooterMetrics)).toBeGreaterThanOrEqual(80);

  await page.screenshot({ path: testInfo.outputPath('homepage-v4-desktop.png'), fullPage: false });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
  expect(errors).toEqual([]);
});

test('hero tự chuyển slide kể cả khi con trỏ nằm trên banner và bật giảm chuyển động', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Kiểm tra rê chuột dành cho desktop; mobile được kiểm tra bằng thao tác vuốt.');
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const slider = page.locator('[data-home-hero-slider]');
  const current = slider.locator('[data-home-hero-current]');
  await expect(current).toHaveText('01');
  const sliderBox = await slider.boundingBox();
  expect(sliderBox).toBeTruthy();
  await page.mouse.move(sliderBox.x + sliderBox.width * 0.75, sliderBox.y + sliderBox.height * 0.35);
  await expect(current).toHaveText('02', { timeout: 7000 });
});

test('homepage v4 mobile không tràn và slider vuốt được', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await page.evaluate(async () => { if (document.fonts && document.fonts.ready) await document.fonts.ready; });

  await expect(page.locator('.pts-home-hero h1')).toBeVisible();
  await expect(page.locator('[data-home-hero-slider] [data-home-hero-slide].is-active')).toHaveCount(1);
  await expect(page.locator('.pts-home-category-rail')).toBeVisible();
  await expect(page.locator('.pts-home-tab')).toHaveCount(4);
  await expect(page.locator('.pts-home-solution-visual').first()).toBeVisible();
  const mobileSolutionVisualWidth = await page.locator('.pts-home-solution-visual').first().evaluate(visual => visual.getBoundingClientRect().width);
  expect(mobileSolutionVisualWidth).toBeGreaterThanOrEqual(300);
  await expect(page.locator('.pts-home-solution-visual img')).toHaveCount(4);
  const panelPhoto = page.locator('.pts-home-category--panel img').first();
  expect(await panelPhoto.evaluate(image => ({
    fit: getComputedStyle(image).objectFit,
    transform: getComputedStyle(image).transform,
    clip: getComputedStyle(image.closest('.pts-home-category')).overflow,
  }))).toEqual({ fit: 'cover', transform: 'none', clip: 'hidden' });
  const contactFooterGap = await page.evaluate(() => {
    const panel = document.querySelector('.pts-home-contact-panel');
    const footer = document.querySelector('footer');
    return footer.getBoundingClientRect().top - panel.getBoundingClientRect().bottom;
  });
  expect(contactFooterGap).toBeGreaterThanOrEqual(72);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();

  await page.screenshot({ path: testInfo.outputPath('homepage-v4-mobile.png'), fullPage: false });
});

test('homepage v4 tải đủ ảnh và giữ đúng thứ tự câu chuyện', async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const expectedHeadings = [
    'Sản phẩm năng lượng mặt trời',
    'Chiếu sáng phù hợp từng không gian',
    'Giải pháp cho từng nhu cầu',
    'Đồng hành từ tư vấn đến vận hành',
    'Bạn đang cần giải pháp nào?',
  ];
  const headings = page.locator('.pts-home-v4 h2');
  const headingTexts = await headings.allTextContents();
  expectedHeadings.forEach((heading, index) => {
    expect(headingTexts.indexOf(heading), heading).toBeGreaterThan(index === 0 ? -1 : headingTexts.indexOf(expectedHeadings[index - 1]));
  });

  const sections = page.locator('.pts-home-v4 section');
  for (let index = 0; index < await sections.count(); index += 1) {
    await sections.nth(index).scrollIntoViewIfNeeded();
    await page.waitForTimeout(80);
  }
  await page.waitForTimeout(250);

  for (const [slot, asset] of [
    ['inverter', 'category-inverter-user-2026.jpg'],
    ['panel', 'category-panels-user-2026.jpg'],
    ['storage', 'category-storage-user-2026.jpg'],
    ['pump', 'category-pump-user-2026.jpg'],
  ]) {
    const image = page.locator(`.pts-home-category--${slot} img`).first();
    await image.scrollIntoViewIfNeeded();
    await expect(image).toBeVisible();
    expect(await image.evaluate(element => element.currentSrc)).toContain(asset);
  }
  await page.locator('.pts-home-catalog').screenshot({ path: testInfo.outputPath('homepage-v4-catalog-glass.png') });

  const heroPhotos = await page.locator('[data-home-hero-slide] img').evaluateAll(images => images.map(image => image.currentSrc || image.getAttribute('data-src') || image.src));
  expect(heroPhotos).toHaveLength(4);
  expect(heroPhotos.every(source => source && !source.includes('noimage.png'))).toBeTruthy();
  expect(new Set(heroPhotos).size).toBe(4);
  expect(await page.locator('[data-home-hero-slide-key]').evaluateAll(nodes => nodes.map(node => node.getAttribute('data-home-hero-slide-key')))).toEqual(['street-light', 'inverter', 'panel', 'storage']);
  const solutionPhotos = await page.locator('.pts-home-solution-visual img').evaluateAll(images => images.map(image => image.currentSrc || image.getAttribute('data-src') || image.src));
  expect(solutionPhotos).toHaveLength(4);
  expect(solutionPhotos.every(source => source && !source.includes('noimage.png'))).toBeTruthy();
  expect(solutionPhotos.some(source => source.includes('home-solution-construction-v1.png'))).toBeTruthy();

  const failedImages = await page.locator('.pts-home-v4 img').evaluateAll(images => images
    .filter(image => image.currentSrc && image.naturalWidth === 0)
    .map(image => image.currentSrc));
  expect(failedImages).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('homepage-v4-full.png'), fullPage: true });
});

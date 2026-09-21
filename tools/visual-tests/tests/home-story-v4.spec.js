const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    try { window.sessionStorage.setItem('pts-popup-campaign', 'current'); } catch (error) {}
  });
});

test('homepage v4 giữ đủ mạch nội dung và hero là một slider bốn ảnh', async ({ page }, testInfo) => {
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

  await expect(page.locator('.pts-home-category')).toHaveCount(await page.locator('.pts-home-category').count());
  expect(await page.locator('.pts-home-category').count()).toBeGreaterThanOrEqual(5);
  await expect(page.locator('.pts-home-category-copy small').first()).not.toBeEmpty();
  await expect(page.locator('.pts-home-tab')).toHaveCount(4);
  await expect(page.locator('.pts-home-solution-chips').first()).toBeVisible();
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
  const dealerPhoto = page.locator('.pts-home-dealer-media img');
  await dealerPhoto.scrollIntoViewIfNeeded();
  await expect.poll(() => dealerPhoto.evaluate(image => image.complete && image.naturalWidth > 0)).toBeTruthy();
  expect(await dealerPhoto.evaluate(image => getComputedStyle(image).objectFit)).toBe('cover');
  await expect(page.locator('.pts-home-about')).toBeVisible();
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

test('hero tự chuyển slide kể cả khi con trỏ nằm trên banner và bật giảm chuyển động', async ({ page }) => {
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
    ['storage', 'category-battery-storage-v1.png'],
    ['pump', 'category-solar-pump-v1.png'],
  ]) {
    const image = page.locator(`.pts-home-category--${slot} img`).first();
    await expect(image).toBeVisible();
    expect(await image.evaluate(element => element.currentSrc)).toContain(asset);
  }

  const heroPhotos = await page.locator('[data-home-hero-slide] img').evaluateAll(images => images.map(image => image.currentSrc || image.getAttribute('data-src') || image.src));
  expect(heroPhotos).toHaveLength(4);
  expect(heroPhotos.every(source => source && !source.includes('noimage.png'))).toBeTruthy();
  const solutionPhotos = await page.locator('.pts-home-solution-visual img').evaluateAll(images => images.map(image => image.currentSrc || image.getAttribute('data-src') || image.src));
  expect(solutionPhotos).toHaveLength(4);
  expect(solutionPhotos.every(source => source && !source.includes('noimage.png'))).toBeTruthy();

  const failedImages = await page.locator('.pts-home-v4 img').evaluateAll(images => images
    .filter(image => image.currentSrc && image.naturalWidth === 0)
    .map(image => image.currentSrc));
  expect(failedImages).toEqual([]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
  await page.screenshot({ path: testInfo.outputPath('homepage-v4-full.png'), fullPage: true });
});

const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function openAbout(page) {
  const pageErrors = [];
  const failedLocalRequests = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('requestfailed', request => {
    if (request.url().startsWith('http://localhost:8080')) {
      failedLocalRequests.push(`${request.method()} ${request.url()} ${request.failure()?.errorText || ''}`);
    }
  });
  await page.goto('/gioi-thieu/', { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);
  // Visit below-fold sections before checking lazy images; moving the People
  // section below history must not classify a deferred image as broken.
  for (const section of await page.locator('.pts-about-page > section').all()) {
    await section.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
  }
  await page.locator('.pts-about-page img').evaluateAll(async images => {
    await Promise.all(images.map(image => image.decode().catch(() => {})));
  });
  await page.evaluate(() => window.scrollTo(0, 0));
  return { pageErrors, failedLocalRequests };
}

test('About page keeps alternating introduction and history with Admin-backed sections', async ({ page }) => {
  const health = await openAbout(page);
  const main = page.locator('.pts-about-page');

  await expect(main).toBeVisible();
  await expect(main.locator('h1')).toContainText(/Phúc Thịnh Solar|Phuc Thinh Solar/);
  await expect(main.locator('.pts-about-section-nav a')).toHaveCount(5);
  await expect(main.locator('> section')).toHaveCount(7);
  await expect(main.locator('.pts-about-history .pts-about-milestone')).toHaveCount(1);
  const introImage = main.locator('.pts-about-story-media img');
  const historyImage = main.locator('.pts-about-history-media img');
  await expect(introImage).toHaveCount(1);
  await expect(historyImage).toHaveCount(1);
  await expect(main.locator('.pts-about-principle')).toHaveCount(3);
  await expect(main.locator('.pts-about-trust-card')).toHaveCount(4);
  await expect(main.locator('.pts-about-compass-item')).toHaveCount(3);
  await expect(main.locator('.pts-about-certificate-card')).toHaveCount(3);
  await expect(main).not.toContainText('Đối tác chiến lược');
  await expect(main).not.toContainText('Giao hàng toàn quốc');
  await expect(main).not.toContainText('Thanh toán linh hoạt');
  await expect(main).not.toContainText('độc quyền');

  // DB hiện chỉ có một record mang năm đã xác thực; mockup không được dùng để
  // tự dựng thêm các mốc 2021/2023/2026.
  await expect(main.locator('.pts-about-milestone')).toHaveCount(1);
  await expect(main.locator('.pts-about-year')).toHaveText('2019');

  // Icon mang ý nghĩa nội dung phải lấy từ ảnh record Admin; timeline và khối
  // kim chỉ nam dùng ảnh Admin hoặc marker số thay cho Font Awesome hard-code.
  await expect(main.locator('.pts-about-trust-icon img')).toHaveCount(4);
  await expect(main.locator('.pts-about-compass-number, .pts-about-compass-icon img')).toHaveCount(3);
  await expect(main.locator('.pts-about-trust-icon i, .pts-about-compass-icon i, .pts-about-milestone-icon i')).toHaveCount(0);

  const readableTextSizes = await main.locator('.pts-about-milestone p, .pts-about-trust-card p, .pts-about-compass-item p').evaluateAll(elements => elements.map(element => parseFloat(getComputedStyle(element).fontSize)));
  expect(readableTextSizes.length).toBeGreaterThan(0);
  expect(Math.min(...readableTextSizes)).toBeGreaterThanOrEqual(15);

  const brokenImages = await main.locator('img').evaluateAll(images => images
    .filter(image => image.complete && image.naturalWidth === 0)
    .map(image => image.currentSrc || image.src || image.dataset.src));
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(brokenImages).toEqual([]);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
  expect(health.pageErrors).toEqual([]);
  expect(health.failedLocalRequests).toEqual([]);
});

test('Legacy About URL redirects permanently to the Giới thiệu canonical route', async ({ request }) => {
  const response = await request.get('/ve-chung-toi/', { maxRedirects: 0 });
  expect(response.status()).toBe(301);
  expect(response.headers().location).toMatch(/\/gioi-thieu\/$/);
});

test('About certificate gallery and contact CTA reuse current interactions', async ({ page }) => {
  await openAbout(page);

  await page.locator('[data-about-cert-item-open]').first().click();
  await expect(page.locator('#pbOverlay')).toHaveClass(/show/);
  await expect(page.locator('#pbCaption .counter')).toContainText('/5');
  await page.locator('#pbCloseBtn').click();

  const hotline = page.locator('.pts-about-contact-button.is-hotline');
  await expect(hotline).toHaveAttribute('href', 'tel:0944555957');
  await expect(page.locator('.pts-about-contact-button.is-zalo')).toHaveAttribute('href', /zalo\.me\/0944555957/);

  await page.locator('.pts-about-contact-button.is-primary').click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
});

test('About mobile layout stacks without horizontal overflow', async ({ page }, testInfo) => {
  test.skip(!testInfo.project.name.includes('mobile'), 'Mobile-only layout assertion');
  await openAbout(page);

  await expect(page.locator('.pts-about-story-grid')).toHaveCSS('grid-template-columns', /\d+(\.\d+)?px/);
  await expect(page.locator('.pts-about-timeline')).toHaveCSS('display', 'block');
  await expect(page.locator('.pts-about-contact-actions')).toHaveCSS('display', 'grid');
  const trustLayout = await page.locator('.pts-about-trust-layout').evaluate(layout => {
    const layoutBox = layout.getBoundingClientRect();
    const headingBox = layout.querySelector('.pts-about-section-heading').getBoundingClientRect();
    const firstCard = layout.querySelector('.pts-about-trust-card').getBoundingClientRect();
    const firstTitle = layout.querySelector('.pts-about-trust-card h3').getBoundingClientRect();
    const firstDescription = layout.querySelector('.pts-about-trust-card p').getBoundingClientRect();
    return {
      columns: getComputedStyle(layout).gridTemplateColumns.split(' ').length,
      headingBottom: headingBox.bottom,
      firstCardTop: firstCard.top,
      titleWidth: firstTitle.width,
      descriptionWidth: firstDescription.width,
      cardRight: firstCard.right,
      layoutRight: layoutBox.right,
    };
  });
  expect(trustLayout.columns).toBe(1);
  expect(trustLayout.firstCardTop).toBeGreaterThanOrEqual(trustLayout.headingBottom - 1);
  expect(trustLayout.titleWidth).toBeGreaterThan(120);
  expect(trustLayout.descriptionWidth).toBeGreaterThan(120);
  expect(trustLayout.cardRight).toBeLessThanOrEqual(trustLayout.layoutRight + 1);
  const peopleOrder = await page.locator('.pts-about-people').evaluate(section => {
    const heading = section.querySelector('h2').getBoundingClientRect().top;
    const imageNode = section.querySelector('.pts-about-people-media');
    const image = imageNode ? imageNode.getBoundingClientRect().top : null;
    const lead = section.querySelector('.pts-about-lead').getBoundingClientRect().top;
    return { heading, image, lead };
  });
  expect(peopleOrder.heading).toBeLessThan(peopleOrder.lead);
  if (peopleOrder.image !== null) {
    expect(peopleOrder.heading).toBeLessThan(peopleOrder.image);
    expect(peopleOrder.image).toBeLessThan(peopleOrder.lead);
  }
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
});

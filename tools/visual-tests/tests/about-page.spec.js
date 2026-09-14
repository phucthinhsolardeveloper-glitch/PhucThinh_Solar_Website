const { test, expect } = require('@playwright/test');

async function openAbout(page) {
  const pageErrors = [];
  const failedLocalRequests = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('requestfailed', request => {
    if (request.url().startsWith('http://localhost:8080')) {
      failedLocalRequests.push(`${request.method()} ${request.url()} ${request.failure()?.errorText || ''}`);
    }
  });
  await page.goto('/ve-chung-toi/', { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);
  return { pageErrors, failedLocalRequests };
}

test('About page has the concise six-section corporate layout', async ({ page }) => {
  const health = await openAbout(page);
  const main = page.locator('.pts-about-page');

  await expect(main).toBeVisible();
  await expect(main.locator('> section')).toHaveCount(6);
  await expect(main.locator('.pts-about-principle')).toHaveCount(3);
  await expect(main.locator('.pts-about-trust-card')).toHaveCount(4);
  await expect(main.locator('.pts-about-compass-item')).toHaveCount(3);
  await expect(main.locator('.pts-about-certificate-card')).toHaveCount(4);

  await expect(main).not.toContainText('Đối tác chiến lược');
  await expect(main).not.toContainText('Giao hàng toàn quốc');
  await expect(main).not.toContainText('Thanh toán linh hoạt');
  await expect(main).not.toContainText('độc quyền');

  // DB hiện chỉ có một record mang năm đã xác thực; mockup không được dùng để
  // tự dựng thêm các mốc 2021/2023/2026.
  await expect(main.locator('.pts-about-milestone')).toHaveCount(1);
  await expect(main.locator('.pts-about-year')).toHaveText('2019');

  const icons = main.locator('.pts-about-principle-icon i, .pts-about-trust-icon i, .pts-about-compass-icon i');
  expect(await icons.count()).toBeGreaterThan(0);
  for (const icon of await icons.all()) {
    await expect(icon).toHaveCSS('font-family', /Font Awesome/);
    expect(await icon.evaluate(element => getComputedStyle(element, '::before').content)).not.toBe('none');
  }

  const brokenImages = await main.locator('img').evaluateAll(images => images
    .filter(image => image.complete && image.naturalWidth === 0)
    .map(image => image.currentSrc || image.src || image.dataset.src));
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(brokenImages).toEqual([]);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
  expect(health.pageErrors).toEqual([]);
  expect(health.failedLocalRequests).toEqual([]);
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
  const peopleOrder = await page.locator('.pts-about-people').evaluate(section => {
    const heading = section.querySelector('h2').getBoundingClientRect().top;
    const image = section.querySelector('.pts-about-people-media').getBoundingClientRect().top;
    const principles = section.querySelector('.pts-about-principles').getBoundingClientRect().top;
    return { heading, image, principles };
  });
  expect(peopleOrder.heading).toBeLessThan(peopleOrder.image);
  expect(peopleOrder.image).toBeLessThan(peopleOrder.principles);
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
});

const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('homepage showroom v4 keeps the approved story and real journeys working', async ({ page, isMobile }, testInfo) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  if (!isMobile) await page.setViewportSize({ width: 1440, height: 950 });
  const response = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(response.ok()).toBeTruthy();

  await expect(page.locator('.top-utility-bar')).toBeHidden();
  await expect(page.locator('.pts-home-v4')).toBeVisible();
  await expect(page.locator('[data-home-hero-slide]')).toHaveCount(4);
  await expect(page.locator('[data-home-hero-slide].is-active')).toHaveCount(1);
  await expect(page.locator('.pts-home-tab')).toHaveCount(4);
  await expect(page.locator('.pts-home-steps li')).toHaveCount(5);
  await expect(page.locator('#yeu-cau-tu-van form')).toBeVisible();

  const categoryRail = page.locator('.pts-home-category-rail');
  const categoryLayout = await categoryRail.evaluate(element => ({
    display: getComputedStyle(element).display,
    wrap: getComputedStyle(element).flexWrap,
    categoryCount: element.querySelectorAll('.pts-home-category').length,
  }));
  expect(categoryLayout.display).toBe(isMobile ? 'flex' : 'grid');
  if (isMobile) expect(categoryLayout.wrap).toBe('nowrap');
  expect(categoryLayout.categoryCount).toBe(4);
  await expect(page.locator('.pts-home-category-featured')).toHaveCount(1);

  const firstCategory = page.locator('.pts-home-category').first();
  await expect(firstCategory.locator('img')).toBeVisible();
  expect(await firstCategory.getAttribute('href')).toMatch(/^danh-muc-san-pham\//);
  await page.locator('[data-home-hero-next]').click();
  await expect(page.locator('[data-home-hero-current]')).toHaveText('02');

  const horizontalOverflow = await page.evaluate(() => ({
    documentWidth: document.documentElement.scrollWidth,
    viewportWidth: document.documentElement.clientWidth,
    offenders: Array.from(document.querySelectorAll('body *')).map(element => ({
      selector: element.tagName.toLowerCase() + (element.id ? '#' + element.id : '') + (typeof element.className === 'string' && element.className.trim() ? '.' + element.className.trim().split(/\s+/).slice(0, 2).join('.') : ''),
      right: Math.round(element.getBoundingClientRect().right),
      width: Math.round(element.getBoundingClientRect().width),
      overflow: getComputedStyle(element).overflowX,
    })).filter(element => element.right > document.documentElement.clientWidth + 1).slice(0, 8),
  }));
  expect(horizontalOverflow.documentWidth - horizontalOverflow.viewportWidth, JSON.stringify(horizontalOverflow)).toBeLessThanOrEqual(1);
  expect(pageErrors).toEqual([]);
  await page.screenshot({ path: testInfo.outputPath('home-story-v4.png'), fullPage: false });
});

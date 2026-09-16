const { test, expect } = require('@playwright/test');

const viewports = [
  { name: 'phone-360', width: 360, height: 800 },
  { name: 'phone-390', width: 390, height: 844 },
  { name: 'tablet-768', width: 768, height: 1024 },
  { name: 'tablet-landscape-1024', width: 1024, height: 768 },
  { name: 'laptop-1366', width: 1366, height: 768 },
  { name: 'desktop-1440', width: 1440, height: 900 },
  { name: 'full-hd-1920', width: 1920, height: 1080 },
  { name: 'wide-2k-2560', width: 2560, height: 1440 },
];

const routes = ['/', '/san-pham/', '/giai-phap/', '/tin-tuc/', '/dai-ly/'];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

for (const viewport of viewports) {
  test(`${viewport.name} keeps the primary public journeys usable`, async ({ page, baseURL }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });

    for (const route of routes) {
      const pageErrors = [];
      page.removeAllListeners('pageerror');
      page.on('pageerror', error => pageErrors.push(error.message));
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response && response.ok(), `${viewport.name} ${route}`).toBeTruthy();
      await expect(page.locator('body'), `${viewport.name} ${route}`).toBeVisible();
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, `${viewport.name} ${route}`).toBeLessThanOrEqual(1);
      expect(pageErrors, `${viewport.name} ${route}`).toEqual([]);
    }

    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const logo = page.locator('.pts-header-v3 .logo-header img');
    await expect(logo, `${viewport.name} phải giữ logo và lối về trang chủ`).toBeVisible();
    expect(await logo.evaluate(image => image.naturalWidth), `${viewport.name} logo phải tải thành công`).toBeGreaterThan(0);
    const expectedContainer = viewport.width >= 2300 ? 1920 : (viewport.width >= 1800 ? 1680 : 1560);
    const configuredContainer = await page.evaluate(() => parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--pts-container')));
    expect(configuredContainer).toBe(expectedContainer);

    if (viewport.width <= 1120) {
      await expect(page.locator('.pts-header-nav')).toBeHidden();
      await expect(page.locator('.pts-header-v3 .btn-mmenu')).toBeVisible();
    } else {
      await expect(page.locator('.pts-header-nav')).toBeVisible();
    }

    const familyColumns = await page.locator('.pts-product-family-grid').first().evaluate(grid =>
      new Set(Array.from(grid.children).slice(0, 5).map(item => Math.round(item.getBoundingClientRect().top))).size
    );
    if (viewport.width <= 767) expect(familyColumns).toBeGreaterThanOrEqual(2);
    else if (viewport.width <= 1199) expect(familyColumns).toBeGreaterThanOrEqual(1);
    else expect(familyColumns).toBe(1);

    if (viewport.width >= 1800) {
      const containerWidth = await page.locator('.pts-retail-campaigns .uk-container').evaluate(node => node.getBoundingClientRect().width);
      expect(containerWidth / viewport.width).toBeGreaterThanOrEqual(0.74);
      const productNameSize = await page.locator('.pts-product-family-grid .product-card .caption .name').first().evaluate(node => parseFloat(getComputedStyle(node).fontSize));
      expect(productNameSize).toBeGreaterThanOrEqual(15);
    }

    await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });
    const dealerGridColumns = await page.locator('.pts-dl-hero-grid').evaluate(grid => getComputedStyle(grid).gridTemplateColumns.split(' ').length);
    expect(dealerGridColumns).toBe(viewport.width <= 991 ? 1 : 2);
    await expect(page.locator('#dang-ky-dai-ly form')).toBeVisible();

    await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#inf_contact_page h1')).toHaveCount(1);
    const contactColumns = await page.locator('.pts-contact-intro-row').evaluate(grid => getComputedStyle(grid).gridTemplateColumns.split(' ').length);
    expect(contactColumns).toBe(viewport.width <= 991 ? 1 : 2);
  });
}

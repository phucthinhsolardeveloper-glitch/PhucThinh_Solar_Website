const { test, expect } = require('@playwright/test');

const routes = [
  ['home', '/'],
  ['products', '/san-pham/'],
  ['solutions', '/giai-phap/'],
  ['projects', '/du-an-da-thuc-hien/'],
  ['news', '/tin-tuc/'],
  ['about', '/ve-chung-toi/'],
  ['contact', '/lien-he/']
];

for (const [name, path] of routes) {
  test(`${name} has no browser-critical rendering failures`, async ({ page, baseURL }) => {
    const pageErrors = [];
    const failedLocalResponses = [];
    const baseOrigin = new URL(baseURL).origin;

    page.on('pageerror', error => pageErrors.push(error.message));
    page.on('response', response => {
      const url = response.url();
      if (url.startsWith(baseOrigin) && response.status() >= 400) {
        failedLocalResponses.push(`${response.status()} ${url}`);
      }
    });

    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
    await expect(page.locator('body')).toBeVisible();

    await page.evaluate(async () => {
      const step = Math.max(400, Math.floor(window.innerHeight * 0.8));
      for (let position = 0; position < document.body.scrollHeight; position += step) {
        window.scrollTo(0, position);
        await new Promise(resolve => setTimeout(resolve, 25));
      }
      window.scrollTo(0, 0);
    });

    const brokenImages = await page.locator('img').evaluateAll(images => images
      .filter(image => image.currentSrc && image.complete && image.naturalWidth === 0)
      .map(image => image.currentSrc));
    const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

    expect(pageErrors).toEqual([]);
    expect(failedLocalResponses).toEqual([]);
    expect(brokenImages).toEqual([]);
    expect(horizontalOverflow).toBeLessThanOrEqual(1);
  });
}

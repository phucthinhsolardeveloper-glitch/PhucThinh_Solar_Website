const { test, expect } = require('@playwright/test');

test('public routes reuse one JavaScript bundle without runtime errors', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());

  const bundleUrls = [];
  for (const route of ['/', '/danh-muc-san-pham/bien-tan-inverter/hoa-luoi/', '/du-an/']) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
    expect(response.status(), route).toBe(200);
    const bundle = await page.locator('script[src*="/assets/caches/"][src$=".js"], script[src*="/assets/caches/"][src*=".js?v="]').evaluateAll(scripts =>
      scripts.map(script => script.src).filter(src => /\/assets\/caches\/[^/]+\.js(?:\?|$)/.test(src))
    );
    expect(bundle, route).toHaveLength(1);
    bundleUrls.push(bundle[0]);
  }

  expect(new Set(bundleUrls).size).toBe(1);
  const bundleSource = await page.evaluate(async url => (await fetch(url)).text(), bundleUrls[0]);
  expect(bundleSource.includes('Bootstrap v4.0.0')).toBe(false);
  expect(await page.evaluate(() => window.jQuery.fn.modal.Constructor.VERSION)).toBe('4.6.0');
  expect(await page.evaluate(() => typeof window.$JssorSlider$)).toBe('undefined');
  expect(errors).toEqual([]);
});

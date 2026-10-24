const { test, expect } = require('@playwright/test');

const viewports = [
  { name: '390 mobile', width: 390, height: 844, mobile: true },
  { name: '768 tablet', width: 768, height: 900, mobile: true },
  { name: '1440 desktop', width: 1440, height: 900, mobile: false },
  { name: '1920 desktop', width: 1920, height: 1080, mobile: false },
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

for (const viewport of viewports) {
  test(`dealer page keeps a clear preview-to-form journey at ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    const response = await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();

    const hub = page.locator('.pts-dealer-hub');
    const overview = hub.locator('.pts-dl-confidence-layout');
    const formCard = hub.locator('.pts-dl-form-card');
    const form = formCard.locator('form');
    await expect(hub.locator('h1')).toHaveCount(1);
    await expect(form).toBeVisible();
    await expect(hub.locator('.pts-dl-overview-cta')).toHaveAttribute('href', /#dang-ky-dai-ly$/);
    await expect(hub.locator('.pts-dl-product-card').first()).toBeVisible();
    await expect(hub.locator('.pts-dl-support')).toBeVisible();

    const layout = await overview.evaluate(element => {
      const formBox = element.querySelector('.pts-dl-form-card').getBoundingClientRect();
      const introBox = element.querySelector('.pts-dl-hero-copy').getBoundingClientRect();
      const productGrid = element.querySelector('.pts-dl-product-grid');
      const productBox = productGrid ? productGrid.getBoundingClientRect() : null;
      return {
        formTop: formBox.top + scrollY,
        introTop: introBox.top + scrollY,
        productBottom: productBox ? productBox.bottom + scrollY : introBox.bottom + scrollY,
        formPosition: getComputedStyle(element.querySelector('.pts-dl-form-card')).position,
        supportNested: Boolean(element.querySelector('.pts-dl-support')),
      };
    });

    expect(layout.supportNested).toBe(false);
    if (viewport.mobile) {
      expect(layout.formTop).toBeLessThan(1200);
      expect(layout.formTop).toBeLessThan(layout.introTop);
      const previews = hub.locator('.pts-dl-mobile-preview-item');
      await expect(previews.first()).toBeVisible();
      expect(await previews.count()).toBeGreaterThanOrEqual(1);
      const previewBottom = await previews.last().evaluate(el => el.getBoundingClientRect().bottom + scrollY);
      expect(previewBottom).toBeLessThanOrEqual(layout.formTop);
    } else {
      await expect(hub.locator('.pts-dl-mobile-preview')).toBeHidden();
      expect(layout.formPosition).toBe('sticky');
      expect(Math.abs(layout.formTop - layout.introTop)).toBeLessThanOrEqual(2);
    }

    await expect(page.locator('.pts-dl-quick-nav a')).toHaveText([
      'Sản phẩm phân phối', 'Quyền lợi đại lý', 'Đăng ký hợp tác'
    ]);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
  });
}

test('dealer registration form contract remains unchanged', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });

  const form = page.locator('#dang-ky-dai-ly form');
  await expect(form).toHaveCount(1);
  await expect(form.locator('input[name="form_csrf"]')).toHaveCount(1);
  await expect(form.locator('input[name="recaptcha_response_newsletter2"]')).toHaveCount(1);
  await expect(form.locator('input[name="dataNewsletter[fullname]"]')).toHaveCount(1);
  await expect(form.locator('input[name="dataNewsletter[phone]"]')).toHaveCount(1);
  await expect(form.locator('input[name="dataNewsletter[email]"]')).toHaveCount(1);
  await expect(form.locator('select[name="dataNewsletter[address]"]')).toHaveCount(1);
  await expect(form.locator('input[name="dataNewsletter[company]"]')).toHaveCount(1);
  await expect(form.locator('button[name="submit-newsletter2"]')).toHaveCount(1);
  await expect(form.locator('input[name="form_csrf"]')).not.toHaveValue('');
});

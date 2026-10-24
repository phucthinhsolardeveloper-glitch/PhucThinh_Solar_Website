const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('dealer mobile keeps registration reachable before lengthy information', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  const response = await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  await expect(page.locator('#dang-ky-dai-ly form')).toBeVisible();
  const position = await page.locator('#dang-ky-dai-ly').evaluate(node => {
    const rect = node.getBoundingClientRect();
    return { top: rect.top + scrollY, width: rect.width };
  });
  expect(position.top).toBeLessThan(1200);
  expect(position.width).toBeGreaterThan(300);
  await expect(page.locator('.pts-dl-quick-nav a')).toHaveText([
    'Sản phẩm phân phối', 'Quyền lợi đại lý', 'Đăng ký hợp tác'
  ]);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('dealer quick navigation updates the URL and scrolls to one target each', async ({ page }) => {
  await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });

  for (const [index, id] of ['nang-luc-phan-phoi', 'ho-tro-dai-ly', 'dang-ky-dai-ly'].entries()) {
    await page.locator('.pts-dl-quick-nav a').nth(index).click();
    await expect(page).toHaveURL(new RegExp(`/dai-ly/#${id}$`));
    await expect(page.locator(`#${id}`)).toHaveCount(1);
  }
});

test('dealer registration form contract remains unchanged', async ({ page }) => {
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

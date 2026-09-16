const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('Dealer page has a compact hero and a complete registration journey', async ({ page }, testInfo) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));

  const response = await page.goto('/dai-ly/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();

  const hub = page.locator('.pts-dealer-hub');
  await expect(hub).toBeVisible();
  await expect(hub.locator('h1')).toHaveCount(1);
  await expect(hub.locator('.pts-dl-eyebrow')).toHaveCount(0);
  expect(await hub.locator('section .section-title h2').count()).toBeGreaterThanOrEqual(2);

  const layout = await page.evaluate(() => {
    const breadcrumb = document.querySelector('.pts-breadcrumb-bar').getBoundingClientRect();
    const banner = document.querySelector('.pts-dl-hero-banner').getBoundingClientRect();
    return { gap: banner.top - breadcrumb.bottom, bannerHeight: banner.height };
  });
  expect(layout.gap).toBeLessThanOrEqual(2);
  expect(layout.bannerHeight).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 230 : 290);

  const form = hub.locator('#dang-ky-dai-ly form');
  await expect(form).toBeVisible();
  await expect(form.locator('label')).toHaveCount(5);
  await expect(form.locator('[name="dataNewsletter[fullname]"]')).toHaveAttribute('autocomplete', 'name');
  await expect(form.locator('[name="dataNewsletter[phone]"]')).toHaveAttribute('autocomplete', 'tel');
  await expect(form.locator('[name="dataNewsletter[email]"]')).toHaveAttribute('autocomplete', 'email');
  await expect(form.locator('[name="dataNewsletter[address]"]')).toHaveAttribute('required', '');
  await expect(form.locator('[name="dataNewsletter[company]"]')).toHaveAttribute('autocomplete', 'organization');
  await expect(form.locator('[name="form_csrf"]')).not.toHaveValue('');
  await expect(hub.locator('.pts-dl-form-card')).toHaveCSS('background-image', /linear-gradient/);

  const dealerHeroOrder = await hub.locator('.pts-dl-hero-grid').evaluate(grid => {
    const description = grid.querySelector('.pts-dl-hero-desc').getBoundingClientRect();
    const formCard = grid.querySelector('.pts-dl-form-card').getBoundingClientRect();
    return { descriptionTop: description.top, formTop: formCard.top };
  });
  if (testInfo.project.name.includes('mobile')) {
    expect(dealerHeroOrder.formTop).toBeLessThan(dealerHeroOrder.descriptionTop);
  } else {
    expect(Math.abs(dealerHeroOrder.formTop - dealerHeroOrder.descriptionTop)).toBeLessThanOrEqual(2);
  }

  for (const anchor of ['#dang-ky-dai-ly', '#nang-luc-phan-phoi', '#ho-tro-dai-ly']) {
    await expect(page.locator(anchor)).toHaveCount(1);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  expect(pageErrors).toEqual([]);
});

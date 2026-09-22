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

  const quickLinks = hub.locator('.pts-dl-quick-nav a');
  await expect(quickLinks).toHaveCount(3);
  await expect(quickLinks.nth(0)).toHaveAttribute('href', '#dang-ky-dai-ly');
  await expect(quickLinks.nth(1)).toHaveAttribute('href', '#nang-luc-phan-phoi');
  await expect(quickLinks.nth(2)).toHaveAttribute('href', '#ho-tro-dai-ly');
  await expect(hub.locator('.pts-dl-support')).toHaveCSS('background-color', 'rgb(6, 21, 55)');
  await expect(hub.locator('.pts-dl-support .section-title h2')).toHaveCSS('color', 'rgb(255, 255, 255)');

  const dealerOverview = await hub.locator('.pts-dl-confidence-layout').evaluate(layout => {
    const description = layout.querySelector('.pts-dl-hero-desc').getBoundingClientRect();
    const formCard = layout.querySelector('.pts-dl-form-card').getBoundingClientRect();
    return { descriptionTop: description.top, formTop: formCard.top };
  });
  if (testInfo.project.name.includes('mobile')) {
    expect(dealerOverview.formTop).toBeLessThanOrEqual(dealerOverview.descriptionTop);
  } else {
    expect(Math.abs(dealerOverview.formTop - dealerOverview.descriptionTop)).toBeLessThanOrEqual(2);
  }

  for (const anchor of ['#dang-ky-dai-ly', '#nang-luc-phan-phoi', '#ho-tro-dai-ly']) {
    await expect(page.locator(anchor)).toHaveCount(1);
  }

  const footerJoin = await page.evaluate(() => {
    const dealer = document.querySelector('.pts-dealer-hub').getBoundingClientRect();
    const footer = document.querySelector('footer').getBoundingClientRect();
    return footer.top - dealer.bottom;
  });
  expect(Math.abs(footerJoin)).toBeLessThanOrEqual(1);

  if (!testInfo.project.name.includes('mobile')) {
    await page.evaluate(async () => {
      for (let y = 0; y < document.body.scrollHeight; y += 650) {
        window.scrollTo(0, y);
        await new Promise(resolve => setTimeout(resolve, 60));
      }
      window.scrollTo(0, 0);
    });
    await page.screenshot({ path: testInfo.outputPath('dealer-redesign.png'), fullPage: true });
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  expect(pageErrors).toEqual([]);
});

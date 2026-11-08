#!/usr/bin/env node
// Follows an ad landing to a product consultation. The form POST is intercepted in the browser;
// no lead, email or external request is created.
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://127.0.0.1:18085';

(async () => {
  const catalog = await (await fetch(`${base}/_api/catalog?per_page=1`)).json();
  const product = catalog.products?.[0];
  if (!product?.url) throw new Error('No product route to inspect');

  const browser = await chromium.launch();
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
    let captured;
    await page.route('**/api/v1/lead-form', async (route) => {
      captured = route.request().postDataJSON();
      await route.fulfill({ status: 200, contentType: 'application/json', body: JSON.stringify({ ok: true, message: 'Đã nhận thử' }) });
    });

    await page.goto(`${base}/?utm_source=google&utm_campaign=solar2026`, { waitUntil: 'networkidle' });
    const stored = await page.evaluate(() => sessionStorage.getItem('pts-lead-campaign'));
    await page.goto(base + product.url, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: 'Nhận tư vấn', exact: true }).first().click();
    const dialog = page.getByRole('dialog');
    await dialog.locator('input[name=fullname]').fill('Khách kiểm thử');
    await dialog.locator('input[name=phone]').fill('0912345678');
    await dialog.getByRole('button', { name: 'Gửi yêu cầu tư vấn' }).click();
    await dialog.getByText('Đã tiếp nhận yêu cầu').waitFor();

    const expected = `${product.url}?utm_source=google&utm_campaign=solar2026`;
    const result = { campaignStored: stored === 'utm_source=google&utm_campaign=solar2026', productUrl: product.url,
      sourceUrl: captured?.data?.source_url || null, requestIntercepted: Boolean(captured) };
    console.log(JSON.stringify(result, null, 2));
    if (!result.campaignStored || !result.requestIntercepted || result.sourceUrl !== expected) process.exitCode = 1;
  } finally {
    await browser.close();
  }
})().catch((error) => { console.error(error.message); process.exitCode = 1; });

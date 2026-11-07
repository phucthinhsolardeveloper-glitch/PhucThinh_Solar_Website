#!/usr/bin/env node
// Walks the buying path in a real browser: product page -> add to cart -> cart -> checkout form.
// It checks the session cookie keeps the cart between pages. It never submits the order form.
//
//   AUDIT_BASE=http://127.0.0.1:3110 node audit/shop-flow.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 412, height: 900 } });
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  const problems = [];
  page.on('response', (response) => { if (response.status() >= 500) problems.push(`${response.status()} ${response.url()}`); });

  const catalog = await (await fetch(`${base}/_api/catalog?per_page=48`)).json();
  const product = catalog.products.find((item) => item.pricing.mode === 'price');
  if (!product) throw new Error('No priced product to buy');
  await page.goto(`${base}${product.url}`, { waitUntil: 'networkidle' });
  await page.getByRole('button', { name: 'Thêm vào giỏ' }).first().click();
  await page.waitForTimeout(1500);

  await page.goto(`${base}/gio-hang/`, { waitUntil: 'networkidle' });
  const lines = await page.locator('main').getByText(product.name, { exact: false }).count();
  const cookies = (await page.context().cookies()).map((cookie) => cookie.name);
  await page.goto(`${base}/thanh-toan/`, { waitUntil: 'networkidle' });
  const form = page.locator('form[action="/api/v1/checkout"]');
  const fields = await form.locator('input[name="checkout_token"]').count();

  const result = { product: product.url, cartShowsProduct: lines > 0, sessionCookie: cookies.includes('pts_session'), checkoutFormToApi: fields === 1, serverErrors: problems };
  console.log(JSON.stringify(result, null, 2));
  await browser.close();
  process.exit(result.cartShowsProduct && result.sessionCookie && result.checkoutFormToApi && !problems.length ? 0 : 1);
})();

#!/usr/bin/env node
// Consent choice, a contact lead and a full checkout in a real browser, ending on the GA4 events they push.
// It SAVES a lead and an order, so it only runs against a local or .test address with its own database.
//
// Consent defaults are written only on the canonical host with a GTM id configured. To check them locally:
// run Nuxt with NUXT_PUBLIC_CANONICAL_SITE_ORIGIN=http://qa.pts.test:3130, set a made-up GTM id in the test
// database and map the host:
//
//   AUDIT_BASE=http://qa.pts.test:3130 AUDIT_HOST_MAP="MAP qa.pts.test 127.0.0.1" node audit/consent-lead-purchase.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';
const host = new URL(base).hostname;
if (!(host === 'localhost' || host === '127.0.0.1' || host.endsWith('.test'))) {
  console.error(`Refusing to run against ${host}: this script saves a lead and an order.`);
  process.exit(2);
}

const customer = { fullname: 'Khach Kiem Thu QA', phone: '+84 912.345 678', email: 'qa-order@example.test', address: '1 Duong Kiem Thu' };
const secrets = [customer.fullname, customer.phone, '0912345678', customer.email, customer.address];

(async () => {
  const browser = await chromium.launch({ args: process.env.AUDIT_HOST_MAP ? [`--host-resolver-rules=${process.env.AUDIT_HOST_MAP}`] : [] });
  const checks = {};
  const notes = {};
  const serverErrors = [];

  async function visitor() {
    const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
    // Nothing leaves the machine: analytics and translation hosts are cut off.
    await context.route(/^https?:\/\/([a-z0-9-]+\.)*(googletagmanager\.com|google-analytics\.com|googleadservices\.com|doubleclick\.net|gtranslate\.net|google\.com)\//, (route) => route.abort());
    const page = await context.newPage();
    page.on('response', (response) => { if (response.status() >= 500) serverErrors.push(`${response.status()} ${response.url()}`); });
    return { context, page };
  }
  const open = (page, path) => page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
  // dataLayer holds both plain objects and gtag "arguments" objects.
  const layer = (page) => page.evaluate(() => (window.dataLayer || []).map((entry) => {
    if (entry && typeof entry.length === 'number' && entry[0] !== undefined) return { gtag: Array.from(entry) };
    return JSON.parse(JSON.stringify(entry));
  }));
  const consent = (entries, kind) => entries.filter((e) => e.gtag && e.gtag[0] === 'consent' && e.gtag[1] === kind).map((e) => e.gtag[2].analytics_storage + '/' + e.gtag[2].ad_storage);
  const named = (entries, name) => entries.filter((e) => e.event === name);
  const stored = async (page, context) => ({
    storage: await page.evaluate(() => localStorage.getItem('pts-consent')),
    cookie: (await context.cookies()).find((c) => c.name === 'pts_consent')?.value ?? null,
  });

  // 1. Consent: denied until chosen; each choice survives a reload and the notice stays away.
  for (const [button, value] of [['Từ chối', 'denied'], ['Đồng ý', 'granted']]) {
    const { context, page } = await visitor();
    await open(page, '/lien-he/');
    const notice = page.getByRole('region', { name: 'Thông báo cookie' });
    await notice.waitFor({ timeout: 8000 });
    const before = consent(await layer(page), 'default');
    await notice.getByRole('button', { name: button }).click();
    const updated = consent(await layer(page), 'update');
    const saved = await stored(page, context);
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(2200);
    const after = consent(await layer(page), 'default');
    const shownAgain = await notice.count();
    await page.evaluate(() => localStorage.removeItem('pts-consent'));
    await page.reload({ waitUntil: 'networkidle' });
    const cookieOnly = consent(await layer(page), 'default');
    notes[`consent_${value}`] = { before, updated, saved, after, cookieOnly };
    checks[`consent_${value}`] = before[0] === 'denied/denied' && updated[0] === `${value}/${value}` && saved.storage === value && saved.cookie === value
      && after[0] === `${value}/${value}` && cookieOnly[0] === `${value}/${value}` && shownAgain === 0;
    await context.close();
  }

  const { context, page } = await visitor();

  // 2. A contact lead through the real API: one generate_lead, nothing the visitor typed in it.
  await open(page, '/lien-he/?gclid=qa-click-id');
  await page.getByRole('region', { name: 'Thông báo cookie' }).getByRole('button', { name: 'Từ chối' }).click();
  const form = page.locator('main form').filter({ has: page.locator('input[name="phone"]') }).first();
  await form.locator('input[name="fullname"]').fill(customer.fullname);
  await form.locator('input[name="phone"]').fill(customer.phone);
  const answer = page.waitForResponse((r) => r.url().includes('/api/v1/lead-form') && r.request().method() === 'POST');
  await form.locator('button[type="submit"]').click();
  const leadStatus = (await answer).status();
  await page.waitForTimeout(1000);
  const afterLead = await layer(page);
  const leadEvents = named(afterLead, 'generate_lead');
  const funnel = afterLead.filter((entry) => /^(lead_form_|generate_lead$)/.test(entry.event || ''));
  notes.lead = { status: leadStatus, events: leadEvents, funnel: funnel.map((entry) => entry.event) };
  checks.leadFunnel = notes.lead.funnel.join() === 'lead_form_start,lead_form_submit,generate_lead' && !secrets.some((secret) => JSON.stringify(funnel).includes(secret));
  checks.lead = leadStatus === 200 && leadEvents.length === 1 && leadEvents[0].form_source === 'contact_page' && leadEvents[0].form_kind === 'contact'
    && !secrets.some((secret) => JSON.stringify(leadEvents).includes(secret));

  // 3. Buy one product and read the purchase event on the thank-you page.
  const catalog = await page.evaluate(async () => (await fetch('/_api/catalog?per_page=48')).json());
  const product = catalog.products.find((item) => item.pricing.mode === 'price');
  await open(page, product.url);
  await page.getByRole('button', { name: 'Thêm vào giỏ' }).first().click();
  await page.waitForTimeout(1500);
  await open(page, '/thanh-toan/');
  for (const [key, value] of Object.entries(customer)) await page.locator(`[name="dataOrder[${key}]"]`).fill(value);
  const city = page.locator('select[name="dataOrder[city]"]');
  await city.selectOption({ index: 1 });
  const ward = page.locator('select[name="dataOrder[ward]"]');
  await page.waitForFunction(() => document.querySelector('select[name="dataOrder[ward]"]')?.options.length > 1);
  await ward.selectOption({ index: 1 });
  // Bank transfer or cash; the online gateway would leave the site.
  const payments = page.locator('input[name="dataOrder[payments]"]');
  await payments.first().check({ force: true });
  await Promise.all([
    page.waitForURL(/\/cam-on\//, { timeout: 30000 }),
    page.locator('form[action="/api/v1/checkout"] button[type="submit"]:visible').first().click(),
  ]);
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  const bought = await layer(page);
  const purchases = named(bought, 'purchase');
  const success = named(bought, 'order_success');
  const token = new URL(page.url()).searchParams.get('token');
  const thanks = await page.evaluate(async (t) => (await fetch(`/_api/order-thanks?token=${t}`)).json(), token);
  const ecommerce = purchases[0]?.ecommerce ?? {};
  const goods = (ecommerce.items ?? []).reduce((sum, item) => sum + item.price * item.quantity, 0);
  notes.purchase = { value: ecommerce.value, shipping: ecommerce.shipping, goods, orderValue: success[0]?.order_value, order: thanks.order && { subtotal: thanks.order.subtotal, shippingFee: thanks.order.shippingFee, totalPrice: thanks.order.totalPrice } };
  checks.purchaseOnce = purchases.length === 1 && success.length === 1 && bought.filter((e) => e.gtag && e.gtag[1] === 'purchase').length === 1;
  checks.purchaseValueIsGoods = ecommerce.value === thanks.order.subtotal && ecommerce.shipping === thanks.order.shippingFee && goods === ecommerce.value
    && success[0]?.order_value === thanks.order.totalPrice && thanks.order.subtotal + thanks.order.shippingFee === thanks.order.totalPrice;
  checks.purchaseHasNoCustomerData = !secrets.some((secret) => JSON.stringify(bought).includes(secret));
  await page.reload({ waitUntil: 'networkidle' });
  await page.waitForTimeout(1500);
  checks.reloadSendsNoSecondPurchase = named(await layer(page), 'purchase').length === 0;

  // 4. Estimator: nothing for the figures shown on load, one event once the visitor changes something.
  const estimator = await open(page, '/uoc-tinh-dien-mat-troi/');
  if (estimator.status() === 200) {
    await page.waitForTimeout(2000);
    const onLoad = named(await layer(page), 'estimator_result').length;
    for (const bill of ['3000000', '5000000']) {
      await page.locator('.est-money input').fill(bill);
      await page.waitForTimeout(1500);
    }
    const events = named(await layer(page), 'estimator_result');
    notes.estimator = { onLoad, events };
    checks.estimator = onLoad === 0 && events.length === 1 && !JSON.stringify(events).match(/3000000|5000000|bill/);
  } else {
    notes.estimator = `NOT RUN: page answered ${estimator.status()} (tool switched off)`;
  }

  // 5. List addresses: refused filters and pages past the end are 404; real and campaign addresses stay 200.
  // Fetched inside the page: only the browser knows a mapped .test host. A redirect reads as 0.
  const status = (path) => page.evaluate(async (p) => (await fetch(p, { redirect: 'manual', headers: { accept: 'text/html' } })).status, path);
  const brandPath = process.env.AUDIT_BRAND_PATH || '/thuong-hieu/solax/';
  const expected = {
    '/san-pham/': 200, '/san-pham/?page=2': 200, '/san-pham/?utm_source=qa&gclid=1&fbclid=2': 200, '/san-pham/?sort=unknown': 200,
    '/san-pham/?brand=abc': 404, '/san-pham/?price=9': 404, '/san-pham/?page=999': 404, '/san-pham/?page=0': 200, '/san-pham/?need=du-an-cong-trinh,du-an-cong-trinh': 404,
    [brandPath]: 200, [`${brandPath}?page=999`]: 404, [`${brandPath}?price=9`]: 404, [`${brandPath}?utm_source=qa`]: 200,
  };
  const actual = {};
  for (const path of Object.keys(expected)) actual[path] = await status(path);
  notes.statuses = actual;
  checks.statuses = Object.entries(expected).every(([path, code]) => actual[path] === code);

  checks.noServerErrors = serverErrors.length === 0;
  console.log(JSON.stringify({ notes, serverErrors }, null, 2));
  console.log(JSON.stringify(checks));
  await browser.close();
  process.exit(Object.values(checks).every(Boolean) ? 0 : 1);
})().catch((error) => { console.error(error); process.exit(1); });

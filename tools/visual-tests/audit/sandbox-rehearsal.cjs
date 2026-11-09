#!/usr/bin/env node
// Buys on the candidate stack with the VNPay simulator (tools/sandbox-sim): success, success reported twice,
// failure, cancel. Then reads Mailpit to see which mails the worker really sent over SMTP.
// Only for the local rehearsal stack with docker-compose.sim.yml. Orders it creates start with the name
// "Khách thử SIM" and are listed at the end so they can be removed.
// The shop accepts 5 orders per IP in 10 minutes (anti-spam), so run it at most once per 10 minutes.
//
//   AUDIT_BASE=http://127.0.0.1:18085 SIM=http://127.0.0.1:18086 MAILPIT=http://127.0.0.1:18025 node audit/sandbox-rehearsal.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://127.0.0.1:18085';
const sim = process.env.SIM || 'http://127.0.0.1:18086';
const mailpit = process.env.MAILPIT || 'http://127.0.0.1:18025';
if (!/^http:\/\/(127\.0\.0\.1|localhost):/.test(base)) throw new Error('Local candidate only');

async function buy(browser, choice, label) {
  const page = await browser.newPage({ viewport: { width: 412, height: 900 } });
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  const catalog = await (await fetch(`${base}/_api/catalog?per_page=48`)).json();
  const product = catalog.products.find((item) => item.pricing.mode === 'price');
  await page.goto(`${base}${product.url}`, { waitUntil: 'networkidle' });
  await page.getByRole('button', { name: 'Thêm vào giỏ' }).first().click();
  await page.waitForTimeout(1200);
  await page.goto(`${base}/thanh-toan/`, { waitUntil: 'networkidle' });
  await page.fill('input[name="dataOrder[fullname]"]', 'Khách thử SIM');
  await page.fill('input[name="dataOrder[phone]"]', '0900000000');
  await page.fill('input[name="dataOrder[email]"]', 'khach-thu-sim@example.invalid');
  const city = page.locator('select[name="dataOrder[city]"]');
  await city.selectOption({ index: 1 });
  const ward = page.locator('select[name="dataOrder[ward]"]');
  await ward.locator('option:nth-child(2)').waitFor({ state: 'attached', timeout: 15000 });
  await ward.selectOption({ index: 1 });
  await page.fill('input[name="dataOrder[address]"]', '1 Đường Thử');
  await page.locator('label.checkout-pay__option', { hasText: 'VNPay' }).click();
  await page.waitForTimeout(800);
  await Promise.all([page.waitForURL((url) => url.href.startsWith(sim), { timeout: 30000 }), page.locator('button[name="thanhtoan"]').last().click()]);
  const reference = new URL(page.url()).searchParams.get('vnp_TxnRef');
  await Promise.all([page.waitForURL((url) => url.href.startsWith(base), { timeout: 30000 }), page.getByText(label).click()]);
  await page.waitForLoadState('networkidle');
  const result = { choice, order: reference, landed: new URL(page.url()).pathname, heading: (await page.locator('h1').first().textContent().catch(() => ''))?.trim() };
  await page.close();
  return result;
}

(async () => {
  const browser = await chromium.launch();
  const runs = [];
  for (const [choice, label] of [['00', 'Thanh toán thành công'], ['00x2', 'Thành công, VNPay báo về 2 lần'], ['51', 'Thất bại: tài khoản không đủ số dư'], ['24', 'Khách hủy giao dịch']]) {
    runs.push(await buy(browser, choice, label).catch((error) => ({ choice, error: error.message.split('\n')[0] })));
  }
  await browser.close();
  await new Promise((resolve) => setTimeout(resolve, 8000)); // worker sends queued mail
  const simLog = await (await fetch(`${sim}/_sim/log`)).json();
  const messages = (await (await fetch(`${mailpit}/api/v1/messages?limit=50`)).json()).messages || [];
  for (const run of runs) {
    run.ipn = simLog.filter((entry) => entry.path === 'ipn' && entry.ref === run.order).map((entry) => entry.answer);
    run.mails = messages.filter((message) => (message.Subject || '').includes(run.order || '#none')).map((message) => `${message.To.map((to) => to.Address).join(',')}: ${message.Subject}`);
  }
  console.log(JSON.stringify(runs, null, 2));
})();

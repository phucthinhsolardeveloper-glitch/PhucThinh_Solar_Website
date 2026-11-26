#!/usr/bin/env node
// Lead forms and the checkout phone field in a real browser. Every lead POST is answered by this script,
// so nothing is saved or mailed; the checkout form is filled but never submitted.
//
//   AUDIT_BASE=http://127.0.0.1:3111 node audit/lead-form-errors.cjs
const { chromium } = require('playwright');

const base = process.env.AUDIT_BASE || 'http://localhost:3100';

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  let script = [];
  const posts = [];
  await page.route('**/api/v1/lead-form', (route) => {
    posts.push(JSON.parse(route.request().postData()));
    const [status, body] = script.shift() || [500, {}];
    return route.fulfill({ status, contentType: 'application/json', body: JSON.stringify(body) });
  });
  const expired = [419, { error: { code: 'INVALID_SESSION', message: 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.' } }];
  const ok = [200, { ok: true, message: 'Gửi yêu cầu tư vấn thành công.' }];
  const results = {};

  async function send(name, answers) {
    script = [...answers];
    posts.length = 0;
    await page.goto(`${base}/lien-he/`, { waitUntil: 'networkidle' });
    const form = page.locator('main form').filter({ has: page.locator('input[name="phone"]') }).first();
    await form.locator('input[name="fullname"]').fill('Khách kiểm thử');
    await form.locator('input[name="phone"]').fill('+84 944.555 957');
    await form.locator('button[type="submit"]').click();
    await page.waitForTimeout(2500);
    const alerts = (await form.getByRole('alert').allInnerTexts()).join(' | ');
    const successShown = await page.getByText('Gửi yêu cầu tư vấn thành công.').count() > 0;
    const events = await page.evaluate(() => (window.dataLayer || []).filter((entry) => entry && entry.event === 'generate_lead').map((entry) => ({ ...entry })));
    // The enquiry funnel as GA4 gets it: started, submitted, then one outcome. Nothing typed may appear in it.
    const layer = await page.evaluate(() => (window.dataLayer || []).filter((entry) => entry && /^(lead_form_|generate_lead$)/.test(entry.event || '')).map((entry) => ({ ...entry })));
    const funnel = layer.map((entry) => entry.event + (entry.error_type ? `:${entry.error_type}` : ''));
    const leaked = /Khách kiểm thử|0944|0912/.test(JSON.stringify(layer));
    results[name] = { posts: posts.length, alerts, successShown, events, funnel, leaked };
  }

  await send('expiredThenOk', [expired, ok]);
  await send('expiredTwice', [expired, expired, ok]);
  await send('invalid', [[422, { error: { code: 'VALIDATION_FAILED', message: 'Số điện thoại không hợp lệ.' } }]]);
  await send('rateLimitedWithoutBody', [[429, {}]]);

  // What the server never sees: a tapped choice, then a send the browser refuses, then a valid send.
  const funnelOf = () => page.evaluate(() => (window.dataLayer || []).filter((e) => e && /^(lead_form_|generate_lead$)/.test(e.event || ''))
    .map((e) => e.event + (e.error_type ? `:${e.error_type}` : '')))
  posts.length = 0;
  await page.goto(`${base}/lien-he/`, { waitUntil: 'networkidle' });
  // The cookie notice slides in over the lower screen; answer it first or a tap lands on it.
  await page.getByRole('region', { name: 'Thông báo cookie' }).getByRole('button', { name: 'Từ chối' }).click({ timeout: 6000 }).catch(() => {});
  const contactForm = page.locator('main form[data-lead-form="contact_page"]');
  // A button left at the very bottom edge sits under the bar that returns when the keyboard closes; tap it mid-screen.
  const sendButton = async () => {
    const button = contactForm.locator('button[type="submit"]');
    await button.evaluate((el) => el.scrollIntoView({ block: 'center' }));
    await page.waitForTimeout(300);
    await button.click();
  };
  await contactForm.locator('button[type="button"]').first().click();
  await page.waitForTimeout(300);
  const chipOnly = await funnelOf();
  await contactForm.locator('input[name="fullname"]').fill('Khách kiểm thử');
  await contactForm.locator('input[name="phone"]').fill('09abc');
  await sendButton();
  await page.waitForTimeout(500);
  const refusedSend = await funnelOf();
  const postsAfterRefused = posts.length;
  script = [ok];
  await contactForm.locator('input[name="phone"]').fill('0944 555 957');
  await sendButton();
  await page.waitForTimeout(1500);
  const afterValid = await funnelOf();
  const leaked = await page.evaluate(() => /Khách kiểm thử|0944|09abc/.test(JSON.stringify((window.dataLayer || []).filter((e) => e && /^(lead_form_|generate_lead$)/.test(e.event || '')))));
  results.choiceAndRefusal = { chipOnly, refusedSend, postsAfterRefused, afterValid, leaked };

  // The checkout form only exists with something in the cart (a local session; no order is sent).
  const catalog = await (await fetch(`${base}/_api/catalog?per_page=48`)).json();
  await page.goto(`${base}${catalog.products.find((item) => item.pricing.mode === 'price').url}`, { waitUntil: 'networkidle' });
  await page.getByRole('button', { name: 'Thêm vào giỏ' }).first().click();
  await page.waitForTimeout(1500);
  await page.goto(`${base}/thanh-toan/`, { waitUntil: 'networkidle' });
  const phone = page.locator('input[name="dataOrder[phone]"]').first();
  const checkout = {};
  if (await phone.count()) {
    for (const typed of ['0944 555 957', '+84944555957', '0944abc957', '094455595']) {
      await phone.fill(typed);
      await phone.blur();
      await page.waitForTimeout(200);
      checkout[typed] = await phone.evaluate((el) => el.closest('.is-valid') ? 'valid' : el.closest('.is-invalid') ? 'invalid' : 'unmarked');
    }
  }
  results.checkoutPhone = checkout;
  console.log(JSON.stringify(results, null, 2));
  await browser.close();
  // The page keeps short words together with non-breaking spaces.
  const says = (text, part) => text.normalize('NFC').replace(/\s+/g, ' ').includes(part.normalize('NFC'));
  const checks = {
    expiredRetriesOnce: results.expiredThenOk.posts === 2 && results.expiredThenOk.successShown && results.expiredThenOk.events.length === 1,
    secondExpiryStops: results.expiredTwice.posts === 2 && !results.expiredTwice.successShown && results.expiredTwice.events.length === 0,
    serverMessageShown: results.invalid.posts === 1 && says(results.invalid.alerts, 'Số điện thoại không hợp lệ'),
    rateLimitMessage: says(results.rateLimitedWithoutBody.alerts, 'quá nhanh'),
    funnel: Object.entries({ expiredThenOk: 'generate_lead', expiredTwice: 'lead_form_error:session', invalid: 'lead_form_error:validation', rateLimitedWithoutBody: 'lead_form_error:rate_limit' })
      .every(([name, outcome]) => results[name].funnel.join() === `lead_form_start,lead_form_submit,${outcome}` && !results[name].leaked),
    chipStartsForm: results.choiceAndRefusal.chipOnly.join() === 'lead_form_start',
    refusedSendCounted: results.choiceAndRefusal.refusedSend.join() === 'lead_form_start,lead_form_submit,lead_form_error:validation' && results.choiceAndRefusal.postsAfterRefused === 0,
    validSendAfterRefusal: results.choiceAndRefusal.afterValid.join() === 'lead_form_start,lead_form_submit,lead_form_error:validation,lead_form_submit,generate_lead' && !results.choiceAndRefusal.leaked,
    checkoutPhone: JSON.stringify(Object.values(checkout)) === JSON.stringify(['valid', 'valid', 'invalid', 'invalid']),
  };
  console.log(JSON.stringify(checks));
  const passed = Object.values(checks).every(Boolean);
  process.exit(passed ? 0 : 1);
})();

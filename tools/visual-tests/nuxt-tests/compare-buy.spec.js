const { test, expect } = require('@playwright/test');

// Mocked /_api/compare and /_api/cart: no order is created and no DB is written.
const product = (id, slug, pricing) => ({ id, name: `Biến tần ${id}`, slug, url: `/san-pham/${slug}`, code: 'EPV', brand: 'Epsivo', image: '', pricing });
const payload = {
  schemaVersion: 1, locale: 'vi', max: 4,
  products: [
    product(1, 'bien-tan-a', { mode: 'price', currency: 'VND', regularPrice: 1000000, salePrice: null }),
    product(2, 'bien-tan-b', { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null }),
  ],
  rows: [{ label: 'Công suất', values: ['2,2kW', '4kW'], same: false }],
};
const cart = { schemaVersion: 1, csrfToken: 'test-token', items: [], totals: {} };

test('compare table: priced product adds to cart in place, quote product opens consultation', async ({ page }) => {
  const adds = [];
  await page.route('**/_api/compare*', (route) => route.fulfill({ json: payload }));
  await page.route('**/_api/cart', (route) => {
    if (route.request().method() === 'POST') { adds.push(route.request().postDataJSON()); return route.fulfill({ json: { ok: true } }); }
    return route.fulfill({ json: cart });
  });
  await page.goto('/so-sanh-san-pham/?ids=1,2');
  const buy = page.getByRole('button', { name: 'Thêm vào giỏ' });
  await buy.click();
  await expect(page.getByRole('button', { name: 'Đã thêm vào giỏ' })).toBeVisible();
  expect(page.url()).toContain('/so-sanh-san-pham/');
  expect(adds[0]).toMatchObject({ action: 'add', productId: 1, quantity: 1 });
  await expect(page.getByRole('button', { name: 'Thêm vào giỏ' })).toHaveCount(0);
  await page.getByRole('button', { name: 'Nhận báo giá' }).click();
  expect(adds).toHaveLength(1);

  const remove = page.getByRole('button', { name: /Bỏ Biến tần 1 khỏi so sánh/ });
  const box = await remove.boundingBox();
  expect(box.width).toBeGreaterThanOrEqual(44);
  expect(box.height).toBeGreaterThanOrEqual(44);
});

test('compare table: cart failure shows an alert and does not claim success', async ({ page }) => {
  await page.route('**/_api/compare*', (route) => route.fulfill({ json: payload }));
  await page.route('**/_api/cart', (route) => route.request().method() === 'POST'
    ? route.fulfill({ status: 500, json: { message: 'fail' } }) : route.fulfill({ json: cart }));
  await page.goto('/so-sanh-san-pham/?ids=1,2');
  await page.getByRole('button', { name: 'Thêm vào giỏ' }).click();
  await expect(page.getByRole('alert')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Đã thêm vào giỏ' })).toHaveCount(0);
});

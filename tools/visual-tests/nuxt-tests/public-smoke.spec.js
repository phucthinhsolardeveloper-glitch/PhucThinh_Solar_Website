const { test, expect } = require('@playwright/test');

// Covers the Nuxt storefront. Read and session actions only: nothing here submits a form or places an order.
const PAGES = ['/', '/san-pham/', '/giai-phap/', '/gioi-thieu/', '/dai-ly/', '/lien-he/', '/tin-tuc/', '/du-an-da-thuc-hien/', '/gio-hang/'];

test.beforeEach(async ({ page }) => {
  await page.route('**/pts-living-energy/index.html*', (route) => route.abort());
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
});

function watchErrors(page) {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => { if (/hydrat/i.test(message.text())) errors.push(message.text()); });
  return errors;
}

const firstProductPath = async (page) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  return page.locator('article a[href*="/san-pham/"]').first().getAttribute('href');
};

async function addFirstProductToCart(page) {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const button = page.locator('article button:not(:disabled)', { hasText: /Thêm\s+giỏ\s+hàng|^$/ }).first();
  await button.click();
  await expect(page.locator('.cart-card')).toBeVisible({ timeout: 10000 });
}

test('main pages load with no errors, overflow or broken images', async ({ page }) => {
  for (const path of PAGES) {
    const errors = watchErrors(page);
    const response = await page.goto(path, { waitUntil: 'load' });
    expect(response.status(), path).toBe(200);
    await page.evaluate(async () => {
      for (let y = 0; y < document.documentElement.scrollHeight; y += 700) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 40)); }
      scrollTo(0, 0);
    });
    await page.waitForTimeout(300);
    const state = await page.evaluate(() => ({
      overflow: document.documentElement.scrollWidth - innerWidth,
      broken: [...document.images].filter((i) => i.complete && !i.naturalWidth && i.getBoundingClientRect().width).map((i) => i.src).slice(0, 3),
    }));
    expect(state.overflow, `${path} overflow`).toBeLessThanOrEqual(0);
    expect(state.broken, `${path} broken images`).toEqual([]);
    expect(errors, `${path} errors`).toEqual([]);
  }
});

test('unknown pages return 404 and the renamed solution URLs redirect permanently', async ({ page, request }) => {
  expect((await page.goto('/khong-ton-tai-abc/')).status()).toBe(404);
  for (const path of ['/thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh/', '/giai-phap/thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh/']) {
    const response = await request.get(path, { maxRedirects: 0 });
    expect(response.status(), path).toBe(301);
    expect(new URL(response.headers().location, 'http://local').pathname).toBe('/giai-phap/chieu-sang-nang-luong-mat-troi/');
  }
});

test('header search finds products and an empty search shows one message', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('header button[aria-label="Mở tìm kiếm"]').first().click();
  const input = page.locator('#ptsHeaderSearchInput');
  await input.fill('den');
  await input.press('Enter');
  await page.waitForURL(/\/tim-kiem\//);
  await expect(page.locator('article').first()).toBeVisible();

  await page.goto('/tim-kiem/?keyword=zzzzzz', { waitUntil: 'load' });
  await expect(page.getByText('Không tìm thấy sản phẩm phù hợp.')).toHaveCount(1);
  await expect(page.getByText('Chưa có sản phẩm trong danh mục này.')).toHaveCount(0);
});

test('search uses the catalog filter panel and an empty filtered result offers a way back', async ({ page, isMobile }) => {
  await page.goto('/tim-kiem/?keyword=Blue+Carbon&brand=50,51,52', { waitUntil: 'load' });
  await expect(page.getByRole('heading', { name: 'Không tìm thấy sản phẩm phù hợp.' })).toBeVisible();
  if (isMobile) {
    await page.getByRole('button', { name: 'Bộ lọc' }).click();
    await expect(page.locator('#search-filter-drawer')).toBeVisible();
    await page.getByRole('button', { name: 'Đóng bộ lọc' }).click();
  } else {
    await expect(page.getByRole('heading', { name: 'Đang áp dụng' })).toBeVisible();
  }
  await page.getByRole('link', { name: 'Bỏ bộ lọc', exact: true }).click();
  await page.waitForURL((url) => !url.search.includes('brand='));
  await expect(page.locator('article').first()).toBeVisible();
});

test('search suggests products while typing, works by keyboard and remembers the term', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('header button[aria-label="Mở tìm kiếm"]').first().click();
  const input = page.locator('#ptsHeaderSearchInput');
  await input.pressSequentially('den pha', { delay: 30 });
  const list = page.locator('#pts-header-suggest-list');
  await expect(list.locator('.sug-row').first()).toBeVisible();
  await expect(list.locator('.search-mark').first()).toBeVisible();
  await expect(list.locator('.sug-all')).toContainText('Xem tất cả');

  await input.press('ArrowDown');
  await expect(input).toHaveAttribute('aria-activedescendant', /pts-header-suggest-/);
  await input.press('Enter');
  await page.waitForURL((url) => !url.pathname.startsWith('/san-pham/') || url.pathname.length > '/san-pham/'.length);
  await page.waitForTimeout(400);

  await page.goto('/tim-kiem/?keyword=zzzzzz', { waitUntil: 'load' });
  await page.locator('input[name="keyword"]').click();
  await page.locator('input[name="keyword"]').fill('');
  await expect(page.locator('.sug-start')).toBeVisible();
});

test('two-syllable words and last words are kept together on a line', async ({ page }) => {
  await page.goto('/giai-phap/he-thong-luu-tru-bess/', { waitUntil: 'load' });
  await page.waitForTimeout(600);
  const result = await page.evaluate(() => {
    const text = document.querySelector('main, article').innerText;
    const blocks = [...document.querySelectorAll('main p, article p')].filter((el) => el.innerText.trim().split(/\s+/).length > 3 && el.getClientRects().length);
    return { kept: /gi[aả]i\u00A0pháp|năng\u00A0lượng/i.test(document.body.textContent), plain: text.length > 0, count: blocks.length };
  });
  expect(result.kept).toBe(true);
  expect(result.count).toBeGreaterThan(0);
  const source = await page.request.get('/giai-phap/he-thong-luu-tru-bess/').then((response) => response.text());
  expect(source).not.toContain('năng\u00A0lượng');
});

test('catalog pagination stays on one line', async ({ page }) => {
  await page.goto('/san-pham/?p=5', { waitUntil: 'load' });
  const nav = page.locator('nav[aria-label^="Phân trang"]').first();
  await nav.scrollIntoViewIfNeeded();
  const box = await nav.boundingBox();
  const viewport = page.viewportSize();
  expect(box.x + box.width).toBeLessThanOrEqual(viewport.width);
  expect(box.height).toBeLessThanOrEqual(60);
});

test('cart journey reaches checkout without placing an order', async ({ page, isMobile }) => {
  const errors = watchErrors(page);
  await addFirstProductToCart(page);
  await page.goto('/gio-hang/', { waitUntil: 'load' });
  await expect(page.getByText('Tổng tạm tính').first()).toBeVisible();
  if (isMobile) {
    await expect(page.locator('.dock')).toHaveCount(0);
    await expect(page.locator('.m-island')).toBeVisible();
    await page.locator('.m-island a').click();
  } else {
    await expect(page.locator('.m-island')).toBeHidden();
    await page.locator('a.cart-summary__cta').click();
  }
  await page.waitForURL(/\/thanh-toan\//);
  if (isMobile) await expect(page.locator('.checkout-bar')).toBeVisible();
  expect(errors).toEqual([]);
});

test('solutions chapter bar stays one line and uses short labels', async ({ page }) => {
  await page.goto('/giai-phap/', { waitUntil: 'load' });
  const bar = page.locator('.story-index');
  await bar.scrollIntoViewIfNeeded();
  expect((await bar.boundingBox()).height).toBeLessThanOrEqual(82);
  await expect(bar.getByText('Khảo sát', { exact: true })).toBeVisible();
});

test('consultation modal keeps every field inside the panel', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const quote = page.locator('article :is(a,button)', { hasText: 'Liên hệ báo giá' }).first();
  test.skip(await quote.count() === 0, 'No quote-only product in this dataset.');
  await quote.click();
  const dialog = page.locator('[role="dialog"]:has(form [name="fullname"])');
  await expect(dialog).toBeVisible();
  const outside = await dialog.evaluate((panel) => {
    const right = panel.getBoundingClientRect().right;
    return [...panel.querySelectorAll('form *')].filter((e) => { const r = e.getBoundingClientRect(); return r.width && r.right > right + 0.5; }).length;
  });
  expect(outside).toBe(0);
});

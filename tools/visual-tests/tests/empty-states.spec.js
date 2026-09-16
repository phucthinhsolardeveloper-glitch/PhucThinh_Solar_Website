const { test, expect } = require('@playwright/test');

// Keep the DOM in Vietnamese: the GTranslate widget rewrites nodes asynchronously and would race assertions.
test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

// Shared browser-health probe: page errors, failed local requests, broken images, horizontal overflow.
async function collectHealth(page, baseURL, action) {
  const pageErrors = [];
  const failedLocalResponses = [];
  const baseOrigin = new URL(baseURL).origin;
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('response', response => {
    const url = response.url();
    if (url.startsWith(baseOrigin) && response.status() >= 400) {
      failedLocalResponses.push(`${response.status()} ${url}`);
    }
  });

  await action();

  await page.evaluate(async () => {
    const step = Math.max(400, Math.floor(window.innerHeight * 0.8));
    for (let position = 0; position < document.body.scrollHeight; position += step) {
      window.scrollTo(0, position);
      await new Promise(resolve => setTimeout(resolve, 25));
    }
    window.scrollTo(0, 0);
  });

  const brokenImages = await page.locator('img').evaluateAll(images => images
    .filter(image => image.currentSrc && image.complete && image.naturalWidth === 0)
    .map(image => image.currentSrc));
  const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);

  expect(pageErrors).toEqual([]);
  expect(failedLocalResponses).toEqual([]);
  expect(brokenImages).toEqual([]);
  expect(horizontalOverflow).toBeLessThanOrEqual(1);
}

// response.ok() chỉ kiểm tra HTTP status - một PHP Fatal error vẫn trả 200
// (đã gặp thật với api/fillter_service.php khi public_watermark.php không
// được nạp ở bootstrap api/config.php, audit 2026-09-12). Đọc thẳng body để
// chặn lỗi PHP lọt qua mà vẫn báo test xanh.
async function assertHealthyAjaxResponse(responsePromise) {
  const response = await responsePromise;
  expect(response.ok()).toBeTruthy();
  const body = await response.text();
  expect(body).not.toMatch(/Fatal error|Warning:|Notice:|Parse error|Deprecated:/);
  return body;
}

test('empty cart page renders its empty state', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/gio-hang/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  const emptyCart = page.locator('.empty-cart');
  await expect(emptyCart).toBeVisible();
  await expect(emptyCart).toContainText('Chưa có sản phẩm');
  await expect(emptyCart.locator('i.fa-cart-plus')).toBeVisible();
  await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
  await expect(page.locator('footer')).toBeVisible();
});

test('entrance popup appears once per tab and resets in a new tab', async ({ page, context }) => {
  const firstResponse = await page.goto('/', { waitUntil: 'domcontentloaded' });
  expect(firstResponse && firstResponse.ok()).toBeTruthy();
  await expect(page.locator('#popup')).toBeVisible();

  const campaignKey = await page.locator('#popup').getAttribute('data-campaign-key');
  expect(campaignKey).toMatch(/^[a-f0-9]{20}$/);
  await page.reload({ waitUntil: 'domcontentloaded' });
  await expect(page.locator('#popup')).not.toBeVisible();

  const newTab = await context.newPage();
  const newTabResponse = await newTab.goto('/', { waitUntil: 'domcontentloaded' });
  expect(newTabResponse && newTabResponse.ok()).toBeTruthy();
  await expect(newTab.locator('#popup')).toBeVisible();
  await newTab.close();
});

test('search with no match shows the not-found notice', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/tim-kiem/?keyword=zzzznothingfound999', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toContainText('Không tìm thấy kết quả');
  await expect(page.locator('.product-item')).toHaveCount(0);
  await expect(page.locator('.breadCrumbs')).toBeVisible();
});

test('empty product category keeps navigation, filters and consultation entry points', async ({ page, baseURL }) => {
  // Trước đây bám vào "Đèn AC" vì danh mục đó vốn không có sản phẩm nào. Sau đợt
  // tái cấu trúc cây danh mục 2026-09-08, "Đèn AC" đã bị ẩn (không còn route),
  // và cả 10 danh mục còn hoạt động đều có sản phẩm. Ép rỗng bằng bộ lọc is_cat
  // trỏ tới một danh mục con không tồn tại, trên một danh mục cấp 1 còn thật —
  // vẫn đúng mục đích test là kiểm tra khung trang khi 0 kết quả.
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/danh-muc-san-pham/quat-nang-luong-mat-troi/?is_cat=999999', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toContainText('Không tìm thấy kết quả');
  await expect(page.locator('.product-item')).toHaveCount(0);
  await expect(page.locator('.filter_product')).toBeVisible();
  await expect(page.locator('.breadCrumbs')).toBeVisible();
  // Sidebar consult box đã bị bỏ vì trùng CTA (audit 2026-09-10); hotline
  // Header V3 là lối tư vấn dùng chung và vẫn phải tồn tại khi kết quả rỗng.
  await expect(page.locator('#headerscroll .hdr-call')).toHaveAttribute('href', /^tel:/);
});

test('search with matches lists product cards', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/tim-kiem/?keyword=ORIANA', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.alert-warning')).toHaveCount(0);
  expect(await page.locator('.product-item').count()).toBeGreaterThan(0);
  // Cấu hình Admin có thể bật giá công khai/giỏ hàng hoặc giữ chế độ báo giá.
  // Card phải đồng bộ với chính trạng thái đang được Header công bố.
  const commerceEnabled = await page.locator('#headerscroll .icon-cart-header').count() > 0;
  if (commerceEnabled) {
    expect(await page.locator('.product-card .addcart').count()).toBeGreaterThan(0);
  } else {
    await expect(page.locator('.product-card .addcart')).toHaveCount(0);
    expect(await page.locator('.product-card .pts-card-ask').count()).toBeGreaterThan(0);
  }

  const filteredResponse = page.waitForResponse(response => response.url().includes('/api/fillter_service.php?'));
  await page.locator('select[name="orderby"]').selectOption('new');
  const filteredBody = await assertHealthyAjaxResponse(filteredResponse);
  expect(filteredBody).toContain('product-item');
  const filteredUrl = new URL(page.url());
  expect(filteredUrl.pathname).toBe('/tim-kiem/');
  expect(filteredUrl.searchParams.get('keyword')).toBe('ORIANA');
  expect(await page.locator('.product-item').count()).toBeGreaterThan(0);
});

test('search preserves the promotional frame layered over the product image', async ({ page, baseURL }) => {
  // Trước đây test bám cứng vào mã JD-200NEW. Chủ project xoá mã đó khỏi
  // catalogue là test gãy, dù tính năng không hỏng. Giờ tự tìm sản phẩm nào
  // đang thực sự gắn khung khuyến mãi; không còn cái nào thì skip có lý do
  // thay vì báo đỏ giả.
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });

  const framedCount = await page.locator('.product-card .pts-promo-frame').count();
  test.skip(
    framedCount === 0,
    'Không sản phẩm nào đang gắn khung khuyến mãi (chưa có photo_khuyenmai và chưa bật chiến dịch nào), không có gì để kiểm tra xếp lớp.'
  );

  const promotedCard = page.locator('.product-card:has(.pts-promo-frame)').first();
  await expect(promotedCard).toBeVisible();
  await expect(promotedCard.locator('.pts-product-photo')).toBeVisible();
  await expect(promotedCard.locator('.pts-promo-frame')).toBeVisible();

  const layers = await promotedCard.locator('.product-card-img').evaluate(container => {
    const base = container.querySelector('.pts-product-photo');
    const overlay = container.querySelector('.pts-promo-frame');
    return {
      sameParent: Boolean(base && overlay && base.parentElement === overlay.parentElement),
      baseZ: base ? getComputedStyle(base).zIndex : null,
      overlayZ: overlay ? getComputedStyle(overlay).zIndex : null,
    };
  });
  expect(layers.sameParent).toBeTruthy();
  expect(Number(layers.overlayZ)).toBeGreaterThan(Number(layers.baseZ));
});

test('contact page serves a CSRF-protected lead form wired to the PHP handler', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  // The live form carries WordPress-era class names; the shared handler runs from sources/allpage.php.
  const form = page.locator('form:has([name="dataContact[fullname]"])');
  await expect(form).toHaveCount(1);
  await expect(form.locator('input[name="form_csrf"]')).toHaveCount(1);
  await expect(form.locator('input[name="submit-contact"]')).toBeVisible();
  // Email was made optional in the 2026-09-08 UX pass (hotline/Zalo are the
  // primary channels here); fullname stays required and still blocks an
  // empty submit via native constraint validation.
  await expect(form.locator('[name="dataContact[email]"]')).not.toHaveAttribute('required', '');
  await expect(form.locator('[name="dataContact[fullname]"]')).toHaveAttribute('required', '');
  // Empty submit is blocked by native constraint validation — still on /lien-he/.
  await form.locator('input[name="submit-contact"]').click();
  expect(new URL(page.url()).pathname.replace(/\/$/, '')).toBe('/lien-he');
});

test('imageless imported product detail renders without broken media or bad requests', async ({ page, baseURL }) => {
  await collectHealth(page, baseURL, async () => {
    const response = await page.goto('/san-pham/den-pha-nang-luong-mat-troi-oriana-db60100-100w', { waitUntil: 'domcontentloaded' });
    expect(response && response.ok()).toBeTruthy();
  });
  await expect(page.locator('.right-pro-detail > .title-pro-detail')).toBeVisible();
  const quoteCta = page.locator('.btn-contact-price');
  if (await quoteCta.count()) {
    await expect(quoteCta.locator('a[href^="tel:"]')).toBeVisible();
    await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
  } else {
    await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(2);
  }
  // No photo -> the MagicZoom trigger must fall back to the placeholder, never request the bare upload dir.
  await expect(page.locator('#Zoom-1')).not.toHaveAttribute('href', /upload\/product\/?$/);
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  // Review section shell is present in the DOM even with zero reviews (inside an inactive tab).
  await expect(page.locator('[name="dataReview[fullname]"]')).toBeAttached();
});

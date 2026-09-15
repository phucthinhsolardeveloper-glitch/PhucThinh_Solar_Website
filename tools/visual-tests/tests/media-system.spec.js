const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function imageMetrics(locator) {
  return locator.evaluate(img => {
    const box = img.getBoundingClientRect();
    return {
      complete: img.complete,
      naturalWidth: img.naturalWidth,
      naturalHeight: img.naturalHeight,
      width: box.width,
      height: box.height,
      fit: getComputedStyle(img).objectFit,
    };
  });
}

test('media system thắng CSS legacy ở các thành phần chính', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const styles = await page.locator('.product-card').first().evaluate(card => {
    const media = card.querySelector('.product-card-img');
    return {
      border: getComputedStyle(card).borderTopWidth,
      background: getComputedStyle(card).backgroundColor,
      ratio: getComputedStyle(media).aspectRatio,
    };
  });
  expect(styles.border).toBe('0px');
  expect(styles.background).toBe('rgba(0, 0, 0, 0)');
  expect(styles.ratio).toBe('1 / 1');
});

test('ảnh campaign trang chủ đủ nét và giữ cover', async ({ page }) => {
  await page.goto('/', { waitUntil: 'networkidle' });
  const images = page.locator('.pts-retail-campaign-media img');
  expect(await images.count()).toBeGreaterThan(0);
  for (let index = 0; index < await images.count(); index++) {
    const metrics = await imageMetrics(images.nth(index));
    expect(metrics.complete).toBeTruthy();
    expect(metrics.naturalWidth).toBeGreaterThanOrEqual(metrics.width * 1.45);
    expect(metrics.fit).toBe('cover');
  }
});

test('banner catalog tải trực tiếp và được phân bố giữa các nhóm sản phẩm', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'networkidle' });
  const banners = page.locator('.pts-catalog-campaign');
  expect(await banners.count()).toBe(2);
  const order = await page.locator('.pts-catblock, .pts-catalog-campaign').evaluateAll(nodes => nodes.map(node => node.classList.contains('pts-catalog-campaign') ? 'banner' : 'category'));
  expect(order[0]).toBe('category');
  expect(order[order.length - 1]).toBe('category');
  expect(order.filter(value => value === 'banner')).toHaveLength(2);

  for (let index = 0; index < await banners.count(); index++) {
    const image = banners.nth(index).locator('img');
    const metrics = await imageMetrics(image);
    expect(metrics.complete).toBeTruthy();
    expect(metrics.naturalWidth).toBeGreaterThanOrEqual(metrics.width);
    expect(metrics.fit).toBe('cover');
    await expect(banners.nth(index).locator('h3')).not.toBeEmpty();
  }
});

test('ảnh tư vấn Liên hệ và ảnh Đại lý không bị kéo giãn hoặc đè chữ', async ({ page }) => {
  await page.goto('/lien-he/', { waitUntil: 'networkidle' });
  const contactImage = page.locator('.pts-contact-intro-media img');
  await expect(contactImage).toBeVisible();
  const contactMetrics = await imageMetrics(contactImage);
  expect(contactMetrics.fit).toBe('cover');
  expect(contactMetrics.naturalWidth / contactMetrics.naturalHeight).toBeCloseTo(1.6, 1);
  await expect(page.locator('#yeu-cau-tu-van form')).toBeVisible();

  await page.goto('/dai-ly', { waitUntil: 'networkidle' });
  const dealerImage = page.locator('.pts-dealer-hero-media img');
  await expect(dealerImage).toBeVisible();
  const dealerMetrics = await imageMetrics(dealerImage);
  expect(dealerMetrics.fit).toBe('cover');
  const separation = await page.locator('.agency_1 .inner_content').evaluate(container => {
    const media = container.querySelector('.pts-dealer-hero-media').getBoundingClientRect();
    const copy = container.querySelector('.inner').getBoundingClientRect();
    return copy.top >= media.bottom - 1;
  });
  expect(separation).toBeTruthy();
});

test('ảnh card Giải pháp nằm trên nội dung và không che chữ', async ({ page }) => {
  await page.goto('/giai-phap/', { waitUntil: 'networkidle' });
  const cards = page.locator('.pts-sh-choice');
  expect(await cards.count()).toBeGreaterThan(0);
  for (let index = 0; index < await cards.count(); index++) {
    const positions = await cards.nth(index).evaluate(card => {
      const media = card.querySelector('.pts-sh-choice-media').getBoundingClientRect();
      const copy = card.querySelector('.pts-sh-choice-copy').getBoundingClientRect();
      return { mediaBottom: media.bottom, copyTop: copy.top };
    });
    expect(positions.copyTop).toBeGreaterThanOrEqual(positions.mediaBottom - 1);
    expect((await imageMetrics(cards.nth(index).locator('img'))).fit).toBe('cover');
  }
});

test('card sản phẩm dùng khung vuông và contain', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const cardImage = page.locator('.product-card-img img.pts-product-photo').first();
  await expect(cardImage).toBeVisible();
  const metrics = await imageMetrics(cardImage);
  expect(metrics.fit).toBe('contain');
  expect(metrics.width / metrics.height).toBeCloseTo(1, 1);
});

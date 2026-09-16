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

test('dải chứng nhận có nền quản trị được và bản quét đủ lớn để nhận diện', async ({ page }, testInfo) => {
  await page.goto('/', { waitUntil: 'networkidle' });
  const proof = page.locator('.pts-proof-band');
  await expect(proof).toBeVisible();

  const background = proof.locator('.pts-proof-background img');
  await expect(background).toBeVisible();
  await expect(background).toHaveAttribute('src', /home-proof-showroom-v2\.jpg/);
  expect((await imageMetrics(background)).fit).toBe('cover');

  const certificates = proof.locator('.pts-proof-card img');
  expect(await certificates.count()).toBeGreaterThan(0);
  expect(await certificates.count()).toBeLessThanOrEqual(3);
  for (let index = 0; index < await certificates.count(); index++) {
    const metrics = await imageMetrics(certificates.nth(index));
    expect(metrics.complete).toBeTruthy();
    expect(metrics.fit).toBe('contain');
    expect(metrics.height).toBeGreaterThanOrEqual(testInfo.project.name.includes('mobile') ? 150 : 185);
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
  const dealerImage = page.locator('.pts-dl-hero-banner-media img');
  await expect(dealerImage).toBeVisible();
  const dealerMetrics = await imageMetrics(dealerImage);
  expect(dealerMetrics.fit).toBe('cover');
  await expect(page.locator('.pts-dl-hero-banner-shade')).toBeVisible();
  await expect(page.locator('.pts-dl-hero-banner h1')).toBeVisible();
});

test('trang Giới thiệu dùng ảnh showroom thật và chọn WebP đúng kích thước màn hình', async ({ page, request }) => {
  await page.goto('/gioi-thieu/', { waitUntil: 'networkidle' });
  const image = page.locator('.pts-about-story-media img');
  await expect(image).toBeVisible();

  const media = await image.evaluate(img => ({
    currentSrc: img.currentSrc,
    fallbackSrc: img.getAttribute('src'),
    naturalWidth: img.naturalWidth,
    naturalHeight: img.naturalHeight,
    renderedWidth: img.getBoundingClientRect().width,
    fit: getComputedStyle(img).objectFit,
  }));
  expect(media.currentSrc).toMatch(/about-phuc-thinh-showroom-20260916(?:-960|\.jpg)\.webp$/);
  expect(media.fallbackSrc).toMatch(/about-phuc-thinh-showroom-20260916\.jpg$/);
  expect(media.naturalWidth).toBeGreaterThanOrEqual(media.renderedWidth - 1);
  expect(media.naturalWidth / media.naturalHeight).toBeCloseTo(1.88, 1);
  expect(media.fit).toBe('cover');

  const response = await request.get(media.currentSrc);
  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('image/webp');
});

test('thumbnail động ưu tiên WebP và trả đúng MIME', async ({ page, request }) => {
  await page.goto('/danh-muc-san-pham/den-san-vuon/', { waitUntil: 'networkidle' });
  const hero = page.locator('.pts-category-hero-media img');
  await expect(hero).toBeVisible();
  const src = await hero.getAttribute('src');
  expect(src).toMatch(/catalog-garden-lighting-v2\.jpg\.webp$/);
  const response = await request.get(src);
  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('image/webp');
});

test('trang Liên hệ có nhịp nội dung gọn, một H1 và form nằm ngay sau dải tư vấn', async ({ page }, testInfo) => {
  await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#inf_contact_page h1')).toHaveCount(1);
  await expect(page.locator('#yeu-cau-tu-van h2')).toHaveText('Gửi tin nhắn cho chúng tôi');

  const layout = await page.evaluate(() => {
    const breadcrumb = document.querySelector('.pts-breadcrumb-bar').getBoundingClientRect();
    const intro = document.querySelector('.pts-contact-intro-row').getBoundingClientRect();
    const form = document.querySelector('#yeu-cau-tu-van').getBoundingClientRect();
    return {
      introGap: intro.top - breadcrumb.bottom,
      formGap: form.top - intro.bottom,
      overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
  });

  expect(layout.introGap).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 8 : 30);
  expect(layout.formGap).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 16 : 20);
  expect(layout.overflow).toBeLessThanOrEqual(1);
});

test('ảnh card Giải pháp nằm trên nội dung và không che chữ', async ({ page }) => {
  await page.goto('/giai-phap/', { waitUntil: 'networkidle' });
  const cards = page.locator('.pts-sh-choice');
  expect(await cards.count()).toBeGreaterThan(0);
  for (let index = 0; index < await cards.count(); index++) {
    const card = cards.nth(index);
    const positions = await card.evaluate(card => {
      const media = card.querySelector('.pts-sh-choice-media').getBoundingClientRect();
      const copy = card.querySelector('.pts-sh-choice-copy').getBoundingClientRect();
      return { mediaBottom: media.bottom, copyTop: copy.top };
    });
    expect(positions.copyTop).toBeGreaterThanOrEqual(positions.mediaBottom - 1);
    const image = card.locator('img');
    if (await image.count()) {
      expect((await imageMetrics(image)).fit).toBe('cover');
    } else {
      // Trang chưa được Admin cấp ảnh thật dùng placeholder có chủ đích;
      // không ép template phát sinh một thẻ img hỏng/bịa ảnh dự án.
      await expect(card.locator('.pts-sh-choice-media.is-placeholder > span')).toBeVisible();
    }
  }

  await expect(page.locator('.pts-sh-choice-media.is-placeholder')).toHaveCount(0);
  const solutionFallbacks = [
    ['dien-mat-troi-ho-gia-dinh', 'solution-home-solar-v2.jpg'],
    ['he-thong-luu-tru-bess', 'solution-bess-v2.jpg'],
    ['bom-nuoc-nang-luong-mat-troi', 'solution-solar-pump-v2.jpg'],
  ];
  for (const [slug, filename] of solutionFallbacks) {
    const image = page.locator(`a[href="giai-phap/${slug}/"] .pts-sh-choice-media img`);
    await expect(image).toBeVisible();
    await expect(image).toHaveAttribute('src', new RegExp(filename.replace('.', '\\.')));
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

test('dải nhảy nhanh danh mục đủ tương phản và giữ một hàng trên mobile', async ({ page }, testInfo) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const jump = page.locator('.pts-catalog-jump');
  const firstLink = jump.locator('a').first();
  await expect(firstLink).toBeVisible();

  const styles = await firstLink.evaluate(link => ({
    background: getComputedStyle(link).backgroundColor,
    color: getComputedStyle(link).color,
    radius: parseFloat(getComputedStyle(link).borderTopLeftRadius),
  }));
  /* 2026-09-16: trước đây khoá cứng nền vàng rgb(231,196,95). Chủ project bỏ
     màu đó cho chip chưa chọn ("mấy nút có cái màu này là tôi không ưng") -
     vàng giờ chỉ dành cho hành động chính và điểm nhấn, chip dùng nền trung
     tính. Kiểm tra đúng bất biến cần giữ (tương phản chữ/nền đạt chuẩn đọc
     được, bo góc nhỏ theo hệ phẳng) thay vì khoá một mã màu cụ thể. */
  const srgb = value => {
    const channel = value / 255;
    return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
  };
  const luminance = color => {
    const [r, g, b] = color.match(/\d+/g).map(Number);
    return 0.2126 * srgb(r) + 0.7152 * srgb(g) + 0.0722 * srgb(b);
  };
  const lighter = Math.max(luminance(styles.background), luminance(styles.color));
  const darker = Math.min(luminance(styles.background), luminance(styles.color));
  const contrastRatio = (lighter + 0.05) / (darker + 0.05);
  expect(contrastRatio).toBeGreaterThanOrEqual(4.5);
  expect(styles.radius).toBeLessThanOrEqual(5);

  if (testInfo.project.name.includes('mobile')) {
    const rail = await jump.evaluate(element => ({
      wrap: getComputedStyle(element).flexWrap,
      overflowX: getComputedStyle(element).overflowX,
      scrollWidth: element.scrollWidth,
      clientWidth: element.clientWidth,
    }));
    expect(rail.wrap).toBe('nowrap');
    expect(rail.overflowX).toBe('auto');
    expect(rail.scrollWidth).toBeGreaterThan(rail.clientWidth);
  }
});

test('danh mục Đèn sân vườn dùng banner ảnh và card có cùng hàng đáy', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/den-san-vuon/', { waitUntil: 'domcontentloaded' });

  const heroImage = page.locator('.pts-category-hero.has-image .pts-category-hero-media img');
  await expect(heroImage).toBeVisible();
  await expect(heroImage).toHaveAttribute('src', /catalog-garden-lighting-v2\.jpg/);
  await expect(page.locator('.pts-category-hero-shade')).toBeVisible();
  expect((await imageMetrics(heroImage)).fit).toBe('cover');

  const cards = page.locator('.grid-product .product-card');
  expect(await cards.count()).toBeGreaterThanOrEqual(4);
  const metrics = await cards.evaluateAll(nodes => nodes.slice(0, 4).map(card => {
    const cardBox = card.getBoundingClientRect();
    const media = card.querySelector('.product-card-img').getBoundingClientRect();
    const price = card.querySelector('.price-product').getBoundingClientRect();
    const action = card.querySelector('.product-card-btns a').getBoundingClientRect();
    return {
      height: cardBox.height,
      mediaHeight: media.height,
      priceCenter: price.top + price.height / 2,
      actionCenter: action.top + action.height / 2,
    };
  }));
  expect(Math.max(...metrics.map(item => item.height)) - Math.min(...metrics.map(item => item.height))).toBeLessThanOrEqual(1);
  expect(Math.max(...metrics.map(item => item.mediaHeight)) - Math.min(...metrics.map(item => item.mediaHeight))).toBeLessThanOrEqual(1);
  for (const card of metrics) expect(Math.abs(card.priceCenter - card.actionCenter)).toBeLessThanOrEqual(12);
});

test('mười nhóm danh mục có ảnh nền đúng ngữ cảnh thay vì lặp một ảnh', async ({ page }) => {
  const expectedHeroes = [
    ['den-nlmt', 'catalog-street-lighting.jpg'],
    ['bien-tan-inverter', 'catalog-inverter-system-v2.jpg'],
    ['tam-pin-nang-luong-mat-troi', 'catalog-solar-panel-mounting-v2.jpg'],
    ['tru-den', 'catalog-lamp-posts-v2.jpg'],
    ['den-san-vuon', 'catalog-garden-lighting-v2.jpg'],
    ['quat-nang-luong-mat-troi', 'catalog-solar-fan-v3.jpg'],
    ['bien-tan-bom', 'catalog-solar-pump-v2.jpg'],
    ['thiet-bi-dien-nang-luong-mat-troi', 'catalog-solar-electrical-accessories-v2.jpg'],
    ['he-thong-khung-nhom', 'catalog-solar-panel-mounting-v2.jpg'],
    ['pin-luu-tru', 'catalog-battery-storage-v2.jpg'],
  ];

  for (const [slug, filename] of expectedHeroes) {
    await page.goto(`/danh-muc-san-pham/${slug}/`, { waitUntil: 'domcontentloaded' });
    const hero = page.locator('.pts-category-hero.has-image .pts-category-hero-media img');
    await expect(hero, `${slug} phải có banner riêng`).toBeVisible();
    await expect(hero).toHaveAttribute('src', new RegExp(filename.replace('.', '\\.')));
    expect((await imageMetrics(hero)).fit).toBe('cover');
  }
});

test('nội dung tư vấn danh mục được cân giữa và tách nhịp khỏi lưới sản phẩm', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/pin-luu-tru/', { waitUntil: 'domcontentloaded' });
  const section = page.locator('.pts-category-content');
  const inner = section.locator('.pts-category-content-inner');
  await expect(section).toBeVisible();
  await expect(inner.locator('.pts-desc-subtitle').first()).toBeVisible();

  const layout = await inner.evaluate(element => {
    const innerBox = element.getBoundingClientRect();
    const sectionBox = element.parentElement.getBoundingClientRect();
    return {
      centerDelta: Math.abs((innerBox.left + innerBox.width / 2) - (sectionBox.left + sectionBox.width / 2)),
      maxWidth: parseFloat(getComputedStyle(element).maxWidth),
      background: getComputedStyle(element.parentElement).backgroundColor,
    };
  });
  expect(layout.centerDelta).toBeLessThanOrEqual(1);
  expect(layout.maxWidth).toBeLessThanOrEqual(980);
  expect(layout.background).toBe('rgb(244, 247, 251)');
});

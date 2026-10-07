const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function imageMetrics(locator) {
  /* 2026-09-16: ảnh nằm dưới màn hình giờ dùng loading="lazy" của trình duyệt
     (trang chủ trước đây tải ngay 68 ảnh = 1.2MB, đúng nguyên nhân đặc tả
     báo "đơ và lag"). Nên phải cuộn tới ảnh và đợi tải xong trước khi đo -
     đúng những gì người dùng thật gặp. Vẫn kiểm tra đủ: ảnh tải được, đủ nét,
     object-fit đúng; không nới lỏng assertion nào. */
  await locator.scrollIntoViewIfNeeded();
  await locator.evaluate(img => img.complete && img.naturalWidth > 0
    ? true
    : new Promise(resolve => {
        const done = () => resolve(true);
        img.addEventListener('load', done, {once: true});
        img.addEventListener('error', done, {once: true});
        setTimeout(done, 3000);
      }));
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

test('ảnh ứng dụng trang chủ đủ nét và giữ cover', async ({ page }) => {
  await page.goto('/', { waitUntil: 'networkidle' });
  const images = page.locator('.pts-home-use-media img');
  expect(await images.count()).toBeGreaterThan(0);
  for (let index = 0; index < await images.count(); index++) {
    const metrics = await imageMetrics(images.nth(index));
    expect(metrics.complete).toBeTruthy();
    expect(metrics.naturalWidth).toBeGreaterThanOrEqual(metrics.width * 1.45);
    expect(metrics.fit).toBe('cover');
  }
});

test('hero trang chủ có đủ bốn ảnh quản trị được và hiển thị cover', async ({ page }) => {
  await page.goto('/', { waitUntil: 'networkidle' });
  const slider = page.locator('[data-home-hero-slider]');
  await expect(slider).toBeVisible();
  const images = slider.locator('.pts-home-hero-banner-media img');
  await expect(images).toHaveCount(4);
  await expect.poll(() => images.evaluateAll(items => items.every(image => image.complete && image.naturalWidth > 0))).toBeTruthy();
  const metrics = await images.evaluateAll(items => items.map(image => ({ width: image.naturalWidth, fit: getComputedStyle(image).objectFit })));
  metrics.forEach(image => {
    expect(image.width).toBeGreaterThan(0);
    expect(image.fit).toBe('cover');
  });
});

test('banner catalog tải trực tiếp và hiện đúng 1 CTA cuối trang', async ({ page }) => {
  /* Landing danh mục v3 (2026-09-23): slot Admin catalog-campaign giờ render
     1 CTA tư vấn cuối trang (.pts-catalog-support), không còn nhiều banner
     xen giữa các khối danh mục (.pts-catblock đã bị thay bằng bố cục lưới
     dùng chung). Xem docs/13-handoffs/2026-09-23-product-category-landing-v3.md.
     2026-09-28: banner này đổi sang nền phẳng (không còn ảnh nền
     .pts-catalog-support-media) - chỉ còn icon + chữ, xem product_tpl.php. */
  await page.goto('/san-pham/', { waitUntil: 'networkidle' });
  const banner = page.locator('.pts-catalog-support');
  await expect(banner).toHaveCount(1);

  await expect(banner.locator('.pts-catalog-support-icon')).toBeVisible();
  await expect(banner.locator('h2')).not.toBeEmpty();
  await expect(banner.locator('a')).toBeVisible();
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
  expect(src).toMatch(/catalog-ai-garden-lighting\.webp$/);
  const response = await request.get(src);
  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-type']).toContain('image/webp');
});

test('trang Liên hệ có nhịp nội dung gọn, một H1 và form nằm ngay sau dải tư vấn', async ({ page }, testInfo) => {
  await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#inf_contact_page h1')).toHaveCount(1);
  // 2026-09-22: tiêu đề form đổi từ "Gửi tin nhắn cho chúng tôi" (chung chung)
  // sang "Đăng ký nhận tư vấn" theo bản redesign Liên hệ — nội dung, không
  // phải lỗi; cập nhật assertion theo copy đã duyệt.
  await expect(page.locator('#yeu-cau-tu-van h2')).toHaveText('Đăng ký nhận tư vấn');

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

  /* Bản contact-v3 chủ động giữ khoảng thở giữa hero và form để tách hai
     nhiệm vụ: khám phá kênh liên hệ trước, sau đó mới nhập yêu cầu. */
  expect(layout.introGap).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 48 : 56);
  expect(layout.formGap).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 48 : 72);
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

test('card sản phẩm dùng ảnh chuẩn hóa, khung vuông và contain', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const cardImage = page.locator('.product-card-img img.pts-product-photo').first();
  await expect(cardImage).toBeVisible();
  const metrics = await imageMetrics(cardImage);
  expect(metrics.fit).toBe('contain');
  expect(metrics.width / metrics.height).toBeCloseTo(1, 1);
  expect(metrics.naturalWidth).toBe(300);
  expect(await cardImage.getAttribute('src')).toContain('/300x300x4/');
});

test('dải nhảy nhanh danh mục đủ tương phản và giữ một hàng trên mobile', async ({ page }, testInfo) => {
  /* Landing danh mục v3 (2026-09-23): .pts-catalog-jump được thay bằng rail
     ảnh ngang "Tất cả + danh mục đang có dữ liệu" (.pts-category-tabs, mục
     con .pts-category-tab) — cùng vai trò nhảy nhanh, khác tên class. */
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const jump = page.locator('.pts-category-tabs').first();
  const firstLink = jump.locator('.pts-category-tab').first();
  await expect(firstLink).toBeVisible();

  const styles = await firstLink.evaluate(link => ({
    background: getComputedStyle(link).backgroundColor,
    color: getComputedStyle(link).color,
    radius: parseFloat(getComputedStyle(link).borderTopLeftRadius),
  }));
  /* 2026-09-16: trước đây khoá cứng nền vàng rgb(231,196,95). Audit UX xác định
     màu đó không phù hợp cho chip chưa chọn -
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
  await expect(heroImage).toHaveAttribute('src', /catalog-ai-garden-lighting\.webp/);
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
      actionBelowPrice: action.top - (price.top + price.height),
      /* Khoảng cách từ đáy CTA tới đáy card — bất biến NỘI TẠI của card, không
         phụ thuộc hàng lưới đang nằm ở đâu trên trang (khác absolute top/bottom
         vốn lệch hàng trăm px giữa các hàng khi lưới xuống dòng, ví dụ lưới 2
         cột trên mobile so với 4 cột trên desktop). */
      actionBottomGap: cardBox.top + cardBox.height - (action.top + action.height),
    };
  }));
  expect(Math.max(...metrics.map(item => item.height)) - Math.min(...metrics.map(item => item.height))).toBeLessThanOrEqual(1);
  expect(Math.max(...metrics.map(item => item.mediaHeight)) - Math.min(...metrics.map(item => item.mediaHeight))).toBeLessThanOrEqual(1);
  /* CTA giờ là hàng chữ đầy đủ nằm DƯỚI giá (product-category-v3.css:
     .product-right .product-card-btns{position:static;margin-top:auto}),
     không còn là nút icon tròn nổi cùng hàng với giá như bản cũ — kiểm tra
     đúng bất biến còn lại: CTA nằm dưới giá, và đáy CTA cách đáy card đều
     nhau giữa các card (đúng ý "cùng hàng đáy" của tên test, tính tương đối
     theo từng card để không vỡ khi lưới xuống nhiều hàng ở mobile). */
  for (const card of metrics) expect(card.actionBelowPrice).toBeGreaterThanOrEqual(-1);
  expect(Math.max(...metrics.map(item => item.actionBottomGap)) - Math.min(...metrics.map(item => item.actionBottomGap))).toBeLessThanOrEqual(1);
});

test('chín nhóm danh mục có ảnh nền đúng ngữ cảnh thay vì lặp một ảnh', async ({ page }) => {
  const expectedHeroes = [
    ['den-nlmt', 'catalog-ai-solar-lighting.webp'],
    ['bien-tan-inverter', 'catalog-ai-inverter.webp'],
    ['tam-pin-nang-luong-mat-troi', 'catalog-ai-solar-panels.webp'],
    ['tru-den', 'catalog-lamp-posts-v2.jpg'],
    ['den-san-vuon', 'catalog-ai-garden-lighting.webp'],
    ['quat-nang-luong-mat-troi', 'catalog-ai-solar-fan.webp'],
    ['bien-tan-bom', 'catalog-ai-solar-pump.webp'],
    ['thiet-bi-dien-nang-luong-mat-troi', 'catalog-ai-solar-accessories.webp'],
    ['pin-luu-tru', 'catalog-ai-battery-storage.webp'],
  ];

  for (const [slug, filename] of expectedHeroes) {
    await page.goto(`/danh-muc-san-pham/${slug}/`, { waitUntil: 'domcontentloaded' });
    const hero = page.locator('.pts-category-hero.has-image .pts-category-hero-media img');
    await expect(hero, `${slug} phải có banner riêng`).toBeVisible();
    await expect(hero).toHaveAttribute('src', new RegExp(filename.replace('.', '\\.')));
    expect((await imageMetrics(hero)).fit).toBe('cover');
  }
});

test('URL Hệ khung cũ chuyển về danh mục Tấm pin', async ({ request }) => {
  const response = await request.get('/danh-muc-san-pham/he-thong-khung-nhom/', {
    maxRedirects: 0,
  });
  expect(response.status()).toBe(301);
  expect(new URL(response.headers().location).pathname).toBe('/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/');
});

test('danh mục không tự bật khối mô tả dài khi Admin chưa bật', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/pin-luu-tru/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pts-category-content')).toHaveCount(0);
  await expect(page.locator('.grid-product .product-card').first()).toBeVisible();
});

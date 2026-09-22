const { test, expect } = require('@playwright/test');

// Widget GTranslate (cdn.gtranslate.net) tự dịch trang sang tiếng Anh, nên các
// assertion so chuỗi tiếng Việt đỏ ngẫu nhiên tuỳ script kịp chạy hay không —
// ví dụ tiêu đề modal báo giá vốn hardcode "Tư vấn & báo giá" trong template lại
// trả về "Consultation & Quotation". visual-baseline.spec.js đã chặn sẵn; chặn ở
// đây cho nhất quán, để test đo giao diện thật chứ không đo bản dịch máy.
test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  /* 2026-09-16: popup chào mừng chỉ hiện SAU khi apps.js chạy, nên
     dismissEntrancePopup() gọi lúc popup chưa kịp render sẽ thấy "không có
     popup" rồi popup bung lên sau đó, chắn mọi tương tác và kéo
     visibility:hidden lên cụm nút liên hệ nổi (rule .modal-open
     #button-contact-vr) - làm 4 test đỏ ngẫu nhiên kèm timeout 30s.
     Chặn tất định: ghi đè sessionStorage.getItem để khoá 'pts-popup-campaign'
     luôn trả đúng data-campaign-key của trang. Không hardcode 'current' được
     vì key thật là hash động theo chiến dịch (vd "c90a85b8b62ba4fd4c06").
     Popup vẫn được kiểm tra riêng ở test dành cho nó. */
  await page.addInitScript(() => {
    try {
      const original = window.sessionStorage.getItem.bind(window.sessionStorage);
      window.sessionStorage.getItem = key => {
        if (key === 'pts-popup-campaign') {
          const popup = document.querySelector('#popup');
          return popup ? (popup.getAttribute('data-campaign-key') || 'current') : 'current';
        }
        return original(key);
      };
    } catch (error) {}
  });
});

async function dismissEntrancePopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    await popup.locator('[data-dismiss="modal"]').click();
    await expect(popup).not.toBeVisible();
  }
}

// response.ok() chỉ kiểm tra HTTP status - một PHP Fatal error vẫn trả 200
// (đã gặp thật với api/product2.php, product3.php, fillter_service.php khi
// public_watermark.php không được nạp ở bootstrap api/config.php, audit
// 2026-09-12). Đọc thẳng body để chặn các API AJAX sản phẩm lọt lỗi PHP mà
// vẫn báo test xanh.
async function assertHealthyAjaxResponse(responsePromise) {
  const response = await responsePromise;
  expect(response.ok()).toBeTruthy();
  const body = await response.text();
  expect(body).not.toMatch(/Fatal error|Warning:|Notice:|Parse error|Deprecated:/);
  return body;
}

test('header, menu, breadcrumb and footer remain usable', async ({ page, isMobile }) => {
  const pageErrors = [];
  const failedFontRequests = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  page.on('requestfailed', request => {
    if (/\.(?:woff2?|ttf|eot)(?:\?|$)/i.test(request.url())) failedFontRequests.push(request.url());
  });

  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#headerscroll')).toBeVisible();
  await expect(page.locator('.breadCrumbs')).toBeVisible();
  await expect(page.locator('.pts-breadcrumb-bar')).toHaveCSS('border-bottom-width', '0px');
  const headerCart = page.locator('#headerscroll .icon-cart-header');
  if (await headerCart.count()) {
    await expect(headerCart).toBeVisible();
  }
  const phoneLink = page.locator('#headerscroll .hdr-call');
  await expect(phoneLink).toHaveAttribute('href', /^tel:/);
  const phoneIcon = phoneLink.locator('.hdr-call-ic .fa-phone-alt');
  await expect(phoneIcon).toBeAttached();
  if (isMobile) {
    await expect(phoneLink).toBeHidden();
  } else {
    await expect(phoneLink).toBeVisible();
    await expect(phoneLink.locator('.hdr-call-txt')).toContainText('Mua hàng');
    await expect(phoneIcon).toBeVisible();
  }
  await page.evaluate(() => document.fonts.ready);
  expect(await phoneIcon.evaluate(element => getComputedStyle(element).fontFamily)).toContain('Font Awesome 5 Free');
  expect(await page.evaluate(() => document.fonts.check('900 16px "Font Awesome 5 Free"'))).toBeTruthy();
  expect(failedFontRequests).toEqual([]);

  const hamburger = page.locator('#hamburger');
  if (isMobile || await hamburger.isVisible()) {
    await expect(hamburger).toBeVisible();
    await hamburger.click();
    await expect(page.locator('#menu')).toBeVisible();
    await page.keyboard.press('Escape');
  } else {
    await expect(page.locator('#headerscroll .menu').first()).toBeVisible();
    const catalogMenu = page.locator('#headerscroll .menu-catalog');
    const catalogSubmenu = catalogMenu.locator(':scope > ul');
    await expect(catalogSubmenu).toBeAttached();
    await catalogMenu.hover();
    await expect(catalogSubmenu).toBeVisible();
    await expect(catalogSubmenu.locator('a[href="ac"]')).toHaveCount(0);
    await expect(catalogSubmenu.locator('a[href="danh-muc-san-pham/den-ac/"]')).toHaveCount(0);

    const catalogParentWithChildren = catalogSubmenu.locator(':scope > li').filter({ has: page.locator(':scope > ul') }).first();
    await expect(catalogParentWithChildren).toBeVisible();
    await catalogParentWithChildren.hover();
    await expect(catalogParentWithChildren.locator(':scope > ul')).toBeVisible();
    await expect(catalogParentWithChildren.locator(':scope > ul a').first()).toHaveAttribute('href', /^danh-muc-san-pham\/[^/]+\/[^/]+\/$/);
  }

  await page.locator('footer').scrollIntoViewIfNeeded();
  await expect(page.locator('footer')).toBeVisible();
  await page.locator('#footer_logo').scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);
  await expect(page.locator('#footer_logo img')).toBeVisible();
  await expect(page.locator('footer .footer-policy-column .footer-bct-badge')).toHaveCount(1);
  await expect(page.locator('footer .footer-brand-column .footer-bct-badge')).toHaveCount(0);
  await expect(page.locator('footer .footer-legal-name')).toContainText('CÔNG TY TNHH CÔNG NGHỆ');
  await expect(page.locator('footer .footer-legal-name')).toContainText('PHÚC THỊNH SOLAR');
  await expect(page.locator('footer .CRlist_office_center')).toContainText('Phường Hội Phú');
  await expect(page.locator('footer .footer-contact-lines a')).toHaveCount(2);
  await expect(page.locator('footer .footer-contact-lines')).toContainText('Tư vấn bán hàng');
  await expect(page.locator('footer .footer-contact-lines')).toContainText('Hỗ trợ kỹ thuật');
  await expect(page.locator('footer .content_cn')).toContainText('Kho TP.HCM');
  await expect(page.locator('footer .content_cn')).toContainText('Kho Đà Nẵng');
  await expect(page.locator('footer .content_cn')).toContainText('Kho Gia Lai');
  expect(pageErrors).toEqual([]);
});

test('content surfaces stay flat across product and content hubs', async ({ page }) => {
  const assertFlat = async selector => {
    const surface = page.locator(selector).first();
    await expect(surface).toBeVisible();
    expect(await surface.evaluate(element => ({
      border: getComputedStyle(element).borderTopWidth,
      background: getComputedStyle(element).backgroundColor,
      shadow: getComputedStyle(element).boxShadow
    }))).toEqual({ border: '0px', background: 'rgba(0, 0, 0, 0)', shadow: 'none' });
  };

  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });
  await assertFlat('.product-card');
  await assertFlat('.filter_product');
  await assertFlat('.pts-filter-card');
  const productHref = await page.locator('.product-card .product-item').first().getAttribute('href');
  expect(productHref).toBeTruthy();
  await page.goto(`/${productHref}`, { waitUntil: 'domcontentloaded' });
  const gallery = page.locator('.left-pro-detail > .MagicZoom');
  await expect(gallery).toBeVisible();
  expect(await gallery.evaluate(element => ({
    border: getComputedStyle(element).borderTopWidth,
    background: getComputedStyle(element).backgroundColor,
    shadow: getComputedStyle(element).boxShadow
  }))).toEqual({ border: '0px', background: 'rgb(255, 255, 255)', shadow: 'none' });
  await assertFlat('.tabs-pro-detail');

  await page.goto('/tin-tuc/', { waitUntil: 'domcontentloaded' });
  await assertFlat('.nh-card');
  await expect(page.locator('.nh-eyebrow')).toBeHidden();

  await page.goto('/du-an-da-thuc-hien/', { waitUntil: 'domcontentloaded' });
  await assertFlat('.pts-ph-card');
  await expect(page.locator('.pts-ph-mark')).toBeHidden();

  await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
  await assertFlat('.pts-sh-choice');
  await expect(page.locator('.pts-sh-eyebrow').first()).toBeHidden();

  await page.goto('/gioi-thieu/', { waitUntil: 'domcontentloaded' });
  await assertFlat('.pts-about-certificate-card');
  await expect(page.locator('.pts-about-eyebrow').first()).toBeHidden();

  // Border của field vẫn được giữ vì là ranh giới nhập liệu có ý nghĩa.
  await page.goto('/lien-he', { waitUntil: 'domcontentloaded' });
  const contactField = page.locator('.formContact input[type="text"]').first();
  await expect(contactField).toBeVisible();
  expect(await contactField.evaluate(element => parseFloat(getComputedStyle(element).borderTopWidth))).toBeGreaterThan(0);
});

test('mobile home starts with the approved full-width hero slider', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Mobile-only homepage gutter regression.');
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  await expect(page.locator('.wap-slider, .pts-hero-carousel, .pts-hero2, .pts-hero-layout-toggle')).toHaveCount(0);
  await expect(page.locator('.pts-home-hero')).toBeVisible();
  await expect(page.locator('[data-home-hero-slide]')).toHaveCount(4);
  await expect(page.locator('[data-home-hero-slide].is-active')).toHaveCount(1);
  await expect(page.locator('.pts-home-category-featured')).toBeVisible();
  expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBe(await page.evaluate(() => document.documentElement.clientWidth));
});

test('home contact dock stays compact and exposes existing contact links', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  const dock = page.locator('#button-contact-vr.pts-contact-dock');
  const toggle = dock.locator('.pts-contact-dock-toggle');
  await expect(toggle).toBeVisible();
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  await expect(dock.locator('#gom-all-in-one')).toHaveAttribute('aria-hidden', 'true');

  await toggle.click();
  await expect(toggle).toHaveAttribute('aria-expanded', 'true');
  await expect(dock.locator('#gom-all-in-one')).toHaveAttribute('aria-hidden', 'false');
  await expect(dock.locator('a[href^="tel:"]')).toBeVisible();
  await expect(dock.locator('a[href*="zalo.me"]')).toBeVisible();

  await page.keyboard.press('Escape');
  await expect(toggle).toHaveAttribute('aria-expanded', 'false');
});

/* 2026-09-18: chủ project báo "mới load trang thì nháy 3 nút call/Zalo/Facebook
   rồi mới gom thành 1 icon". Nguyên nhân: theme-v2.css chỉ thu gọn dock khi có
   class .is-enhanced, mà class đó do apps.js thêm - tức sau khi trang đã vẽ.
   Đã sửa bằng cách gắn class `pts-js` vào <html> từ inline script trong <head>
   (chạy trước lần vẽ đầu tiên) và nhân đôi quy tắc thu gọn theo class đó.
   Test chặn script chứa apps.js để mô phỏng đúng khoảnh khắc trước khi JS
   chính chạy.
   2026-09-21: debug-js chuyển sang false (xem class.JsMinify.php) nên apps.js
   không còn là request riêng - nó được GỘP vào 1 file
   assets/caches/<hash>.js cùng ~23 script khác. Chặn đúng file apps.js gốc
   không còn tác dụng vì trình duyệt không bao giờ gọi URL đó nữa. Chặn theo
   nội dung phản hồi (chứa "GA_FRAMEWORK.ContactDock") để test đúng cả hai chế
   độ debug-js, không phải đoán tên file bundle theo hash. */
test('cụm liên hệ nổi đã thu gọn từ lần vẽ đầu, không nháy 3 nút', async ({ page }) => {
  await page.route('**/*.js*', async route => {
    const response = await route.fetch();
    const body = await response.text();
    if (body.includes('GA_FRAMEWORK.ContactDock')) { await route.abort(); return; }
    await route.fulfill({ response });
  });
  await page.goto('/', { waitUntil: 'load' });

  await expect(page.locator('html')).toHaveClass(/\bpts-js\b/);
  // apps.js bị chặn nên .is-enhanced KHÔNG được thêm - đúng trạng thái lúc chớm tải.
  await expect(page.locator('#button-contact-vr.pts-contact-dock')).not.toHaveClass(/is-enhanced/);

  const group = page.locator('#gom-all-in-one');
  expect(await group.evaluate(node => getComputedStyle(node).opacity)).toBe('0');
  expect(await group.evaluate(node => getComputedStyle(node).visibility)).toBe('hidden');
  await expect(page.locator('.pts-contact-dock-toggle')).toBeVisible();
});

/* Mặt còn lại của cùng một quyết định: mở sẵn 3 nút là FALLBACK có chủ ý cho
   người không chạy được JS. Nếu ai đó "sửa" bằng cách đảo mặc định CSS thành
   thu gọn thì test này đỏ, vì lúc đó không có JS sẽ mất hẳn kênh liên hệ. */
test('không có JavaScript thì cụm liên hệ vẫn hiện đủ 3 kênh', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto(`${baseURL}/`, { waitUntil: 'load' });

  await expect(page.locator('#gom-all-in-one')).toBeVisible();
  await expect(page.locator('#gom-all-in-one .button-contact')).toHaveCount(3);
  await expect(page.locator('#gom-all-in-one a[href^="tel:"]')).toBeVisible();
  await expect(page.locator('#gom-all-in-one a[href*="zalo.me"]')).toBeVisible();
  await context.close();
});

test('home follows the approved storytelling layout with real commerce links', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);

  await expect(page.locator('.pts-home-hero')).toBeVisible();
  await expect(page.locator('.pts-home-category')).toHaveCount(5);
  await expect(page.locator('.pts-home-use')).toHaveCount(3);
  await expect(page.locator('.pts-home-tab')).toHaveCount(4);
  await expect(page.locator('.pts-home-steps li')).toHaveCount(5);
  await expect(page.locator('.pts-home-dealer')).toBeVisible();
  await expect(page.locator('.pts-home-about')).toBeVisible();
  await expect(page.locator('#yeu-cau-tu-van form')).toBeVisible();
});

test('product detail keeps gallery and the CTA configured by Admin', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const productLink = page.locator('.grid-product a[href]').filter({ has: page.locator('img') }).first();
  await expect(productLink).toBeVisible();
  const href = await productLink.getAttribute('href');
  expect(href).toBeTruthy();

  const response = await page.goto(href, { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  await expect(page.locator('.breadCrumbs')).toBeVisible();
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  const mainGallery = page.locator('#Zoom-1');
  const measureGallery = () => mainGallery.evaluate(element => {
    const rect = element.getBoundingClientRect();
    const image = element.querySelector('.mz-figure > img, :scope > img');
    const imageRect = image ? image.getBoundingClientRect() : null;
    return {
      width: rect.width,
      height: rect.height,
      imageWidth: imageRect ? imageRect.width : 0,
      imageHeight: imageRect ? imageRect.height : 0,
      fit: image ? getComputedStyle(image).objectFit : ''
    };
  });
  const galleryBefore = await measureGallery();
  expect(galleryBefore.width).toBeGreaterThan(0);
  expect(Math.abs(galleryBefore.width - galleryBefore.height)).toBeLessThanOrEqual(1);
  expect(galleryBefore.fit).toBe('contain');
  await expect(page.locator('.right-pro-detail > .title-pro-detail')).toBeVisible();
  const quoteCta = page.locator('.btn-contact-price');
  if (await quoteCta.count()) {
    await expect(quoteCta).toBeVisible();
    await expect(quoteCta.locator('a[href^="tel:"]')).toBeVisible();
    await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(0);
  } else {
    await expect(page.locator('.btn-product-detail .addcart')).toHaveCount(2);
  }

  // Tab "Mô tả sản phẩm": một cột, đã bỏ các khối story/duo/canvas rời rạc.
  await expect(page.locator('.pts-story-card, .pts-detail-duo, .pts-description-canvas')).toHaveCount(0);
  const descPane = page.locator('#info-pro-detail');
  if (await descPane.count()) {
    await expect(descPane.locator('.pts-product-desc')).toHaveCount(1);
  }
});

test('switching product photos does not shrink the detail gallery', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const productHrefs = await page.locator('.grid-product a.product-item[href]').evaluateAll(links =>
    Array.from(new Set(links.map(link => link.getAttribute('href')).filter(Boolean))).slice(0, 20)
  );

  let galleryFound = false;
  for (const href of productHrefs) {
    const response = await page.goto(href, { waitUntil: 'domcontentloaded' });
    if (!response || !response.ok()) continue;
    if (await page.locator('.gallery-thumb-pro .thumb-pro-detail').count() > 1) {
      galleryFound = true;
      break;
    }
  }
  expect(galleryFound, 'test data must include a product with multiple gallery photos').toBeTruthy();

  const gallery = page.locator('#Zoom-1');
  const measure = () => gallery.evaluate(element => {
    const frame = element.getBoundingClientRect();
    const image = Array.from(element.querySelectorAll('.mz-figure > img, :scope > img'))
      .filter(candidate => {
        const rect = candidate.getBoundingClientRect();
        const style = getComputedStyle(candidate);
        return rect.width > 0 && rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
      })
      .sort((left, right) => right.getBoundingClientRect().width * right.getBoundingClientRect().height - left.getBoundingClientRect().width * left.getBoundingClientRect().height)[0];
    const imageRect = image ? image.getBoundingClientRect() : { width: 0, height: 0 };
    return {
      width: frame.width,
      height: frame.height,
      imageSource: image ? image.currentSrc : '',
      imageNaturalWidth: image ? image.naturalWidth : 0,
      imageWidth: imageRect.width,
      imageHeight: imageRect.height,
      fit: image ? getComputedStyle(image).objectFit : '',
    };
  });
  const before = await measure();
  expect(before.width).toBeGreaterThan(0);
  expect(Math.abs(before.width - before.height)).toBeLessThanOrEqual(1);
  const secondPhoto = page.locator('.gallery-thumb-pro .thumb-pro-detail').nth(1);
  await secondPhoto.click();
  await expect.poll(async () => (await measure()).imageSource).not.toBe(before.imageSource);
  const after = await measure();
  expect(Math.abs(before.width - after.width)).toBeLessThanOrEqual(1);
  expect(Math.abs(before.height - after.height)).toBeLessThanOrEqual(1);
  expect(after.fit).toBe('contain');
  // Ảnh giữ khung vuông ổn định nhưng chừa khoảng thở bên trong showroom.
  expect(after.imageWidth).toBeGreaterThanOrEqual(after.width * 0.85);
  expect(after.imageHeight).toBeGreaterThanOrEqual(after.height * 0.85);
  expect(after.imageNaturalWidth).toBeGreaterThanOrEqual(600);
  expect(after.imageSource).toContain('/600x600x4/');
});

test('quote request CTA opens the protected consultation form', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const quoteCard = page.locator('.product-card:has(.pts-card-ask)').first();
  const quoteCardCount = await quoteCard.count();
  test.skip(quoteCardCount === 0, 'Catalogue hiện không có sản phẩm ở chế độ báo giá để mở modal này.');
  const href = await quoteCard.locator('.product-item').getAttribute('href');
  expect(href).toBeTruthy();

  await page.goto(href, { waitUntil: 'domcontentloaded' });
  const modal = page.locator('#exampleModal');
  await expect(modal).not.toBeVisible();
  await page.locator('.btn-contact-price [data-target="#exampleModal"]').click();
  await expect(modal).toBeVisible();
  await expect(modal.locator('#quoteRequestModalLabel')).toContainText('Tư vấn & báo giá');

  const form = modal.locator('form.validation-newsletter');
  await expect(form.locator('input[name="form_csrf"]')).toHaveCount(1);
  await expect(form.locator('[name="dataNewsletter[fullname]"]')).toBeVisible();
  await expect(form.locator('[name="dataNewsletter[phone]"]')).toBeVisible();
  await expect(form.locator('[name="submit-newsletter"]')).toBeVisible();
  await expect(form.locator('[name="submit-newsletter"]')).toBeEnabled();

  await modal.locator('[data-dismiss="modal"]').click();
  await expect(modal).not.toBeVisible();
});

test('product landing renders category products in the initial HTML without startup API requests', async ({ page }) => {
  const product3Requests = [];
  page.on('request', request => {
    if (request.url().includes('/api/product3.php')) product3Requests.push(request.url());
  });

  const response = await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  expect(response && response.ok()).toBeTruthy();
  const blocks = page.locator('.paging-prolist[data-server-rendered="1"]');
  expect(await blocks.count()).toBeGreaterThan(0);
  await expect(blocks.first().locator('.product-card').first()).toBeAttached();
  expect(await blocks.first().locator('.product-card').count()).toBeLessThanOrEqual(4);
  await page.waitForTimeout(700);
  expect(product3Requests).toEqual([]);
});

test('dealer CTA opens a complete, styled and enabled form', async ({ page }) => {
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  // Demoted from a full-width button to a small subordinate link in the
  // 2026-09-08 UX pass (PDP CTA hierarchy: "Gọi báo giá" leads, dealer signup
  // is secondary) — same modal target, new container class.
  await page.locator('.pts-pdp-dealer-link [data-target="#ptsDealerModal"]').click();
  const modal = page.locator('#ptsDealerModal');
  await expect(modal).toBeVisible();
  await expect(modal).toHaveClass(/pts-lead-modal/);
  await expect(modal.locator('[name="dataNewsletter[fullname]"]')).toHaveAttribute('required', '');
  await expect(modal.locator('[name="dataNewsletter[phone]"]')).toHaveAttribute('required', '');
  await expect(modal.locator('[name="dataNewsletter[email]"]')).toHaveAttribute('required', '');
  await expect(modal.locator('[name="dataNewsletter[address]"]')).toHaveAttribute('required', '');
  await expect(modal.locator('[name="dataNewsletter[company]"]')).toHaveAttribute('required', '');
  await expect(modal.locator('[name="submit-newsletter2"]')).toBeEnabled();
  await expect(page.locator('#button-contact-vr')).toBeHidden();
});

test('mobile news and project category rails do not re-layout into Slick after load', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The native category rail is a mobile/tablet layout.');
  for (const [path, selector] of [['/tin-tuc/', '.nh-filters'], ['/du-an-da-thuc-hien/', '.pts-ph-filters > div']]) {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    const rail = page.locator(selector).first();
    await expect(rail).toBeVisible();
    const initial = await rail.evaluate(element => ({
      height: element.getBoundingClientRect().height,
      flexWrap: getComputedStyle(element).flexWrap,
      overflowX: getComputedStyle(element).overflowX,
    }));
    await page.waitForTimeout(700);
    const settled = await rail.evaluate(element => ({
      height: element.getBoundingClientRect().height,
      slick: element.classList.contains('slick-initialized'),
    }));
    expect(initial.flexWrap).toBe('nowrap');
    expect(['auto', 'scroll']).toContain(initial.overflowX);
    expect(settled.slick).toBeFalsy();
    expect(Math.abs(settled.height - initial.height)).toBeLessThanOrEqual(1);
  }
});

test('mobile product detail separates description and specifications without overflow', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The stacked editorial layout only applies on mobile.');
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  const editorial = page.locator('.pts-pdp-editorial-grid');
  const description = page.locator('.pts-pdp-editorial-main');
  const specifications = page.locator('.pts-pdp-spec-column');
  await expect(editorial).toBeVisible();
  await expect(description).toBeVisible();
  await expect(specifications).toBeVisible();
  const layout = await editorial.evaluate(element => {
    const main = element.querySelector('.pts-pdp-editorial-main').getBoundingClientRect();
    const specs = element.querySelector('.pts-pdp-spec-column').getBoundingClientRect();
    return {
      direction: getComputedStyle(element).flexDirection,
      specsPosition: getComputedStyle(element.querySelector('.pts-pdp-spec-panel')).position,
      descriptionBeforeSpecs: main.top < specs.top,
    };
  });
  expect(layout.direction).toBe('column');
  expect(layout.specsPosition).toBe('relative');
  expect(layout.descriptionBeforeSpecs).toBeTruthy();
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('mobile child breadcrumb stays vertically aligned with its parent', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The compact breadcrumb behavior only applies on mobile.');
  await page.goto('/danh-muc-san-pham/den-nlmt/den-op-tran/', { waitUntil: 'domcontentloaded' });
  const visibleAnchors = page.locator('.pts-breadcrumb-bar .breadcrumb-item:visible a');
  expect(await visibleAnchors.count()).toBeGreaterThanOrEqual(2);
  const centers = await visibleAnchors.evaluateAll(anchors => anchors.map(anchor => {
    const rect = anchor.getBoundingClientRect();
    return rect.top + rect.height / 2;
  }));
  expect(Math.max(...centers) - Math.min(...centers)).toBeLessThanOrEqual(1);
});

test('mobile back-to-top arrow uses the same box center as its circle', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The 38px Back to Top override only applies on mobile.');
  await page.goto('/san-pham/', { waitUntil: 'domcontentloaded' });
  const metrics = await page.locator('.progress-wrap').evaluate(element => {
    const box = element.getBoundingClientRect();
    const icon = getComputedStyle(element, '::after');
    return {
      boxWidth: box.width,
      boxHeight: box.height,
      iconWidth: parseFloat(icon.width),
      iconHeight: parseFloat(icon.height),
      iconLeft: parseFloat(icon.left),
      iconTop: parseFloat(icon.top),
      display: icon.display,
      alignItems: icon.alignItems,
      justifyContent: icon.justifyContent,
    };
  });
  expect(metrics.iconWidth).toBe(metrics.boxWidth);
  expect(metrics.iconHeight).toBe(metrics.boxHeight);
  expect(metrics.iconLeft).toBe(0);
  expect(metrics.iconTop).toBe(0);
  expect(metrics.display).toBe('flex');
  expect(metrics.alignItems).toBe('center');
  expect(metrics.justifyContent).toBe('center');
});

test('home exposes the five approved category slots with real destinations', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  const categoryTrack = page.locator('.pts-home-category-rail');
  const categories = page.locator('.pts-home-category');
  await expect(page.locator('.pts-home-category-featured')).toHaveCount(1);
  await expect(categoryTrack.locator(':scope > .pts-home-category')).toHaveCount(4);
  await expect(categories).toHaveCount(5);
  const categoryTops = await categories.evaluateAll(items => items.map(item => Math.round(item.offsetTop)));
  expect(new Set(categoryTops).size).toBe(2);

  for (const category of await categories.all()) {
    await category.scrollIntoViewIfNeeded();
    await expect(category).toBeVisible();
    await expect(category.locator('.pts-home-category-bg img')).toBeVisible();
    expect(await category.getAttribute('href')).toBeTruthy();
  }
});

test('product category sidebar keeps child categories only in the type filter', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });

  await expect(page.locator('.pts-filter-card .filter-cat li > ul')).toHaveCount(0);
  await expect(page.locator('.pts-filter-card .filter-cat > .aside-content > ul > li').first()).toBeVisible();
  await expect(page.locator('input[name="car_cat"]')).not.toHaveCount(0);
  await expect(page.locator('input[name="car_cat"]').first()).toBeAttached();

  await page.goto('/den-op-tran/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('input[name="car_cat"]')).toHaveCount(0);
});

test('legacy child category redirects permanently to its hierarchical canonical URL', async ({ request }) => {
  const legacy = await request.get('/den-op-tran/', { maxRedirects: 0 });
  expect(legacy.status()).toBe(301);
  expect(legacy.headers().location).toMatch(/\/danh-muc-san-pham\/den-nlmt\/den-op-tran\/$/);
  const canonical = await request.get('/danh-muc-san-pham/den-nlmt/den-op-tran/');
  expect(canonical.ok()).toBeTruthy();
});

test('parent type query and legacy product links canonicalize without changing results', async ({ page, request }) => {
  // Kết quả của URL dạng query phải trùng URL canonical. So sánh chính hai tập
  // kết quả thay vì chốt cứng số sản phẩm — số đó đổi theo dữ liệu thật.
  await page.goto('/danh-muc-san-pham/den-nlmt/den-op-tran/', { waitUntil: 'domcontentloaded' });
  const canonicalHrefs = await page.locator('#show-product .product-item').evaluateAll(els => els.map(e => e.getAttribute('href')));
  expect(canonicalHrefs.length).toBeGreaterThan(0);

  await page.goto('/danh-muc-san-pham/den-nlmt/?id_list=20&is_cat=24', { waitUntil: 'domcontentloaded' });
  expect(new URL(page.url()).pathname).toBe('/danh-muc-san-pham/den-nlmt/den-op-tran/');
  const cards = page.locator('#show-product .product-item');
  expect(await cards.evaluateAll(els => els.map(e => e.getAttribute('href')))).toEqual(canonicalHrefs);
  for(const card of await cards.all()) await expect(card).toHaveAttribute('href', /^san-pham\/[a-z0-9-]+$/);
  const legacyHref = 'product/' + (await cards.first().getAttribute('href')).replace(/^san-pham\//, '') + '/';
  const legacyProduct = await request.get('/' + legacyHref, { maxRedirects: 0 });
  expect(legacyProduct.status()).toBe(301);
  expect(legacyProduct.headers().location).toMatch(/\/san-pham\/[a-z0-9-]+$/);
});

test('mobile product filters open as an accessible drawer and close after filtering', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'The filter drawer is a mobile/tablet interaction.');
  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });

  const trigger = page.locator('#opensideBar-product');
  const drawer = page.locator('#product-filter-drawer');
  await expect(trigger).toBeVisible();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(drawer).not.toHaveClass(/show/);
  await expect(drawer).toHaveAttribute('aria-hidden', 'true');

  await trigger.click();
  await expect(trigger).toHaveAttribute('aria-expanded', 'true');
  await expect(drawer).toHaveClass(/show/);
  await expect(drawer).toHaveAttribute('aria-hidden', 'false');
  await expect(page.locator('body')).toHaveClass(/pts-filter-drawer-open/);
  await expect(drawer.locator('.pts-filter-drawer-close')).toBeFocused();

  await page.keyboard.press('Escape');
  await expect(trigger).toBeFocused();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(drawer).not.toHaveClass(/show/);

  await trigger.click();
  await expect(drawer).toHaveClass(/show/);
  const firstBrand = drawer.locator('input[name="car_brand"]').first();
  await expect(firstBrand).toBeAttached();
  const filteredResponse = page.waitForResponse(response => response.url().includes('/api/fillter_service.php?'));
  await firstBrand.evaluate(input => {
    input.checked = true;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await assertHealthyAjaxResponse(filteredResponse);
  await expect(drawer).not.toHaveClass(/show/);
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
  await expect(page.locator('body')).not.toHaveClass(/pts-filter-drawer-open/);
});

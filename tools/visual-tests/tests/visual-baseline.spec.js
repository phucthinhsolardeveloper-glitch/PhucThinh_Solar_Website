const { test, expect } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function settlePage(page) {
  await page.evaluate(async () => {
    const initialHeight = document.body.scrollHeight;
    const step = Math.max(500, Math.ceil(initialHeight / 20));
    for (let position = 0; position < initialHeight; position += step) {
      window.scrollTo(0, position);
      await new Promise(resolve => setTimeout(resolve, 30));
    }
    /* theme-v2 đặt scroll-behavior:smooth. Nếu gọi scrollTo(0, 0) rồi chụp
       ngay, trang dài trên mobile vẫn có thể đang chạy animation cuộn và ảnh
       baseline vô tình ghi lại sticky header giữa trang. Ép lần trở về đầu
       trang là tức thời, đồng thời chờ sự kiện scroll cập nhật class .fixed. */
    const root = document.documentElement;
    const inlineScrollBehavior = root.style.scrollBehavior;
    root.style.scrollBehavior = 'auto';
    window.scrollTo(0, 0);
    await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    root.style.scrollBehavior = inlineScrollBehavior;
    if (window.jQuery) {
      window.jQuery('.slick-initialized').slick?.('slickPause');
      window.jQuery('.owl-carousel').trigger('stop.owl.autoplay');
    }
  });
  // Chờ font và ảnh thật sự xong: font swap đổi chiều cao chữ và ảnh chưa
  // decode làm dịch layout vài px, khiến baseline lệch ngẫu nhiên giữa các lần chạy.
  await page.evaluate(async () => {
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    /* 2026-09-16: ảnh dưới màn hình giờ dùng loading="lazy" nên sẽ KHÔNG bao
       giờ complete khi ở đầu trang - chờ chúng chỉ tốn 3s timeout rồi vẫn chụp
       trong lúc ảnh TRONG viewport có thể chưa decode xong, làm baseline đỏ
       ngẫu nhiên rồi pass ở retry. Chỉ chờ đúng ảnh đang nằm trong viewport
       (phần thực sự bị chụp), và chờ decode() thay vì chỉ sự kiện load để pixel
       đã sẵn sàng khi screenshot. */
    const inViewport = Array.from(document.images).filter(image => {
      const box = image.getBoundingClientRect();
      return box.width > 0 && box.height > 0 && box.bottom > 0 && box.top < window.innerHeight;
    });
    await Promise.all(inViewport.map(image => {
      const settled = image.complete && image.naturalWidth > 0
        ? Promise.resolve()
        : new Promise(resolve => {
            const finish = () => { clearTimeout(timer); resolve(); };
            const timer = setTimeout(finish, 5000);
            image.addEventListener('load', finish, { once: true });
            image.addEventListener('error', finish, { once: true });
          });
      return settled.then(() => {
        if (!image.decode) return undefined;
        /* decode() không tự có timeout — nếu treo (ảnh hỏng/máy đang thiếu
           RAM) thì cả Promise.all treo vô thời hạn, kéo cả test theo dù ảnh
           khác đã sẵn sàng. Ép trần 4s mỗi ảnh, không chờ decode nữa thì vẫn
           chụp (giống nhánh timeout của bước load ở trên). */
        return Promise.race([
          image.decode().catch(() => {}),
          new Promise(resolve => setTimeout(resolve, 4000)),
        ]);
      });
    }));
  });
  await page.waitForTimeout(1000);
}

async function dismissEntrancePopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    await popup.locator('[data-dismiss="modal"]').click();
    await expect(popup).not.toBeVisible();
  }
}

for (const [name, path] of [['home', '/'], ['products', '/san-pham/']]) {
  test(`${name} visual baseline`, async ({ page }) => {
    if (name === 'products') {
      /* /san-pham/ liệt kê hàng trăm sản phẩm thật (415 ở thời điểm viết) nên
         settlePage() phải chờ decode() nhiều ảnh hơn hẳn trang chủ — trên máy
         CI/host đang bận (ít RAM trống) việc này có thể vượt timeout mặc định
         30s dù trang render đúng (đã xác nhận bằng ảnh chụp thủ công không
         lỗi). Nới thời gian riêng cho đúng trang nặng này, không đụng dung
         sai pixel của assertion bên dưới. */
      test.setTimeout(180000);
    }
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    if (name === 'home') {
      // Đóng băng slide đầu để ảnh baseline không phụ thuộc lúc chụp vào
      // autoplay 4,2 giây đã được bật cho khách truy cập.
      await page.locator('[data-home-hero-slider]').evaluate(slider => {
        slider.dispatchEvent(new Event('touchstart'));
      });
    }
    await dismissEntrancePopup(page);
    await settlePage(page);
    await expect(page).toHaveScreenshot(`${name}.png`, {
      animations: 'disabled',
      fullPage: false,
      maxDiffPixelRatio: 0.01,
      timeout: 15000
    });
  });
}

test('home featured categories visual baseline', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await settlePage(page);
  const categories = page.locator('.pts-home-catalog').first();
  await categories.scrollIntoViewIfNeeded();
  /* 2026-09-16: settlePage() chỉ chờ ảnh trong viewport ở ĐẦU trang (xem chú
     thích trong hàm), nên ảnh của rail danh mục - vốn dùng lazy JS và nằm dưới
     màn hình - chưa kịp tải khi chụp, làm baseline đỏ ổn định. Sau khi cuộn tới
     đúng vùng sắp chụp thì chờ thêm cho ảnh của riêng vùng đó tải.
     Không dùng image.decode(): với ảnh lazy JS chưa được gán src, decode() có
     thể treo vô thời hạn và làm cả test timeout 30s. */
  await categories.evaluate(node => new Promise(resolve => {
    const done = () => {
      const images = Array.from(node.querySelectorAll('img'));
      return images.every(image => !image.getAttribute('src') || image.complete);
    };
    if (done()) return resolve();
    const deadline = Date.now() + 6000;
    const timer = setInterval(() => {
      if (done() || Date.now() > deadline) { clearInterval(timer); resolve(); }
    }, 150);
  }));
  await page.locator('.top-utility-bar, #headerscroll, #button-contact-vr').evaluateAll(elements => elements.forEach(element => {
    element.style.setProperty('display', 'none', 'important');
  }));
  await page.waitForTimeout(300);
  await expect(categories).toHaveScreenshot('home-featured-categories.png', {
    animations: 'disabled',
    maxDiffPixelRatio: 0.01,
    timeout: 15000
  });
});

test('product detail visual baseline', async ({ page, isMobile }) => {
  test.skip(isMobile, 'MagicZoom detail is covered by DOM regression on mobile; its element canvas is not screenshot-stable.');
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  await settlePage(page);
  const productDetail = page.locator('.grid-pro-detail');
  const detailTarget = isMobile ? page.locator('.right-pro-detail') : productDetail;
  await detailTarget.scrollIntoViewIfNeeded();
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  await page.waitForTimeout(500);
  await expect(isMobile ? page : productDetail).toHaveScreenshot('product-detail.png', {
    animations: 'disabled',
    maxDiffPixelRatio: 0.01,
    timeout: 15000
  });
});

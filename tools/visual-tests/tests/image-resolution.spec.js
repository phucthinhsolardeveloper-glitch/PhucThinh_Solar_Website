const { test, expect } = require('@playwright/test');

// GTranslate không liên quan tới ảnh nhưng chặn cho nhẹ và ổn định như các spec khác.
test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

// Đổi getImage() từ 'size-error' sang 'sizes' làm ảnh đi qua bộ sinh thumbnail.
// Chọn sai cỡ là ảnh bị phóng to lên và mờ — đúng lỗi đã xảy ra ngày 06/09 với
// hai ảnh trang "Về chúng tôi": thumbnail 300px nhưng hiển thị 696px.
//
// Test này bắt cả hai chiều:
//   - Ảnh mờ: file nhỏ hơn khung hiển thị (phóng to lên).
//   - Ảnh phí: file lớn hơn nhiều lần khung hiển thị (tải thừa băng thông).
//
// Ngưỡng lỏng có chủ ý: dưới 1.0x là mờ thấy rõ, trên 3.0x là phí rõ ràng.
// Khoảng giữa để dành cho màn retina và các khác biệt bố cục nhỏ.
const PAGES = [
  ['trang chủ', '/'],
  ['về chúng tôi', '/ve-chung-toi/'],
  ['danh sách sản phẩm', '/san-pham/'],
  ['giải pháp', '/giai-phap/'],
  ['dự án', '/du-an-da-thuc-hien/'],
  ['tin tức', '/tin-tuc/'],
  ['đại lý', '/dai-ly'],
  ['liên hệ', '/lien-he/'],
];

async function measureImages(page) {
  return page.evaluate(() => {
    const rows = [];
    document.querySelectorAll('img').forEach(img => {
      const src = img.currentSrc || img.src;
      // Xét ảnh nội dung qua upload/thumbs; bộ ảnh campaign đóng gói cũng đi
      // qua thumbs nên chịu cùng ngưỡng nét với ảnh Admin.
      if (!/\/(thumbs|upload)\//.test(src)) return;
      const box = img.getBoundingClientRect();
      if (box.width < 4 || img.naturalWidth === 0) return;
      rows.push({
        file: src.split('/').pop(),
        displayed: Math.round(box.width),
        natural: img.naturalWidth,
        ratio: img.naturalWidth / Math.round(box.width),
      });
    });
    return rows;
  });
}

for (const [label, path] of PAGES) {
  test(`ảnh trên ${label} không bị mờ vì thumbnail quá nhỏ`, async ({ page }) => {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    // Cuộn hết trang để ảnh lazy-load kịp tải trước khi đo.
    await page.evaluate(async () => {
      const step = Math.max(600, Math.ceil(document.body.scrollHeight / 12));
      for (let y = 0; y < document.body.scrollHeight; y += step) {
        window.scrollTo(0, y);
        await new Promise(r => setTimeout(r, 60));
      }
      window.scrollTo(0, 0);
    });
    await page.evaluate(async () => {
      const pending = Array.from(document.images).filter(i => !i.complete);
      await Promise.all(pending.map(i => new Promise(done => {
        i.addEventListener('load', done, { once: true });
        i.addEventListener('error', done, { once: true });
      })));
    });

    const images = await measureImages(page);
    expect(images.length, 'trang phải có ảnh để đo').toBeGreaterThan(0);

    const blurry = images.filter(i => i.ratio < 1.0);
    expect(
      blurry,
      'Ảnh bị phóng to nên mờ — tăng cỡ trong tham số sizes của getImage():\n' +
        blurry.map(i => `  ${i.file}: file ${i.natural}px, hiển thị ${i.displayed}px (${i.ratio.toFixed(2)}x)`).join('\n')
    ).toEqual([]);
  });

  // Chỉ chạy trên desktop: site phục vụ CÙNG một file ảnh cho mọi khung hình,
  // không có srcset theo viewport. Trên mobile khung hiển thị nhỏ hơn hẳn nên
  // ảnh cỡ desktop luôn vượt ngưỡng — báo đỏ ở đó là báo nhầm, không phải lỗi.
  test(`ảnh trên ${label} không tải thừa dung lượng`, async ({ page, isMobile }) => {
    test.skip(isMobile, 'Ảnh dùng chung cho mọi khung hình; chỉ đo lãng phí ở desktop.');
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await page.evaluate(async () => {
      const pending = Array.from(document.images).filter(i => !i.complete);
      await Promise.all(pending.map(i => new Promise(done => {
        i.addEventListener('load', done, { once: true });
        i.addEventListener('error', done, { once: true });
      })));
    });

    const images = await measureImages(page);
    const wasteful = images.filter(i => i.ratio > 3.0);
    expect(
      wasteful,
      'Ảnh tải lớn hơn 3 lần khung hiển thị — dùng sizes thay vì size-error, hoặc giảm cỡ:\n' +
        wasteful.map(i => `  ${i.file}: file ${i.natural}px, hiển thị ${i.displayed}px (${i.ratio.toFixed(1)}x)`).join('\n')
    ).toEqual([]);
  });
}

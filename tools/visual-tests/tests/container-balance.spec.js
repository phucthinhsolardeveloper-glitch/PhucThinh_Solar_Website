const { test, expect } = require('@playwright/test');

const routes = [
  ['Trang chủ', '/', '.pts-home-container'],
  ['Danh mục sản phẩm', '/san-pham/', '.wap-main > .wrap-main'],
  ['Danh mục sản phẩm con', '/danh-muc-san-pham/den-nlmt/', '.wap-main > .wrap-main'],
  ['Chi tiết sản phẩm', '/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', '.wap-main > .wrap-main'],
  ['Giải pháp', '/giai-phap/', '.pts-solution-hub .uk-container'],
  ['Chi tiết giải pháp', '/giai-phap/dien-mat-troi-ho-gia-dinh/', '.pts-solution-detail .uk-container'],
  ['Dự án', '/du-an-da-thuc-hien/', '.pts-project-hub .uk-container'],
  ['Chi tiết dự án', '/du-an-dien-nlmt-ho-anh-hoa-thanh-an/', '.wap-main > .wrap-main'],
  ['Kiến thức', '/tin-tuc/', '.pts-news-hub .uk-container'],
  ['Chi tiết kiến thức', '/tin-tuc/8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio/', '.wap-main > .wrap-main'],
  ['Giới thiệu', '/ve-chung-toi/', '.pts-about-page .uk-container'],
  ['Hợp tác đại lý', '/dai-ly/', '.pts-dealer-hub .uk-container'],
  ['Liên hệ', '/lien-he/', '.pts-contact-intro-row'],
];

function expectedPageEdge(viewportWidth) {
  const isWide = viewportWidth >= 1600;
  const responsiveGutter = isWide
    ? Math.min(220, Math.max(96, viewportWidth * 0.08))
    : Math.min(96, Math.max(40, viewportWidth * 0.05));
  const contentWidth = Math.min(isWide ? 2200 : 1560, viewportWidth - responsiveGutter);
  return (viewportWidth - contentWidth) / 2;
}

// 1600px là mốc quan trọng trên laptop có display scale 125%: trước đây
// trang con bị ép tối thiểu 1560px trong khi homepage chỉ rộng 1472px.
for (const width of [1366, 1440, 1600, 1920, 2560]) {
  test(`các trang public cùng trục container với trang chủ ở ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1080 });
    const expectedEdge = expectedPageEdge(width);

    for (const [name, route, selector] of routes) {
      await page.goto(route, { waitUntil: 'domcontentloaded' });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - innerWidth);
      expect(overflow, `${name} không được tràn ngang`).toBeLessThanOrEqual(1);

      const target = page.locator(selector).first();
      await expect(target, `${name} phải có container chính`).toBeVisible();

      const edges = await target.evaluate(node => {
        const rect = node.getBoundingClientRect();
        return { left: rect.left, right: innerWidth - rect.right };
      });

      expect(Math.abs(edges.left - edges.right), `${name} phải cân giữa màn hình`).toBeLessThanOrEqual(1);
      expect(Math.abs(edges.left - expectedEdge), `${name} phải cùng trục với trang chủ`).toBeLessThanOrEqual(1);

      if (route !== '/') {
        const breadcrumb = page.locator('.pts-breadcrumb-bar .wrap-content2').first();
        await expect(breadcrumb, `${name} phải có breadcrumb`).toBeVisible();
        const breadcrumbEdges = await breadcrumb.evaluate(node => {
          const rect = node.getBoundingClientRect();
          return { left: rect.left, right: innerWidth - rect.right };
        });
        expect(Math.abs(breadcrumbEdges.left - breadcrumbEdges.right), `${name} breadcrumb phải cân giữa`).toBeLessThanOrEqual(1);
        expect(Math.abs(breadcrumbEdges.left - expectedEdge), `${name} breadcrumb phải cùng trục nội dung`).toBeLessThanOrEqual(1);
      }
    }
  });
}

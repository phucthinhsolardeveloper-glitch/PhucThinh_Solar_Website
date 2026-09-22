const { test, expect } = require('@playwright/test');

const routes = [
  ['Trang chủ', '/', '.pts-home-container'],
  ['Danh mục sản phẩm', '/san-pham/', '.pts-catblock'],
  ['Giải pháp', '/giai-phap/', '.pts-solution-hub .uk-container'],
  ['Dự án', '/du-an-da-thuc-hien/', '.pts-project-hub .uk-container'],
  ['Kiến thức', '/tin-tuc/', '.pts-news-hub .uk-container'],
  ['Giới thiệu', '/ve-chung-toi/', '.pts-about-page .uk-container'],
  ['Hợp tác đại lý', '/dai-ly/', '.pts-dealer-hub .uk-container'],
  ['Liên hệ', '/lien-he/', '.pts-contact-intro-row'],
];

function expectedPageEdge(viewportWidth) {
  const responsiveGutter = Math.min(220, Math.max(96, viewportWidth * 0.08));
  const contentWidth = Math.min(2200, viewportWidth - responsiveGutter);
  return (viewportWidth - contentWidth) / 2;
}

for (const width of [1920, 2560]) {
  test(`các trang public cùng trục container với trang chủ ở ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1080 });
    const expectedEdge = expectedPageEdge(width);

    for (const [name, route, selector] of routes) {
      await page.goto(route, { waitUntil: 'domcontentloaded' });
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

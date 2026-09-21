const { test, expect } = require('@playwright/test');

const widths = [320, 360, 375, 390, 414, 768];
const routes = [
  ['home', '/'],
  ['about', '/gioi-thieu/'],
  ['category', '/danh-muc-san-pham/den-nlmt/'],
  ['product-detail', '/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w'],
  ['news', '/tin-tuc/'],
  ['news-detail', '/tin-tuc/8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio/'],
  ['contact', '/lien-he/'],
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('responsive matrix không tràn ngang và chỉ có một page gutter trên mobile', async ({ page }) => {
  test.setTimeout(120000);
  for (const width of widths) {
    await page.setViewportSize({ width, height: 900 });

    for (const [name, route] of routes) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
      expect(response && response.status(), `${name} ${width}px status`).toBe(200);

      const layout = await page.evaluate(() => {
        const px = value => Number.parseFloat(value) || 0;
        const wrap = document.querySelector('.wrap-main');
        const style = getComputedStyle(wrap);
        return {
          overflow: document.documentElement.scrollWidth - window.innerWidth,
          homeWrapper: wrap.classList.contains('wrap-home'),
          paddingLeft: px(style.paddingLeft),
          paddingRight: px(style.paddingRight),
        };
      });

      expect(layout.overflow, `${name} ${width}px overflow`).toBeLessThanOrEqual(1);
      if (width < 768) {
        const gutter = width <= 359 ? 12 : 16;
        const expected = layout.homeWrapper ? 0 : gutter;
        expect(layout.paddingLeft, `${name} ${width}px left gutter owner`).toBe(expected);
        expect(layout.paddingRight, `${name} ${width}px right gutter owner`).toBe(expected);
      }
    }
  }
});

test('wrapper cấu trúc được flatten nhưng entity card vẫn giữ trên mobile', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 900 });

  await page.goto('/', { waitUntil: 'domcontentloaded' });
  const home = await page.evaluate(() => {
    const showcase = document.querySelector('.pts-home-category-featured').getBoundingClientRect();
    const category = document.querySelector('.pts-home-category');
    return {
      showcaseLeft: showcase.left,
      showcaseRight: window.innerWidth - showcase.right,
      categoryExists: Boolean(category),
    };
  });
  expect(home.showcaseLeft).toBeGreaterThanOrEqual(12);
  expect(Math.abs(home.showcaseLeft - home.showcaseRight)).toBeLessThanOrEqual(1);
  expect(home.categoryExists).toBeTruthy();

  await page.goto('/gioi-thieu/', { waitUntil: 'domcontentloaded' });
  const about = await page.evaluate(() => {
    const history = document.querySelector('.pts-about-history .pts-about-milestone');
    const historyMedia = document.querySelector('.pts-about-history-media').getBoundingClientRect();
    const certificate = document.querySelector('.pts-about-certificate-card');
    const certificateThumb = document.querySelector('.pts-about-certificate-thumb');
    const certificateStyle = getComputedStyle(certificate);
    return {
      historyColumns: getComputedStyle(history).gridTemplateColumns,
      historyMediaLeft: historyMedia.left,
      historyMediaRight: window.innerWidth - historyMedia.right,
      certificateColumns: certificateStyle.gridTemplateColumns,
      certificateMinHeight: certificateStyle.minHeight,
      certificateHeight: certificate.getBoundingClientRect().height,
      certificateThumbHeight: certificateThumb.getBoundingClientRect().height,
    };
  });
  expect(about.historyColumns.split(' ')).toHaveLength(1);
  expect(Math.abs(about.historyMediaLeft)).toBeLessThanOrEqual(1);
  expect(Math.abs(about.historyMediaRight)).toBeLessThanOrEqual(1);
  expect(about.certificateColumns.split(' ')).toHaveLength(2);
  expect(about.certificateMinHeight).toBe('0px');
  expect(about.certificateThumbHeight).toBe(112);
  expect(about.certificateHeight).toBeLessThanOrEqual(140);

  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });
  const category = await page.evaluate(() => {
    const hero = document.querySelector('.pts-category-hero').getBoundingClientRect();
    const filter = getComputedStyle(document.querySelector('.pts-filter-card'));
    return {
      heroLeft: hero.left,
      heroRight: window.innerWidth - hero.right,
      filterPaddingLeft: filter.paddingLeft,
      filterBorder: filter.borderTopWidth,
    };
  });
  expect(Math.abs(category.heroLeft)).toBeLessThanOrEqual(1);
  expect(Math.abs(category.heroRight)).toBeLessThanOrEqual(1);
  expect(category.filterPaddingLeft).toBe('0px');
  expect(category.filterBorder).toBe('0px');

  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  const detail = await page.evaluate(() => {
    const tabs = getComputedStyle(document.querySelector('.tabs-pro-detail'));
    const copy = getComputedStyle(document.querySelector('.pts-product-desc'));
    return {
      tabsBorder: tabs.borderTopWidth,
      tabsRadius: tabs.borderRadius,
      copyPaddingLeft: copy.paddingLeft,
    };
  });
  expect(detail.tabsBorder).toBe('0px');
  expect(detail.tabsRadius).toBe('0px');
  expect(detail.copyPaddingLeft).toBe('0px');

  await page.goto('/tin-tuc/', { waitUntil: 'domcontentloaded' });
  const news = await page.locator('.nh-cta-band').evaluate(element => {
    const rect = element.getBoundingClientRect();
    return { left: rect.left, right: window.innerWidth - rect.right };
  });
  expect(Math.abs(news.left)).toBeLessThanOrEqual(1);
  expect(Math.abs(news.right)).toBeLessThanOrEqual(1);

  await page.goto('/tin-tuc/8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio/', { waitUntil: 'domcontentloaded' });
  const newsDetail = await page.evaluate(() => {
    const cta = document.querySelector('.nh-cta-band').getBoundingClientRect();
    const article = getComputedStyle(document.querySelector('.box-detail-news'));
    return {
      ctaLeft: cta.left,
      ctaRight: window.innerWidth - cta.right,
      articlePaddingLeft: article.paddingLeft,
    };
  });
  expect(Math.abs(newsDetail.ctaLeft)).toBeLessThanOrEqual(1);
  expect(Math.abs(newsDetail.ctaRight)).toBeLessThanOrEqual(1);
  expect(newsDetail.articlePaddingLeft).toBe('0px');

  await page.goto('/lien-he/', { waitUntil: 'domcontentloaded' });
  const contact = await page.evaluate(() => {
    const intro = document.querySelector('.pts-contact-intro-row').getBoundingClientRect();
    const form = getComputedStyle(document.querySelector('#yeu-cau-tu-van .formContact'));
    return {
      introLeft: intro.left,
      introRight: window.innerWidth - intro.right,
      formPaddingLeft: form.paddingLeft,
      formBorder: form.borderTopWidth,
      formBackground: form.backgroundColor,
    };
  });
  expect(Math.abs(contact.introLeft)).toBeLessThanOrEqual(1);
  expect(Math.abs(contact.introRight)).toBeLessThanOrEqual(1);
  expect(contact.formPaddingLeft).toBe('0px');
  expect(contact.formBorder).toBe('0px');
  expect(contact.formBackground).toBe('rgba(0, 0, 0, 0)');
});

test('mốc 768px giữ nguyên boxed hierarchy', async ({ page }) => {
  await page.setViewportSize({ width: 768, height: 900 });

  for (const route of ['/', '/tin-tuc/', '/lien-he/']) {
    await page.goto(route, { waitUntil: 'domcontentloaded' });
    const padding = await page.locator('.wrap-main.wrap-home').evaluate(element => {
      const style = getComputedStyle(element);
      return [Number.parseFloat(style.paddingLeft), Number.parseFloat(style.paddingRight)];
    });
    expect(padding[0]).toBeGreaterThan(0);
    expect(padding[1]).toBeGreaterThan(0);
  }

  await page.goto('/danh-muc-san-pham/den-nlmt/', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.pts-category-hero')).toHaveCSS('border-radius', '3px');
});

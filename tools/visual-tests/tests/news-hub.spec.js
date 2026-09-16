const { test, expect } = require('@playwright/test');

// Giữ DOM tiếng Việt ổn định và loại nhiễu pageerror từ widget dịch bên thứ ba;
// bản thân hub/bài viết Tin tức không phụ thuộc widget này để hoạt động.
test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('News feature cards keep image, copy and action separated on wide screens', async ({ page }) => {
  await page.setViewportSize({ width: 2048, height: 1080 });
  await page.goto('/tin-tuc/', { waitUntil: 'domcontentloaded' });

  const cards = await page.locator('.nh-secondary').evaluateAll(nodes => nodes.map(card => {
    const media = card.querySelector('.nh-media').getBoundingClientRect();
    const copy = card.querySelector('.nh-card-copy').getBoundingClientRect();
    const arrow = card.querySelector('.nh-arrow').getBoundingClientRect();
    return {
      mediaRight: media.right,
      copyLeft: copy.left,
      copyRight: copy.right,
      arrowLeft: arrow.left,
      arrowRight: arrow.right,
    };
  }));

  expect(cards).toHaveLength(2);
  for (const card of cards) {
    expect(card.mediaRight).toBeLessThanOrEqual(card.copyLeft + 1);
    expect(card.arrowLeft).toBeGreaterThanOrEqual(card.copyLeft);
    expect(card.arrowRight).toBeLessThan(card.copyRight - 24);
  }
});

test('News gallery, pagination, categories, search and consultation', async ({ page }) => {
  const errors=[];
  page.on('pageerror', e=>errors.push({ message: e.message, stack: e.stack }));
  await page.goto('/tin-tuc/', {waitUntil:'domcontentloaded'});
  await expect(page.locator('main.pts-news-hub')).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.nh-featured .nh-card')).toHaveCount(3);
  await expect(page.locator('.nh-grid .nh-card')).toHaveCount(6);
  const first=await page.locator('.nh-card').evaluateAll(es=>es.map(e=>e.getAttribute('href')));
  expect(new Set(first).size).toBe(first.length);
  for(const img of await page.locator('.pts-news-hub img').all()) {
    await img.scrollIntoViewIfNeeded();
    await expect.poll(()=>img.evaluate(e=>e.complete && e.naturalWidth>0)).toBe(true);
  }
  await page.evaluate(()=>window.scrollTo(0,0));
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  await Promise.all([
    page.waitForURL(url=>url.searchParams.get('p')==='2'),
    page.locator('.nh-pagination [rel=next]').click()
  ]);
  await expect(page.locator('.nh-featured')).toHaveCount(0);
  const second=await page.locator('.nh-grid .nh-card').evaluateAll(es=>es.map(e=>e.getAttribute('href')));
  expect(second.length).toBeGreaterThan(0);
  expect(second.some(h=>first.includes(h))).toBe(false);
  await page.goto('/tin-tuc/');
  const category=page.locator('.nh-filter').nth(1);
  const categoryName=await category.innerText();
  const categoryHref=await category.getAttribute('href');
  await Promise.all([
    page.waitForURL(url=>url.pathname.endsWith('/'+categoryHref.replace(/^\//,''))),
    category.click()
  ]);
  await expect(page.locator('.nh-filter.is-active')).toContainText(categoryName.trim());
  await expect(page.locator('.nh-featured')).toHaveCount(0);
  await page.locator('#nh-search-input').fill('zz-no-match-pts-2026');
  await Promise.all([
    page.waitForURL(url=>url.searchParams.get('q')==='zz-no-match-pts-2026'),
    page.locator('.nh-search button').click()
  ]);
  await expect(page.locator('.nh-empty')).toBeVisible();
  await expect(page.locator('meta[name=robots]')).toHaveAttribute('content','noindex,follow');
  await expect(page.locator('#nh-search-input')).toHaveValue('zz-no-match-pts-2026');
  await page.goto('/tin-tuc/?q='+encodeURIComponent("' OR 1=1 -- <img src=x onerror=alert(1)>"));
  // Legacy request firewall may reject this before the hub; source-level
  // tests separately prove parameter binding and escaping.
  expect(await page.locator('.nh-empty').count() > 0 || (await page.locator('body').innerText()).trim() === '404 Not found').toBe(true);
  await expect(page.locator('.nh-query img')).toHaveCount(0);
  await page.goto('/tin-tuc/?q='+encodeURIComponent('năng lượng'));
  expect(await page.locator('.nh-card').count()).toBeGreaterThan(0);
  const next=page.locator('.nh-pagination [rel=next]');
  if(await next.count()) expect(await next.getAttribute('href')).toContain('q=');
  await page.locator('.nh-consult').click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
  await page.goto('/'+first[0],{waitUntil:'domcontentloaded'});
  await expect(page.locator('.box-detail-news')).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.nh-cta')).toBeVisible();
  expect(errors).toEqual([]);
});

test('News detail does not render the featured image twice when CKEditor already contains the same visual', async ({ page }) => {
  const duplicateCoverArticles = [
    '/phuc-thinh-solar-chinh-thuc-la-doi-tac-phan-phoi-doc-quyen-den-nang-luong-mat-troi-blue-carbon-tai-viet-nam/',
    '/duoc-gi-khi-hop-tac-cung-phuc-thinh-solar/',
    '/blue-carbon-den-nang-luong-mat-troi-cho-du-an/',
    '/4-luu-y-quan-trong-khi-lap-dat-va-su-dung-den-nang-luong-mat-troi/',
    '/top-4-loi-ich-khi-su-dung-den-nang-luong-mat-troi/',
    '/pho-thu-tuong-can-giai-phap-thuc-day-dien-mat-troi-mai-nha/',
  ];
  for(const path of duplicateCoverArticles) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status(), path).toBe(200);
    await expect(page.locator('.box-detail-news')).toBeVisible();
    await expect(page.locator('.pts-news-detail-cover'), path).toHaveCount(0);
    expect(await page.locator('#toc-content img').count(), path).toBeGreaterThan(0);
  }

  const distinctImagesPath = '/chip-led-cree-thuong-hieu-top-1/';
  const distinctResponse = await page.goto(distinctImagesPath, { waitUntil: 'domcontentloaded' });
  expect(distinctResponse.status()).toBe(200);
  await expect(page.locator('.pts-news-detail-cover')).toHaveCount(1);
  await expect(page.locator('#toc-content img')).toHaveCount(1);
});

const { test, expect } = require('@playwright/test');
test('News gallery, pagination, categories, search and consultation', async ({ page }, info) => {
  const errors=[];
  page.on('pageerror', e=>errors.push(e.message));
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
  await page.screenshot({path:info.outputPath('news-hub.png'),fullPage:true});
  expect(await page.evaluate(()=>document.documentElement.scrollWidth-document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  await page.locator('.nh-pagination [rel=next]').click();
  await expect(page.locator('.nh-featured')).toHaveCount(0);
  const second=await page.locator('.nh-grid .nh-card').evaluateAll(es=>es.map(e=>e.getAttribute('href')));
  expect(second.length).toBeGreaterThan(0);
  expect(second.some(h=>first.includes(h))).toBe(false);
  await page.goto('/tin-tuc/');
  const category=page.locator('.nh-filter').nth(1);
  const categoryName=await category.innerText();
  await category.click();
  await expect(page.locator('.nh-filter.is-active')).toContainText(categoryName.trim());
  await expect(page.locator('.nh-featured')).toHaveCount(0);
  await page.locator('#nh-search-input').fill('zz-no-match-pts-2026');
  await page.locator('.nh-search button').click();
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

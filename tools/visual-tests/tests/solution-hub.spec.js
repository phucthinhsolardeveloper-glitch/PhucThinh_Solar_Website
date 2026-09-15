const { test, expect } = require('@playwright/test');

test('Solution hub supports choices, equipment and consultation without layout errors', async ({ page }, testInfo) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
  const hub = page.locator('.pts-solution-hub');
  await expect(hub).toBeVisible();
  await expect(hub.locator('h1')).toHaveCount(1);
  await expect(hub.locator('.pts-sh-choice')).toHaveCount(5);
  await expect(hub.locator('.pts-sh-choice h2')).toHaveText([
    'Điện mặt trời gia đình',
    'Điện mặt trời doanh nghiệp',
    'Lưu trữ điện',
    'Chiếu sáng mặt trời',
    'Bơm nước mặt trời',
  ]);
  await expect(hub.locator('.pts-sh-editorial-grid .toc-list > li')).toHaveCount(5);
  const choiceLayout = await hub.locator('.pts-sh-choice-grid').evaluate(grid => ({
    clientWidth: grid.clientWidth,
    scrollWidth: grid.scrollWidth,
    tops: Array.from(grid.querySelectorAll('.pts-sh-choice')).map(card => Math.round(card.getBoundingClientRect().top)),
  }));
  expect(new Set(choiceLayout.tops).size).toBe(1);
  const editorialWidth = await hub.locator('#toc-content').evaluate(content => content.getBoundingClientRect().width);
  const introLayout = await hub.locator('.pts-sh-intro-grid').evaluate(intro => {
    const title = intro.querySelector('h1').getBoundingClientRect();
    const copy = intro.querySelector('.pts-sh-intro-copy > p').getBoundingClientRect();
    return { title: { top: title.top, right: title.right, bottom: title.bottom, left: title.left }, copy: { top: copy.top, left: copy.left } };
  });
  if(testInfo.project.name.includes('mobile')) {
    expect(choiceLayout.scrollWidth).toBeGreaterThan(choiceLayout.clientWidth);
    await expect(hub.locator('.pts-sh-editorial-grid > .pts-sd-toc')).toBeHidden();
    expect(editorialWidth).toBeGreaterThan(300);
    expect(introLayout.copy.top).toBeGreaterThan(introLayout.title.bottom);
  } else {
    expect(choiceLayout.scrollWidth).toBeLessThanOrEqual(choiceLayout.clientWidth + 1);
    await expect(hub.locator('.pts-sh-editorial-grid > .pts-sd-toc')).toBeVisible();
    expect(editorialWidth).toBeGreaterThan(700);
    expect(Math.abs(introLayout.copy.top - introLayout.title.top)).toBeLessThanOrEqual(2);
    expect(introLayout.copy.left - introLayout.title.right).toBeGreaterThan(40);
    const editorialGap = await hub.locator('.pts-sh-editorial-grid').evaluate(grid => {
      const toc = grid.querySelector('.pts-sd-toc').getBoundingClientRect();
      const content = grid.querySelector('#toc-content').getBoundingClientRect();
      return content.left - toc.right;
    });
    expect(editorialGap).toBeGreaterThan(75);
  }
  await expect(hub.locator('.pts-sh-equipment-link')).toHaveCount(4);
  await expect(hub.locator('.date_content, .author_post')).toHaveCount(0);
  const links = await hub.locator('.pts-sh-choice, .pts-sh-equipment-link').evaluateAll(nodes => nodes.map(node => node.href));
  for(const link of links) {
    const response = await page.request.get(link);
    expect(response.status(), link).toBe(200);
  }
  for(const image of await hub.locator('img').all()) {
    await image.scrollIntoViewIfNeeded();
    await expect.poll(() => image.evaluate(el => el.complete && el.naturalWidth > 0)).toBe(true);
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  if(testInfo.project.name.includes('mobile')) {
    const introImage = hub.locator('.pts-sh-intro-image');
    if(await introImage.count()) {
      const order = await hub.evaluate(el => ({copy:el.querySelector('.pts-sh-intro-copy').getBoundingClientRect().top,image:el.querySelector('.pts-sh-intro-image').getBoundingClientRect().top}));
      expect(order.copy).toBeLessThan(order.image);
    }
  }
  await expect(hub.locator('.pts-sh-hotline')).toHaveAttribute('href', 'tel:0944555957');
  await hub.locator('.pts-sh-consult').first().click();
  await expect(page.locator('#ptsContactModal')).toBeVisible();
  expect(errors).toEqual([]);
});

test('Existing business and household solution routes share the upgraded design', async ({ page }) => {
  for(const route of ['/doanh-nghiep', '/danh-muc-giai-phap/ho-gia-dinh/']) {
    const response = await page.goto(route, { waitUntil: 'domcontentloaded' });
    expect(response.status(), route).toBe(200);
    const category = page.locator('.pts-solution-category');
    await expect(category).toBeVisible();
    await expect(category.locator('h1')).toHaveCount(1);
    await expect(category.locator('.pts-sh-category-nav a')).toHaveCount(2);
    await expect(category.locator('.pts-sh-category-card')).toHaveCount(1);
    await expect(category.locator('.date_content, .author_post')).toHaveCount(0);
    await expect(category.locator('.pts-sh-category-card')).toHaveAttribute('href', /^giai-phap\/[a-z0-9-]+\/$/);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
  }
});

test('Solution detail uses the canonical nested route and keeps legacy links working', async ({ page }) => {
  await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
  const canonicalHref = await page.locator('.pts-sh-choice').first().getAttribute('href');
  expect(canonicalHref).toMatch(/^giai-phap\/[a-z0-9-]+\/$/);
  const slug = canonicalHref.split('/')[1];

  for(const legacyPath of [`/${slug}`, `/solution/${slug}/`]) {
    const legacyResponse = await page.request.get(legacyPath, { maxRedirects: 0 });
    expect(legacyResponse.status(), legacyPath).toBe(301);
    expect(new URL(legacyResponse.headers().location).pathname).toBe(`/${canonicalHref}`);
  }

  const detailResponse = await page.goto(`/${canonicalHref}`, { waitUntil: 'domcontentloaded' });
  expect(detailResponse.status()).toBe(200);
  const detail = page.locator('.pts-solution-detail');
  await expect(detail).toBeVisible();
  await expect(page.locator('h1')).toHaveCount(1);
  await expect(page.locator('.breadcrumb-item')).toHaveCount(3);
  await expect(detail.locator('.pts-solution-editorial h2')).toHaveCount(7);
  await expect(detail.locator('.date_content, .author_post')).toHaveCount(0);
  await expect(detail.locator('.pts-sd-switcher-all')).toHaveAttribute('href', 'giai-phap/');
  await expect(detail.locator('.pts-sd-switcher a')).toHaveCount(6);
  const switcherPaths = await detail.locator('.pts-sd-switcher a').evaluateAll(links => links.map(link => new URL(link.href).pathname));
  expect(switcherPaths).toEqual([
    '/giai-phap/',
    '/giai-phap/dien-mat-troi-ho-gia-dinh/',
    '/giai-phap/dien-mat-troi-doanh-nghiep/',
    '/giai-phap/he-thong-luu-tru-bess/',
    '/giai-phap/chieu-sang-nang-luong-mat-troi/',
    '/giai-phap/bom-nuoc-nang-luong-mat-troi/',
  ]);
  await expect(detail.locator('.pts-sh-equipment-link')).toHaveCount(4);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
});

test('Renamed lighting solution keeps both previous public URLs as permanent redirects', async ({ page }) => {
  const destination = '/giai-phap/chieu-sang-nang-luong-mat-troi/';
  for(const legacyPath of [
    '/thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh/',
    '/giai-phap/thiet-bi-den-nang-luong-mat-troi-quy-mo-ho-gia-dinh/',
  ]) {
    const response = await page.request.get(legacyPath, { maxRedirects: 0 });
    expect(response.status(), legacyPath).toBe(301);
    expect(new URL(response.headers().location).pathname).toBe(destination);
  }
});

test('All five solution details share the balanced responsive template', async ({ page }, testInfo) => {
  const paths = [
    '/giai-phap/dien-mat-troi-ho-gia-dinh/',
    '/giai-phap/dien-mat-troi-doanh-nghiep/',
    '/giai-phap/he-thong-luu-tru-bess/',
    '/giai-phap/chieu-sang-nang-luong-mat-troi/',
    '/giai-phap/bom-nuoc-nang-luong-mat-troi/',
  ];
  for(const path of paths) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status(), path).toBe(200);
    const detail = page.locator('.pts-solution-detail');
    await expect(detail).toBeVisible();
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('.breadcrumb-item')).toHaveCount(3);
    expect(await detail.locator('.pts-solution-editorial h2').count()).toBeGreaterThanOrEqual(3);
    const toc = detail.locator('.pts-sd-content-grid > .pts-sd-toc');
    if(testInfo.project.name.includes('mobile')) {
      await expect(toc).toBeHidden();
    } else {
      await expect(toc).toBeVisible();
      const contentGap = await detail.locator('.pts-sd-content-grid').evaluate(grid => {
        const tocBox = grid.querySelector('.pts-sd-toc').getBoundingClientRect();
        const contentBox = grid.querySelector('.pts-solution-editorial').getBoundingClientRect();
        return contentBox.left - tocBox.right;
      });
      expect(contentGap, path).toBeGreaterThan(75);
    }
    const hero = detail.locator('.pts-sd-hero-grid');
    if(await hero.evaluate(node => node.classList.contains('is-text-only'))) {
      const heroLayout = await hero.evaluate(node => {
        const title = node.querySelector('h1').getBoundingClientRect();
        const lead = node.querySelector('.pts-sd-lead').getBoundingClientRect();
        return { title: { top: title.top, right: title.right, bottom: title.bottom }, lead: { top: lead.top, left: lead.left } };
      });
      if(testInfo.project.name.includes('mobile')) {
        expect(heroLayout.lead.top, path).toBeGreaterThan(heroLayout.title.bottom);
      } else {
        expect(Math.abs(heroLayout.lead.top - heroLayout.title.top), path).toBeLessThanOrEqual(2);
        expect(heroLayout.lead.left - heroLayout.title.right, path).toBeGreaterThan(40);
      }
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth), path).toBeLessThanOrEqual(1);
  }
});

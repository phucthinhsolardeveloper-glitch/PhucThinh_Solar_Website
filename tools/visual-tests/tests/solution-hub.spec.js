const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

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
  const hubEditorialText = await hub.locator('.pts-sh-editorial').innerText();
  expect(hubEditorialText).not.toMatch(/\b(?:backup|battery|load|grid|cabinet|MWp|kWp)\b|phụ tải|bài toán|kiến trúc hệ thống|chiến lược vận hành/i);
  expect((await hub.locator('.pts-sh-editorial h2').allTextContents()).some(heading => heading.includes('?'))).toBe(false);
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
    /* Quy tắc UX 2026-09-16: landing dùng CHUNG kiểu mục lục với bài
       chi tiết, nên mục lục hiện cả trên mobile (trước đây bị ẩn). Vẫn phải bị
       giới hạn chiều cao + tự cuộn để không đẩy nội dung xuống quá sâu. */
    const landingToc = hub.locator('.pts-sh-editorial-grid > .pts-sd-toc');
    await expect(landingToc).toBeVisible();
    const tocBox = await landingToc.evaluate(toc => {
      const list = toc.querySelector('.toc-list');
      return {
        tocWidth: toc.getBoundingClientRect().width,
        listHeight: list.getBoundingClientRect().height,
        listOverflowY: getComputedStyle(list).overflowY,
        itemCount: list.querySelectorAll('a').length
      };
    });
    expect(tocBox.itemCount).toBeGreaterThan(0);
    expect(tocBox.tocWidth).toBeLessThanOrEqual(await page.evaluate(() => window.innerWidth));
    expect(tocBox.listHeight).toBeLessThanOrEqual(240);
    expect(tocBox.listOverflowY).toBe('auto');
    expect(editorialWidth).toBeGreaterThan(300);
    expect(introLayout.copy.top).toBeGreaterThan(introLayout.title.bottom);
  } else {
    expect(choiceLayout.scrollWidth).toBeLessThanOrEqual(choiceLayout.clientWidth + 1);
    await expect(hub.locator('.pts-sh-editorial-grid > .pts-sd-toc')).toBeVisible();
    expect(editorialWidth).toBeGreaterThan(700);
    expect(Math.abs(introLayout.copy.top - introLayout.title.top)).toBeLessThanOrEqual(2);
    expect(introLayout.copy.left - introLayout.title.right).toBeGreaterThan(40);
    const editorialEdges = await hub.locator('.pts-sh-editorial-grid').evaluate(grid => {
      const toc = grid.querySelector('.pts-sd-toc').getBoundingClientRect();
      const content = grid.querySelector('#toc-content').getBoundingClientRect();
      const gridBox = grid.getBoundingClientRect();
      return { tocLeft: toc.left, contentRight: content.right, gridLeft: gridBox.left, gridRight: gridBox.right };
    });
    expect(Math.abs(editorialEdges.tocLeft - editorialEdges.gridLeft)).toBeLessThanOrEqual(1);
    expect(Math.abs(editorialEdges.contentRight - editorialEdges.gridRight)).toBeLessThanOrEqual(1);
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
  await expect(detail.locator('.pts-sd-switcher-all')).toHaveAttribute('href', '/giai-phap/');
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
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
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
    /* Nhắm đúng bài chính bằng #toc-content. Class .pts-solution-editorial còn
       được dùng cho khối "Thông tin bổ sung" (.pts-sd-extra); khối đó rỗng ở
       local nên không render, nhưng CÓ dữ liệu trên production -> locator cũ
       khớp 2 element và vỡ ở strict mode khi chạy với dữ liệu thật. */
    const editorial = detail.locator('#toc-content.pts-solution-editorial');
    await expect(editorial, path).toHaveCount(1);
    expect(await editorial.locator('h2').count()).toBeGreaterThanOrEqual(3);
    const editorialText = await editorial.innerText();
    expect(editorialText, path).not.toMatch(/\b(?:backup|battery|load|grid|cabinet|MWp|kWp)\b|phụ tải|bài toán|kiến trúc hệ thống|chiến lược vận hành/i);
    const sectionHeadings = await editorial.locator('h2').allTextContents();
    expect(sectionHeadings.some(heading => heading.includes('?')), path).toBe(false);
    const toc = detail.locator('.pts-sd-content-grid > .pts-sd-toc');
    await expect(toc).toBeVisible();
    const tocSurface = await toc.evaluate(node => ({
      background: getComputedStyle(node).backgroundColor,
      linkColor: getComputedStyle(node.querySelector('a')).color,
      /* 2026-09-16: trước đây khoá cứng rgb(7,27,70) = --sh-navy riêng của hub
         Giải pháp. Mỗi hub từng tự định nghĩa navy riêng (4 giá trị lệch nhau
         2-5%) nên toàn site trông không liền mạch; nay tất cả trỏ về token
         chung --pts-navy. So với chính token đó để test còn đúng khi thương
         hiệu đổi màu, nhưng vẫn bắt được nếu mục lục dùng màu lạ ngoài hệ. */
      tokenNavy: getComputedStyle(document.documentElement).getPropertyValue('--pts-navy').trim(),
    }));
    const hexToRgb = hex => {
      const value = hex.replace('#', '');
      const full = value.length === 3 ? value.split('').map(c => c + c).join('') : value;
      const int = parseInt(full, 16);
      return `rgb(${(int >> 16) & 255}, ${(int >> 8) & 255}, ${int & 255})`;
    };
    expect(tocSurface.tokenNavy, path).toMatch(/^#[0-9a-fA-F]{3,6}$/);
    expect(tocSurface.background, path).toBe(hexToRgb(tocSurface.tokenNavy));
    expect(tocSurface.linkColor, path).not.toBe(tocSurface.background);
    await expect(toc.locator('a').first()).toHaveClass(/is-active/);
    await expect(toc.locator('a').first()).toHaveAttribute('aria-current', 'location');
    if(testInfo.project.name.includes('mobile')) {
      const mobileToc = await toc.evaluate(node => ({
        position: getComputedStyle(node).position,
        listHeight: node.querySelector('.toc-list').getBoundingClientRect().height,
      }));
      expect(mobileToc.position, path).toBe('static');
      expect(mobileToc.listHeight, path).toBeLessThanOrEqual(221);
    } else {
      const contentGap = await detail.locator('.pts-sd-content-grid').evaluate(grid => {
        const tocBox = grid.querySelector('.pts-sd-toc').getBoundingClientRect();
        const contentBox = grid.querySelector('.pts-solution-editorial').getBoundingClientRect();
        return contentBox.left - tocBox.right;
      });
      expect(contentGap, path).toBeGreaterThan(65);
    }
    const hero = detail.locator('.pts-sd-hero-grid');
    await expect(hero).not.toHaveClass(/is-text-only/);
    await expect(hero.locator('.pts-sd-hero-media img')).toHaveCount(1);
    await expect.poll(() => hero.locator('img').evaluate(img => img.complete && img.naturalWidth > 0)).toBe(true);
    const heroSurface = await hero.evaluate(node => ({
      background: getComputedStyle(node).backgroundColor,
      titleColor: getComputedStyle(node.querySelector('h1')).color,
      /* Cùng lý do như mục lục ở trên: so với token navy chung thay vì mã màu
         cứng của bảng màu riêng cũ. */
      tokenNavy: getComputedStyle(document.documentElement).getPropertyValue('--pts-navy').trim(),
    }));
    const heroHexToRgb = hex => {
      const value = hex.replace('#', '');
      const full = value.length === 3 ? value.split('').map(c => c + c).join('') : value;
      const int = parseInt(full, 16);
      return `rgb(${(int >> 16) & 255}, ${(int >> 8) & 255}, ${int & 255})`;
    };
    expect(heroSurface.background, path).toBe(heroHexToRgb(heroSurface.tokenNavy));
    expect(heroSurface.titleColor, path).toBe('rgb(255, 255, 255)');
    const pageSpacing = await page.evaluate(() => {
      const breadcrumb = document.querySelector('.pts-breadcrumb-shell');
      const heroGrid = document.querySelector('.pts-sd-hero-grid');
      const wrapper = document.querySelector('.wrap-main.solution-page');
      const footer = document.querySelector('footer');
      if(!breadcrumb || !heroGrid || !wrapper || !footer) return null;
      const wrapperBox = wrapper.getBoundingClientRect();
      const footerBox = footer.getBoundingClientRect();
      const wrapperStyle = getComputedStyle(wrapper);
      return {
        breadcrumbToHero: heroGrid.getBoundingClientRect().top - breadcrumb.getBoundingClientRect().bottom,
        footerAfterWrapper: footerBox.top - wrapperBox.bottom,
        marginTop: wrapperStyle.marginTop,
        marginBottom: wrapperStyle.marginBottom,
      };
    });
    expect(pageSpacing, path).not.toBeNull();
    expect(pageSpacing.breadcrumbToHero, path).toBeLessThanOrEqual(testInfo.project.name.includes('mobile') ? 28 : 34);
    expect(pageSpacing.footerAfterWrapper, path).toBeLessThanOrEqual(1);
    expect(pageSpacing.marginTop, path).toBe('0px');
    expect(pageSpacing.marginBottom, path).toBe('0px');
    if(path.includes('he-thong-luu-tru-bess')) {
      await expect(hero.locator('img')).toHaveAttribute('src', /solution-bess-v2\.jpg(?:\.webp)?/);
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth), path).toBeLessThanOrEqual(1);
  }
  expect(errors).toEqual([]);
});

const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Phone shell only.');
  await page.route('**/pts-living-energy/index.html*', (route) => route.abort());
  await page.route('https://cdn.gtranslate.net/**', (route) => route.abort());
  await page.addInitScript(() => { try { sessionStorage.setItem('pts-popup-campaign', 'current'); } catch {} });
});

test('menu opens as a square-edged navy sheet with the close button on the right', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('.pts-mobile-nav-trigger').first().click();
  const panel = page.locator('#ptsMobileMenu');
  await expect(panel).toBeVisible();
  const box = await panel.boundingBox();
  expect(await panel.evaluate((e) => getComputedStyle(e).borderTopLeftRadius)).toBe('0px');
  const close = await panel.getByRole('button', { name: 'Đóng danh mục điều hướng' }).boundingBox();
  expect(close.x).toBeGreaterThan(box.x + box.width / 2);
  await panel.getByRole('button', { name: 'Đóng danh mục điều hướng' }).click();
  await expect(panel).toBeHidden();
});

test('filter drawer shows the count and returns to results', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.locator('button', { hasText: 'Bộ lọc' }).first().click();
  const drawer = page.locator('#catalog-filter-drawer');
  await expect(drawer).toBeVisible();
  await drawer.getByRole('button', { name: 'Xem sản phẩm' }).click();
  await expect(drawer).toBeHidden();

  await page.goto('/san-pham/?brand=57', { waitUntil: 'load' });
  await expect(page.locator('.filter-count').first()).toHaveText('1');
});

test('filter and sort stick while the list scrolls', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.evaluate(() => scrollTo(0, 1500));
  await page.waitForTimeout(500);
  const box = await page.locator('.m-sticky-tools').boundingBox();
  expect(box.y).toBeLessThanOrEqual(16);
  expect(box.y).toBeGreaterThanOrEqual(0);
});

test('product page uses the buying island and a short form', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const href = await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href');
  await page.goto(href, { waitUntil: 'load' });
  await expect(page.locator('.buy-island')).toBeVisible();
  await expect(page.locator('.dock')).toHaveCount(0);
  const form = page.locator('#pts-pdp-consultation form');
  await expect(form.locator('[name="phone"]')).toBeVisible();
  await expect(form.locator('[name="email"]')).toBeHidden();
  await form.getByRole('button', { name: /Thêm chi tiết/ }).click();
  await expect(form.locator('[name="email"]')).toBeVisible();
});

test('a touch never turns on the hover zoom, and a tap opens the viewer', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.goto(await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href'), { waitUntil: 'load' });
  const photo = page.locator('.pdp-zoom-image').first();
  const stage = page.locator('.pdp-studio-stage');
  await page.waitForTimeout(500);

  // Browsers follow a touch with a compatibility mouse move. iOS Safari then drops the click if that move changes the page.
  await stage.evaluate((e) => {
    const { left, top } = e.getBoundingClientRect();
    const at = { bubbles: true, clientX: left + 40, clientY: top + 40 };
    e.dispatchEvent(new PointerEvent('pointermove', { ...at, pointerType: 'touch' }));
    e.dispatchEvent(new MouseEvent('mousemove', at));
  });
  await page.waitForTimeout(100);
  expect(await photo.evaluate((e) => e.style.transform)).toBe('scale(1)');

  await stage.tap();
  const viewer = page.getByRole('dialog', { name: /Ảnh phóng to/ });
  await expect(viewer).toBeVisible();
  await viewer.getByRole('button', { name: 'Đóng ảnh phóng to' }).tap();
  await expect(viewer).toBeHidden();
  expect(await photo.evaluate((e) => e.style.transform)).toBe('scale(1)');
});

test('cart island steps aside once the summary button is on screen', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.getByRole('button', { name: /Thêm\s+giỏ\s+hàng|vào\s+giỏ/ }).first().click();
  await page.waitForTimeout(800);
  await page.goto('/gio-hang/', { waitUntil: 'load' });
  const island = page.locator('.m-island');
  const action = page.locator('a.cart-summary__cta');
  await expect(island).toBeVisible();

  await action.evaluate((e) => e.scrollIntoView({ block: 'center', behavior: 'instant' }));
  await expect(island).toBeHidden();

  await page.evaluate(() => window.scrollTo({ top: document.documentElement.scrollHeight, behavior: 'instant' }));
  await page.waitForTimeout(300);
  await expect(island).toBeHidden();

  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await expect(island).toBeVisible();
});

test('photo viewer keeps its arrows on top, sharpens, and drops a photo that fails', async ({ page }) => {
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const hrefs = await page.locator('article a[href*="/san-pham/"]').evaluateAll((links) => [...new Set(links.map((a) => a.getAttribute('href')))]);
  const next = page.getByRole('button', { name: 'Ảnh tiếp theo' });
  let multi = '';
  for (const href of hrefs) {
    await page.goto(href, { waitUntil: 'load' });
    await page.locator('.pdp-studio-stage').tap();
    await page.waitForTimeout(300);
    if (await next.count()) { multi = href; break; }
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
  }
  expect(multi).not.toBe('');

  const onTop = await page.getByRole('button', { name: 'Ảnh trước' }).evaluate((button) => {
    const box = button.getBoundingClientRect();
    return button.contains(document.elementFromPoint(box.left + box.width / 2, box.top + box.height / 2));
  });
  expect(onTop).toBe(true);
  const viewer = page.getByRole('dialog', { name: /Ảnh phóng to/ });
  await expect.poll(() => viewer.locator('img').first().getAttribute('src')).toMatch(/^\/thumbs\/1600x1600x3\//);
  await page.keyboard.press('Escape');

  let first = '';
  await page.route('**/thumbs/960x960x3/**', (route) => {
    first ||= route.request().url();
    return route.request().url() === first ? route.continue() : route.abort();
  });
  await page.goto(multi, { waitUntil: 'load' });
  await page.locator('.pdp-studio-stage').tap();
  await expect(viewer).toBeVisible();
  await expect(next).toHaveCount(0, { timeout: 8000 });
  await expect(viewer.locator('img').first()).not.toHaveJSProperty('naturalWidth', 0);
});

test('search and the support sheet never stay open together', async ({ page }) => {
  await page.goto('/gioi-thieu/', { waitUntil: 'load' });
  await page.locator('.dock').getByRole('button', { name: 'Hỗ trợ' }).click();
  const sheet = page.getByRole('dialog', { name: 'Hỗ trợ nhanh' });
  await expect(sheet).toBeVisible();
  await page.getByRole('button', { name: 'Mở tìm kiếm' }).first().click();
  await expect(sheet).toBeHidden();
  await expect(page.locator('input[type="search"]:visible').first()).toBeVisible();
  // In the other order the search scrim covers the dock, so the first tap outside closes search.
  await page.evaluate(() => document.activeElement?.blur());
  await page.mouse.click(200, 700);
  await expect(page.locator('input[type="search"]:visible')).toHaveCount(0);
  await expect(sheet).toBeHidden();
});

test('solution pages keep the switcher in one place and centre the chosen chip', async ({ page }) => {
  await page.goto('/giai-phap/dien-mat-troi-doanh-nghiep/', { waitUntil: 'load' });
  const rail = page.locator('.solution-rail [data-pill-rail]');
  await expect(rail).toHaveCount(1);
  const top = (await rail.boundingBox()).y;
  const count = await rail.locator('a').count();
  for (let index = 1; index < count - 1; index += 1) {
    const before = page.url();
    await rail.locator('a').nth(index).evaluate((link) => link.click());
    await page.waitForFunction((url) => location.href !== url || true, before);
    await page.waitForTimeout(900);
    await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
    await page.waitForTimeout(200);
    expect(Math.abs((await rail.boundingBox()).y - top)).toBeLessThan(2);
    const offset = await rail.evaluate((element) => {
      const box = element.getBoundingClientRect();
      const active = element.querySelector('[aria-current="page"]').getBoundingClientRect();
      return Math.abs(active.left + active.width / 2 - box.left - box.width / 2);
    });
    expect(offset).toBeLessThan(120);
  }
});

test('tapping a chip moves the rail once, without restarting after the page changes', async ({ page }) => {
  await page.goto('/danh-muc-san-pham/den-nlmt/den-san-vuon/', { waitUntil: 'load' });
  await expect(page.locator('.catalog-chip-rail .rail-pill')).toHaveCount(1);
  await page.waitForTimeout(500);
  await page.evaluate(() => {
    window.__rail = [];
    const started = performance.now();
    (function sample() {
      const rail = document.querySelector('.catalog-chip-rail');
      if (rail) window.__rail.push(rail.scrollLeft);
      if (performance.now() - started < 1500) requestAnimationFrame(sample);
    })();
  });
  await page.locator('.catalog-chip-rail a').nth(4).tap();
  await page.waitForURL(/den-ufo/);
  await page.waitForTimeout(1600);
  const positions = await page.evaluate(() => window.__rail);
  const backwards = positions.filter((value, index) => index > 0 && value < positions[index - 1] - 2);
  expect(backwards).toEqual([]);
});

for (const [name, path] of [['about', '/gioi-thieu/'], ['product', null]]) {
  test(`${name} section rail follows a fast scroll in order and reaches the last section`, async ({ page }) => {
    let target = path;
    if (!target) {
      await page.goto('/san-pham/', { waitUntil: 'load' });
      target = await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href');
    }
    await page.goto(target, { waitUntil: 'load' });
    await page.waitForTimeout(800);
    await page.evaluate(() => {
      window.__order = [];
      let last = -2;
      (function sample() {
        const active = document.querySelector('[data-pill-rail] a[aria-current]');
        const links = active ? [...active.closest('[data-pill-rail]').querySelectorAll('a')] : [];
        const index = active ? links.indexOf(active) : -1;
        if (index !== last && index >= 0) { window.__order.push(index); last = index; }
        requestAnimationFrame(sample);
      })();
    });
    const lastId = await page.evaluate(() => [...document.querySelectorAll('[data-pill-rail] a')].at(-1).getAttribute('href').split('#')[1]);
    const end = await page.evaluate((id) => document.getElementById(id).getBoundingClientRect().top + scrollY - 40, lastId);
    for (let y = 0; y < end; y += 470) {
      await page.evaluate((top) => window.scrollTo({ top, behavior: 'instant' }), y);
      await page.waitForTimeout(40);
    }
    await page.evaluate((top) => window.scrollTo({ top, behavior: 'instant' }), end);
    await page.waitForTimeout(300);
    const order = await page.evaluate(() => window.__order);
    expect(order).toEqual([...order].sort((a, b) => a - b));
    const count = await page.locator('[data-pill-rail] a').count();
    expect(order.at(-1)).toBe(count - 1);
  });
}

test('long text is justified without large holes, and the home hero tells its story on one card', async ({ page }) => {
  await page.goto('/gioi-thieu/', { waitUntil: 'load' });
  await page.waitForTimeout(800);
  const widest = await page.evaluate(() => {
    let worst = 0;
    const range = document.createRange();
    document.querySelectorAll('.text-justified').forEach((block) => {
      const size = parseFloat(getComputedStyle(block).fontSize);
      const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
      while (walker.nextNode()) {
        const node = walker.currentNode;
        for (let index = node.data.indexOf(' '); index !== -1; index = node.data.indexOf(' ', index + 1)) {
          range.setStart(node, index);
          range.setEnd(node, index + 1);
          const box = range.getBoundingClientRect();
          if (box.height > 0) worst = Math.max(worst, box.width / size);
        }
      }
    });
    return { worst, count: document.querySelectorAll('.text-justified').length };
  });
  expect(widest.count).toBeGreaterThan(0);
  expect(widest.worst).toBeLessThanOrEqual(0.85);

  await page.goto('/', { waitUntil: 'load' });
  const steps = page.locator('.hero-steps button');
  await expect(steps).toHaveCount(4);
  await steps.nth(2).tap();
  await expect(steps.nth(2)).toHaveAttribute('aria-current', 'true');
  const gap = await page.evaluate(() => document.querySelector('.hero-caption').getBoundingClientRect().top - document.querySelector('.hero-copy > div').getBoundingClientRect().bottom);
  expect(gap).toBeGreaterThanOrEqual(0);
});

test('home hero keeps advancing after a bar is tapped', async ({ page }) => {
  await page.goto('/', { waitUntil: 'load' });
  const bars = page.locator('.hero-steps button');
  await expect(bars).toHaveCount(4);
  await bars.nth(1).tap();
  await expect(bars.nth(1)).toHaveAttribute('aria-current', 'true');
  await expect(bars.nth(2)).toHaveAttribute('aria-current', 'true', { timeout: 6000 });
  // A vertical scroll starting on the banner ends in touchcancel rather than touchend.
  await page.evaluate(() => {
    const hero = document.querySelector('.hero-copy-layer');
    const touch = new Touch({ identifier: 1, target: hero, clientX: 200, clientY: 400 });
    hero.dispatchEvent(new TouchEvent('touchstart', { bubbles: true, touches: [touch], changedTouches: [touch] }));
    hero.dispatchEvent(new TouchEvent('touchcancel', { bubbles: true, touches: [], changedTouches: [touch] }));
  });
  await expect(bars.nth(3)).toHaveAttribute('aria-current', 'true', { timeout: 6000 });
});

test('the cart lives in the dock, with the header icon only where there is no dock', async ({ page }) => {
  await page.goto('/gioi-thieu/', { waitUntil: 'load' });
  await expect(page.locator('header [data-cart-target]')).toBeHidden();
  await expect(page.locator('.dock [data-cart-target]')).toBeVisible();
  const header = await page.locator('.pts-header-capsule').boundingBox();
  expect(header.height).toBeLessThanOrEqual(66);
  const logo = await page.locator('.pts-header-capsule a img').first().boundingBox();
  expect(logo.width).toBeGreaterThan(205);

  await page.goto('/san-pham/', { waitUntil: 'load' });
  await page.goto(await page.locator('article a[href*="/san-pham/"]').first().getAttribute('href'), { waitUntil: 'load' });
  await expect(page.locator('header [data-cart-target]')).toBeVisible();
});

test('dock, sidebar and island keep one tint when transparency is off', async ({ page, context }) => {
  const cdp = await context.newCDPSession(page);
  await cdp.send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-transparency', value: 'reduce' }] });
  const paint = (e) => getComputedStyle(e).backgroundImage;
  await page.goto('/san-pham/', { waitUntil: 'load' });
  const dock = await page.locator('.dock').evaluate(paint);
  await page.locator('.pts-mobile-nav-trigger').first().click();
  const sidebar = await page.locator('#ptsMobileMenu').evaluate(paint);
  expect(sidebar).toBe(dock);
  expect(dock).not.toContain('0.');
});

const { test, expect } = require('@playwright/test');

const routes = [
  {
    name: 'home',
    path: '/',
    card: '.pts-home-category',
    visual: '.pts-home-category-bg img',
    cta: '.pts-home-btn-primary',
  },
  {
    name: 'catalog',
    path: '/san-pham/',
    card: '.product-card .product-item',
    visual: '.product-card .product-card-img .pts-product-photo',
    cta: '.product-card-btns .pts-card-add, .product-card-btns .pts-card-ask, .product-card-btns .pts-card-detail',
  },
  {
    name: 'solutions',
    path: '/giai-phap/',
    card: '.pts-sh-choice',
    visual: '.pts-sh-choice-media img',
    cta: '.pts-sh-consult',
  },
  {
    name: 'dealer',
    path: '/dai-ly/',
    card: '.pts-dl-product-card',
    visual: '.pts-dl-product-media img',
    cta: '.pts-dl-submit',
  },
  {
    name: 'projects',
    path: '/du-an-da-thuc-hien/',
    card: '.pts-ph-card',
    visual: '.pts-ph-card-media > img',
    cta: '.pts-ph-featured-cta, .pts-ph-arrow',
  },
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    try {
      const originalGetItem = window.sessionStorage.getItem.bind(window.sessionStorage);
      window.sessionStorage.getItem = key => {
        if (key === 'pts-popup-campaign') {
          const popup = document.querySelector('#popup');
          return popup ? (popup.getAttribute('data-campaign-key') || 'current') : 'current';
        }
        return originalGetItem(key);
      };
    } catch (error) {}
  });
});

async function dismissPopup(page) {
  await page.waitForTimeout(100);
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    const close = popup.locator('[data-dismiss="modal"]').first();
    if (await close.isVisible()) await close.click();
  }
}

async function firstVisible(page, selector) {
  const candidates = page.locator(selector);
  for (let index = 0; index < await candidates.count(); index += 1) {
    const candidate = candidates.nth(index);
    if (await candidate.isVisible()) return candidate;
  }
  throw new Error(`No visible element matched ${selector}`);
}

async function readMotionStyle(locator) {
  return locator.evaluate(element => {
    const style = getComputedStyle(element);
    return {
      transitionProperty: style.transitionProperty,
      transitionDuration: style.transitionDuration,
      transform: style.transform,
      backgroundColor: style.backgroundColor,
      borderColor: style.borderTopColor,
      color: style.color,
      boxShadow: style.boxShadow,
    };
  });
}

function hasActiveTransition(style) {
  return style.transitionProperty !== 'none'
    && style.transitionDuration.split(',').some(duration => parseFloat(duration) > 0);
}

function visualSignature(style) {
  return [style.transform, style.backgroundColor, style.borderColor, style.color, style.boxShadow].join('|');
}

async function assertNoHorizontalOverflow(page, route) {
  const overflow = await page.evaluate(() => ({
    documentWidth: document.documentElement.scrollWidth,
    viewportWidth: document.documentElement.clientWidth,
  }));
  expect(overflow.documentWidth - overflow.viewportWidth, route).toBeLessThanOrEqual(1);
}

async function focusCardWithKeyboard(page, selector) {
  await page.evaluate(() => document.activeElement && document.activeElement.blur());
  for (let index = 0; index < 140; index += 1) {
    await page.keyboard.press('Tab');
    if (await page.evaluate(cardSelector => {
      const active = document.activeElement;
      return Boolean(active && (active.matches(cardSelector) || active.closest(cardSelector)));
    }, selector)) return;
  }
  throw new Error(`Keyboard focus did not reach ${selector}`);
}

test('shared motion states are visible and stable on desktop routes', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Desktop interaction assertions run in the desktop project.');
  await page.setViewportSize({ width: 1440, height: 900 });

  for (const route of routes) {
    const pageErrors = [];
    const errorHandler = error => pageErrors.push(error.message);
    page.on('pageerror', errorHandler);
    const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok(), route.name).toBeTruthy();
    await dismissPopup(page);

    const card = await firstVisible(page, route.card);
    const visual = await firstVisible(page, route.visual);
    const cta = await firstVisible(page, route.cta);
    expect(hasActiveTransition(await readMotionStyle(card)), route.name).toBeTruthy();
    expect(hasActiveTransition(await readMotionStyle(cta)), route.name).toBeTruthy();

    const beforeCardHover = await readMotionStyle(visual);
    await card.hover();
    await page.waitForTimeout(230);
    const afterCardHover = await readMotionStyle(visual);
    expect(visualSignature(afterCardHover), route.name).not.toBe(visualSignature(beforeCardHover));

    const beforeCtaHover = await readMotionStyle(cta);
    await cta.hover();
    await page.waitForTimeout(170);
    const afterCtaHover = await readMotionStyle(cta);
    expect(visualSignature(afterCtaHover), `${route.name} CTA`).not.toBe(visualSignature(beforeCtaHover));

    await focusCardWithKeyboard(page, route.card);
    const focusState = await page.evaluate(cardSelector => {
      const element = document.activeElement;
      const withinCard = !!element && (element.matches(cardSelector) || !!element.closest(cardSelector));
      const style = element ? getComputedStyle(element) : null;
      return { matches: withinCard && element.matches(':focus-visible'),
        outlineStyle: style?.outlineStyle || 'none', outlineWidth: parseFloat(style?.outlineWidth || '0') };
    }, route.card);
    expect(focusState.matches, route.name).toBeTruthy();
    expect(focusState.outlineStyle, route.name).not.toBe('none');
    expect(focusState.outlineWidth, route.name).toBeGreaterThanOrEqual(2);
    await assertNoHorizontalOverflow(page, route.name);
    expect(pageErrors, route.name).toEqual([]);
    page.removeListener('pageerror', errorHandler);
  }
});

test('reduced motion disables transitions without removing focus feedback', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'Reduced-motion assertions run in the desktop project.');
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.emulateMedia({ reducedMotion: 'reduce' });

  for (const route of routes) {
    const pageErrors = [];
    const errorHandler = error => pageErrors.push(error.message);
    page.on('pageerror', errorHandler);
    const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok(), route.name).toBeTruthy();
    await dismissPopup(page);

    const card = await firstVisible(page, route.card);
    const visual = await firstVisible(page, route.visual);
    const cta = await firstVisible(page, route.cta);
    for (const target of [card, visual, cta]) {
      const style = await readMotionStyle(target);
      expect(style.transitionProperty, route.name).toBe('none');
      expect(style.transitionDuration, route.name).toBe('0s');
    }
    expect(await page.evaluate(() => getComputedStyle(document.documentElement).scrollBehavior)).toBe('auto');

    await card.hover();
    expect((await readMotionStyle(visual)).transform, route.name).toBe('none');
    await focusCardWithKeyboard(page, route.card);
    const focusState = await page.evaluate(cardSelector => {
      const element = document.activeElement;
      const withinCard = !!element && (element.matches(cardSelector) || !!element.closest(cardSelector));
      const style = element ? getComputedStyle(element) : null;
      return { matches: withinCard && element.matches(':focus-visible'),
        outlineStyle: style?.outlineStyle || 'none', outlineWidth: parseFloat(style?.outlineWidth || '0') };
    }, route.card);
    expect(focusState.matches, route.name).toBeTruthy();
    expect(focusState.outlineStyle, route.name).not.toBe('none');
    expect(focusState.outlineWidth, route.name).toBeGreaterThanOrEqual(2);
    await assertNoHorizontalOverflow(page, route.name);
    expect(pageErrors, route.name).toEqual([]);
    page.removeListener('pageerror', errorHandler);
  }
});

test('mobile routes keep touch hover static and avoid horizontal overflow', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'mobile-chromium', 'Touch assertions run in the mobile project.');
  await page.setViewportSize({ width: 390, height: 844 });

  for (const route of routes) {
    const pageErrors = [];
    const errorHandler = error => pageErrors.push(error.message);
    page.on('pageerror', errorHandler);
    const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok(), route.name).toBeTruthy();
    await dismissPopup(page);

    const card = await firstVisible(page, route.card);
    const visual = await firstVisible(page, route.visual);
    await card.hover();
    expect((await readMotionStyle(visual)).transform, route.name).toBe('none');
    await focusCardWithKeyboard(page, route.card);
    const focusState = await page.evaluate(cardSelector => {
      const element = document.activeElement;
      const withinCard = !!element && (element.matches(cardSelector) || !!element.closest(cardSelector));
      const style = element ? getComputedStyle(element) : null;
      return { matches: withinCard && element.matches(':focus-visible'),
        outlineStyle: style?.outlineStyle || 'none', outlineWidth: parseFloat(style?.outlineWidth || '0') };
    }, route.card);
    expect(focusState.matches, route.name).toBeTruthy();
    expect(focusState.outlineStyle, route.name).not.toBe('none');
    expect(focusState.outlineWidth, route.name).toBeGreaterThanOrEqual(2);
    await assertNoHorizontalOverflow(page, route.name);
    expect(pageErrors, route.name).toEqual([]);
    page.removeListener('pageerror', errorHandler);
  }
});

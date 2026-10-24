const { test, expect } = require('@playwright/test');

const viewports = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'desktop', width: 1440, height: 900 },
];

const routes = [
  { name: 'home', path: '/', card: '.pts-home-category', radius: '4px' },
  { name: 'catalog', path: '/san-pham/', card: '.product-card', radius: '0px' },
  {
    name: 'pdp',
    path: '/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-18500-500w',
    card: '.pts-pdp-v2 .pts-pdp-content-section',
    radius: '0px',
    control: '.pts-pdp-v2 .btn-product-detail a',
  },
  { name: 'solutions', path: '/giai-phap/', card: '.pts-sh-choice', radius: '0px' },
  { name: 'projects', path: '/du-an-da-thuc-hien/', card: '.pts-ph-card', radius: '0px' },
  { name: 'news', path: '/tin-tuc/', card: '.nh-card', radius: '0px', control: '.nh-search' },
  { name: 'dealers', path: '/dai-ly/', card: '.pts-dl-product-card', radius: '0px' },
  {
    name: 'about',
    path: '/ve-chung-toi/',
    card: '.pts-about-contact',
    radius: '0px',
    control: '.pts-about-contact-button',
  },
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.emulateMedia({ reducedMotion: 'reduce' });
});

async function firstVisible(page, selector, label) {
  const candidates = page.locator(selector);
  for (let index = 0; index < await candidates.count(); index += 1) {
    const candidate = candidates.nth(index);
    if (await candidate.isVisible()) return candidate;
  }
  throw new Error(`${label} did not render a visible ${selector}`);
}

async function readFlatStyle(locator) {
  return locator.evaluate(element => {
    const style = getComputedStyle(element);
    return {
      borderRadius: style.borderRadius,
      boxShadow: style.boxShadow,
      transitionProperty: style.transitionProperty,
      transitionDuration: style.transitionDuration,
    };
  });
}

async function assertNoHorizontalOverflow(page, label) {
  const overflow = await page.evaluate(() => ({
    documentWidth: document.documentElement.scrollWidth,
    viewportWidth: document.documentElement.clientWidth,
  }));
  expect(overflow.documentWidth - overflow.viewportWidth, label).toBeLessThanOrEqual(1);
}

test('PTS Precision stays cohesive at 390px and 1440px', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'The spec sets both audited viewports explicitly.');

  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });

    for (const route of routes) {
      const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
      expect(response && response.ok(), `${viewport.name}/${route.name} route`).toBeTruthy();

      const tokenValues = await page.evaluate(() => {
        const style = getComputedStyle(document.documentElement);
        return {
          radiusNone: style.getPropertyValue('--pts-radius-none').trim(),
          radiusSm: style.getPropertyValue('--pts-radius-sm').trim(),
          radiusControl: style.getPropertyValue('--pts-radius-control').trim(),
          radiusRound: style.getPropertyValue('--pts-radius-round').trim(),
          elevationSurface: style.getPropertyValue('--pts-elevation-surface').trim(),
          reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        };
      });
      expect(tokenValues, `${viewport.name}/${route.name} tokens`).toMatchObject({
        radiusNone: '0',
        radiusSm: '3px',
        radiusControl: '4px',
        radiusRound: '999px',
        elevationSurface: 'none',
        reducedMotion: true,
      });

      const card = await firstVisible(page, route.card, `${viewport.name}/${route.name}`);
      const cardStyle = await readFlatStyle(card);
      expect(cardStyle.borderRadius, `${viewport.name}/${route.name} card radius`).toBe(route.radius);
      expect(cardStyle.boxShadow, `${viewport.name}/${route.name} card shadow`).toBe('none');
      expect(cardStyle.transitionDuration, `${viewport.name}/${route.name} reduced duration`).toBe('0s');

      if (route.control) {
        const control = await firstVisible(page, route.control, `${viewport.name}/${route.name} control`);
        const controlStyle = await readFlatStyle(control);
        expect(controlStyle.borderRadius, `${viewport.name}/${route.name} control radius`)
          .toBe('4px');
        expect(await control.isEnabled(), `${viewport.name}/${route.name} control operable`).toBeTruthy();
      }

      if (route.name === 'pdp') {
        const content = await firstVisible(page, '.pts-pdp-v2 .pts-pdp-details', `${viewport.name}/pdp content`);
        expect((await readFlatStyle(content)).borderRadius, `${viewport.name}/pdp detail surface`).toBe('0px');
      }

      await assertNoHorizontalOverflow(page, `${viewport.name}/${route.name} overflow`);
    }
  }
});

test('neutral product and solution cards do not translate on hover and remain keyboard operable', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'The spec sets both audited viewports explicitly.');

  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });

    for (const route of [
      { name: 'catalog', path: '/san-pham/', card: '.product-card', cta: '.product-card-btns a' },
      { name: 'solutions', path: '/giai-phap/', card: '.pts-sh-choice', cta: '.pts-sh-consult' },
    ]) {
      const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
      expect(response && response.ok(), `${viewport.name}/${route.name} route`).toBeTruthy();
      const card = await firstVisible(page, route.card, `${viewport.name}/${route.name}`);
      const cta = await firstVisible(page, route.cta, `${viewport.name}/${route.name} CTA`);

      await card.hover();
      expect(await card.evaluate(element => getComputedStyle(element).transform),
        `${viewport.name}/${route.name} card hover transform`).toBe('none');

      await cta.focus();
      const focusState = await cta.evaluate(element => ({
        active: document.activeElement === element,
        display: getComputedStyle(element).display,
        pointerEvents: getComputedStyle(element).pointerEvents,
      }));
      expect(focusState.active, `${viewport.name}/${route.name} CTA focus`).toBeTruthy();
      expect(focusState.display, `${viewport.name}/${route.name} CTA display`).not.toBe('none');
      expect(focusState.pointerEvents, `${viewport.name}/${route.name} CTA pointer events`).not.toBe('none');
    }
  }
});

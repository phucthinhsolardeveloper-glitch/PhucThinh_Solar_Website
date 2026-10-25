const { test, expect } = require('@playwright/test');

const viewports = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'desktop', width: 1440, height: 900 },
];

const navy = 'rgb(10, 34, 81)';
const gold = 'rgb(201, 150, 46)';
const goldLight = 'rgb(224, 182, 84)';
const muted = 'rgb(100, 116, 139)';
const line = 'rgb(229, 231, 235)';
const errorRed = 'rgb(179, 38, 30)';

const routes = [
  {
    name: 'home',
    path: '/',
    primary: '.pts-home-btn-primary',
    secondary: '.pts-home-btn-outline',
    field: '.pts-home-contact-form input',
  },
  { name: 'dealer', path: '/dai-ly/', primary: '.pts-dl-submit', field: '.pts-dl-input' },
  { name: 'news', path: '/tin-tuc/', primary: '.nh-consult', search: '.nh-search' },
  { name: 'solutions', path: '/giai-phap/', primary: '.pts-sh-consult' },
  { name: 'projects', path: '/du-an-da-thuc-hien/', primary: '.pts-ph-consult' },
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

async function readStyle(locator) {
  return locator.evaluate(element => {
    const style = getComputedStyle(element);
    const box = element.getBoundingClientRect();
    return {
      backgroundColor: style.backgroundColor,
      color: style.color,
      borderColor: style.borderColor,
      borderRadius: style.borderRadius,
      cursor: style.cursor,
      height: box.height,
      outlineColor: style.outlineColor,
      outlineStyle: style.outlineStyle,
      outlineWidth: style.outlineWidth,
      transitionDuration: style.transitionDuration,
      transform: style.transform,
    };
  });
}

async function assertControlGeometry(locator, label) {
  const style = await readStyle(locator);
  expect(style.borderRadius, `${label} radius`).toBe('4px');
  expect(style.height, `${label} hit height`).toBeGreaterThanOrEqual(44);
  expect(style.transitionDuration, `${label} reduced motion`).toBe('0s');
}

async function assertPrimaryState(page, locator, label) {
  await assertControlGeometry(locator, label);
  expect((await readStyle(locator)).backgroundColor, `${label} default gold`).toBe(gold);
  expect((await readStyle(locator)).color, `${label} default navy text`).toBe(navy);

  await locator.hover();
  const hoverStyle = await readStyle(locator);
  expect(hoverStyle.backgroundColor, `${label} hover gold`).toBe(goldLight);
  expect(hoverStyle.color, `${label} hover navy text`).toBe(navy);
  expect(hoverStyle.transform, `${label} hover does not translate`).toBe('none');

  // Keyboard modality triggers :focus-visible; programmatic focus after hover does not.
  await page.keyboard.press('Tab');
  await locator.focus();
  const focusStyle = await readStyle(locator);
  expect(focusStyle.outlineStyle, `${label} keyboard outline`).not.toBe('none');
  expect(focusStyle.outlineColor, `${label} keyboard outline color`).toBe(navy);

  const disabledStyle = await locator.evaluate(element => {
    if (element instanceof HTMLButtonElement) element.disabled = true;
    else element.setAttribute('aria-disabled', 'true');
    const style = getComputedStyle(element);
    return {
      backgroundColor: style.backgroundColor,
      color: style.color,
      cursor: style.cursor,
      transform: style.transform,
    };
  });
  expect(disabledStyle.backgroundColor, `${label} disabled surface`).toBe(line);
  expect(disabledStyle.color, `${label} disabled text`).toBe(muted);
  expect(disabledStyle.cursor, `${label} disabled cursor`).toBe('not-allowed');
  expect(disabledStyle.transform, `${label} disabled transform`).toBe('none');

  await locator.evaluate(element => {
    if (element instanceof HTMLButtonElement) element.disabled = false;
    else element.removeAttribute('aria-disabled');
  });
}

test('PTS Control Family v1 stays coherent at 390px and 1440px', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name !== 'desktop-chromium', 'The spec sets both audited viewports explicitly.');

  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));

  for (const viewport of viewports) {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });

    for (const route of routes) {
      const response = await page.goto(route.path, { waitUntil: 'domcontentloaded' });
      expect(response && response.ok(), `${viewport.name}/${route.name} response`).toBeTruthy();

      const primary = await firstVisible(page, route.primary, `${viewport.name}/${route.name} primary`);
      await assertPrimaryState(page, primary, `${viewport.name}/${route.name} primary`);

      if (route.secondary) {
        const secondary = await firstVisible(page, route.secondary, `${viewport.name}/${route.name} secondary`);
        await assertControlGeometry(secondary, `${viewport.name}/${route.name} secondary`);
        expect((await readStyle(secondary)).color, `${viewport.name}/${route.name} secondary text`).toBe(navy);
        await page.keyboard.press('Tab');
        await secondary.focus();
        expect((await readStyle(secondary)).outlineColor, `${viewport.name}/${route.name} secondary focus`).toBe(navy);
      }

      if (route.field) {
        const field = await firstVisible(page, route.field, `${viewport.name}/${route.name} field`);
        await assertControlGeometry(field, `${viewport.name}/${route.name} field`);
        await page.keyboard.press('Tab');
        await field.focus();
        const focusStyle = await readStyle(field);
        expect(focusStyle.outlineStyle, `${viewport.name}/${route.name} field focus`).not.toBe('none');
        expect(focusStyle.outlineColor, `${viewport.name}/${route.name} field focus color`).toBe(navy);

        const errorStyle = await field.evaluate(element => {
          element.setAttribute('aria-invalid', 'true');
          const style = getComputedStyle(element);
          return { borderColor: style.borderColor };
        });
        expect(errorStyle.borderColor, `${viewport.name}/${route.name} field error`).toBe(errorRed);
        await field.evaluate(element => element.removeAttribute('aria-invalid'));
      }

      if (route.search) {
        const search = await firstVisible(page, route.search, `${viewport.name}/${route.name} search`);
        await assertControlGeometry(search, `${viewport.name}/${route.name} search`);
        const searchInput = await firstVisible(page, `${route.search} input`, `${viewport.name}/${route.name} search input`);
        await page.keyboard.press('Tab');
        await searchInput.focus();
        const searchFocus = await readStyle(search);
        expect(searchFocus.outlineStyle, `${viewport.name}/${route.name} search focus`).not.toBe('none');
        expect(searchFocus.outlineColor, `${viewport.name}/${route.name} search focus color`).toBe(navy);

        const submit = await search.evaluate(form => {
          let submitted = false;
          const submitter = form.querySelector('button[type="submit"], button:not([type])');
          form.addEventListener('submit', event => {
            event.preventDefault();
            submitted = true;
          }, { once: true });
          form.requestSubmit(submitter);
          return {
            submitted,
            method: form.method,
            action: new URL(form.action, location.href).pathname,
          };
        });
        expect(submit.submitted, `${viewport.name}/${route.name} native submit event`).toBe(true);
        expect(submit.method, `${viewport.name}/${route.name} search method`).toBe('get');
        expect(submit.action, `${viewport.name}/${route.name} search action`).toBe('/tin-tuc/');
      }

      const overflow = await page.evaluate(() => ({
        documentWidth: document.documentElement.scrollWidth,
        viewportWidth: document.documentElement.clientWidth,
        reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
      }));
      expect(overflow.reducedMotion, `${viewport.name}/${route.name} reduced motion media`).toBe(true);
      expect(overflow.documentWidth - overflow.viewportWidth, `${viewport.name}/${route.name} horizontal overflow`).toBeLessThanOrEqual(1);
    }
  }

  expect(pageErrors, 'public routes should not emit page errors').toEqual([]);
});

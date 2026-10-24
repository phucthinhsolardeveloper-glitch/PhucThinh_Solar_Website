const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

test('primary CTA giữ cùng surface language trên các hub public', async ({ page }) => {
  const cases = [
    ['solution', '/giai-phap/', '.pts-sh-consult'],
    ['project', '/du-an-da-thuc-hien/', '.pts-ph-consult'],
    ['about', '/gioi-thieu/', '.pts-about-contact-button.is-primary'],
    ['dealer', '/dai-ly/', '.pts-dl-submit'],
  ];

  for (const [name, path, selector] of cases) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok(), `${name} route`).toBeTruthy();
    const cta = page.locator(selector).first();
    await expect(cta, `${name} CTA`).toBeVisible();

    const style = await cta.evaluate(element => {
      const computed = getComputedStyle(element);
      return {
        borderRadius: computed.borderRadius,
        backgroundImage: computed.backgroundImage,
        boxShadow: computed.boxShadow,
        backgroundColor: computed.backgroundColor,
      };
    });

    expect(style.borderRadius, `${name} radius`).toBe('4px');
    expect(style.backgroundImage, `${name} gradient`).toBe('none');
    expect(style.boxShadow, `${name} shadow`).toBe('none');
    expect(style.backgroundColor, `${name} gold surface`).toBe('rgb(201, 150, 46)');

    await cta.hover();
    await expect.poll(async () => cta.evaluate(element => getComputedStyle(element).backgroundColor),
      { message: `${name} hover gold`, timeout: 2000 }).toBe('rgb(224, 182, 84)');
  }
});

test('interactive elements có focus ring nhìn thấy sau khi keyboard focus', async ({ page }) => {
  const routes = ['/', '/san-pham/', '/giai-phap/', '/du-an-da-thuc-hien/', '/gioi-thieu/', '/tin-tuc/', '/dai-ly/', '/lien-he/'];

  for (const path of routes) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response && response.ok(), `${path} route`).toBeTruthy();

    await page.keyboard.press('Tab');
    const result = await page.evaluate(pathname => {
      const scope = document.querySelector('main') || document.querySelector('.wrap-main');
      const element = pathname === '/dai-ly/'
        ? scope && scope.querySelector('.pts-dl-input')
        : scope && scope.querySelector('a[href], button, input:not([type="hidden"]), select, textarea');
      if (!element) return null;
      element.focus();
      const computed = getComputedStyle(element);
      return {
        tagName: element.tagName,
        outlineStyle: computed.outlineStyle,
        outlineWidth: computed.outlineWidth,
      };
    }, path);

    expect(result, `${path} interactive control`).not.toBeNull();
    expect(result.outlineStyle, `${path} focus style`).toBe('solid');
    expect(result.outlineWidth, `${path} focus width`).toBe('3px');
  }
});

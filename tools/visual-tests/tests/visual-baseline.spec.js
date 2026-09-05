const { test, expect } = require('@playwright/test');

test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function settlePage(page) {
  await page.evaluate(async () => {
    const initialHeight = document.body.scrollHeight;
    const step = Math.max(500, Math.ceil(initialHeight / 20));
    for (let position = 0; position < initialHeight; position += step) {
      window.scrollTo(0, position);
      await new Promise(resolve => setTimeout(resolve, 30));
    }
    window.scrollTo(0, 0);
    if (window.jQuery) {
      window.jQuery('.slick-initialized').slick?.('slickPause');
      window.jQuery('.owl-carousel').trigger('stop.owl.autoplay');
    }
  });
  await page.waitForTimeout(1000);
}

async function dismissEntrancePopup(page) {
  const popup = page.locator('#popup');
  if (await popup.isVisible()) {
    await popup.locator('[data-dismiss="modal"]').click();
    await expect(popup).not.toBeVisible();
  }
}

for (const [name, path] of [['home', '/'], ['products', '/san-pham/']]) {
  test(`${name} visual baseline`, async ({ page }) => {
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await dismissEntrancePopup(page);
    await settlePage(page);
    await expect(page).toHaveScreenshot(`${name}.png`, {
      animations: 'disabled',
      fullPage: false,
      maxDiffPixelRatio: 0.01,
      timeout: 15000
    });
  });
}

test('home featured categories visual baseline', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' });
  await dismissEntrancePopup(page);
  await settlePage(page);
  const categories = page.locator('.pts-home-category-wrap');
  await categories.scrollIntoViewIfNeeded();
  await page.locator('.top-utility-bar, #headerscroll, #button-contact-vr').evaluateAll(elements => elements.forEach(element => {
    element.style.setProperty('display', 'none', 'important');
  }));
  await page.waitForTimeout(300);
  await expect(categories).toHaveScreenshot('home-featured-categories.png', {
    animations: 'disabled',
    maxDiffPixelRatio: 0.01,
    timeout: 15000
  });
});

test('product detail visual baseline', async ({ page, isMobile }) => {
  test.skip(isMobile, 'MagicZoom detail is covered by DOM regression on mobile; its element canvas is not screenshot-stable.');
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  await settlePage(page);
  const productDetail = page.locator('.grid-pro-detail');
  const detailTarget = isMobile ? page.locator('.right-pro-detail') : productDetail;
  await detailTarget.scrollIntoViewIfNeeded();
  await expect(page.locator('#Zoom-1 img').first()).toBeVisible();
  await page.waitForTimeout(500);
  await expect(isMobile ? page : productDetail).toHaveScreenshot('product-detail.png', {
    animations: 'disabled',
    maxDiffPixelRatio: 0.01,
    timeout: 15000
  });
});

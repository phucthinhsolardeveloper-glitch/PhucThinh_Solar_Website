const { test, expect } = require('@playwright/test');

const productRoute = '/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-18500-500w';

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function assertNoHorizontalOverflow(page) {
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(1);
}

test('premium refinement giữ hero caption/control và trạng thái keyboard ở mọi khung', async ({ page }) => {
  for (const width of [390, 768, 1440]) {
    await page.setViewportSize({ width, height: width < 800 ? 844 : 900 });
    await page.goto('/', { waitUntil: 'domcontentloaded' });

    const slider = page.locator('[data-home-hero-slider]');
    const caption = slider.locator('.pts-home-hero-banner-copy').first();
    const next = slider.locator('[data-home-hero-next]');
    const pause = slider.locator('[data-home-hero-pause]');
    await expect(caption).toBeVisible();
    const controlSize = await next.evaluate(element => {
      const rect = element.getBoundingClientRect();
      return { width: rect.width, height: rect.height };
    });
    expect(controlSize.width).toBeGreaterThanOrEqual(44);
    expect(controlSize.height).toBeGreaterThanOrEqual(44);
    await expect(pause).toBeVisible();

    await next.click();
    await expect(slider.locator('[data-home-hero-current]')).toHaveText('02');
    await expect(slider.locator('[data-home-hero-slide].is-active .pts-home-hero-banner-copy')).toBeVisible();

    await page.keyboard.press('Tab');
    await next.focus();
    await expect(next).toBeFocused();
    const focusRing = await next.evaluate(element => {
      const style = getComputedStyle(element);
      return { focusVisible: element.matches(':focus-visible'), outlineStyle: style.outlineStyle, outlineWidth: style.outlineWidth, boxShadow: style.boxShadow };
    });
    expect(focusRing.focusVisible).toBeTruthy();
    expect(focusRing.outlineStyle !== 'none' || focusRing.boxShadow !== 'none').toBeTruthy();

    await pause.click();
    await expect(pause).toHaveAttribute('aria-pressed', 'true');
    await assertNoHorizontalOverflow(page);
  }
});

test('premium refinement tôn trọng reduced motion trên hero', async ({ page }) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/', { waitUntil: 'domcontentloaded' });

  const motion = await page.locator('[data-home-hero-slide]').first().evaluate(element => {
    const style = getComputedStyle(element);
    return { transitionDuration: style.transitionDuration, animationName: style.animationName };
  });
  expect(motion.transitionDuration).toBe('0s');
  expect(motion.animationName).toBe('none');
  await assertNoHorizontalOverflow(page);
});

test('premium refinement PDP giữ gallery vuông, thumbnail click/keyboard và placeholder có nghĩa', async ({ page }) => {
  for (const width of [390, 768, 1440]) {
    await page.setViewportSize({ width, height: width < 800 ? 844 : 900 });
    await page.goto(productRoute, { waitUntil: 'domcontentloaded' });

    const gallery = page.locator('#Zoom-1');
    const thumbnails = page.locator('.gallery-thumb-pro .thumb-pro-detail');
    await expect(gallery).toBeVisible();
    const thumbnailCount = await thumbnails.count();
    // Chỉ hiện gallery thumbnail khi có ít nhất một ảnh phụ, không lặp ảnh chính.
    expect(thumbnailCount === 0 || thumbnailCount > 1).toBeTruthy();
    await expect(page.locator('.pts-pdp-decision-metric')).toHaveCount(4);
    await expect(page.locator('.pts-pdp-decision-metric.is-placeholder strong').first()).toHaveText('Đang cập nhật');
    await expect(page.locator('.pts-pdp-decision-metric.is-placeholder strong').first()).not.toContainText('[...]');

    const galleryMetrics = await gallery.evaluate(element => {
      const rect = element.getBoundingClientRect();
      return { width: rect.width, height: rect.height };
    });
    expect(Math.abs(galleryMetrics.width - galleryMetrics.height)).toBeLessThanOrEqual(1);

    const thumbSizes = await thumbnails.evaluateAll(elements => elements.map(element => {
      const rect = element.getBoundingClientRect();
      return { width: rect.width, height: rect.height };
    }));
    expect(thumbSizes.every(size => size.width >= 44 && size.height >= 44)).toBeTruthy();

    const second = thumbnails.nth(1);
    if (thumbnailCount > 1) {
      const before = await gallery.evaluate(element => {
        const image = Array.from(element.querySelectorAll('img')).find(candidate => {
          const rect = candidate.getBoundingClientRect();
          return rect.width > 0 && rect.height > 0 && getComputedStyle(candidate).visibility !== 'hidden';
        });
        return image ? image.currentSrc : '';
      });
      await second.click();
      await expect.poll(() => gallery.evaluate(element => {
        const image = Array.from(element.querySelectorAll('img')).find(candidate => {
          const rect = candidate.getBoundingClientRect();
          return rect.width > 0 && rect.height > 0 && getComputedStyle(candidate).visibility !== 'hidden';
        });
        return image ? image.currentSrc : '';
      })).not.toBe(before);

      await second.focus();
      await page.keyboard.press('Enter');
      await expect(second).toBeFocused();
    }

    await assertNoHorizontalOverflow(page);
  }
});

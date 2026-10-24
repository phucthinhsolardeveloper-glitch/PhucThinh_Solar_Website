const { test, expect } = require('@playwright/test');

const viewports = [
  { name: '390 mobile', width: 390, height: 844, mobile: true },
  { name: '768 tablet', width: 768, height: 900, mobile: true },
  { name: '1440 desktop', width: 1440, height: 900, mobile: false },
  { name: '1920 desktop', width: 1920, height: 1080, mobile: false },
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

for (const viewport of viewports) {
  test(`solution journey stays choice-first at ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    const response = await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);

    const hub = page.locator('.pts-solution-hub');
    const intro = hub.locator('.pts-sh-intro');
    const details = intro.locator('.pts-sh-intro-details');
    const summary = intro.locator('.pts-sh-intro-summary');
    const description = details.locator('p');
    const choices = hub.locator('.pts-sh-choice');
    const choiceGrid = hub.locator('.pts-sh-choice-grid');

    await expect(hub.locator('h1')).toHaveCount(1);
    await expect(intro.locator('.pts-sd-explore')).toHaveCSS('color', 'rgb(10, 34, 81)');
    expect(await choices.count()).toBeGreaterThan(0);
    expect((await description.textContent()).trim()).not.toBe('');
    expect(await intro.locator('.pts-sh-consult').count()).toBe(0);
    await expect(hub.locator('.pts-sh-contact .pts-sh-consult')).toHaveCount(1);

    const layout = await choiceGrid.evaluate(grid => {
      const sectionBox = grid.closest('.pts-sh-choices').getBoundingClientRect();
      const cards = Array.from(grid.querySelectorAll('.pts-sh-choice'));
      const copyHeights = cards.map(card => card.querySelector('.pts-sh-choice-copy').getBoundingClientRect().height);
      return {
        sectionTop: sectionBox.top,
        copyHeights,
        scrollWidth: grid.scrollWidth,
        clientWidth: grid.clientWidth,
      };
    });

    expect(layout.copyHeights.length).toBeGreaterThan(0);
    expect(Math.max(...layout.copyHeights) - Math.min(...layout.copyHeights)).toBeLessThanOrEqual(1);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);

    if (viewport.mobile) {
      await expect(summary).toBeVisible();
      expect(await details.evaluate(node => node.open)).toBe(false);
      expect(await summary.evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(44);
      expect(layout.sectionTop).toBeLessThan(viewport.height);
      if (viewport.width < 800) {
        expect(layout.scrollWidth).toBeGreaterThanOrEqual(layout.clientWidth);
      }

      await summary.click();
      expect(await details.evaluate(node => node.open)).toBe(true);
      await expect(description).toBeVisible();
      expect((await description.textContent()).trim().length).toBeGreaterThan(20);
    } else {
      expect(await details.evaluate(node => node.open)).toBe(true);
      await expect(summary).toBeHidden();
      expect(layout.scrollWidth).toBeLessThanOrEqual(layout.clientWidth + 1);
    }

    const targets = await choices.evaluateAll(nodes => nodes.map(node => new URL(node.href).pathname));
    for (const target of targets) {
      expect(target).toMatch(/^\/giai-phap\/[a-z0-9-]+\/$/);
    }
  });
}

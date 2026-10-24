const { test, expect } = require('@playwright/test');

const viewports = [
  { name: '390 mobile', width: 390, height: 844, mobile: true, columns: 1 },
  { name: '768 tablet', width: 768, height: 900, mobile: true, columns: 2 },
  { name: '1440 desktop', width: 1440, height: 900, mobile: false, columns: 3 },
  { name: '1920 desktop', width: 1920, height: 1080, mobile: false, columns: 3 },
];

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

for (const viewport of viewports) {
  test(`solution hub has an editorial choice layout at ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    const response = await page.goto('/giai-phap/', { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);

    const hub = page.locator('.pts-solution-hub');
    const intro = hub.locator('.pts-sh-intro');
    const details = intro.locator('.pts-sh-intro-details');
    const summary = intro.locator('.pts-sh-intro-summary');
    const choicesSection = hub.locator('.pts-sh-choices');
    const choices = hub.locator('.pts-sh-choice');
    const choiceGrid = hub.locator('.pts-sh-choice-grid');

    await expect(hub.locator('h1')).toHaveCount(1);
    await expect(choicesSection.locator('h2')).toHaveCount(1);
    await expect(choicesSection.locator('h2')).not.toBeEmpty();
    await expect(intro.locator('.pts-sd-explore')).toHaveCSS('color', 'rgb(10, 34, 81)');
    await expect(hub.locator('.pts-sh-contact .pts-sh-consult')).toHaveCount(1);
    expect(await choices.count()).toBeGreaterThan(0);
    expect((await details.locator('p').textContent()).trim()).not.toBe('');

    const layout = await choiceGrid.evaluate(grid => {
      const cards = Array.from(grid.querySelectorAll('.pts-sh-choice'));
      const media = cards.map(card => card.querySelector('.pts-sh-choice-media').getBoundingClientRect());
      const copy = cards.map(card => card.querySelector('.pts-sh-choice-copy').getBoundingClientRect());
      return {
        columns: getComputedStyle(grid).gridTemplateColumns.split(' ').length,
        media,
        copy,
        scrollWidth: grid.scrollWidth,
        clientWidth: grid.clientWidth,
      };
    });

    expect(layout.columns).toBe(viewport.columns);
    expect(layout.scrollWidth).toBeLessThanOrEqual(layout.clientWidth + 1);
    for (const [index, media] of layout.media.entries()) {
      expect(layout.copy[index].top).toBeGreaterThanOrEqual(media.bottom - 1);
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);

    if (viewport.mobile) {
      await expect(summary).toBeVisible();
      expect(await details.evaluate(node => node.open)).toBe(false);
      expect(await summary.evaluate(node => node.getBoundingClientRect().height)).toBeGreaterThanOrEqual(44);
      await summary.click();
      expect(await details.evaluate(node => node.open)).toBe(true);
      await expect(details.locator('p')).toBeVisible();
      expect((await details.locator('p').textContent()).trim().length).toBeGreaterThan(20);
    } else {
      expect(await details.evaluate(node => node.open)).toBe(true);
      await expect(summary).toBeHidden();
    }

    const targets = await choices.evaluateAll(nodes => nodes.map(node => new URL(node.href).pathname));
    for (const target of targets) {
      expect(target).toMatch(/^\/giai-phap\/[a-z0-9-]+\/$/);
    }
  });
}

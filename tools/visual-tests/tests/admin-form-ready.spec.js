const { test, expect } = require('@playwright/test');

test('admin form submit is enabled when DOM is ready, before slow images finish', async ({ page }) => {
  await page.route('**/__pts_slow_admin_image', async route => {
    await new Promise(resolve => setTimeout(resolve, 3000));
    await route.abort();
  });
  await page.addInitScript(() => {
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.createElement('form');
      form.className = 'validation-form';
      form.innerHTML = '<button type="submit" disabled>Save</button>';
      document.body.appendChild(form);
      const image = document.createElement('img');
      image.src = '/__pts_slow_admin_image';
      document.body.appendChild(image);
    });
  });

  await page.goto('/administrator/index.php?com=user&act=login', { waitUntil: 'domcontentloaded' });
  const state = await page.evaluate(() => ({
    readyState: document.readyState,
    enabled: !document.querySelector('.validation-form button[type="submit"]').disabled,
  }));
  expect(state.readyState).not.toBe('complete');
  expect(state.enabled).toBe(true);
});

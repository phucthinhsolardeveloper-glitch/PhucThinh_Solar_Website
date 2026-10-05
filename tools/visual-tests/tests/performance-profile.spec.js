const { test, expect } = require('@playwright/test');

test('profile representative public routes', async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
  await page.addInitScript(() => {
    window.__ptsPerf = { longTasks: [], lcp: 0 };
    if (!window.PerformanceObserver) return;
    try {
      new PerformanceObserver(list => {
        for (const entry of list.getEntries()) window.__ptsPerf.longTasks.push(Math.round(entry.duration));
      }).observe({ type: 'longtask', buffered: true });
      new PerformanceObserver(list => {
        for (const entry of list.getEntries()) window.__ptsPerf.lcp = Math.round(entry.startTime);
      }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (_) {}
  });

  for (const route of ['/', '/danh-muc-san-pham/bien-tan-inverter/hoa-luoi/', '/du-an/', '/san-pham/inverter-solax-x3-forth-125-plus']) {
    const response = await page.goto(route, { waitUntil: 'load' });
    expect(response.status(), route).toBe(200);
    const metrics = await page.evaluate(() => {
      const nav = performance.getEntriesByType('navigation')[0];
      const resources = performance.getEntriesByType('resource');
      const origin = location.origin;
      return {
        path: location.pathname,
        domContentLoadedMs: Math.round(nav.domContentLoadedEventEnd),
        loadMs: Math.round(nav.loadEventEnd),
        lcpMs: window.__ptsPerf.lcp,
        longTasks: window.__ptsPerf.longTasks,
        transferredKb: Math.round(resources.reduce((sum, item) => sum + item.transferSize, 0) / 1024),
        topResources: resources.filter(item => item.name.startsWith(origin)).sort((a, b) => b.transferSize - a.transferSize).slice(0, 6).map(item => ({
          path: new URL(item.name).pathname,
          kb: Math.round(item.transferSize / 1024),
          ms: Math.round(item.duration),
        })),
      };
    });
    console.log(JSON.stringify(metrics));
  }
});

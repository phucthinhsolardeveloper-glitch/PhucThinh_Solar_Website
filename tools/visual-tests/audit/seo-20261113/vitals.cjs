// Rough Core Web Vitals on a mid phone: 4x CPU, ~4G (9 Mbps down, 150 ms RTT). Usage: node vitals.cjs <url> [...]
const { chromium } = require('playwright')
;(async () => {
  const b = await chromium.launch()
  for (const url of process.argv.slice(2)) {
    const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 })
    const p = await ctx.newPage(); const cdp = await ctx.newCDPSession(p)
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 })
    await cdp.send('Network.enable'); await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 9e6 / 8, uploadThroughput: 1.5e6 / 8 })
    let bytes = 0, reqs = 0; const types = {}
    p.on('response', async (r) => { reqs++; const len = +(r.headers()['content-length'] || 0); bytes += len; const t = r.request().resourceType(); types[t] = (types[t] || 0) + len })
    await p.addInitScript(() => {
      window.__v = { lcp: 0, cls: 0, lt: 0, lcpEl: '' }
      new PerformanceObserver((l) => { for (const e of l.getEntries()) { window.__v.lcp = e.startTime; window.__v.lcpEl = (e.element?.tagName || '') + ' ' + (e.url || '').slice(-50) } }).observe({ type: 'largest-contentful-paint', buffered: true })
      new PerformanceObserver((l) => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__v.cls += e.value }).observe({ type: 'layout-shift', buffered: true })
      new PerformanceObserver((l) => { for (const e of l.getEntries()) window.__v.lt += Math.max(0, e.duration - 50) }).observe({ type: 'longtask', buffered: true })
    })
    const t0 = Date.now(); await p.goto(url, { waitUntil: 'load', timeout: 90000 }); const load = Date.now() - t0
    await p.waitForTimeout(3000)
    const v = await p.evaluate(() => ({ ...window.__v, fcp: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0, html: performance.getEntriesByType('navigation')[0]?.transferSize || 0 }))
    console.log(`${url.replace(/^https?:\/\/[^/]+/, '') || '/'}  FCP ${Math.round(v.fcp)}ms  LCP ${Math.round(v.lcp)}ms (${v.lcpEl})  CLS ${v.cls.toFixed(3)}  TBT~${Math.round(v.lt)}ms  load ${load}ms  html ${Math.round(v.html / 1024)}KB  reqs ${reqs}  ` + Object.entries(types).filter(([, n]) => n > 20000).map(([t, n]) => `${t} ${Math.round(n / 1024)}KB`).join(' '))
    await ctx.close()
  }
  await b.close()
})()

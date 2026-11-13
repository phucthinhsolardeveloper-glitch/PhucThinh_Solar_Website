const { chromium } = require('playwright')
;(async () => {
  const [base, start, target, sc, cpu] = process.argv.slice(2)
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })
  const cdp = await p.context().newCDPSession(p); if (+cpu) await cdp.send('Emulation.setCPUThrottlingRate', { rate: +cpu })
  await p.goto(base + start, { waitUntil: 'networkidle' }); await p.waitForTimeout(800)
  await p.evaluate((y) => window.scrollTo(0, y), +sc); await p.waitForTimeout(900)
  await p.evaluate(() => { window.__l = []; const t0 = performance.now(); const tick = () => { const h1 = document.querySelector('main h1, h1'); const hd = document.querySelector('.pts-site-header').getBoundingClientRect().top
    window.__l.push(`${Math.round(performance.now() - t0)}ms y=${Math.round(scrollY)} url=${location.pathname} h1=${(h1?.textContent || '').trim().slice(0, 22)} header=${Math.round(hd)}`); if (performance.now() - t0 < 4000) requestAnimationFrame(tick) }; requestAnimationFrame(tick) })
  await p.locator(target).first().click()
  await p.waitForTimeout(4200)
  let last = ''; for (const s of await p.evaluate(() => window.__l)) { const k = s.replace(/^\d+ms /, ''); if (k !== last) console.log(s); last = k }
  await b.close()
})()

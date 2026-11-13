const { chromium } = require('playwright')
;(async () => {
  const base = process.argv[2]
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 390, height: 844 }, locale: 'vi-VN', isMobile: true, hasTouch: true })
  const m = async (label) => console.log(label.padEnd(48), JSON.stringify(await p.evaluate(() => {
    const r = (s) => { const e = document.querySelector(s); if (!e) return null; const b = e.getBoundingClientRect(); return [Math.round(b.top), Math.round(b.height)] }
    return { path: location.pathname.slice(0, 40), header: r('header'), mast: r('.catalog-masthead, [class*=masthead]'), brands: r('.masthead-brands'), chips: r('.catalog-chip-rail'), toolbar: r('.catalog-toolbar'), grid: r('[data-results] ul, [data-results] .grid') }
  })))
  await p.goto(base + '/danh-muc-san-pham/den-nlmt/', { waitUntil: 'networkidle' }); await m('load den-nlmt')
  for (const name of ['Đèn Pha', 'Đèn Sân Vườn', 'Tất cả']) {
    await p.locator('.catalog-chip-rail a', { hasText: name }).first().click(); await p.waitForLoadState('networkidle'); await p.waitForTimeout(700); await m('chip ' + name)
  }
  for (const path of ['/danh-muc-san-pham/quat-nang-luong-mat-troi/', '/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/', '/danh-muc-san-pham/thiet-bi-dien-nang-luong-mat-troi/cap-day-dien/']) {
    await p.goto(base + path, { waitUntil: 'networkidle' }); await m('load ' + path.split('/')[2] + (path.split('/')[3] ? '/' + path.split('/')[3] : ''))
  }

  await b.close() })()

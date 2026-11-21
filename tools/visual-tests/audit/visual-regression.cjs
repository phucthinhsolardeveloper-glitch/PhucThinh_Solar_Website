#!/usr/bin/env node
/* So sánh hình ảnh trước/sau khi refactor giao diện.
 *   shoot  <thư mục> [base]     chụp các trang và trạng thái chính ở 390 và 1440 px
 *   diff   <thư mục A> <thư mục B> [ngưỡng%]   so từng ảnh, in % điểm ảnh khác và lưu ảnh chênh lệch
 * Chạy từ tools/visual-tests: PLAYWRIGHT_BROWSERS_PATH=$PWD/.browsers node audit/visual-regression.cjs shoot /tmp/truoc http://127.0.0.1:3200
 * Hai lần chụp phải dùng CÙNG dữ liệu và CÙNG cổng backend. Không gửi form, không bấm mua.
 */
const { chromium } = require('playwright')
const fs = require('fs'); const path = require('path')
const ROUTES = ['/', '/san-pham/', '/danh-muc-san-pham/', '/gioi-thieu/', '/giai-phap/', '/tin-tuc/', '/lien-he/', '/dai-ly/', '/gio-hang/', '/du-an/', '/uoc-tinh-dien-mat-troi/', '/404-khong-ton-tai']
const WIDTHS = [390, 768, 1440]
const slug = (r) => (r === '/' ? 'home' : r.replace(/^\/|\/$/g, '').replace(/\//g, '_'))

async function settle(p) {
  await p.waitForLoadState('networkidle').catch(() => {})
  await p.evaluate(async () => { const h = document.documentElement.scrollHeight; for (let y = 0; y < h; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 40)) } window.scrollTo(0, 0) })
  await p.waitForTimeout(400)
}
async function shoot(dir, base) {
  fs.mkdirSync(dir, { recursive: true })
  const b = await chromium.launch(); const log = []
  for (const w of WIDTHS) {
    const ctx = await b.newContext({ viewport: { width: w, height: 844 }, reducedMotion: 'reduce', hasTouch: w < 800, isMobile: w < 800, locale: 'vi-VN' })
    await ctx.addInitScript(() => { try { localStorage.setItem('pts-consent', JSON.stringify({ analytics: false, ads: false, ts: 1 })) } catch {} document.cookie = 'pts_consent=1; path=/' })
    await ctx.route(/cdn\.gtranslate\.net|googletagmanager\.com|google-analytics\.com/, r => r.abort())
    const p = await ctx.newPage()
    let routes = [...ROUTES]
    // một trang sản phẩm và một bài viết thật: lấy từ trang danh sách
    for (const [list, sel, label] of [['/san-pham/', 'a[href^="/san-pham/"]:not([href="/san-pham/"])', 'pdp'], ['/tin-tuc/', 'main a[href^="/"]:not([href*="tin-tuc"]):not([href*="san-pham"]):not([href*="danh-muc"])', 'bai']]) {
      await p.goto(base + list, { waitUntil: 'domcontentloaded' }).catch(() => {})
      const href = await p.$$eval(sel, as => as.map(a => a.getAttribute('href')).find(h => h && !h.includes('?') && !h.includes('#') && h.replace(/\//g,'').length >= 20))
      if (href) routes.push(href)
    }
    for (const r of routes) {
      const res = await p.goto(base + r, { waitUntil: 'domcontentloaded' }).catch(() => null)
      await settle(p)
      const f = path.join(dir, `${slug(r)}__${w}.png`)
      await p.screenshot({ path: f, fullPage: true, animations: 'disabled' }).catch(e => log.push('LỖI ' + r + ' ' + e.message))
      log.push(`${res ? res.status() : '???'} ${w} ${r}`)
    }
    // trạng thái: menu mở (di động), tìm kiếm mở, cookie
    await p.goto(base + '/', { waitUntil: 'domcontentloaded' }); await settle(p)
    const menu = await p.$('button[aria-controls="ptsMobileMenu"]')
    if (menu && w <= 1320) { await menu.click(); await p.waitForTimeout(700); await p.screenshot({ path: path.join(dir, `state_menu__${w}.png`) }); await p.keyboard.press('Escape'); await p.waitForTimeout(400) }
    const s = await p.$('button[aria-controls="ptsHeaderSearch"]')
    if (s) { await s.click(); await p.waitForTimeout(600); await p.screenshot({ path: path.join(dir, `state_search__${w}.png`) }); await p.keyboard.press('Escape'); await p.waitForTimeout(300) }
    await p.evaluate(() => window.scrollTo(0, 1200)); await p.waitForTimeout(500)
    await p.screenshot({ path: path.join(dir, `state_scrolled__${w}.png`) })
    await ctx.close()
    // cookie notice: không đặt đồng ý
    const c2 = await b.newContext({ viewport: { width: w, height: 844 }, reducedMotion: 'reduce', locale: 'vi-VN' }); await c2.route(/cdn\.gtranslate\.net|googletagmanager\.com|google-analytics\.com/, r => r.abort())
    const p2 = await c2.newPage(); await p2.goto(base + '/', { waitUntil: 'domcontentloaded' }); await p2.waitForTimeout(3000)
    await p2.screenshot({ path: path.join(dir, `state_cookie__${w}.png`) }); await c2.close()
  }
  fs.writeFileSync(path.join(dir, '_log.txt'), log.join('\n')); console.log(log.join('\n')); await b.close()
}
async function diff(a, bdir, limit = 0.2) {
  const files = fs.readdirSync(a).filter(f => f.endsWith('.png')).sort()
  const b = await chromium.launch(); const pg = await (await b.newContext()).newPage(); await pg.setContent('<canvas id=c></canvas>')
  const out = path.join(bdir, '_diff'); fs.mkdirSync(out, { recursive: true }); let bad = 0
  console.log('ảnh | % điểm ảnh khác | khác kích thước')
  for (const f of files) {
    const fb = path.join(bdir, f); if (!fs.existsSync(fb)) { console.log('THIẾU', f); bad++; continue }
    const r = await pg.evaluate(async ([x, y]) => {
      const load = async (s) => { const i = new Image(); i.src = 'data:image/png;base64,' + s; await i.decode(); return i }
      const A = await load(x), B = await load(y); const W = Math.max(A.width, B.width), H = Math.max(A.height, B.height)
      const c = document.getElementById('c'); c.width = W; c.height = H; const g = c.getContext('2d', { willReadFrequently: true })
      g.fillStyle = '#f0f'; g.fillRect(0, 0, W, H); g.drawImage(A, 0, 0); const da = g.getImageData(0, 0, W, H).data
      g.fillStyle = '#f0f'; g.fillRect(0, 0, W, H); g.drawImage(B, 0, 0); const db = g.getImageData(0, 0, W, H).data
      const o = g.createImageData(W, H); let n = 0
      for (let i = 0; i < da.length; i += 4) { const d = Math.abs(da[i] - db[i]) + Math.abs(da[i + 1] - db[i + 1]) + Math.abs(da[i + 2] - db[i + 2]); if (d > 24) { n++; o.data[i] = 255; o.data[i + 3] = 255 } else { o.data[i] = da[i]; o.data[i + 1] = da[i + 1]; o.data[i + 2] = da[i + 2]; o.data[i + 3] = 90 } }
      g.putImageData(o, 0, 0); return { pct: n * 100 / (W * H), size: A.width + 'x' + A.height + ' vs ' + B.width + 'x' + B.height, same: A.width === B.width && A.height === B.height, png: c.toDataURL('image/png').split(',')[1] }
    }, [fs.readFileSync(path.join(a, f)).toString('base64'), fs.readFileSync(fb).toString('base64')])
    const flag = r.pct > limit || !r.same ? 'KHÁC' : 'ok  '; if (flag === 'KHÁC') { bad++; fs.writeFileSync(path.join(out, f), Buffer.from(r.png, 'base64')) }
    console.log(flag, f.padEnd(34), r.pct.toFixed(3).padStart(7) + '%', r.same ? '' : r.size)
  }
  await b.close(); console.log(bad ? `\n${bad}/${files.length} ảnh khác (ảnh chênh lệch: ${out})` : `\nTất cả ${files.length} ảnh khớp`); process.exit(bad ? 1 : 0)
}
const [cmd, x, y, z] = process.argv.slice(2)
if (cmd === 'shoot') shoot(x, y || 'http://127.0.0.1:3200'); else if (cmd === 'diff') diff(x, y, z ? Number(z) : 0.2); else console.log(require('fs').readFileSync(__filename, 'utf8').split('*/')[0])

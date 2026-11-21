#!/usr/bin/env node
/* Ghép ảnh trước/sau cạnh nhau: node audit/vr-sidebyside.cjs <thư mục trước> <thư mục sau> <ra.png> <tên ảnh>... [--top=N] (cắt N px đầu) */
const { chromium } = require('playwright'); const fs = require('fs'); const path = require('path')
;(async () => {
  const args = process.argv.slice(2); const top = Number((args.find(a => a.startsWith('--top=')) || '').split('=')[1] || 0)
  const [A, B, out, ...names] = args.filter(a => !a.startsWith('--top='))
  const b = await chromium.launch(); const p = await (await b.newContext()).newPage()
  const rows = names.map(n => [fs.readFileSync(path.join(A, n)).toString('base64'), fs.readFileSync(path.join(B, n)).toString('base64'), n])
  const png = await p.evaluate(async ([rows, top]) => {
    const load = async s => { const i = new Image(); i.src = 'data:image/png;base64,' + s; await i.decode(); return i }
    const imgs = []; for (const [a, b, n] of rows) imgs.push([await load(a), await load(b), n])
    const H = Math.max(...imgs.map(([a, b]) => Math.min(top || 1e9, Math.max(a.height, b.height)))); const W = imgs.reduce((s, [a, b]) => s + Math.max(a.width, b.width) * 2 + 24, 0)
    const c = document.createElement('canvas'); c.width = W; c.height = H + 20; const g = c.getContext('2d'); g.fillStyle = '#888'; g.fillRect(0, 0, W, H + 20)
    let x = 0; g.fillStyle = '#000'; g.font = '14px sans-serif'
    for (const [a, b, n] of imgs) { const w = Math.max(a.width, b.width); g.fillText('TRƯỚC · ' + n, x + 4, 14); g.drawImage(a, 0, 0, a.width, Math.min(a.height, H), x, 20, a.width, Math.min(a.height, H)); x += w + 12; g.fillText('SAU', x + 4, 14); g.drawImage(b, 0, 0, b.width, Math.min(b.height, H), x, 20, b.width, Math.min(b.height, H)); x += w + 12 }
    return c.toDataURL('image/png').split(',')[1]
  }, [rows, top])
  fs.writeFileSync(out, Buffer.from(png, 'base64')); await b.close(); console.log(out)
})()

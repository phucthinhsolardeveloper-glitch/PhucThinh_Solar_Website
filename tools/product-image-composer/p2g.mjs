#!/usr/bin/env node
// Ảnh 03 "Đặc điểm nhận diện sản phẩm" (mẫu input/mau-7-anh/3.png) cho đèn pha.
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { esc, measureText, contrastRatio, prepareProduct, filterDetachedComponents } from './avatar.mjs';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const args = process.argv.slice(2);
const arg = (key, fallback) => { const i = args.indexOf(key); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const dataFile = path.join(inputRoot, arg('--data', 'den-pha-72.json'));
const plateFile = path.join(outputRoot, 'nen/p2-nen-03.png');
const sampleFile = path.join(inputRoot, 'mau-7-anh/3.png');
const logoFile = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const outDir = path.join(outputRoot, arg('--out', 'p2g'));
const brandOrder = ['BLUE CARBON', 'JINDIAN', 'ORIANA'];
const certBrands = new Set(['BLUE CARBON', 'JINDIAN']);
const FONT = 'Arial, Be Vietnam Pro, sans-serif';
const safeCode = c => String(c || '').replace(/[^a-zA-Z0-9._-]/g, '-');
const dataUri = (buf, mime = 'png') => `data:image/${mime};base64,${buf.toString('base64')}`;
const T = (text, x, y, size, fill, weight = 400, anchor = 'start', extra = '') => `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" font-family="${FONT}" font-size="${size}" font-weight="${weight}" ${extra}>${esc(text)}</text>`;
const watts = item => { const n = Number(item.power_watts); return Number.isFinite(n) && n > 0 ? `${Number.isInteger(n) ? n : String(item.power_watts).replace(/\.0+$/, '')}W` : ''; };

// ---------- sản phẩm ----------
async function avatarQa(code) {
  for (const f of [path.join(outputRoot, 'final58', `${code}.qa.json`), path.join(outputRoot, 'm58b/v5', `${code}.qa.json`), path.join(outputRoot, 'm58b/v9e', `${code}.qa.json`), path.join(outputRoot, 'm58b/v6-den', `${code}.qa.json`), path.join(outputRoot, 'm58b/v6-dieu-khien', `${code}.qa.json`), path.join(outputRoot, 'm58b/v6-inverter-luu-tru', `${code}.qa.json`), path.join(outputRoot, 'm58b/v6-tam-pin-quat-bom', `${code}.qa.json`)])
    try { const j = JSON.parse(await fs.readFile(f, 'utf8')); if (j.passed === false || !j.source) continue; return { file: f, json: j }; } catch {}
  return null;
}
const V9_CODES = new Set(['JD-6128','BCT-WW5.0-MONO','JD-T100','DB60100','DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W']); // dùng ảnh PNG có sẵn của lượt avatar v9 (cutout alpha/trắng đã duyệt)
async function selectedProduct(item, avatar) {
  const v9 = V9_CODES.has(item.code);
  if (!v9 && !avatar?.json?.source && !item.image) throw new Error('Không có source đã chọn trong QA ảnh đại diện.');
  const source = v9 ? (item.code === 'JD-6128' ? 'jd-6128-8657.png' : `${item.code}.png`) : (avatar?.json?.source || item.image);
  const special = v9 ? (item.code === 'JD-6128' ? path.join(inputRoot, 'san-pham', 'jd-6128-8657.png') : path.join(outputRoot, 'v9/v9-input/san-pham', source)) : ['JD-T200', 'JD-T300'].includes(item.code) ? path.join(outputRoot, 'v9e/cutout', `${item.code}.png`) : path.join(inputRoot, 'san-pham', source);
  const prepared = await prepareProduct(special);
  if (prepared.status === 'needs-cutout-review') throw new Error(prepared.reason);
  const filtered = await filterDetachedComponents(prepared.png, source);
  const png = filtered.png || prepared.png;
  const tight = await sharp(png).trim({ background: { r: 0, g: 0, b: 0, alpha: 0 }, threshold: 0 }).png().toBuffer();
  return { buffer: tight, source };
}


const MS = { logo: { x: 37, y: 27, w: 329, h: 45 } };
async function sizeForWidth(text, targetW, weight) {
  let size = 40, w = await measureText(text, size, weight);
  size = size * targetW / w; w = await measureText(text, size, weight);
  return { size: Number(size.toFixed(1)), w };
}
async function fitW(text, target, start, weight, minRatio = .78) {
  let size = start, w = await measureText(text, size, weight);
  while (w > target && size > start * minRatio) { size -= .5; w = await measureText(text, size, weight); }
  return { size, w };
}

async function render(item) {
  const av = await avatarQa(item.code);
  const product = await selectedProduct(item, av);
  const meta = await sharp(product.buffer).metadata();
  const maxW = 540, maxH = 470, baseY = 735, cx0 = 318;
  const scale = Math.min(maxW / meta.width, maxH / meta.height);
  const pw = Math.round(meta.width * scale), ph = Math.round(meta.height * scale);
  const px = Math.round(cx0 - pw / 2), py = baseY - ph;
  const layer = await sharp(product.buffer).resize(pw, ph, { fit: 'fill', kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  const brand = String(item.brand).toUpperCase();
  const w = watts(item);
  const sub = `${brand} · ${item.code}${w ? ` (${w})` : ''}`;
  const warranty = item.warranty ? String(item.warranty).toLocaleUpperCase('vi') : null;
  const cert = certBrands.has(item.brand);
  const cap1 = `BỘ ĐÈN NĂNG LƯỢNG MẶT TRỜI ${item.code}`;
  const cap2 = `MÃ MODEL: ${item.code}${warranty ? ` · BẢO HÀNH: ${warranty}` : ''}`;
  const parts = [], texts = [];
  const rec = (text, x, y, size, anchor, w, source) => { const left = anchor === 'middle' ? x - w / 2 : x; texts.push({ text, source, box: { x: left, y: y - size * .78, w, h: size }, size }); };

  parts.push(`<defs><radialGradient id="gFloor"><stop offset="0" stop-color="#ffe7a3" stop-opacity=".55"/><stop offset="1" stop-color="#ffe7a3" stop-opacity="0"/></radialGradient><filter id="b4"><feGaussianBlur stdDeviation="4"/></filter><filter id="shadowSoft"><feGaussianBlur stdDeviation="7"/></filter></defs>`);
  const logoPng = await sharp(logoFile).resize(MS.logo.w, MS.logo.h, { fit: 'fill' }).png().toBuffer();
  parts.push(`<image href="${dataUri(logoPng)}" x="${MS.logo.x}" y="${MS.logo.y}" width="${MS.logo.w}" height="${MS.logo.h}" preserveAspectRatio="none"/>`);
  const title = 'ĐẶC ĐIỂM NHẬN DIỆN SẢN PHẨM';
  const tf = await sizeForWidth(title, 675, 800);
  parts.push(T(title, 450, 150, tf.size, '#ffffff', 800, 'middle', `textLength="675" lengthAdjust="spacingAndGlyphs"`));
  rec(title, 450, 150, tf.size, 'middle', 675, 'static');
  const sf = await fitW(sub, 520, 19, 400);
  parts.push(T(sub, 450, 187, sf.size, '#e9f0ff', 400, 'middle', `letter-spacing=".6"`));
  rec(sub, 450, 187, sf.size, 'middle', sf.w + sub.length * .6, 'brand/code/power_watts');

  // sản phẩm trên bục
  parts.push(`<ellipse cx="${cx0}" cy="${baseY + 6}" rx="${Math.min(320, pw * .5)}" ry="20" fill="url(#gFloor)"/>`);
  parts.push(`<ellipse cx="${cx0}" cy="${baseY + 2}" rx="${Math.min(300, pw * .46)}" ry="11" fill="#000a1e" opacity=".55" filter="url(#shadowSoft)"/>`);
  parts.push(`<image href="${dataUri(layer)}" x="${px}" y="${py}" width="${pw}" height="${ph}" preserveAspectRatio="none"/>`);

  // chú thích dưới (sàn), có nền tối mờ để đọc được trên vệt sáng
  const c1 = await fitW(cap1, 520, 20, 500), c2 = await fitW(cap2, 520, 20, 500);
  const cw1 = c1.w + cap1.length * .8, cw2 = c2.w + cap2.length * .8, cwm = Math.max(cw1, cw2) + 36;
  parts.push(`<rect x="${(cx0 - cwm / 2).toFixed(1)}" y="755" width="${cwm.toFixed(1)}" height="72" rx="14" fill="#031a4a" fill-opacity=".62" stroke="#ffd669" stroke-opacity=".35" stroke-width="1"/>`);
  parts.push(T(cap1, cx0, 785, c1.size, '#f4f7ff', 500, 'middle', 'letter-spacing=".8"'));
  parts.push(T(cap2, cx0, 814, c2.size, '#ffe9a8', 600, 'middle', 'letter-spacing=".8"'));
  rec(cap1, cx0, 785, c1.size, 'middle', cw1, 'code');
  rec(cap2, cx0, 814, c2.size, 'middle', cw2, 'code/warranty');

  // nhãn dưới ba huy hiệu (huy hiệu có sẵn trong nền, tâm x≈788)
  const labels = [
    { cy: 262, l1: 'BẢO HÀNH', l2: warranty || '', src: 'warranty' },
    { cy: 430, l1: 'THƯƠNG HIỆU', l2: brand, src: 'brand' },
    { cy: 597, l1: cert ? 'NHÀ PHÂN PHỐI' : 'PHÚC THỊNH SOLAR', l2: cert ? 'ỦY QUYỀN' : 'PHÂN PHỐI', src: cert ? 'giấy chứng nhận' : 'static' },
  ];
  for (const b of labels) {
    const f1 = await fitW(b.l1, 142, 15.5, 700), f2 = b.l2 ? await fitW(b.l2, 142, 18, 800) : null;
    parts.push(T(b.l1, 704, b.cy - 4, f1.size, '#ffffff', 700, 'end', 'letter-spacing=".5" stroke="#031a4a" stroke-width="3" stroke-opacity=".55" paint-order="stroke"'));
    rec(b.l1, 704 - f1.w, b.cy - 4, f1.size, 'start', f1.w, b.src);
    if (f2) { parts.push(T(b.l2, 704, b.cy + 19, f2.size, '#ffd669', 800, 'end', 'stroke="#031a4a" stroke-width="3" stroke-opacity=".55" paint-order="stroke"')); rec(b.l2, 704 - f2.w, b.cy + 19, f2.size, 'start', f2.w, b.src); }
  }

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="${dataUri(await fs.readFile(plateFile))}" width="900" height="900"/>${parts.join('')}</svg>`;
  const output = path.join(outDir, `${safeCode(item.code)}.jpg`);
  await sharp(Buffer.from(svg)).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).flatten({ background: '#fff' }).toColorspace('srgb').jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(output);
  const m = await sharp(output).metadata(), st = await fs.stat(output);
  const inFrame = texts.every(t => t.box.x >= 15 && t.box.x + t.box.w <= 885 && t.box.y >= 8 && t.box.y + t.box.h <= 892);
  const overlaps = [];
  for (let i = 0; i < texts.length; i++) for (let j = i + 1; j < texts.length; j++) {
    const a = texts[i].box, b = texts[j].box;
    if (a.x < b.x + b.w - 1 && a.x + a.w > b.x + 1 && a.y + 2 < b.y + b.h - 2 && a.y + a.h - 2 > b.y + 2) overlaps.push([texts[i].text, texts[j].text]);
  }
  const productClear = py >= 200 && px >= 20 && px + pw <= 592;
  const qa = { code: item.code, brand: item.brand, image: path.basename(output), source: product.source, product: { x: px, y: py, w: pw, h: ph, bottom: baseY }, texts: { sub, cap1, cap2, badge3: labels[2].l1 + ' ' + labels[2].l2, certBrand: cert },
    checks: { canvas: m.width === 1600 && m.height === 1600 && !m.hasAlpha && st.size < 5 * 1024 * 1024, inFrame, overlaps, productClear } };
  qa.pass = qa.checks.canvas && inFrame && overlaps.length === 0 && productClear;
  await fs.writeFile(output.replace(/\.jpg$/, '.qa.json'), JSON.stringify(qa, null, 1));
  return { output, qa };
}
async function comparison() {
  const a = await sharp(sampleFile).resize(900, 900).png().toBuffer();
  const b = await sharp(path.join(outDir, 'BCT-WR1.0.jpg')).resize(900, 900).png().toBuffer();
  await sharp({ create: { width: 1800, height: 900, channels: 3, background: '#fff' } }).composite([{ input: a, left: 0, top: 0 }, { input: b, left: 900, top: 0 }]).jpeg({ quality: 93 }).toFile(path.join(outDir, 'so-voi-mau-03.jpg'));
}
async function contactSheet(brand, rows) {
  const rr = rows.filter(x => x.qa.brand === brand); if (!rr.length) return;
  const cols = 5, cell = 240, labelH = 30, pieces = [];
  for (let i = 0; i < rr.length; i++) {
    const x = (i % cols) * cell, y = Math.floor(i / cols) * (cell + labelH);
    pieces.push({ input: await sharp(rr[i].output).resize(cell, cell).png().toBuffer(), left: x, top: y });
    pieces.push({ input: Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${labelH}"><rect width="100%" height="100%" fill="#f3f6fa"/><text x="${cell / 2}" y="20" text-anchor="middle" font-family="Arial" font-size="14" fill="#172c4b">${esc(rr[i].qa.code)}${rr[i].qa.pass ? '' : ' ✗'}</text></svg>`), left: x, top: y + cell });
  }
  await sharp({ create: { width: cols * cell, height: Math.ceil(rr.length / cols) * (cell + labelH), channels: 3, background: '#fff' } }).composite(pieces).jpeg({ quality: 88 }).toFile(path.join(outDir, `tong-hop-${brand.toLowerCase().replace(/ /g, '-')}.jpg`));
}

async function main() {
  await fs.mkdir(outDir, { recursive: true });
  const data = JSON.parse(await fs.readFile(dataFile, 'utf8'));
  let all = data.filter(x => brandOrder.includes(x.brand));
  const only = arg('--codes', null);
  const pilot = args.includes('--pilot');
  const sel = pilot ? all.filter(x => x.code === 'BCT-WR1.0') : only ? all.filter(x => only.split(',').includes(x.code)) : all;
  const done = [], exc = [];
  for (const item of sel) {
    try { const r = await render(item); done.push(r); console.log(r.qa.pass ? 'PASS' : 'FAIL', item.code, r.qa.pass ? '' : JSON.stringify({ o: r.qa.checks.overlaps, f: r.qa.checks.inFrame, rc: r.qa.checks.rowsInCard, rr: r.qa.checks.rightInCard })); }
    catch (e) { exc.push({ code: item.code, reason: e.message }); console.error('EXC', item.code, e.message); }
  }
  if (pilot) await comparison();
  else if (!only) { for (const b of brandOrder) await contactSheet(b, done); await fs.writeFile(path.join(outDir, '_ngoai-le.md'), `# Ngoại lệ ảnh 03\n\n${exc.map(x => `- **${x.code}**: ${x.reason}`).join('\n') || '- Không có'}\n`); }
  console.log(JSON.stringify({ rendered: done.length, passed: done.filter(x => x.qa.pass).length, exceptions: exc }, null, 1));
}
await main();

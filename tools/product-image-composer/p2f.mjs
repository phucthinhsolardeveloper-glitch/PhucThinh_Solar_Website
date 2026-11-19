#!/usr/bin/env node
// Ảnh 04 "Nạp năng lượng ban ngày / Chiếu sáng ban đêm" (mẫu input/mau-7-anh/4.png) cho đèn pha.
// Sản phẩm = ảnh thật (cutout đã chọn khi làm ảnh đại diện), chữ/biểu tượng/pin/mũi tên do mã dựng, nền = nền mẫu 4 đã làm sạch.
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
const plateFile = path.join(outputRoot, 'nen/p2-nen-04.png');
const sampleFile = path.join(inputRoot, 'mau-7-anh/4.png');
const logoFile = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const outDir = path.join(outputRoot, arg('--out', 'p2f'));
const targetsFile = path.join(outputRoot, arg('--targets', 'p2d/targets.json'));
const brandOrder = ['BLUE CARBON', 'JINDIAN', 'ORIANA'];
const FONT = 'Arial, Be Vietnam Pro, sans-serif';
const NAVY = '#0b3d91', INK = '#1a2b55';

const safeCode = c => String(c || '').replace(/[^a-zA-Z0-9._-]/g, '-');
const dataUri = (buf, mime = 'png') => `data:image/${mime};base64,${buf.toString('base64')}`;
const T = (text, x, y, size, fill, weight = 400, anchor = 'start', extra = '') => `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" font-family="${FONT}" font-size="${size}" font-weight="${weight}" ${extra}>${esc(text)}</text>`;

// ---------- chuẩn hoá dữ liệu hiển thị (chỉ định dạng lại, không thêm số) ----------
function panelText(p) {
  let s = String(p || '').replace(/\([^)]*\)/g, ' ').trim();
  let type = '';
  const m = s.match(/\b(mono\s*crystalline|polycrystalline|mono|poly)\b/i);
  if (m) { type = m[1]; s = s.replace(m[0], ' '); }
  s = s.replace(/,/g, ' ').replace(/\s+/g, ' ').trim().replace(/^(\d+(?:[.,]\d+)?V)\s+(\d)/, '$1/$2');
  if (type) type = /^mono$/i.test(type) ? 'MONO' : /^poly$/i.test(type) ? 'Poly' : /^mono/i.test(type) ? 'MONO crystalline' : 'Polycrystalline';
  return `${type ? type + ' ' : ''}${s}`.trim();
}
function batteryInfo(b) {
  const raw = String(b || '').replace(/\([^)]*\)/g, ' ');
  const chem = (raw.match(/LiFePO4|Li-ion/i) || [''])[0].replace(/lifepo4/i, 'LiFePO4').replace(/li-ion/i, 'Li-ion');
  const v = (raw.match(/(\d+(?:[.,]\d+)?)\s*V\b/i) || [])[1];
  const cap = (raw.match(/(\d[\d.,]*)\s*(mAh|Ah)/i) || []).slice(1, 3).join('');
  const volt = v ? `${v.replace(',', '.')}V` : '';
  const spec = volt && cap ? `${volt}/${cap}` : (cap || volt);
  return { chem, spec, text: `${chem ? chem + ' ' : ''}${spec}`.trim() };
}
function lowerFirst(s) { return s; }
function extras(item) {
  const joined = (item.additional_specs || []).map(x => `${x.label || ''}: ${x.value || ''}`).join(' | ');
  const low = joined.toLowerCase();
  return {
    onoff: /bật\s*tắt theo nhu cầu/i.test(joined),
    cable5: /(cáp nguồn|dây dài)\s*5\s*mét/i.test(joined),
    chargeLed: /đèn báo sạc/i.test(joined),
    remote: /remote|điều khiển từ xa/i.test(String(item.package_contents || '')),
    lumen: (joined.match(/quang thông:\s*([\d.,]+)\s*lumen/i) || [])[1] || null,
    height: (joined.match(/chiều cao lắp đặt:\s*([^|]+)/i) || [])[1]?.trim() || null,
    ik: (joined.match(/chuẩn chống va đập:\s*(IK\d+)/i) || [])[1] || null,
    sensor: /cảm biến ánh sáng/i.test(low),
  };
}
function leftRows(item) {
  const e = extras(item), bat = batteryInfo(item.battery_capacity), rows = [];
  const add = (icon, value, label, field) => rows.push({ icon, value, label, field });
  if (e.onoff) add('power', 'BẬT/TẮT', 'Theo nhu cầu', 'additional_specs');
  if (bat.text) add('battery', bat.spec || bat.chem || 'PIN', bat.chem && bat.spec ? `Pin ${bat.chem}` : 'Pin lưu trữ', 'battery_capacity');
  if (e.cable5) add('cable', '5M', 'Cáp nguồn 5 mét', 'additional_specs');
  if (e.chargeLed) add('led', 'LED', 'Có đèn báo sạc', 'additional_specs');
  if (e.remote) add('remote', 'REMOTE', 'Điều khiển từ xa', 'package_contents');
  if (e.sensor) add('sensor', 'CẢM BIẾN', 'Ánh sáng & điều khiển', 'additional_specs');
  if (e.lumen) add('lumen', `${e.lumen} LM`, 'Quang thông', 'additional_specs');
  if (item.warranty) add('shield', String(item.warranty).toLocaleUpperCase('vi'), 'Bảo hành', 'warranty');
  const hgt = item.installation_height || e.height;
  if (hgt) add('ruler', String(hgt).replace(/\s*m$/i, ' M').toLocaleUpperCase('vi'), 'Chiều cao lắp đặt', item.installation_height ? 'installation_height' : 'additional_specs');
  if (item.lighting_area) add('sensor', String(item.lighting_area).replace(/\s*m²$/i, ' M²').toLocaleUpperCase('vi'), 'Diện tích chiếu sáng', 'lighting_area');
  if (item.color_temperature) add('lumen', String(item.color_temperature).replace(/\s+/g, ''), 'Nhiệt độ màu', 'color_temperature');
  if (e.ik) add('shield', e.ik, 'Chống va đập', 'additional_specs');
  if (item.waterproof_rating) add('water', String(item.waterproof_rating), 'Chống nước', 'waterproof_rating');
  if (item.led_chip) add('led', 'LED', String(item.led_chip), 'led_chip');
  if (item.lamp_dimensions) add('ruler', String(item.lamp_dimensions).replace(/\s+/g, ' '), 'Kích thước đèn', 'lamp_dimensions');
  { const n = Number(item.power_watts); if (Number.isFinite(n) && n > 0) add('led', `${Number.isInteger(n) ? n : n}W`, 'Công suất đèn', 'power_watts'); }
  return rows.slice(0, 4);
}

// ---------- biểu tượng nét (viewBox 48) ----------
const icons = {
  power: '<path d="M24 6v17"/><path d="M14.5 13a15 15 0 1 0 19 0"/>',
  battery: '<rect x="11" y="10" width="26" height="33" rx="4"/><path d="M19 6h10v4"/><path d="m26 17-7 12h6l-2 9 8-13h-6z"/>',
  cable: '<path d="M14 5v13a10 10 0 0 0 10 10h2a10 10 0 0 1 10 10v5M10 5h8M6 43h12m14-4h8m-3-34v8m-4-8v8"/>',
  led: '<path d="M24 6a12 12 0 0 0-7 22c2 2 3 4 3 7h8c0-3 1-5 3-7A12 12 0 0 0 24 6ZM19 40h10M21 44h6"/>',
  remote: '<rect x="15" y="4" width="18" height="40" rx="6"/><circle cx="24" cy="14" r="3"/><path d="M19 24h10M19 30h10M19 36h10"/>',
  sensor: '<circle cx="24" cy="24" r="6"/><path d="M24 4v6M24 38v6M4 24h6M38 24h6M10 10l4 4M34 34l4 4M38 10l-4 4M14 34l-4 4"/>',
  lumen: '<path d="M24 5a14 14 0 0 0-9 25c2 2 3 4 3 7h12c0-3 1-5 3-7A14 14 0 0 0 24 5ZM18 41h12m-10 4h8"/>',
  shield: '<path d="m24 4 16 6v12c0 10-7 18-16 22C15 40 8 32 8 22V10zM16 24l6 6 12-14"/>',
  ruler: '<path d="m7 32 25-25 11 11-25 25zM16 28l5 5m1-13 5 5m1-13 5 5"/>',
  water: '<path d="M24 4C20 13 9 23 9 32a15 15 0 0 0 30 0C39 23 28 13 24 4Z"/><path d="M17 33a7 7 0 0 0 7 7"/>',
  bolt: '<path d="M27 4 12 27h11l-3 17 16-24H25z"/>',
  clock: '<circle cx="24" cy="24" r="18"/><path d="M24 12v13l9 6"/>',
};
const iconSvg = (kind, cx, cy, size, stroke, sw = 2.6) => `<svg x="${cx - size / 2}" y="${cy - size / 2}" width="${size}" height="${size}" viewBox="0 0 48 48" fill="none" stroke="${stroke}" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round">${icons[kind]}</svg>`;

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

// ---------- mẫu: đo nền chữ ----------
const MS = { logo: { x: 58, y: 24, w: 332, h: 46 }, t1: { cx: 450.5, w: 535, base: 114 }, t2: { cx: 449.5, w: 403, base: 166 } };

async function fitWidth(text, target, startSize, weight, minRatio = .8) {
  let size = startSize, w = await measureText(text, size, weight);
  while (w > target && size > startSize * minRatio) { size -= .5; w = await measureText(text, size, weight); }
  return { size, w };
}
async function sizeForWidth(text, targetW, weight) { // cỡ chữ để chữ rộng đúng targetW
  let size = 40, w = await measureText(text, size, weight);
  size = size * targetW / w; w = await measureText(text, size, weight);
  return { size: Number(size.toFixed(1)), w };
}

let titleSizes = null;
async function getTitleSizes() {
  if (titleSizes) return titleSizes;
  titleSizes = { t1: await sizeForWidth('NẠP NĂNG LƯỢNG BAN NGÀY', MS.t1.w, 900), t2: await sizeForWidth('CHIẾU SÁNG BAN ĐÊM', MS.t2.w, 900) };
  return titleSizes;
}

async function render(item) {
  const illus = args.includes('--illustration');
  const kind = /quạt/i.test(`${item.cat || ''} ${item.name || ''}`) ? 'fan' : 'lamp';
  const av = illus ? null : await avatarQa(item.code);
  const product = illus ? { buffer: null, source: 'minh họa dùng chung (không dùng ảnh sản phẩm)' } : await selectedProduct(item, av);
  const meta = illus ? { width: 300, height: 290 } : await sharp(product.buffer).metadata();
  const maxW = 300, maxH = 290;
  const scale = Math.min(maxW / meta.width, maxH / meta.height);
  const pw = Math.round(meta.width * scale), ph = Math.round(meta.height * scale);
  const layer = illus ? null : await sharp(product.buffer).resize(pw, ph, { fit: 'fill', kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  const baseY = 526;
  const L = { x: Math.round(200 - pw / 2), y: baseY - ph }, R = { x: Math.round(718 - pw / 2), y: baseY - ph };
  let targets = {};
  try { targets = JSON.parse(await fs.readFile(targetsFile, 'utf8'))[item.code] || {}; } catch {}
  const lamp = Array.isArray(targets.lamp) ? { x: R.x + targets.lamp[0] * pw, y: R.y + targets.lamp[1] * ph } : { x: R.x + pw * .6, y: R.y + ph * .6 };
  const glowR = Math.max(16, Math.min(34, pw * .1));

  const bat = batteryInfo(item.battery_capacity);
  const panel = panelText(item.panel_power);
  if (!panel || !bat.text) throw new Error('Thiếu panel_power hoặc battery_capacity.');
  const sub1 = `Tấm pin ${panel} thu năng lượng ban ngày,`;
  const sub2 = `lưu trong pin ${bat.text} để chiếu sáng ban đêm`;
  const rows = leftRows(item);
  if (rows.length < 4) throw new Error(`Chỉ có ${rows.length} dòng thông tin thật cho thẻ trái.`);
  if (!item.lighting_time) throw new Error('Thiếu lighting_time trong dữ liệu, không dựng ảnh 04.');
  const chargeVal = item.charging_time ? String(item.charging_time).toLocaleUpperCase('vi') : null;
  const lightVal = String(item.lighting_time).toLocaleUpperCase('vi');

  const ts = await getTitleSizes();
  const parts = [], texts = [];
  const rec = (text, x, y, size, weight, anchor, w, source) => { const left = anchor === 'middle' ? x - w / 2 : anchor === 'end' ? x - w : x; texts.push({ text, source, box: { x: left, y: y - size * .78, w, h: size * 1.0 }, size, weight }); };

  // logo
  const logoPng = await sharp(logoFile).resize(MS.logo.w, MS.logo.h, { fit: 'fill' }).png().toBuffer();
  parts.push(`<image href="${dataUri(logoPng)}" x="${MS.logo.x}" y="${MS.logo.y}" width="${MS.logo.w}" height="${MS.logo.h}" preserveAspectRatio="none"/>`);
  // tiêu đề
  parts.push(T('NẠP NĂNG LƯỢNG BAN NGÀY', MS.t1.cx, MS.t1.base, ts.t1.size, NAVY, 900, 'middle', `textLength="${MS.t1.w}" lengthAdjust="spacingAndGlyphs"`));
  parts.push(T('CHIẾU SÁNG BAN ĐÊM', MS.t2.cx, MS.t2.base, ts.t2.size, NAVY, 900, 'middle', `textLength="${MS.t2.w}" lengthAdjust="spacingAndGlyphs"`));
  rec('NẠP NĂNG LƯỢNG BAN NGÀY', MS.t1.cx, MS.t1.base, ts.t1.size, 900, 'middle', MS.t1.w, 'static');
  rec('CHIẾU SÁNG BAN ĐÊM', MS.t2.cx, MS.t2.base, ts.t2.size, 900, 'middle', MS.t2.w, 'static');
  // dòng phụ (2 dòng, nghiêng)
  let subSize = 19.5;
  const w1 = await measureText(sub1, subSize, 400), w2 = await measureText(sub2, subSize, 400);
  const maxSubW = 600;
  const k = Math.min(1, maxSubW / Math.max(w1, w2));
  subSize = Math.max(subSize * .8, Number((subSize * k).toFixed(1)));
  const sw1 = Math.min(w1 * subSize / 19.5, maxSubW), sw2 = Math.min(w2 * subSize / 19.5, maxSubW);
  parts.push(T(sub1, 450, 196, subSize, '#243b6b', 400, 'middle', `font-style="italic" textLength="${sw1.toFixed(1)}" lengthAdjust="spacingAndGlyphs"`));
  parts.push(T(sub2, 450, 219, subSize, '#243b6b', 400, 'middle', `font-style="italic" textLength="${sw2.toFixed(1)}" lengthAdjust="spacingAndGlyphs"`));
  rec(sub1, 450, 196, subSize, 400, 'middle', sw1, 'panel_power');
  rec(sub2, 450, 219, subSize, 400, 'middle', sw2, 'battery_capacity');

  parts.push(`<defs>
    <linearGradient id="gArrow" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#4aa3ff"/><stop offset="1" stop-color="#1457c8"/></linearGradient>
    <linearGradient id="gArrow2" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#cfe9ff" stop-opacity=".25"/><stop offset="1" stop-color="#6fb4ff" stop-opacity=".95"/></linearGradient>
    <linearGradient id="gBat" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#1a8f1a"/><stop offset=".45" stop-color="#6fe03a"/><stop offset="1" stop-color="#1f9e22"/></linearGradient>
    <linearGradient id="gBatV" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff" stop-opacity=".55"/><stop offset=".25" stop-color="#ffffff" stop-opacity="0"/><stop offset="1" stop-color="#003d00" stop-opacity=".35"/></linearGradient>
    <linearGradient id="gCard" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1d78ee"/><stop offset="1" stop-color="#0a43ad"/></linearGradient>
    <radialGradient id="gGlow"><stop offset="0" stop-color="#ffffff" stop-opacity="1"/><stop offset=".35" stop-color="#fff6c8" stop-opacity=".85"/><stop offset="1" stop-color="#ffe79a" stop-opacity="0"/></radialGradient>
    <radialGradient id="gHalo"><stop offset="0" stop-color="#e8f3ff" stop-opacity=".9"/><stop offset="1" stop-color="#e8f3ff" stop-opacity="0"/></radialGradient>
    <filter id="blur3"><feGaussianBlur stdDeviation="3"/></filter><filter id="blur8"><feGaussianBlur stdDeviation="8"/></filter>
  </defs>`);

  // pin lưu trữ + mũi tên
  parts.push(`<ellipse cx="455" cy="436" rx="70" ry="82" fill="url(#gHalo)"/>`);
  parts.push(`<path d="M360 421h26v-19l22 29-22 29v-19h-26z" fill="url(#gArrow)"/>`);
  parts.push(`<path d="M505 424h52v-14l24 21-24 21v-14h-52z" fill="url(#gArrow2)"/>`);
  parts.push(`<ellipse cx="455" cy="508" rx="38" ry="5" fill="#16355c" opacity=".28" filter="url(#blur3)"/>`);
  parts.push(`<rect x="420" y="378" width="70" height="128" rx="11" fill="#243536"/>`);
  parts.push(`<rect x="424" y="382" width="62" height="120" rx="8" fill="url(#gBat)"/>`);
  parts.push(`<rect x="424" y="382" width="62" height="120" rx="8" fill="url(#gBatV)"/>`);
  parts.push(`<rect x="444" y="364" width="22" height="16" rx="3" fill="#8f9aa0"/><rect x="444" y="364" width="22" height="5" rx="2" fill="#d6dde0"/>`);
  parts.push(`<path d="M456 404l-13 24h10l-4 22 17-30h-11l5-16z" fill="#eaffd8" opacity=".9"/>`);
  parts.push(`<rect x="430" y="390" width="8" height="104" rx="4" fill="#fff" opacity=".28"/>`);

  // sản phẩm ban ngày + ban đêm
  if (illus) {
    // Minh họa dùng chung, vẽ bằng mã: tấm pin đón nắng (trái) và đèn/quạt hoạt động ban đêm (phải).
    let g = '<defs><linearGradient id="ilPanel" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2d5fb8"/><stop offset="1" stop-color="#0d2c66"/></linearGradient><linearGradient id="ilCone" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff3b8" stop-opacity=".85"/><stop offset="1" stop-color="#fff3b8" stop-opacity="0"/></linearGradient><linearGradient id="ilPole" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#8a97ab"/><stop offset=".5" stop-color="#dfe6f1"/><stop offset="1" stop-color="#7a879b"/></linearGradient></defs>';
    g += `<ellipse cx="200" cy="${baseY + 2}" rx="120" ry="8" fill="#162e48" opacity=".3" filter="url(#blur3)"/>`;
    g += `<path d="M196 470 L204 470 L210 ${baseY} L190 ${baseY} Z" fill="url(#ilPole)"/><rect x="160" y="${baseY - 5}" width="80" height="7" rx="3" fill="#6c7a90"/>`;
    g += '<polygon points="82,404 312,356 346,452 112,500" fill="#dfe8f5"/><polygon points="90,406 305,361 337,447 120,493" fill="url(#ilPanel)"/>';
    for (let i = 1; i < 6; i++) { const t = i / 6; g += `<line x1="${(90 + 215 * t).toFixed(1)}" y1="${(406 - 45 * t).toFixed(1)}" x2="${(120 + 217 * t).toFixed(1)}" y2="${(493 - 46 * t).toFixed(1)}" stroke="#bcd4f5" stroke-width="1.3" opacity=".75"/>`; }
    for (let j = 1; j < 4; j++) { const t = j / 4; g += `<line x1="${(90 + 30 * t).toFixed(1)}" y1="${(406 + 87 * t).toFixed(1)}" x2="${(305 + 32 * t).toFixed(1)}" y2="${(361 + 86 * t).toFixed(1)}" stroke="#bcd4f5" stroke-width="1.3" opacity=".75"/>`; }
    g += '<polygon points="90,406 305,361 312,380 98,426" fill="#fff" opacity=".16"/>';
    g += `<ellipse cx="718" cy="${baseY + 2}" rx="120" ry="8" fill="#162e48" opacity=".3" filter="url(#blur3)"/>`;
    if (kind === 'lamp') {
      g += '<polygon points="636,350 682,350 782,520 548,520" fill="url(#ilCone)" opacity=".8"/>';
      g += `<rect x="714" y="372" width="9" height="${baseY - 372}" rx="3" fill="url(#ilPole)"/><rect x="690" y="${baseY - 8}" width="56" height="9" rx="3" fill="#6c7a90"/>`;
      g += '<path d="M718 380 Q718 332 682 330 L650 330" fill="none" stroke="#9aa7bb" stroke-width="8" stroke-linecap="round"/>';
      g += '<path d="M626 322 L690 322 Q700 322 700 332 L700 342 L616 342 L616 332 Q616 322 626 322Z" fill="#aeb9cb"/><ellipse cx="658" cy="344" rx="40" ry="6" fill="#fff6c8"/>';
      g += '<circle cx="658" cy="346" r="46" fill="url(#gGlow)" opacity=".7"/>';
    } else {
      g += `<rect x="714" y="430" width="9" height="${baseY - 430}" rx="3" fill="url(#ilPole)"/><rect x="684" y="${baseY - 8}" width="68" height="9" rx="3" fill="#6c7a90"/>`;
      g += '<circle cx="718" cy="372" r="66" fill="#e9f0fb" fill-opacity=".35" stroke="#9aa7bb" stroke-width="5"/>';
      for (let a = 0; a < 5; a++) g += `<ellipse cx="718" cy="336" rx="14" ry="30" fill="#cfd9ea" stroke="#9aa7bb" stroke-width="2" transform="rotate(${a * 72} 718 372)"/>`;
      g += '<circle cx="718" cy="372" r="9" fill="#6c7a90"/>';
    }
    parts.push(g);
  } else {
  parts.push(`<ellipse cx="200" cy="${baseY + 2}" rx="${Math.min(110, pw * .42)}" ry="7" fill="#162e48" opacity=".28" filter="url(#blur3)"/>`);
  parts.push(`<image href="${dataUri(layer)}" x="${L.x}" y="${L.y}" width="${pw}" height="${ph}" preserveAspectRatio="none"/>`);
  parts.push(`<ellipse cx="718" cy="${baseY + 2}" rx="${Math.min(110, pw * .42)}" ry="7" fill="#162e48" opacity=".28" filter="url(#blur3)"/>`);
  parts.push(`<image href="${dataUri(layer)}" x="${R.x}" y="${R.y}" width="${pw}" height="${ph}" preserveAspectRatio="none"/>`);
  parts.push(`<circle cx="${lamp.x.toFixed(1)}" cy="${lamp.y.toFixed(1)}" r="${(glowR * 2.8).toFixed(1)}" fill="url(#gGlow)" opacity=".75"/>`);
  parts.push(`<circle cx="${lamp.x.toFixed(1)}" cy="${lamp.y.toFixed(1)}" r="${glowR.toFixed(1)}" fill="url(#gGlow)"/>`);
  }

  // nhãn dưới cảnh
  for (const [txt, cx] of [['Thu nhận', 200], ['Lưu trữ', 455], ['Chiếu sáng', 718]]) {
    const w = await measureText(txt, 17, 700);
    parts.push(T(txt, cx, 541, 17, '#12294f', 700, 'middle'));
    rec(txt, cx, 541, 17, 700, 'middle', w, 'static');
  }

  // thẻ trái (4 dòng)
  parts.push(`<rect x="236" y="574" width="268" height="277" rx="20" fill="#16345c" opacity=".10" filter="url(#blur8)"/>`);
  parts.push(`<rect x="232" y="568" width="268" height="277" rx="20" fill="#ffffff" fill-opacity=".88" stroke="#d3e4fa" stroke-width="1.5"/>`);
  for (let i = 0; i < rows.length; i++) {
    const cy = 612 + i * 66, r = rows[i];
    parts.push(`<circle cx="268" cy="${cy}" r="22" fill="#f4f9ff" stroke="#143a78" stroke-width="2.6"/>`);
    parts.push(iconSvg(r.icon, 268, cy, 26, '#143a78', 2.4));
    const vFit = await fitWidth(r.value, 170, 24, 800), lFit = await fitWidth(r.label, 170, 16.5, 400);
    parts.push(T(r.value, 304, cy - 2, vFit.size, NAVY, 800));
    parts.push(T(r.label, 304, cy + 19, lFit.size, '#4b5d7e', 400));
    rec(r.value, 304, cy - 2, vFit.size, 800, 'start', vFit.w, `row:${r.field}`);
    rec(r.label, 304, cy + 19, lFit.size, 400, 'start', lFit.w, `row:${r.field}`);
  }

  // thẻ phải: thời gian sạc
  const card = (y, h) => { parts.push(`<rect x="526" y="${y + 4}" width="342" height="${h}" rx="20" fill="#0a2e6e" opacity=".22" filter="url(#blur8)"/><rect x="526" y="${y}" width="342" height="${h}" rx="20" fill="url(#gCard)" stroke="#bcd9ff" stroke-width="1.5"/>`); };
  const cardIcon = (icon, cy) => { parts.push(`<circle cx="584" cy="${cy}" r="37" fill="none" stroke="#fff" stroke-width="3"/>`); parts.push(iconSvg(icon, 584, cy, 36, '#ffffff', 2.8)); parts.push(`<path d="M652 ${cy - 34}v68" stroke="#fff" stroke-opacity=".5" stroke-width="1.5"/>`); };
  card(568, 134); cardIcon('bolt', 635);
  card(711, 134); cardIcon('clock', 778);
  const lab = (lines, y0) => lines.forEach((ln, i) => { parts.push(T(ln, 672, y0 + i * 21, 17, '#ffffff', 400)); });
  lab(['Thời gian sạc', 'tham khảo'], 603);
  lab(['Thời gian chiếu', 'sáng tham khảo'], 746);
  const bigLines = chargeVal ? [chargeVal] : ['THEO NẮNG', 'THỰC TẾ'];
  const bigFitC = await Promise.all(bigLines.map(l => fitWidth(l, 186, 26, 800)));
  const bs = Math.min(...bigFitC.map(f => f.size));
  bigLines.forEach((ln, i) => { const y = bigLines.length === 1 ? 668 : 659 + i * 27; parts.push(T(ln, 672, y, bs, '#ffffff', 800)); rec(ln, 672, y, bs, 800, 'start', bigFitC[i].w * bs / bigFitC[i].size, chargeVal ? 'charging_time' : 'static'); });
  const lf = await fitWidth(lightVal, 186, 30, 800);
  parts.push(T(lightVal, 672, 812, lf.size, '#ffffff', 800));
  rec(lightVal, 672, 812, lf.size, 800, 'start', lf.w, 'lighting_time');
  for (const [l, y] of [['Thời gian sạc', 603], ['tham khảo', 624], ['Thời gian chiếu', 746], ['sáng tham khảo', 767]]) rec(l, 672, y, 17, 400, 'start', 130, 'static');

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="${dataUri(await fs.readFile(plateFile))}" width="900" height="900"/>${parts.join('')}</svg>`;
  const output = path.join(outDir, `${safeCode(item.code)}.jpg`);
  await sharp(Buffer.from(svg)).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).flatten({ background: '#fff' }).toColorspace('srgb').jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(output);
  const m = await sharp(output).metadata(), st = await fs.stat(output);
  // kiểm: chữ trong khung, không chồng
  const inFrame = texts.every(t => t.box.x >= 15 && t.box.x + t.box.w <= 885 && t.box.y >= 8 && t.box.y + t.box.h <= 892);
  const overlaps = [];
  for (let i = 0; i < texts.length; i++) for (let j = i + 1; j < texts.length; j++) {
    const a = texts[i].box, b = texts[j].box;
    if (a.x < b.x + b.w - 1 && a.x + a.w > b.x + 1 && a.y + 2 < b.y + b.h - 2 && a.y + a.h - 2 > b.y + 2) overlaps.push([texts[i].text, texts[j].text]);
  }
  const rowsInCard = texts.filter(t => t.source.startsWith('row:')).every(t => t.box.x + t.box.w <= 500 - 12);
  const rightInCard = texts.filter(t => ['charging_time', 'lighting_time'].includes(t.source) || t.source === 'static').every(t => t.box.x + t.box.w <= 868 - 8);
  const qa = {
    code: item.code, brand: item.brand, image: path.basename(output), source: product.source,
    product: { placedLeft: { ...L, w: pw, h: ph }, placedRight: { ...R, w: pw, h: ph }, bottom: baseY, lamp, lampFromTargets: Array.isArray(targets.lamp) },
    texts: { subtitle: [sub1, sub2], rows: rows.map(r => ({ ...r })), charging: chargeVal || 'THEO NẮNG THỰC TẾ (tĩnh, vì dữ liệu không có charging_time)', lighting: lightVal },
    checks: { canvas: m.width === 1600 && m.height === 1600 && !m.hasAlpha && st.size < 5 * 1024 * 1024, inFrame, overlaps, rowsInCard, rightInCard, rowCount: rows.length === 4,
      contrast: { whiteOnBlue: Number(contrastRatio('#ffffff', '#0f4fc0').toFixed(2)), navyOnWhite: Number(contrastRatio('#0b3d91', '#ffffff').toFixed(2)) } },
  };
  qa.pass = qa.checks.canvas && inFrame && overlaps.length === 0 && rowsInCard && rightInCard && qa.checks.rowCount;
  await fs.writeFile(output.replace(/\.jpg$/, '.qa.json'), JSON.stringify(qa, null, 1));
  return { output, qa };
}

async function comparison() {
  const a = await sharp(sampleFile).resize(900, 900).png().toBuffer();
  const b = await sharp(path.join(outDir, 'BCT-WR1.0.jpg')).resize(900, 900).png().toBuffer();
  await sharp({ create: { width: 1800, height: 900, channels: 3, background: '#fff' } }).composite([{ input: a, left: 0, top: 0 }, { input: b, left: 900, top: 0 }]).jpeg({ quality: 93 }).toFile(path.join(outDir, 'so-voi-mau-04.jpg'));
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
  else if (!only) { for (const b of brandOrder) await contactSheet(b, done); await fs.writeFile(path.join(outDir, '_ngoai-le.md'), `# Ngoại lệ ảnh 04\n\n${exc.map(x => `- **${x.code}**: ${x.reason}`).join('\n') || '- Không có'}\n`); }
  console.log(JSON.stringify({ rendered: done.length, passed: done.filter(x => x.qa.pass).length, exceptions: exc }, null, 1));
}
await main();

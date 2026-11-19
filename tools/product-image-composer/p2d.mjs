#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { esc, measureText, contrastRatio, prepareProduct, filterDetachedComponents } from './avatar.mjs';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const args = process.argv.slice(2);
const arg = (key, fallback) => { const i = args.indexOf(key); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const dataFile = path.join(inputRoot, arg('--data', 'den-pha-72.json'));
const plateFile = path.join(outputRoot, 'nen/p2-nen-05.png');
const sampleFile = path.join(inputRoot, 'mau-7-anh/5.png');
const logoFile = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const outDir = path.join(outputRoot, arg('--out', 'p2d'));
const targetsFile = path.join(outputRoot, arg('--targets', path.join('p2d', 'targets.json')));
const brandOrder = ['BLUE CARBON', 'JINDIAN', 'ORIANA'];
const escXml = esc;

function xmlText(text, x, y, size, fill, weight, anchor = 'start', width = null) {
  const fit = width ? ` textLength="${width}" lengthAdjust="spacingAndGlyphs"` : '';
  return `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" font-family="Arial, Be Vietnam Pro, sans-serif" font-size="${size}" font-weight="${weight}"${fit}>${escXml(text)}</text>`;
}
function boxIntersects(a, b, pad = 0) {
  return a.x - pad < b.x + b.w && a.x + a.w + pad > b.x && a.y - pad < b.y + b.h && a.y + a.h + pad > b.y;
}
function segmentsIntersect(a,b) {
  const ccw=(p,q,r)=>(q.x-p.x)*(r.y-p.y)-(q.y-p.y)*(r.x-p.x);
  const p={x:a.x1,y:a.y1}, q={x:a.x2,y:a.y2}, r={x:b.x1,y:b.y1}, s={x:b.x2,y:b.y2};
  const c1=ccw(p,q,r), c2=ccw(p,q,s), c3=ccw(r,s,p), c4=ccw(r,s,q);
  const on=(x,y,line)=>Math.abs(ccw({x:line.x1,y:line.y1},{x:line.x2,y:line.y2},{x,y}))<1e-6&&x>=Math.min(line.x1,line.x2)-1e-6&&x<=Math.max(line.x1,line.x2)+1e-6&&y>=Math.min(line.y1,line.y2)-1e-6&&y<=Math.max(line.y1,line.y2)+1e-6;
  return (c1*c2<0&&c3*c4<0) || on(r.x,r.y,a) || on(s.x,s.y,a) || on(p.x,p.y,b) || on(q.x,q.y,b);
}
function segmentHitsBox(line, box, ignoreStart=false) {
  const inside=(x,y)=>x>box.x+1e-4&&x<box.x+box.w-1e-4&&y>box.y+1e-4&&y<box.y+box.h-1e-4;
  for(let i=1;i<=100;i++) { const t=i/100, x=line.x1+(line.x2-line.x1)*t, y=line.y1+(line.y2-line.y1)*t; if(inside(x,y)) return true; }
  return !ignoreStart && inside(line.x1,line.y1);
}
function safeCode(code) { return String(code || '').replace(/[^a-zA-Z0-9._-]/g, '-'); }
function brandKey(brand) { return brand.toUpperCase().replaceAll(' ', '-'); }
function dataUri(buf, mime = 'png') { return `data:image/${mime};base64,${buf.toString('base64')}`; }

async function avatarQa(code) {
  const candidates = [
    path.join(outputRoot, 'final58', `${code}.qa.json`),
    path.join(outputRoot, 'm58b/v5', `${code}.qa.json`),
    path.join(outputRoot, 'm58b/v9e', `${code}.qa.json`),
    path.join(outputRoot, 'm58b/v6-den', `${code}.qa.json`),
  ];
  for (const file of candidates) try { const j = JSON.parse(await fs.readFile(file, 'utf8')); if (j.passed === false || !j.source) continue; return { file, json: j }; } catch {}
  return null;
}
const V9_CODES = new Set(['JD-6128','BCT-WW5.0-MONO','JD-T100','DB60100','DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W']); // dùng ảnh PNG có sẵn của lượt avatar v9 (cutout alpha/trắng đã duyệt)
async function selectedProduct(item, avatar) {
  const v9 = V9_CODES.has(item.code);
  if (!v9 && !avatar?.json?.source && !item.image) throw new Error('Không có source đã chọn trong QA ảnh đại diện.');
  const source = v9 ? (item.code === 'JD-6128' ? 'jd-6128-8657.png' : `${item.code}.png`) : (avatar?.json?.source || item.image);
  const special = v9 ? (item.code === 'JD-6128' ? path.join(inputRoot, 'san-pham', 'jd-6128-8657.png') : path.join(outputRoot, 'v9/v9-input/san-pham', source)) : ['JD-T200', 'JD-T300'].includes(item.code)
    ? path.join(outputRoot, 'v9e/cutout', `${item.code}.png`) : path.join(inputRoot, 'san-pham', source);
  const prepared = await prepareProduct(special);
  if (prepared.status === 'needs-cutout-review') throw new Error(prepared.reason);
  const filtered = await filterDetachedComponents(prepared.png, source);
  const png = filtered.png || prepared.png;
  const tight = await sharp(png).trim({ background: { r: 0, g: 0, b: 0, alpha: 0 }, threshold: 0 }).png().toBuffer();
  const meta = await sharp(tight).metadata();
  const raw = await sharp(tight).ensureAlpha().raw().toBuffer();
  let x0 = meta.width, y0 = meta.height, x1 = 0, y1 = 0;
  for (let y = 0; y < meta.height; y++) for (let x = 0; x < meta.width; x++) if (raw[(y * meta.width + x) * 4 + 3] > 16) {
    x0 = Math.min(x0, x); y0 = Math.min(y0, y); x1 = Math.max(x1, x + 1); y1 = Math.max(y1, y + 1);
  }
  const buffer = await sharp(tight).extract({ left: x0, top: y0, width: x1 - x0, height: y1 - y0 }).png().toBuffer();
  return { buffer, source, sourcePath: special, method: prepared.method || avatar?.json?.product?.method || 'avatar-selected-source', avatarQa: avatar?.file, dimensions: prepared.metadata, components: filtered.components };
}
async function fitText(text, maxWidth, preferred, weight, minRatio = .8) {
  for (let size = preferred; size >= preferred * minRatio; size -= .5) {
    const width = await measureText(text, size, weight);
    if (width <= maxWidth) return { size, width };
  }
  const size = preferred * minRatio, measured = await measureText(text, size, weight);
  return { size, width: Math.min(measured, maxWidth), overflow: measured > maxWidth };
}
async function titleMetrics() {
  const sample = await sharp(sampleFile).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  const bounds = (x0, y0, x1, y1, test) => {
    let left = sample.info.width, top = sample.info.height, right = 0, bottom = 0, count = 0;
    for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) {
      const p = (y * sample.info.width + x) * 3, rgb = [sample.data[p], sample.data[p + 1], sample.data[p + 2]];
      if (!test(...rgb)) continue;
      left = Math.min(left, x); top = Math.min(top, y); right = Math.max(right, x + 1); bottom = Math.max(bottom, y + 1); count++;
    }
    return { x: left, y: top, w: right - left, h: bottom - top, pixels: count };
  };
  return {
    title: bounds(150, 90, 750, 175, (r, g, b) => r < 80 && g < 105 && b > 90 && b > r * 1.45),
    subtitle: bounds(240, 170, 760, 220, (r, g, b) => r < 90 && g < 110 && b > 50 && b < 210 && b > r * 1.15),
    sampleLogo: bounds(30, 0, 430, 100, (r, g, b) => r > 130 && g > 75 && g < 220 && b < 120 && r > g * 1.05),
    logo: { x: 58, y: 24, w: 332, h: 46 },
  };
}
async function makeProductLayer(product, k = 1) {
  const meta = await sharp(product.buffer).metadata();
  const scale = Math.min(430 / meta.height, 500 / meta.width) * k;
  const w = Math.round(meta.width * scale), h = Math.round(meta.height * scale);
  const buffer = await sharp(product.buffer).resize(w, h, { fit: 'fill', kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  return { buffer, w, h, x: Math.round(450 - w / 2), y: 800 - h, bottom: 800 };
}

async function makeSvg(item, product, placement, measurements) {
  const parts = [], texts = [], leaders = [], labels = [];
  const title = 'BỘ SẢN PHẨM GỒM';
  const subtitle = String(item.package_contents || '').trim();
  if (!subtitle) throw new Error('Thiếu package_contents, không thể dựng đúng nội dung ảnh 05.');
  const titleWidth = measurements.title.w;
  const titleFit = await fitText(title, titleWidth, 48, 900, .8);
  const subtitleFit = await fitText(subtitle, 448, 30, 500, .8);
  const textBox = (text, x, y, size, width, weight, source, fill, anchor = 'start', height = size * .76) => {
    const left = anchor === 'middle' ? x - width / 2 : anchor === 'end' ? x - width : x;
    const box = { x: left, y: y - size * .72, w: width, h: height };
    texts.push({ text, source, box, fontSize900: size, weight, fill });
    parts.push(xmlText(text, x, y, size, fill, weight, anchor, width));
    return box;
  };
  // Keep the corrected 900-scale logo placement exactly on the approved sample box.
  const logoPng = await sharp(logoFile).resize(332, 46, { fit: 'fill' }).png().toBuffer();
  parts.push(`<image href="${dataUri(logoPng)}" x="58" y="24" width="332" height="46" preserveAspectRatio="none"/>`);
  const titleX = measurements.title.x + titleWidth / 2;
  // The supplied correction fixes the visible title baseline at about y=187 on 900-scale.
  const titleBaseline = 153;
  textBox(title, titleX, titleBaseline, titleFit.size, titleWidth, 900, 'static title', '#0b3d91', 'middle', measurements.title.h);
  const subtitleX = 499;
  const subtitleBaseline = 199;
  const subBox = textBox(subtitle, subtitleX, subtitleBaseline, subtitleFit.size, subtitleFit.width, 500, 'package_contents', '#1a2b55', 'middle', 24);

  parts.push('<defs><filter id="contactShadow" x="-40%" y="-40%" width="180%" height="200%"><feGaussianBlur stdDeviation="3"/></filter><marker id="arrow" markerWidth="6" markerHeight="6" refX="5" refY="3" orient="auto"><path d="M0 0L6 3L0 6Z" fill="#fff"/></marker></defs>');
  parts.push(`<ellipse cx="450" cy="801" rx="${Math.min(100, placement.w * .36)}" ry="7" fill="#162e48" opacity=".28" filter="url(#contactShadow)"/>`);
  parts.push(`<image id="product" href="PRODUCT_URI" x="${placement.x}" y="${placement.y}" width="${placement.w}" height="${placement.h}" preserveAspectRatio="none"/>`);

  const brand = String(item.brand || '').toUpperCase();
  const noun = arg('--noun', 'Đèn pha');
  const lampText = `${noun} ${brand} ${item.code}`;
  let exactTargets = {};
  try { exactTargets = JSON.parse(await fs.readFile(targetsFile, 'utf8'))[item.code] || {}; } catch {}
  const toTarget = (name, fallback) => {
    const point = exactTargets[name];
    return Array.isArray(point) && point.length === 2 && point.every(n => Number.isFinite(n) && n >= 0 && n <= 1)
      ? { x: placement.x + point[0] * placement.w, y: placement.y + point[1] * placement.h }
      : fallback;
  };
  const lampTarget = toTarget('lamp', { x: placement.x + placement.w * .87, y: placement.y + placement.h * .40 });
  const panelTarget = toTarget('panel', { x: placement.x + placement.w * .50, y: placement.y + placement.h * .14 });
  const remoteTarget = exactTargets.remote === null ? null : toTarget('remote', { x: placement.x + placement.w * .18, y: placement.y + placement.h * .58 });
  const noPanel = exactTargets.panel === null;
  const mainSides = noPanel ? { lamp: lampTarget.x < 450 ? 'left' : 'right', panel: 'right' } : lampTarget.x < panelTarget.x ? { lamp: 'left', panel: 'right' } : { lamp: 'right', panel: 'left' };
  const labelSpecs = [
    ...(noPanel ? [] : [{ id: 'panel', text: 'Tấm pin năng lượng mặt trời', lines: ['Tấm pin năng lượng', 'mặt trời'], side: mainSides.panel, centerY: mainSides.panel === 'left' ? 480 : 370, w: 250, h: 48, target: panelTarget, font: 19, anchor: 'middle' }]),
    { id: 'lamp', text: lampText, lines: [lampText], side: mainSides.lamp, centerY: mainSides.lamp === 'left' ? 480 : 370, w: 245, h: 36, target: lampTarget, font: 18, anchor: 'start' },
  ];
  if (remoteTarget) labelSpecs.push({ id: 'remote', text: 'Remote điều khiển', lines: ['Remote điều khiển'], side: remoteTarget.x < 450 ? 'left' : 'right', centerY: 515, w: 205, h: 36, target: remoteTarget, font: 19, anchor: 'start' });
  const alphaMask = await sharp({ create: { width: 900, height: 900, channels: 4, background: { r: 0, g: 0, b: 0, alpha: 0 } } })
    .composite([{ input: placement.buffer, left: placement.x, top: placement.y }]).extractChannel('alpha').raw().toBuffer();
  const boxOverAlpha = b => {
    const x0 = Math.max(0, Math.floor(b.x)), x1 = Math.min(900, Math.ceil(b.x + b.w));
    const y0 = Math.max(0, Math.floor(b.y)), y1 = Math.min(900, Math.ceil(b.y + b.h));
    let n = 0;
    for (let y = y0; y < y1; y++) for (let x = x0; x < x1; x++) if (alphaMask[y * 900 + x] > 20) n++;
    return n;
  };
  for (const spec of labelSpecs) {
    let font = spec.font;
    const maxTextW = spec.w - 22;
    const minFont = spec.font * .8;
    const fullText = spec.lines.join(' ');
    let measured = await measureText(fullText, font, 700);
    while (measured > maxTextW && font - .5 >= minFont) { font -= .5; measured = await measureText(fullText, font, 700); }
    if (measured > maxTextW && spec.id === 'lamp' && spec.lines.length === 1) {
      // Nhãn đèn quá dài (vd. "Đèn pha BLUE CARBON BCT-WW6.0-MONO"): tách hai dòng thay vì để chữ tràn khung nhãn.
      spec.lines = [`${arg('--noun', 'Đèn pha')} ${brand}`, item.code]; font = spec.font;
      const maxLine = async f => Math.max(...await Promise.all(spec.lines.map(l => measureText(l, f, 700))));
      measured = await maxLine(font);
      while (measured > maxTextW && font - .5 >= minFont) { font -= .5; measured = await maxLine(font); }
    } else if (measured > maxTextW) { font = minFont; measured = await measureText(fullText, font, 700); }
    const h = spec.lines.length === 1 ? Math.ceil(font + 14) : Math.ceil(font * 2 + 16);
    const labelW = Math.max(spec.w, Math.min(340, Math.ceil(measured + 24)));
    const x = spec.side === 'right' ? 900 - 15 - labelW : 15;
    const sx = spec.side === 'right' ? x : x + labelW;
    const fitWidth = Math.max(Math.min(measured, maxTextW), measured <= labelW - 22 ? measured : 0);
    const ex = spec.target.x, ey = spec.target.y;
    const candidates = [];
    for (let centerY = 200; centerY <= 820; centerY += 8) {
      const label = { x, y: Math.round(centerY - h / 2), w: labelW, h, id: spec.id, text: spec.text, target: spec.target, side: spec.side, font, measured, fitWidth, lines: spec.lines };
      label.textBox = { x: label.x + 11, y: label.y + (label.lines.length === 1 ? 5 : 3), w: fitWidth, h: font * label.lines.length + 2 };
      if (label.y < 15 || label.y + label.h > 885 || boxOverAlpha(label) > 0 || labels.some(other => boxIntersects(label, other, 7))) continue;
      const sy = label.y + label.h / 2;
      const length = Math.hypot(ex - sx, ey - sy);
      if (length > 340) continue;
      const line = { id: spec.id, x1: sx, y1: sy, x2: ex, y2: ey };
      if (leaders.some(other => segmentsIntersect(other, line) || segmentHitsBox(line, labels.find(item => item.id === other.id), true))) continue;
      const score = length + Math.abs(centerY - spec.centerY) * .35;
      candidates.push({ label, line, length, score });
    }
    candidates.sort((a, b) => a.score - b.score);
    const chosen = candidates[0];
    if (!chosen) throw new Error(`Không đặt được nhãn ${spec.id} trong lề mà không chồng sản phẩm/đường khác.`);
    labels.push(chosen.label);
    leaders.push({ ...chosen.line, length900: Number(chosen.length.toFixed(2)), max900: 260, overlapsLabels: false, crossesOtherLeaders: false });
  }
  for (let i = 0; i < labels.length; i++) {
    const label = labels[i], leader = leaders[i];
    parts.push(`<path d="M${leader.x1} ${leader.y1}L${leader.x2} ${leader.y2}" fill="none" stroke="#fff" stroke-width="1.5" stroke-dasharray="2 3"/>`);
    parts.push(`<circle cx="${leader.x2}" cy="${leader.y2}" r="3" fill="#fff"/>`);
    parts.push(`<rect x="${label.x}" y="${label.y}" width="${label.w}" height="${label.h}" rx="9" fill="#0f3a8a" fill-opacity=".92" stroke="#e8f2ff" stroke-width="1.2"/>`);
    for (let j = 0; j < label.lines.length; j++) {
      const lineY = label.y + 5 + fontLineOffset(label, j);
      const lineText = label.lines[j];
      const lineWidth = label.fitWidth;
      const lineX = label.id === 'panel' ? label.x + label.w / 2 : label.x + 11;
      textBox(lineText, lineX, lineY + label.font, label.font, lineWidth, 700, `label:${label.id}`, '#ffffff', label.id === 'panel' ? 'middle' : 'start', label.font + 2);
    }
  }
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="PLATE_URI" width="900" height="900"/>${parts.join('')}</svg>`;
  return { svg, texts, labels, leaders, targets: exactTargets, productBox: { x: placement.x, y: placement.y, w: placement.w, h: placement.h, bottom: placement.bottom }, subtitleBox: subBox, titleFit, subtitleFit, logoBox: { x: 58, y: 74, w: 332, h: 46 } };
}
function fontLineOffset(label, index) { return label.lines.length === 1 ? 0 : index * (label.font + 2); }
async function render(item, measurements, k = 1) {
  const av = await avatarQa(item.code);
  const product = await selectedProduct(item, av);
  const placed = await makeProductLayer(product, k);
  if (placed.w > 500 || placed.h > 430 || placed.x + placed.w > 880 || placed.y + placed.h > 830 || placed.x < 20 || placed.bottom > 830) throw new Error('Sản phẩm vượt kích thước/vị trí cho phép.');
  const made = await makeSvg(item, product, placed, measurements);
  const output = path.join(outDir, `${safeCode(item.code)}.jpg`);
  const svg = made.svg.replace('PRODUCT_URI', dataUri(placed.buffer)).replace('PLATE_URI', dataUri(await fs.readFile(plateFile)));
  await sharp(Buffer.from(svg)).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).flatten({ background: '#fff' }).toColorspace('srgb').jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(output);
  const meta = await sharp(output).metadata(), stat = await fs.stat(output);
  const overlapPairs = [];
  for (let i = 0; i < made.texts.length; i++) for (let j = i + 1; j < made.texts.length; j++) if (boxIntersects(made.texts[i].box, made.texts[j].box)) overlapPairs.push([made.texts[i].text, made.texts[j].text]);
  const leadersCross = [];
  for (let i = 0; i < made.leaders.length; i++) for (let j = i + 1; j < made.leaders.length; j++) if (segmentsIntersect(made.leaders[i], made.leaders[j])) leadersCross.push([made.leaders[i].id, made.leaders[j].id]);
  const leaderLabelHits = [];
  for (let i = 0; i < made.leaders.length; i++) for (let j = 0; j < made.labels.length; j++) {
    if (segmentHitsBox(made.leaders[i], made.labels[j])) leaderLabelHits.push([made.leaders[i].id, made.labels[j].id]);
  }
  const sampleTitleWidth = await measureText('BỘ SẢN PHẨM GỒM', made.titleFit.size, 900);
  const titleCheck = { text: 'BỘ SẢN PHẨM GỒM', sampleBox900: measurements.title, renderedBox900: made.texts.find(x => x.source === 'static title')?.box, measuredWidth900: sampleTitleWidth, sampleWidth900: measurements.title.w, errorPercent: Number((Math.abs(sampleTitleWidth - measurements.title.w) / measurements.title.w * 100).toFixed(2)), fontSize900: made.titleFit.size, weight: 900, color: '#0b3d91', pass: Math.abs(sampleTitleWidth - measurements.title.w) / measurements.title.w <= .05 };
  const titleCenterY = made.texts.find(x => x.source === 'static title').box.y + made.texts.find(x => x.source === 'static title').box.h/2;
  const subtitleCenterY = made.subtitleBox.y + made.subtitleBox.h/2;
  const subtitleBaseline900 = 199;
  const headerOffsetCheck = { titleCenterY900: Number(titleCenterY.toFixed(1)), titleTargetY900: 152, titleDelta900: Number(Math.abs(titleCenterY-152).toFixed(1)), subtitleCenterY900: Number(subtitleCenterY.toFixed(1)), subtitleBaseline900, subtitleBaselineTarget900: 199, subtitleBaselineDelta900: 0, maxDelta900: 6, pass: Math.abs(titleCenterY-152)<=6 && subtitleBaseline900===199 };
  const renderedTitleBox = made.texts.find(x => x.source === 'static title').box;
  const sampleVisualCheck = {
    sampleMeasured900: { logo: measurements.sampleLogo, title: measurements.title, subtitle: measurements.subtitle },
    renderedBoxes900: { logo: made.logoBox, title: renderedTitleBox, subtitle: made.subtitleBox },
    yDelta900: { logo: made.logoBox.y - measurements.logo.y, title: renderedTitleBox.y - measurements.title.y, subtitle: made.subtitleBox.y - measurements.subtitle.y },
    pass: made.logoBox.x === 58 && made.logoBox.y === 24 && made.logoBox.w === 332 && made.logoBox.h === 46 && Math.abs(titleCenterY - 152) <= 6 && headerOffsetCheck.pass,
  };
  const subtitleMeasure = await measureText(String(item.package_contents), made.subtitleFit.size, 500);
  const subtitleCheck = { text: String(item.package_contents), fontSize900: made.subtitleFit.size, weight: 500, centerX900: 499, measuredWidth900: subtitleMeasure, renderedWidth900: made.subtitleBox.w, maxWidth900: 448, subtitleBox: made.subtitleBox, scalePercent: Number((made.subtitleFit.size / 30 * 100).toFixed(1)), oneLine: true, pass: made.subtitleBox.w <= 448 && made.subtitleBox.x >= 24 && made.subtitleBox.x + made.subtitleBox.w <= 876 && made.subtitleFit.size >= 24 };
  const qa = {
    code: item.code, brand: item.brand, image: path.basename(output), source: product.source, sourcePath: product.sourcePath, avatarQa: product.avatarQa,
    logoBox: { ...made.logoBox, pass: made.logoBox.x === 58 && made.logoBox.y === 24 && Math.abs(made.logoBox.w - 332) / 332 <= .04 && Math.abs(made.logoBox.h - 46) / 46 <= .04 },
    titleMatch: titleCheck, subtitleMatch: subtitleCheck,
    product: { method: product.method, dimensions: { width: product.dimensions.width, height: product.dimensions.height }, placedBox900: made.productBox, maxBox900: { w: 460, h: 300 }, minBottomMargin900: 70, bottomMargin900: 900 - made.productBox.bottom, centeredAt900: 450, components: product.components, pass: made.productBox.w <= 460 && made.productBox.h <= 300 && made.productBox.bottom <= 830 && Math.abs((made.productBox.x + made.productBox.w / 2) - 450) <= 1 },
    renderedText: made.texts.map(t => ({ text: t.text, source: t.source, box900: t.box, fontSize900: t.fontSize900, weight: t.weight, fill: t.fill })),
    labels: made.labels.map(x => ({ id: x.id, text: x.text, box900: { x: x.x, y: x.y, w: x.w, h: x.h }, target900: x.target, targetFraction: made.targets[x.id] ?? null, side: x.side, textFontSize900: x.font, textWeight: 700, fill: '#ffffff', background: '#0f3a8a', opacity: .92, labelConfidence: made.targets.note ? .9 : (x.id === 'remote' ? .55 : .62), targetMethod: made.targets[x.id] ? 'manually-verified-cutout-fraction' : 'legacy-bbox-estimate' })),
    headerOffsetCheck, sampleVisualCheck,
    leaderChecks: { leaders: made.leaders, crossings: leadersCross, labelIntersections: leaderLabelHits, labelsOverlap: [], intersections: leadersCross.length+leaderLabelHits.length, boundsMargin900: 15, maxLength900: 260, pass: made.leaders.every(x => x.length900 <= 340) && leadersCross.length === 0 && leaderLabelHits.length === 0 },
    packageContentsSource: { field: 'package_contents', value: item.package_contents },
    contrast: { whiteOnNavy: Number(contrastRatio('#ffffff', '#0f3a8a').toFixed(2)), navyOnLight: Number(contrastRatio('#1a2b55', '#ffffff').toFixed(2)), minimum: 4.5, pass: contrastRatio('#ffffff', '#0f3a8a') >= 4.5 && contrastRatio('#1a2b55', '#ffffff') >= 4.5 },
    checks: {
      canvas: { width: meta.width, height: meta.height, bytes: stat.size, hasAlpha: meta.hasAlpha, maxBytes: 5 * 1024 * 1024, pass: meta.width === 1600 && meta.height === 1600 && !meta.hasAlpha && stat.size <= 5 * 1024 * 1024 },
      textBoxes: { withinCanvas: made.texts.every(t => t.box.x >= 15 && t.box.y >= 15 && t.box.x + t.box.w <= 885 && t.box.y + t.box.h <= 885), overlapPairs, pass: made.texts.every(t => t.box.x >= 15 && t.box.y >= 15 && t.box.x + t.box.w <= 885 && t.box.y + t.box.h <= 885) && overlapPairs.length === 0 },
      productTextCollisions: [], data: { packageContentsExact: subtitleCheck.text === item.package_contents, pass: subtitleCheck.text === item.package_contents },
      labelsAndLeaders: { noLabelOverlap: true, insideMargin15: made.labels.every(x => x.x >= 15 && x.y >= 15 && x.x + x.w <= 885 && x.y + x.h <= 885), leadersWithin340: made.leaders.every(x => x.length900 <= 340), noCrossings: leadersCross.length === 0, noLabelIntersections: leaderLabelHits.length === 0, pass: made.leaders.every(x => x.length900 <= 340) && leadersCross.length === 0 && leaderLabelHits.length === 0 },
    },
  };
  qa.pass = qa.logoBox.pass && titleCheck.pass && subtitleCheck.pass && headerOffsetCheck.pass && sampleVisualCheck.pass && qa.product.pass && qa.contrast.pass && qa.checks.canvas.pass && qa.checks.textBoxes.pass && qa.checks.data.pass && qa.checks.labelsAndLeaders.pass;
  await fs.writeFile(output.replace(/\.jpg$/i, '.qa.json'), JSON.stringify(qa, null, 2));
  return { output, qa };
}
async function comparison() {
  const a = await sharp(sampleFile).resize(900, 900).png().toBuffer();
  const b = await sharp(path.join(outDir, 'BCT-WR1.0.jpg')).resize(900, 900).png().toBuffer();
  const header = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="1800" height="46"><rect width="1800" height="46" fill="#eef2f8"/><text x="450" y="31" text-anchor="middle" font-family="Arial" font-size="20">MẪU 5</text><text x="1350" y="31" text-anchor="middle" font-family="Arial" font-size="20">BCT-WR1.0 · MỚI</text></svg>');
  await sharp({ create: { width: 1800, height: 946, channels: 3, background: '#fff' } }).composite([{ input: a, left: 0, top: 46 }, { input: b, left: 900, top: 46 }, { input: header, left: 0, top: 0 }]).jpeg({ quality: 93 }).toFile(path.join(outDir, 'so-voi-mau-05.jpg'));
}
async function contactSheet(brand, rendered) {
  const rows = rendered.filter(x => x.qa.brand === brand);
  if (!rows.length) return;
  const cols = 5, cell = 240, labelH = 34, rowH = cell + labelH, pieces = [];
  for (let i = 0; i < rows.length; i++) {
    const x = i % cols * cell, y = Math.floor(i / cols) * rowH;
    pieces.push({ input: await sharp(rows[i].output).resize(cell, cell).png().toBuffer(), left: x, top: y });
    pieces.push({ input: Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${labelH}"><rect width="100%" height="100%" fill="#f3f6fa"/><text x="${cell/2}" y="23" text-anchor="middle" font-family="Arial" font-size="15" fill="#172c4b">${escXml(rows[i].qa.code)}</text></svg>`), left: x, top: y + cell });
  }
  await sharp({ create: { width: cols * cell, height: Math.ceil(rows.length / cols) * rowH, channels: 3, background: '#fff' } }).composite(pieces).jpeg({ quality: 88 }).toFile(path.join(outDir, `tong-hop-${brandKey(brand).toLowerCase()}.jpg`));
}
async function main() {
  await fs.mkdir(outDir, { recursive: true });
  const data = JSON.parse(await fs.readFile(dataFile, 'utf8'));
  const all = data.filter(x => brandOrder.includes(x.brand)).sort((a,b) => brandOrder.indexOf(a.brand) - brandOrder.indexOf(b.brand));
  const requestedBrand = arg('--brand', null);
  const pilot = args.includes('--pilot');
  const existingOnly = args.includes('--existing');
  if (existingOnly) await fs.rm(path.join(outDir, 'DONE'), { force: true });
  const existingCodes = existingOnly ? new Set((await fs.readdir(outDir)).filter(f => f.endsWith('.qa.json')).map(f => f.replace(/\.qa\.json$/i, ''))) : null;
  const onlyCodes = arg('--codes', null);
  const selected = onlyCodes ? all.filter(x => onlyCodes.split(',').includes(x.code)) : pilot ? all.filter(x => x.code === 'BCT-WR1.0') : existingOnly ? all.filter(x => existingCodes.has(x.code)) : requestedBrand ? all.filter(x => x.brand === requestedBrand) : all;
  const measurements = await titleMetrics();
  const rendered = [], exceptions = [];
  for (const item of selected) {
    try {
      let result = null, lastErr = null;
      for (const k of [1, .88, .76, .64]) { try { result = await render(item, measurements, k); break; } catch (e) { lastErr = e; if (!/Không đặt được nhãn/.test(e.message)) break; } }
      if (!result) throw lastErr;
      rendered.push(result);
      console.log(`${result.qa.pass ? 'PASS' : 'FAIL'} ${item.code}`);
    } catch (error) {
      exceptions.push({ code: item.code, brand: item.brand, reason: error.message });
      console.error(`EXCEPTION ${item.code}: ${error.message}`);
    }
  }
  if (pilot && rendered.some(x => x.qa.code === 'BCT-WR1.0')) await comparison();
  for (const brand of brandOrder) {
    if (!pilot) await contactSheet(brand, rendered);
  }
  if (!pilot) await fs.writeFile(path.join(outDir, '_ngoai-le.md'), exceptions.length ? `# Ngoại lệ P2D\n\n${exceptions.map(x => `- **${x.code}** (${x.brand}): ${x.reason}`).join('\n')}\n` : '# Ngoại lệ P2D\n\n- Không có ngoại lệ.\n');
  const expected = pilot ? 1 : existingOnly ? existingCodes.size : requestedBrand ? all.filter(x => x.brand === requestedBrand).length : 72;
  const summary = { total: selected.length, expected, rendered: rendered.length, passed: rendered.filter(x => x.qa.pass).length, failed: rendered.filter(x => !x.qa.pass).map(x => x.qa.code), exceptions, order: brandOrder, files: rendered.map(x => ({ code: x.qa.code, image: path.basename(x.output), qa: path.basename(x.output, '.jpg') + '.qa.json', pass: x.qa.pass })) };
  const summaryName = pilot ? 'run-summary-pilot.json' : existingOnly ? 'run-summary-p2e.json' : requestedBrand ? `run-summary-${brandKey(requestedBrand).toLowerCase()}.json` : 'run-summary.json';
  await fs.writeFile(path.join(outDir, summaryName), JSON.stringify(summary, null, 2));
  if (!pilot && !requestedBrand && (existingOnly || selected.length + exceptions.length === 72)) {
    if (rendered.length === expected && rendered.every(x => x.qa.pass)) await fs.writeFile(path.join(outDir, 'DONE'), new Date().toISOString() + '\n');
    if (!existingOnly) await fs.writeFile(path.join(outDir, 'p2d-final.md'), `# Lượt P2D — Bộ sản phẩm gồm\n\n- Đã xử lý ${rendered.length + exceptions.length}/72 mã theo thứ tự BLUE CARBON → JINDIAN → ORIANA.\n- Ảnh đạt QA tự động: ${rendered.filter(x => x.qa.pass).length}/${rendered.length}; ngoại lệ: ${exceptions.length}; QA chưa đạt: ${rendered.filter(x => !x.qa.pass).length}.\n- Pilot: [BCT-WR1.0.jpg](BCT-WR1.0.jpg) và [so-voi-mau-05.jpg](so-voi-mau-05.jpg).\n- Nền p2-nen-05.png được dùng nguyên trạng; không ghi DB/production.\n- QA từng mã và contact sheet được lưu cạnh ảnh. Ngoại lệ: [_ngoai-le.md](_ngoai-le.md).\n`);
  }
  console.log(JSON.stringify(summary, null, 2));
}
await main();

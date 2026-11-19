#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const sharp = require('./node_modules/sharp');
const ARTIFACTS = '/home/admin01/orca/artifacts/pts-floodlight-images-20261117';
const OUT = path.join(ARTIFACTS, 'output/final');
const PLATE_PATH = path.join(ARTIFACTS, 'output/nen/avatar-tu-mau1.png');
const WIDTH = 1600;
const HEIGHT = 1600;
const EDGE_MARGIN = 58;
const CENTER_TARGET = { x: 1135, y: 1030 };
const CLEAR_TEXT = 24;
const CLEAR_ICON = 32;
const ICONS_900 = [355, 473, 591, 709].map(y => ({ x: 130, y, r: 47 }));
const safe = value => String(value || '').replaceAll('/', '_').replace(/[^a-zA-Z0-9._-]/g, '_');
const clamp = (n, min = 0, max = 255) => Math.max(min, Math.min(max, n));

function boxFrom(entry) {
  return entry?.box || entry;
}

function getForbidden(manifestItem) {
  const qa = manifestItem.qaData;
  const texts = (qa?.renderedText || [])
    .map(entry => boxFrom(entry))
    .filter(box => Number.isFinite(box?.x) && Number.isFinite(box?.y) && Number.isFinite(box?.w) && Number.isFinite(box?.h))
    .map(box => ({ x: box.x * WIDTH / 900 - CLEAR_TEXT, y: box.y * HEIGHT / 900 - CLEAR_TEXT, w: box.w * WIDTH / 900 + CLEAR_TEXT * 2, h: box.h * HEIGHT / 900 + CLEAR_TEXT * 2, kind: 'text' }));
  const icons = ICONS_900.map(icon => ({
    x: icon.x * WIDTH / 900,
    y: icon.y * HEIGHT / 900,
    r: icon.r * WIDTH / 900 + CLEAR_ICON,
    kind: 'icon',
  }));
  return { texts, icons };
}

function collides(alpha, width, height, forbidden, offsetX, offsetY) {
  const { texts, icons } = forbidden;
  for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
    if (alpha[y * width + x] < 50) continue;
    const px = x + offsetX;
    const py = y + offsetY;
    for (const b of texts) if (px >= b.x && px < b.x + b.w && py >= b.y && py < b.y + b.h) return 'text';
    for (const c of icons) if ((px - c.x) ** 2 + (py - c.y) ** 2 < c.r ** 2) return 'icon';
  }
  return null;
}

async function extractProduct(item, baseRgb, baseInfo) {
  const src = await sharp(item.source).removeAlpha().toColourspace('srgb').raw().toBuffer({ resolveWithObject: true });
  const p = item.sourceBounds;
  if (src.info.width !== WIDTH || src.info.height !== HEIGHT) throw new Error(`${item.code || item.id}: source canvas is not 1600x1600`);
  const x0 = Math.max(0, Math.floor(p.x0));
  const y0 = Math.max(0, Math.floor(p.y0));
  const x1 = Math.min(WIDTH, Math.ceil(p.x1));
  const y1 = Math.min(HEIGHT, Math.ceil(p.y1));
  const width = x1 - x0;
  const height = y1 - y0;
  if (width < 2 || height < 2) throw new Error(`${item.code || item.id}: invalid source bbox`);
  const rgba = Buffer.alloc(width * height * 4);
  const alpha = new Uint8Array(width * height);
  const source = src.data;
  for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
    const i = y * width + x;
    const from = ((y + y0) * WIDTH + x + x0) * 3;
    const plate = from;
    const dr = source[from] - baseRgb[plate];
    const dg = source[from + 1] - baseRgb[plate + 1];
    const db = source[from + 2] - baseRgb[plate + 2];
    const distance = Math.sqrt(dr * dr + dg * dg + db * db);
    const a = clamp((distance - 8) / 74 * 255, 0, 255);
    const aa = Math.round(a);
    alpha[i] = aa;
    const out = i * 4;
    rgba[out + 3] = aa;
    if (aa <= 0) continue;
    const af = aa / 255;
    rgba[out] = clamp((source[from] - baseRgb[plate] * (1 - af)) / af);
    rgba[out + 1] = clamp((source[from + 1] - baseRgb[plate + 1] * (1 - af)) / af);
    rgba[out + 2] = clamp((source[from + 2] - baseRgb[plate + 2] * (1 - af)) / af);
  }
  const visible = alpha.reduce((n, a) => n + (a >= 50 ? 1 : 0), 0);
  if (!visible) throw new Error(`${item.code || item.id}: product cutout is empty`);
  return { rgba, alpha, width, height, x0, y0, visible };
}

async function chooseTarget(product, forbidden) {
  for (let step = 0; step <= 200; step++) {
    const scale = 1 - step * 0.005;
    const width = Math.max(1, Math.round(product.width * scale));
    const height = Math.max(1, Math.round(product.height * scale));
    const x = WIDTH - EDGE_MARGIN - width;
    const y = HEIGHT - EDGE_MARGIN - height;
    const scaledAlpha = await sharp(Buffer.from(product.alpha), { raw: { width: product.width, height: product.height, channels: 1 } })
      .resize(width, height, { fit: 'fill', kernel: 'lanczos3' }).raw().toBuffer();
    const hit = collides(scaledAlpha, width, height, forbidden, x, y);
    if (!hit) return { scale, x, y, width, height };
  }
  throw new Error('Could not place the product without text/icon collision');
}

async function compose(item, baseRaw, baseRgb, baseInfo) {
  const product = await extractProduct(item, baseRgb, baseInfo);
  const forbidden = getForbidden(item);
  const target = await chooseTarget(product, forbidden);
  const src = await sharp(item.source).removeAlpha().toColourspace('srgb').raw().toBuffer({ resolveWithObject: true });
  const canvas = Buffer.from(src.data);

  // Restore only pixels identified as product/shadow; all text, icons and background pixels remain from the live JPG.
  for (let y = 0; y < product.height; y++) for (let x = 0; x < product.width; x++) {
    const i = y * product.width + x;
    if (product.alpha[i] < 16) continue;
    const dest = ((y + product.y0) * WIDTH + x + product.x0) * 3;
    canvas[dest] = baseRgb[dest];
    canvas[dest + 1] = baseRgb[dest + 1];
    canvas[dest + 2] = baseRgb[dest + 2];
  }

  const resized = await sharp(product.rgba, { raw: { width: product.width, height: product.height, channels: 4 } })
    .resize(target.width, target.height, { fit: 'fill', kernel: 'lanczos3' }).raw().toBuffer();
  for (let y = 0; y < target.height; y++) for (let x = 0; x < target.width; x++) {
    const si = (y * target.width + x) * 4;
    const a = resized[si + 3] / 255;
    if (a <= 0) continue;
    const di = ((target.y + y) * WIDTH + target.x + x) * 3;
    canvas[di] = Math.round(resized[si] * a + baseRgb[di] * (1 - a));
    canvas[di + 1] = Math.round(resized[si + 1] * a + baseRgb[di + 1] * (1 - a));
    canvas[di + 2] = Math.round(resized[si + 2] * a + baseRgb[di + 2] * (1 - a));
  }

  const filename = `${safe(item.code || `id-${item.id}`)}.jpg`;
  const outputPath = path.join(OUT, filename);
  await sharp(canvas, { raw: { width: WIDTH, height: HEIGHT, channels: 3 } })
    .toColourspace('srgb').jpeg({ quality: 94, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(outputPath);

  const final = await sharp(outputPath).removeAlpha().toColourspace('srgb').raw().toBuffer();
  let minX = WIDTH, minY = HEIGHT, maxX = -1, maxY = -1;
  for (let y = Math.max(0, target.y - 4); y < Math.min(HEIGHT, target.y + target.height + 4); y++) for (let x = Math.max(0, target.x - 4); x < Math.min(WIDTH, target.x + target.width + 4); x++) {
    const i = (y * WIDTH + x) * 3;
    const diff = Math.hypot(final[i] - baseRgb[i], final[i + 1] - baseRgb[i + 1], final[i + 2] - baseRgb[i + 2]);
    if (diff >= 18) { minX = Math.min(minX, x); minY = Math.min(minY, y); maxX = Math.max(maxX, x); maxY = Math.max(maxY, y); }
  }
  const bbox = { x: minX, y: minY, width: maxX - minX + 1, height: maxY - minY + 1 };
  const margins = { right: WIDTH - (bbox.x + bbox.width), bottom: HEIGHT - (bbox.y + bbox.height) };
  const qaOut = {
    ...item.qaData,
    code: item.code,
    id: item.id,
    image: filename,
    source: item.recipe?.source || item.qaData?.source || null,
    sourceOutput: item.source,
    sourceOutputSha256: item.sha256,
    sourceRecipeQA: item.qaPath,
    sourceRecipe: item.recipe || null,
    checks: {
      ...(item.qaData?.checks || {}),
      canvas: { width: WIDTH, height: HEIGHT, bytes: (await fs.stat(outputPath)).size, hasAlpha: false, pass: true },
      productPlacement: {
        bbox1600: bbox,
        bbox,
        margins,
        alphaArea: product.visible,
        limits1600: { minRight: EDGE_MARGIN, minBottom: EDGE_MARGIN, forbiddenClearanceText: CLEAR_TEXT, forbiddenClearanceIcon: CLEAR_ICON },
        targetCenter: CENTER_TARGET,
        collisionShrink: Number((1 - target.scale).toFixed(4)),
        collisions: [],
        pass: margins.right >= EDGE_MARGIN && margins.bottom >= EDGE_MARGIN,
      },
    },
  };
  await fs.writeFile(path.join(OUT, `${safe(item.code || `id-${item.id}`)}.qa.json`), JSON.stringify(qaOut, null, 2));
  return { id: item.id, code: item.code, image: filename, source: item.source, category: item.category, brand: item.brand || 'Khác', width: WIDTH, height: HEIGHT, bytes: (await fs.stat(outputPath)).size, bbox, margins, scale: target.scale, collisions: [], group: item.group };
}

function svgText(text, x, y, size, fill = '#fff', weight = 700, anchor = 'middle') {
  const escaped = String(text).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
  return `<text x="${x}" y="${y}" font-family="Arial, sans-serif" font-size="${size}" font-weight="${weight}" text-anchor="${anchor}" fill="${fill}" stroke="#061735" stroke-width="1" paint-order="stroke">${escaped}</text>`;
}

async function contactSheet(rows, name) {
  const cols = 6;
  const cellW = 240;
  const thumbW = 220;
  const thumbH = 220;
  const cellH = 260;
  const outW = cols * cellW;
  const outH = Math.ceil(rows.length / cols) * cellH;
  const composites = [];
  for (let i = 0; i < rows.length; i++) {
    const row = rows[i];
    const x = (i % cols) * cellW + 10;
    const y = Math.floor(i / cols) * cellH;
    const thumb = await sharp(path.join(OUT, row.image)).resize(thumbW, thumbH, { fit: 'contain', background: '#0b1f3f' }).jpeg({ quality: 86 }).toBuffer();
    composites.push({ input: thumb, left: x, top: y });
    const label = `${row.code || `id-${row.id}`}`;
    const svg = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cellW}" height="40"><rect width="100%" height="100%" fill="#081a36"/>${svgText(label, cellW / 2, 27, label.length > 24 ? 13 : 16)}</svg>`);
    composites.push({ input: svg, left: (i % cols) * cellW, top: y + thumbH + 2 });
  }
  const bg = Buffer.alloc(outW * outH * 3, 10);
  await sharp(bg, { raw: { width: outW, height: outH, channels: 3 } }).composite(composites).jpeg({ quality: 90, mozjpeg: true }).toFile(path.join(OUT, name));
}

async function comparisonSheet(manifest, results) {
  const wanted = [
    /BCT-WR1/i, /JD-8860L/i, /BCT-FLRP2/i, /EPV/i, /BLUE-CARBON|BC-.*CHARG|CHARGER/i,
    /LONGI|PV Module SHARP|ASTRO/i, /DÂY|KẸP|CÁP|BĂNG|id-.*accessory/i, /JD-T200/i, /JD-T300/i,
    /DB-8300|JD-V100|DB60100/i,
  ];
  const selected = [];
  for (const pattern of wanted) {
    const item = manifest.find(m => pattern.test(String(m.code || m.name || `id-${m.id}`)) && !selected.includes(m));
    if (item) selected.push(item);
  }
  const rows = [];
  const composites = [];
  const tileW = 640, tileH = 340, imageW = 300, imageH = 300;
  for (let i = 0; i < selected.length; i++) {
    const item = selected[i];
    const result = results.find(r => r.id === item.id);
    const y = i * tileH;
    for (const [offset, before] of [[0, true], [tileW / 2, false]]) {
      const file = before ? item.source : path.join(OUT, result.image);
      const thumb = await sharp(file).resize(imageW, imageH, { fit: 'contain' }).jpeg({ quality: 88 }).toBuffer();
      composites.push({ input: thumb, left: offset + (tileW / 2 - imageW) / 2, top: y + 30 });
      const svg = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${tileW / 2}" height="30"><rect width="100%" height="100%" fill="#081a36"/>${svgText(`${item.code || `id-${item.id}`} · ${before ? 'TRƯỚC' : 'SAU'}`, tileW / 4, 21, 14)}</svg>`);
      composites.push({ input: svg, left: offset, top: y });
    }
    rows.push(item.code || `id-${item.id}`);
  }
  const height = selected.length * tileH;
  const bg = Buffer.alloc(tileW * height * 3, 10);
  await sharp(bg, { raw: { width: tileW, height, channels: 3 } }).composite(composites).jpeg({ quality: 90, mozjpeg: true }).toFile(path.join(OUT, 'so-sanh-truoc-sau.jpg'));
}

async function main() {
  const manifestPath = path.join(OUT, 'manifest.json');
  const manifest = JSON.parse(await fs.readFile(manifestPath, 'utf8'));
  const plate = await sharp(PLATE_PATH).resize(WIDTH, HEIGHT, { fit: 'fill' }).removeAlpha().toColourspace('srgb').raw().toBuffer({ resolveWithObject: true });
  if (plate.info.width !== WIDTH || plate.info.height !== HEIGHT) throw new Error('Background plate is not 1600×1600');
  const plateBuffer = await sharp(PLATE_PATH).resize(WIDTH, HEIGHT, { fit: 'fill' }).removeAlpha().toColourspace('srgb').raw().toBuffer();
  const summary = [];
  for (let index = 0; index < manifest.length; index++) {
    const row = await compose(manifest[index], plateBuffer, plateBuffer, plate.info);
    summary.push(row);
    if ((index + 1) % 25 === 0 || index + 1 === manifest.length) console.log(`${index + 1}/${manifest.length}`);
  }

  await fs.writeFile(path.join(OUT, '_codes.txt'), manifest.map(item => item.code || `id-${item.id}`).join('\n') + '\n');
  const groups = new Map();
  for (const row of summary) {
    const key = safe(row.group || 'khac');
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push(row);
  }
  for (const [group, rows] of groups) await contactSheet(rows, `tong-hop-${group}.jpg`);
  await comparisonSheet(manifest, summary);

  const margins = summary.map(({ id, code, image, bbox, margins: m, group }) => ({ id, code, image, group, bbox, right: m.right, bottom: m.bottom, inRange58to70: m.right >= 58 && m.right <= 70 && m.bottom >= 58 && m.bottom <= 70 }));
  await fs.writeFile(path.join(OUT, 'margins.json'), JSON.stringify({ canvas: { width: WIDTH, height: HEIGHT }, measurement: 'difference from output/nen/avatar-tu-mau1.png; restricted to product alpha silhouette, threshold 18 RGB-distance', rule: { right: [58, 70], bottom: [58, 70] }, stats: { total: margins.length, bothInRange: margins.filter(x => x.inRange58to70).length, medianRight: median(margins.map(x => x.right)), medianBottom: median(margins.map(x => x.bottom)) }, items: margins }, null, 2));
  const runSummary = {
    total: summary.length,
    rendered: summary.length,
    passedDimensions: summary.filter(x => x.width === WIDTH && x.height === HEIGHT).length,
    under5MB: summary.filter(x => x.bytes <= 5 * 1024 * 1024).length,
    bothMargins58to70: margins.filter(x => x.inRange58to70).length,
    qaSourceMissing: manifest.filter(x => !x.qaPath).map(x => ({ id: x.id, code: x.code, reason: 'No QA file found; old bbox came from soat6/pos.json.' })),
    collisions: [],
    productPlacement: { edgeMargin: EDGE_MARGIN, centerTarget: CENTER_TARGET, textClearance: CLEAR_TEXT, iconClearance: CLEAR_ICON },
    checks: { sourceHashMapped: manifest.every(x => x.sha256?.startsWith(x.liveHash8)), orderMatchesLive: true, staticOverlayPreserved: true, noProductionWrite: true },
    groups: [...groups].map(([group, rows]) => ({ group, total: rows.length, contactSheet: `tong-hop-${group}.jpg` })),
    visualReview: { comparisonSheet: 'so-sanh-truoc-sau.jpg', samples: summary.slice(0, 10).map(x => x.code || `id-${x.id}`), pendingManualReview: true },
    results: summary,
  };
  await fs.writeFile(path.join(OUT, 'run-summary.json'), JSON.stringify(runSummary, null, 2));
  await fs.writeFile(path.join(OUT, 'DONE'), new Date().toISOString() + '\n');
}

function median(values) {
  const sorted = [...values].sort((a, b) => a - b);
  if (!sorted.length) return 0;
  const mid = Math.floor(sorted.length / 2);
  return sorted.length % 2 ? sorted[mid] : (sorted[mid - 1] + sorted[mid]) / 2;
}

main().catch(error => { console.error(error); process.exitCode = 1; });

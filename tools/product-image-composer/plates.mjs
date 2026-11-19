#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const workspaceRoot = path.resolve(here, '../..');
const artifactRoot = path.resolve(process.env.P2_ARTIFACT_ROOT || '/home/admin01/orca/artifacts/pts-floodlight-images-20261117');
const inputRoot = path.join(artifactRoot, 'input/mau-7-anh');
const outputRoot = path.join(artifactRoot, 'output/nen');
const size = 900;
const names = {
  2: 'Logo; tiêu đề và dòng phụ; bốn dải nhãn. Bốn ảnh nhỏ và khung được giữ.',
  3: 'Logo; mọi dòng chữ ở đầu, giữa và cuối. Ba huy hiệu vàng, dải sáng, thành phố và sàn giữ nguyên.',
  4: 'Logo; tiêu đề/dòng phụ; hình đèn, tấm pin, remote, pin AI, mũi tên, nhãn và ba cụm thông tin. Mặt trời, trăng, mây, trời và núi cây giữ nguyên.',
  5: 'Logo; tiêu đề/dòng phụ; hai nhãn và đường dẫn. Núi, thành phố, cây và bục giữ nguyên.',
  6: 'Tiêu đề, dòng thông số theo model, thanh model và tám ô gồm hộp/icon/chữ. Tấm pin mờ, bục và vỏ bảng giữ nguyên.',
  7: 'Logo; hai dòng tiêu đề; bốn thẻ; ba icon và dòng chữ trong dải dưới. Cảnh, bục và hình dạng dải xanh giữ nguyên.',
};

// Hand measured rectangles/polygons on each original 900×900 sample.
const shapes = {
  2: [
    ['rect', 28, 20, 404, 86], ['rect', 414, 44, 881, 153],
    ['rect', 495, 149, 834, 184],
    ['rect', 10, 861, 226, 899], ['rect', 234, 861, 447, 899],
    ['rect', 449, 861, 674, 899], ['rect', 676, 861, 891, 899],
  ],
  3: [
    ['rect', 26, 18, 390, 88],
    ['rect', 98, 84, 808, 163], ['rect', 360, 154, 548, 234],
    ['rect', 268, 153, 636, 198], ['rect', 166, 427, 548, 471],
    ['rect', 165, 775, 548, 825],
  ],
  4: [
    ['rect', 50, 20, 395, 85], ['rect', 168, 76, 725, 185],
    ['rect', 196, 158, 750, 248],
    ['poly', '88,342 341,342 341,538 88,538'],
    ['poly', '353,391 411,391 411,480 353,480'],
    ['poly', '405,351 509,351 509,542 405,542'],
    ['poly', '501,390 575,390 575,481 501,481'],
    ['poly', '569,340 870,340 870,538 569,538'],
    ['rect', 151, 518, 274, 557], ['rect', 416, 518, 492, 557], ['rect', 676, 518, 801, 557],
    ['round', 226, 563, 505, 854, 33],
    ['round', 517, 560, 880, 852, 38],
  ],
  5: [
    ['rect', 48, 20, 404, 87], ['rect', 210, 98, 690, 171],
    ['rect', 263, 168, 652, 213],
    ['round', 8, 398, 194, 501, 8], ['polyline', '62,457 84,481 105,508 137,540 173,563', 14],
    ['round', 690, 278, 898, 386, 8], ['polyline', '790,341 772,368 750,395 728,423', 14],
  ],
  6: [
    ['rect', 92, 48, 810, 132], ['rect', 205, 125, 698, 169],
    ['round', 97, 190, 769, 257, 22],
    ['round', 117, 464, 425, 758, 14], ['round', 436, 464, 749, 758, 14],
  ],
  7: [
    ['rect', 48, 18, 401, 88], ['rect', 145, 92, 767, 164],
    ['rect', 147, 153, 777, 218],
    ['round', 588, 302, 891, 566, 11],
    // Clear only the interior of the curved banner; its white outline/shape stays intact.
    ['path', 'M 12 825 Q 25 790 72 790 L 828 790 Q 875 790 888 825 L 899 898 L 1 898 Z'],
  ],
};

function svgFor(n) {
  const bits = shapes[n].map((shape) => {
    if (shape[0] === 'rect') return `<rect x="${shape[1]}" y="${shape[2]}" width="${shape[3] - shape[1]}" height="${shape[4] - shape[2]}"/>`;
    if (shape[0] === 'round') return `<rect x="${shape[1]}" y="${shape[2]}" width="${shape[3] - shape[1]}" height="${shape[4] - shape[2]}" rx="${shape[5]}"/>`;
    if (shape[0] === 'poly') return `<polygon points="${shape[1]}"/>`;
    if (shape[0] === 'path') return `<path d="${shape[1]}"/>`;
    if (shape[0] === 'polyline') return `<polyline points="${shape[1]}" fill="none" stroke="white" stroke-width="${shape[2]}" stroke-linecap="round" stroke-linejoin="round"/>`;
    return '';
  }).join('\n');
  return Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}"><rect width="100%" height="100%" fill="black"/><g fill="white" stroke="white">${bits}</g></svg>`);
}

async function maskFor(n) {
  const hard = await sharp(svgFor(n)).extractChannel('red').raw().toBuffer();
  // Expand 7 px to cover antialias/shadow, then feather broadly to soften donor seams.
  const expanded = dilate(hard, 7);
  const soft = await sharp(expanded, { raw: { width: size, height: size, channels: 1 } }).blur(15).greyscale().raw().toBuffer();
  for (let i = 0; i < soft.length; i++) {
    // Keep the measured, dilated mask fully opaque; feather only its outer edge.
    if (expanded[i]) soft[i] = 255;
    if (soft[i] < 2) soft[i] = 0;
    else if (soft[i] > 245) soft[i] = 255;
  }
  return { hard, soft };
}

function dilate(mask, radius) {
  const horizontal = Buffer.alloc(mask.length), out = Buffer.alloc(mask.length);
  for (let y = 0; y < size; y++) {
    const counts = new Uint16Array(size + 1);
    for (let x = 0; x < size; x++) counts[x + 1] = counts[x] + (mask[y * size + x] ? 1 : 0);
    for (let x = 0; x < size; x++) if (counts[Math.min(size, x + radius + 1)] - counts[Math.max(0, x - radius)] > 0) horizontal[y * size + x] = 255;
  }
  for (let x = 0; x < size; x++) {
    const counts = new Uint16Array(size + 1);
    for (let y = 0; y < size; y++) counts[y + 1] = counts[y] + (horizontal[y * size + x] ? 1 : 0);
    for (let y = 0; y < size; y++) if (counts[Math.min(size, y + radius + 1)] - counts[Math.max(0, y - radius)] > 0) out[y * size + x] = 255;
  }
  return out;
}

function compositePixels(source, donor, mask) {
  const result = Buffer.from(source);
  for (let i = 0; i < size * size; i++) {
    const a = mask[i] / 255;
    if (!a) continue;
    const p = i * 3;
    for (let c = 0; c < 3; c++) result[p + c] = Math.round(source[p + c] * (1 - a) + donor[p + c] * a);
  }
  return result;
}

const lum = (p) => p[0] * 0.2126 + p[1] * 0.7152 + p[2] * 0.0722;
function metrics(source, result, final900, mask) {
  let exact = true, count = 0, sx = 0, sy = 0, sxx = 0, syy = 0, sxy = 0;
  for (let i = 0; i < size * size; i++) if (!mask[i]) {
    const p = i * 3;
    if (source[p] !== result[p] || source[p + 1] !== result[p + 1] || source[p + 2] !== result[p + 2]) exact = false;
    const x = lum(source.subarray(p, p + 3)); const y = lum(final900.subarray(p, p + 3));
    sx += x; sy += y; sxx += x * x; syy += y * y; sxy += x * y; count++;
  }
  const mx = sx / count, my = sy / count, vx = sxx / count - mx * mx, vy = syy / count - my * my, cov = sxy / count - mx * my;
  const c1 = (0.01 * 255) ** 2, c2 = (0.03 * 255) ** 2;
  const ssim = ((2 * mx * my + c1) * (2 * cov + c2)) / ((mx * mx + my * my + c1) * (vx + vy + c2));
  return { exact, ssim };
}

async function makeContact(pairs) {
  const width = 1800, rowH = 570, height = rowH * 3;
  const layers = [];
  for (let i = 0; i < pairs.length; i++) {
    const { n, srcPath, platePath } = pairs[i]; const y = Math.floor(i / 2) * rowH; const x = (i % 2) * 900;
    const left = await sharp(srcPath).resize(450, 450).png().toBuffer();
    const right = await sharp(platePath).resize(450, 450).png().toBuffer();
    const label = (txt) => Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="450" height="36"><rect width="100%" height="100%" fill="#11283f"/><text x="16" y="25" fill="white" font-size="20" font-family="sans-serif">${txt}</text></svg>`);
    layers.push({ input: label(`Mẫu ${n}`), left: x, top: y });
    layers.push({ input: label(`Nền sạch ${n}`), left: x + 450, top: y });
    layers.push({ input: left, left: x, top: y + 36 }); layers.push({ input: right, left: x + 450, top: y + 36 });
  }
  await sharp({ create: { width, height, channels: 3, background: '#e8edf2' } }).composite(layers).jpeg({ quality: 92 }).toFile(path.join(outputRoot, 'p2-nen-so-sanh.jpg'));
}

async function main() {
  await fs.mkdir(outputRoot, { recursive: true });
  const pairs = [], reportRows = [];
  for (let n = 2; n <= 7; n++) {
    const srcPath = path.join(inputRoot, `${n}.png`), donorPath = path.join(outputRoot, `p2-donor-0${n}.png`);
    const { data: src, info } = await sharp(srcPath).removeAlpha().resize(size, size, { fit: 'fill' }).raw().toBuffer({ resolveWithObject: true });
    const donor = await sharp(donorPath).removeAlpha().resize(size, size, { fit: 'fill', kernel: 'lanczos3' }).raw().toBuffer();
    const { hard, soft } = await maskFor(n);
    const result = compositePixels(src, donor, soft);
    const platePath = path.join(outputRoot, `p2-nen-0${n}.png`), maskPath = path.join(outputRoot, `p2-mask-0${n}.png`);
    await sharp(result, { raw: { width: size, height: size, channels: 3 } }).resize(1600, 1600, { kernel: 'lanczos3' }).png().toFile(platePath);
    await sharp(soft, { raw: { width: size, height: size, channels: 1 } }).png().toFile(maskPath);
    const final900 = await sharp(platePath).resize(size, size, { kernel: 'lanczos3' }).removeAlpha().raw().toBuffer();
    const m = metrics(src, result, final900, soft);
    let edgeSum = 0, edgeN = 0;
    for (let y = 0; y < size; y++) for (let x = 0; x < size; x++) {
      const i = y * size + x;
      if (!soft[i]) continue;
      for (const j of [x + 1 < size ? i + 1 : -1, y + 1 < size ? i + size : -1]) if (j >= 0 && soft[j] === 0) {
        edgeSum += Math.abs(lum(result.subarray(i * 3, i * 3 + 3)) - lum(result.subarray(j * 3, j * 3 + 3))); edgeN++;
      }
    }
    const seam = edgeN ? edgeSum / edgeN : 0;
    const diff = Buffer.alloc(size * size * 3);
    for (let i = 0; i < size * size; i++) if (!soft[i]) for (let c = 0; c < 3; c++) diff[i * 3 + c] = Math.abs(src[i * 3 + c] - result[i * 3 + c]);
    await sharp(diff, { raw: { width: size, height: size, channels: 3 } }).resize(1600, 1600, { kernel: 'nearest' }).png().toFile(path.join(outputRoot, `p2-diff-0${n}.png`));
    pairs.push({ n, srcPath, platePath });
    reportRows.push(`| ${String(n).padStart(2, '0')} | ${m.exact ? '0 (đạt)' : 'có pixel ngoài mask thay đổi'} | ${m.ssim.toFixed(6)} | ${seam.toFixed(3)} (${(seam / 255 * 100).toFixed(3)}%) | ${names[n]} |`);
  }
  await makeContact(pairs);
  const report = `# P2A — Nền sạch từ mẫu 2–7\n\nNguồn từng ảnh là phôi tương ứng input/mau-7-anh/N.png. Donor được tạo bằng image edit/inpaint cục bộ theo danh sách xóa; phép ghép chỉ sử dụng donor trong mặt nạ. Ngoài feathered mask, pixel RGB trước upscale được sao chép nguyên trạng. Mặt nạ được đo tay ở 900×900, mở rộng 7 px và feather Gaussian σ=15 px. Đầu ra 1600×1600 dùng Lanczos.\n\n| Mẫu | Pixel khác ngoài mask trước upscale | SSIM luma ngoài mask sau hạ 900 | Chênh sáng sát biên mask | Vùng xử lý |\n|---:|---:|---:|---:|---|\n${reportRows.join('\n')}\n\n- [So sánh mẫu | nền sạch](p2-nen-so-sanh.jpg).\n- Donor image-edit: p2-donor-02.png … p2-donor-07.png.\n- Mặt nạ: p2-mask-02.png … p2-mask-07.png.\n- Diff ngoài mask: p2-diff-02.png … p2-diff-07.png (đen đồng đều nghĩa là giữ nguyên).\n- Phần được xem bằng mắt và ghi nhận riêng trong p2a-final.md.\n`;
  await fs.writeFile(path.join(outputRoot, 'p2-nen-bao-cao.md'), report);
  console.log(JSON.stringify({ outputRoot, plates: pairs.map((p) => p.platePath), report: path.join(outputRoot, 'p2-nen-bao-cao.md') }, null, 2));
}

main().catch((error) => { console.error(error); process.exitCode = 1; });

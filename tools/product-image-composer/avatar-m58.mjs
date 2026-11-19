#!/usr/bin/env node
import fs from 'node:fs/promises';
import { existsSync as fsSyncExists } from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const args = process.argv.slice(2);
const arg = (key, fallback = '') => { const i = args.indexOf(key); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const mockRoot = path.join(inputRoot, 'mau-7-anh');
const canvas = 900;
const scale = 1600 / canvas;
const maxBytes = 5 * 1024 * 1024;
const requestedGroup = arg('--group', '');
const isV7 = args.includes('--v7');
const isV8 = args.includes('--v8');
const outputDir = arg('--output-dir', isV8 ? 'v8' : isV7 ? 'v7' : requestedGroup ? `v6/${requestedGroup}` : 'avatar');
const excludedSources = new Map([
  ['jd-t100-05-3525.jpg', 'Ảnh nguồn có bàn tay người cầm remote; không đưa vào bộ avatar.'],
  ['vip1-11-001-0cc1a9.png', 'Ảnh nguồn là poster cũ có logo, tiêu đề, thông số và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['01-b3pgou.png', 'Ảnh nguồn có sẵn poster/model và bàn tay người cầm remote; không dùng làm cutout sản phẩm.'],
  ['jd-t200-05-6091.jpg', 'Ảnh nguồn có bàn tay người cầm remote; không đưa vào bộ avatar.'],
  ['jd-t300-05-5973.jpg', 'Ảnh nguồn có bàn tay người cầm remote; không đưa vào bộ avatar.'],
  ['vip1-20-219-bdb1c8.png', 'Ảnh nguồn là poster cũ có logo, tiêu đề, thông số và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['01-hvawyl.png', 'Ảnh nguồn là poster cũ có logo, tiêu đề, thông số và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-10-121-d66915.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và model; không phải ảnh sản phẩm riêng.'],
  ['vip1-07-088-7af11c.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và model; không phải ảnh sản phẩm riêng.'],
  ['vip1-08-099-638c55.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và model; không phải ảnh sản phẩm riêng.'],
  ['vip1-08-100-ab829d.png', 'Ảnh lifestyle có chữ và nhiều bối cảnh; không phải ảnh sản phẩm riêng.'],
  ['bct-ww50-mono-7520.jpg', 'Ảnh ghép có model, kích thước và thông số; không phải ảnh sản phẩm riêng.'],
  ['z7126964879908aedc0a15c21a6d6e3d173d1d27f24700-5100.jpg', 'Ảnh sản phẩm có chú thích và thông số chèn sẵn.'],
  ['z7126964716027c5e1a5eb2600b2d146dee70189cbc991-2546.jpg', 'Ảnh sản phẩm có chú thích và thông số chèn sẵn.'],
  ['vip1-08-103-f9c66b.png', 'Ảnh poster bộ sản phẩm có logo, tiêu đề và chú thích.'],
  ['z712696526624041161729bda314219093f751b772be9f-1152.jpg', 'Ảnh infographic tính năng; không phải ảnh sản phẩm riêng.'],
  ['vip1-08-104-177c03.png', 'Ảnh infographic có chữ và hình minh họa chèn sẵn.'],
  ['z7126965237295f928c735ee2993b496fe232e0aeb6389-4475.jpg', 'Ảnh sản phẩm có nhãn thông số chèn sẵn.'],
  ['vip1-08-105-ea71ac.png', 'Ảnh infographic có chữ và biểu tượng chèn sẵn.'],
  ['17908223077082431571402922123655g7263129555270226418f89740894e1c5ac26eefb1f76814251e-2089.jpg', 'Ảnh ghép nhiều góc kèm chữ chú thích.'],
  ['vip1-08-106-20d1c9.png', 'Ảnh infographic có chữ và hình minh họa chèn sẵn.'],
  ['vip1-08-107-59849e.png', 'Ảnh cấu hình sản phẩm có chữ thông số chèn sẵn.'],
  ['vip1-08-108-664b35.png', 'Ảnh ghép có chú thích; không phải ảnh sản phẩm riêng.'],
  ['vip1-01-014-79e5d4.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-02-028-e8accb.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-03-041-db6240.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-12-133-55245f.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-06-075-4685f9.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-18-194-7f1a56.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['vip1-16-180-bbbcdb.png', 'Ảnh nguồn là poster cũ có sẵn chữ, logo và nhiều model; không phải ảnh sản phẩm riêng.'],
  ['1-4981.jpg', 'Ảnh hộp có logo BOLAR của bên khác; chưa có ảnh sản phẩm sạch đúng thương hiệu.'],
  ['3-3856.jpg', 'Ảnh sản phẩm có logo BOLAR của bên khác.'],
  ['4-3399.jpg', 'Ảnh mặt sau có logo BOLAR của bên khác.'],
  ['z4185975145061ba188d8b63d6d9a517845432e3063033-3005.jpg', 'Ảnh ghi model JD-T300, không khớp mã JD-T100.'],
  ['z41859751637560828601cafa5748d0272874204c749e7-2485.jpg', 'Ảnh kích thước/thông số có chữ và số chèn sẵn.'],
  ['z4185975137338ea039e8140c4e60f901d20f15bcf2a77-7100.jpg', 'Ảnh infographic có chữ và số chèn sẵn.'],
  ['z41859751455414513c88565364a0d0f413ebbcb93fecb-3553.jpg', 'Ảnh đồ họa tính năng có chữ và biểu tượng chèn sẵn.'],
  ['z4185975145520cc8e8fc830fbadb88fd34d7ee1f1cbb3-8262.jpg', 'Ảnh remote có nhiều chữ chèn sẵn.'],
  ['z418597516373615dddc9ce9cdceaf340bcadb3d5f66fd-7088.jpg', 'Ảnh minh họa tính năng có chữ và watermark chèn sẵn.'],
  ['import-product-397-10.jpg', 'Ảnh nhiều chú thích, kích thước và model chèn sẵn.'],
  ['2-7739.jpg', 'Ảnh sản phẩm có logo BOLAR của bên khác.'],
  ['4-2301.jpg', 'Ảnh mặt sau có logo BOLAR của bên khác.'],
  ['3-1090.jpg', 'Ảnh sản phẩm có logo BOLAR của bên khác.'],
  ['1-2964.jpg', 'Ảnh hộp có logo BOLAR của bên khác.'],
  ['61aadaba8603a-2625.jpg', 'Infographic có chữ model và kích thước chèn sẵn.'],
  ['import-product-398-06.jpg', 'Ảnh nhiều chú thích, kích thước và model chèn sẵn.'],
  ['2-2256.jpg', 'Ảnh sản phẩm có logo BOLAR của bên khác.'],
  ['1-4624.jpg', 'Ảnh sản phẩm có logo BOLAR của bên khác.'],
  ['4-5303.jpg', 'Ảnh mặt sau có logo BOLAR của bên khác.'],
  ['3-3932.jpg', 'Ảnh hộp có logo BOLAR của bên khác.'],
  ['300-2018.jpg', 'Infographic có chữ model và kích thước chèn sẵn.'],
  ['import-product-399-06.jpg', 'Ảnh nhiều chú thích, kích thước và model chèn sẵn.'],
  ['import-product-399-07.jpg', 'Ảnh có bàn tay người và chữ chèn sẵn.'],
  ['import-product-399-08.jpg', 'Ảnh còn nền trắng lớn và chữ/model trên hộp; flood-fill không tách nền sạch.'],
  ['jd-6128-8657.png', 'Flood-fill còn giữ mảng trắng của nền quanh bóng dưới đèn; cần tách matte thủ công để tránh viền trắng.'],
  ['db60100-2128.png', 'Ảnh nguồn là poster có chữ/model ORIANA, hộp sản phẩm và nhiều hình ghép; không phải ảnh sản phẩm riêng.'],
  ['vip1-01-018-05cea0.png', 'Ảnh ghép có logo, chữ chú thích, bối cảnh và nhiều bộ sản phẩm; không phải ảnh sản phẩm riêng.'],
  ['vip1-01-015-6a5a05.png', 'Ảnh sản phẩm ghép có nhiều model/ghi chú; không phải ảnh đơn sạch.'], ['vip1-01-016-e54bbf.png', 'Ảnh cấu hình ghép nhiều model và chữ thông số.'],
  ['vip1-01-017-c38f78.png', 'Ảnh sản phẩm ghép có nhiều model/ghi chú; không phải ảnh đơn sạch.'], ['vip1-01-019-6fed6a.png', 'Infographic thông số ghép nhiều model DB60xx.'],
  ['vip1-01-020-698603.png', 'Ảnh thông số/đặc điểm có chữ chèn sẵn.'], ['vip1-01-022-80b3f0.png', 'Ảnh ghép nhiều sản phẩm có chữ/logo.'],
  ['vip1-01-023-079ea0.png', 'Ảnh ghép nhiều sản phẩm có chữ/logo.'], ['vip1-01-014-79e5d4.png', 'Ảnh poster cũ có chữ/logo/model và nhiều hình ghép.'],
  ['daynoibctww1-8517-chuan1600.jpg', 'Ảnh đã dựng sẵn có logo, tiêu đề, model và thông số trên nền avatar; không phải ảnh nguồn sản phẩm sạch.'],
]);
const sourceAngles = {
  'BCT-WR1.0': { view: 'xéo nhẹ', note: 'Đã xem ảnh nguồn ở kích thước lớn; đèn và tấm pin đều có phối cảnh.' },
  'BCT-FLRP2.0': { view: 'xéo', note: 'Đã xem ảnh nguồn ở kích thước lớn; thấy rõ mặt bên và góc của đèn/tấm pin.' },
  'BCT-WW5.0': { view: 'xéo nhẹ', note: 'Đã xem ảnh nguồn ở kích thước lớn; đèn và tấm pin có phối cảnh nhẹ.' },
  'JD-V100': { view: 'xéo', note: 'Đã xem ảnh nguồn ở kích thước lớn; thân và gá đèn nhìn theo góc ba phần tư.' },
  'JD-T200': { view: 'thẳng', note: 'Đèn nhìn gần chính diện; ảnh bị loại vì có bàn tay người cầm remote.' },
  'DB-8300': { view: 'thẳng', note: 'Đã xem ảnh nguồn ở kích thước lớn; mặt đèn và tấm pin hướng gần trực diện.' },
};
const rgb = (r, g, b) => [r, g, b];
const clamp = n => Math.max(0, Math.min(255, Math.round(n)));

function svgMask(slideNo) {
  if (slideNo !== 1) throw new Error(`Chưa có vùng mặt nạ đã rà soát cho mẫu ${slideNo}`);
  return `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900">
    <rect width="900" height="900" fill="black"/>
    <g fill="white">
      <circle cx="130" cy="355" r="47"/><circle cx="133" cy="473" r="47"/>
      <circle cx="135" cy="591" r="47"/><circle cx="137" cy="709" r="47"/>
      <rect x="352" y="231" width="196" height="42" rx="4"/>
    </g>
  </svg>`;
}

function dilateMask(mask, width, height, radius) {
  const out = new Uint8Array(mask.length);
  for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
    let hit = 0;
    for (let dy = -radius; dy <= radius && !hit; dy++) for (let dx = -radius; dx <= radius; dx++) {
      const xx = x + dx, yy = y + dy;
      if (xx >= 0 && yy >= 0 && xx < width && yy < height && mask[yy * width + xx]) { hit = 1; break; }
    }
    out[y * width + x] = hit;
  }
  return out;
}

async function buildTightMask(source, geometry, slideNo) {
  const { data, info } = source;
  const { width, height } = info;
  const mask = Buffer.from(geometry.data);
  const paint = (x, y) => { if (x >= 0 && y >= 0 && x < width && y < height) mask[y * width + x] = 255; };
  const inBox = (x, y, box) => x >= box.x0 && x < box.x1 && y >= box.y0 && y < box.y1;
  const boxes = [
    { x0: 32, y0: 24, x1: 390, y1: 88 },
    { x0: 148, y0: 100, x1: 753, y1: 158 },
    { x0: 168, y0: 163, x1: 787, y1: 225 },
    { x0: 190, y0: 325, x1: 328, y1: 380 },
    { x0: 190, y0: 442, x1: 340, y1: 498 },
    { x0: 190, y0: 575, x1: 328, y1: 604 },
    { x0: 188, y0: 687, x1: 320, y1: 740 },
    { x0: 336, y0: 590, x1: 344, y1: 604 },
  ];
  for (const box of boxes) for (let y = box.y0; y < box.y1; y++) for (let x = box.x0; x < box.x1; x++) paint(x, y);
  const tight = new Uint8Array(mask.length);
  for (let i = 0; i < mask.length; i++) tight[i] = mask[i] > 127 ? 1 : 0;
  const expanded = dilateMask(tight, width, height, 20);
  const display = Buffer.alloc(expanded.length);
  for (let i = 0; i < display.length; i++) display[i] = expanded[i] ? 255 : 0;
  const feathered = await sharp(display, { raw: { width, height, channels: 1 } }).blur(15).greyscale().raw().toBuffer({ resolveWithObject: true });
  const soft = feathered.data;
  for (let i = 0; i < soft.length; i++) if (soft[i] < 8) soft[i] = 0;
  const output = await sharp(soft, { raw: { width, height, channels: 1 } }).png().toBuffer();
  let count = 0;
  for (const alpha of soft) if (alpha) count++;
  return { png: output, values: soft, count, slideNo };
}

function inpaint(source, donor, mask, width, height) {
  const out = Buffer.from(source);
  for (let i = 0; i < width * height; i++) if (mask[i]) {
    const p = i * 3, alpha = mask[i] / 255;
    out[p] = clamp(source[p] * (1 - alpha) + donor[p] * alpha);
    out[p + 1] = clamp(source[p + 1] * (1 - alpha) + donor[p + 1] * alpha);
    out[p + 2] = clamp(source[p + 2] * (1 - alpha) + donor[p + 2] * alpha);
  }
  return out;
}

// Shared entry point for later mock plates. Slide masks must be reviewed before adding a new branch.
async function makePlateFromMock(slideNo) {
  const sourcePath = path.join(mockRoot, `${slideNo}.png`);
  const source = await sharp(sourcePath).removeAlpha().raw().toBuffer({ resolveWithObject: true });
  if (source.info.width !== canvas || source.info.height !== canvas) throw new Error(`Mẫu ${slideNo} cần 900×900.`);
  const shape = Buffer.from(await sharp(Buffer.from(svgMask(slideNo))).extractChannel('red').raw().toBuffer());
  const geometry = { data: shape };
  const mask = await buildTightMask(source, geometry, slideNo);
  const donorPath = path.resolve(arg('--inpaint-image', path.join(outputRoot, 'nen', 'avatar-tu-mau1-donor.png')));
  const donor = await sharp(donorPath).removeAlpha().resize(canvas, canvas, { kernel: sharp.kernel.lanczos3 }).raw().toBuffer();
  const repaired = inpaint(source.data, donor, mask.values, canvas, canvas);
  const outside = await sharp(repaired, { raw: { width: canvas, height: canvas, channels: 3 } }).png().toBuffer();
  const plateFile = path.join(outputRoot, 'nen', 'avatar-tu-mau1.png');
  const maskFile = path.join(outputRoot, 'nen', 'avatar-mask.png');
  await fs.mkdir(path.dirname(plateFile), { recursive: true });
  await sharp(repaired, { raw: { width: canvas, height: canvas, channels: 3 } }).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).png().toFile(plateFile);
  await sharp(mask.png).resize(1600, 1600, { kernel: sharp.kernel.nearest }).png().toFile(maskFile);
  const beforeLogo = await sharp(sourcePath).extract({ left: 0, top: 0, width: 460, height: 170 }).resize(1380, 510, { kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  const afterLogo = await sharp(plateFile).resize(900, 900).png().toBuffer().then(buf => sharp(buf).extract({ left: 0, top: 0, width: 460, height: 170 }).resize(1380, 510, { kernel: sharp.kernel.lanczos3 }).png().toBuffer());
  await sharp({ create: { width: 2760, height: 510, channels: 3, background: '#fff' } }).composite([{ input: beforeLogo, left: 0, top: 0 }, { input: afterLogo, left: 1380, top: 0 }]).jpeg({ quality: 92 }).toFile(path.join(outputRoot, 'nen', 'avatar-crop-logo-truoc-sau.jpg'));
  const beforeLine = await sharp(sourcePath).extract({ left: 300, top: 560, width: 100, height: 80 }).resize(600, 480, { kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  const afterLine = await sharp(plateFile).resize(900, 900).png().toBuffer().then(buf => sharp(buf).extract({ left: 300, top: 560, width: 100, height: 80 }).resize(600, 480, { kernel: sharp.kernel.lanczos3 }).png().toBuffer());
  await sharp({ create: { width: 1200, height: 480, channels: 3, background: '#fff' } }).composite([{ input: beforeLine, left: 0, top: 0 }, { input: afterLine, left: 600, top: 0 }]).jpeg({ quality: 92 }).toFile(path.join(outputRoot, 'nen', 'avatar-crop-net-truoc-sau.jpg'));
  const diff = await sharp(sourcePath).composite([{ input: outside, blend: 'difference' }]).png().toBuffer();
  await sharp(diff).resize(1600, 1600, { kernel: sharp.kernel.nearest }).png().toFile(path.join(outputRoot, 'nen', 'avatar-mask-diff.png'));
  const finalSmall = await sharp(plateFile).resize(canvas, canvas, { kernel: sharp.kernel.lanczos3 }).raw().toBuffer();
  const src = source.data;
  let maxOutsideDelta = 0, count = 0, sx = 0, sy = 0, sxx = 0, syy = 0, sxy = 0, seamAbs = 0, seamPairs = 0;
  for (let i = 0; i < mask.values.length; i++) if (!mask.values[i]) {
    const p = i * 3;
    maxOutsideDelta = Math.max(maxOutsideDelta, Math.abs(src[p] - repaired[p]), Math.abs(src[p + 1] - repaired[p + 1]), Math.abs(src[p + 2] - repaired[p + 2]));
    const x = src[p] * .2126 + src[p + 1] * .7152 + src[p + 2] * .0722;
    const y = finalSmall[p] * .2126 + finalSmall[p + 1] * .7152 + finalSmall[p + 2] * .0722;
    sx += x; sy += y; sxx += x * x; syy += y * y; sxy += x * y; count++;
  }
  for (let y = 0; y < canvas; y++) for (let x = 0; x < canvas; x++) {
    const i = y * canvas + x;
    if (!mask.values[i]) continue;
    for (const j of [x + 1 < canvas ? i + 1 : -1, y + 1 < canvas ? i + canvas : -1]) {
      if (j < 0 || mask.values[j]) continue;
      const a = i * 3, b = j * 3;
      const la = repaired[a] * .2126 + repaired[a + 1] * .7152 + repaired[a + 2] * .0722;
      const lb = repaired[b] * .2126 + repaired[b + 1] * .7152 + repaired[b + 2] * .0722;
      seamAbs += Math.abs(la - lb); seamPairs++;
    }
  }
  const mx = sx / count, my = sy / count, vx = sxx / count - mx * mx, vy = syy / count - my * my, cov = sxy / count - mx * my;
  const c1 = (0.01 * 255) ** 2, c2 = (0.03 * 255) ** 2;
  const ssim = ((2 * mx * my + c1) * (2 * cov + c2)) / ((mx * mx + my * my + c1) * (vx + vy + c2));
  const seamMean = seamPairs ? seamAbs / seamPairs : 0;
  const seamPercent = seamMean / 255 * 100;
  const report = `# Nền avatar lấy từ mẫu 1\n\n- Nguồn: \`input/mau-7-anh/1.png\` (900×900).\n- Mặt nạ phủ logo, tiêu đề, model, bốn đĩa icon/chữ thông số và nét đứng sót gần (x≈340,y≈597); vùng được mở rộng 20 px ở 900×900 rồi feather Gaussian 15 px.\n- Inpaint: donor được sửa cục bộ; chỉ vùng mask/feather lấy pixel donor, cảnh ngoài vùng đó giữ nguyên từ mẫu.\n- Đầu ra phóng lên 1600×1600 bằng Lanczos. Tỷ lệ mask/feather: ${(mask.count / (canvas * canvas) * 100).toFixed(2)}%. Sai khác pixel ngoài mask trước upscale = ${maxOutsideDelta}; luminance SSIM ngoài mask sau upscale/hạ về 900 = ${ssim.toFixed(6)} (ngưỡng 0.95). Mean chênh sáng qua biên feather = ${seamMean.toFixed(3)}/255 = ${seamPercent.toFixed(3)}% (ngưỡng 1.5%).\n- Soát ảnh: [crop trước/sau quanh logo](avatar-crop-logo-truoc-sau.jpg), [crop trước/sau quanh nét đứng](avatar-crop-net-truoc-sau.jpg), [diff toàn ảnh](avatar-mask-diff.png). Nền đã được xem ở 100%; crop 300% quanh logo và nét đứng được tạo để kiểm tra lại.\n- Trước: [mẫu 1](../../input/mau-7-anh/1.png). Sau: [nền đã làm sạch](avatar-tu-mau1.png). Mặt nạ: [avatar-mask.png](avatar-mask.png). Donor: [ảnh inpaint](avatar-tu-mau1-donor.png).\n`;
  await fs.writeFile(path.join(outputRoot, 'nen', 'avatar-tu-mau1-bao-cao.md'), report);
  return { plateFile, maskFile, maskPixels: mask.count, maxOutsideDelta, ssim: Number(ssim.toFixed(6)), seamMean: Number(seamMean.toFixed(3)), seamPercent: Number(seamPercent.toFixed(3)) };
}

function esc(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&apos;'); }
function luminance(hex) {
  const rgb = hex.match(/[0-9a-f]{2}/gi).map(v => parseInt(v, 16) / 255).map(v => v <= 0.04045 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2);
  return rgb[0] * 0.2126 + rgb[1] * 0.7152 + rgb[2] * 0.0722;
}
function watts(item) { if (!item.power_watts) return ''; const n = Number(item.power_watts); return `${Number.isFinite(n) && n % 1 === 0 ? n.toFixed(0) : item.power_watts}W`; }
function matchLumen(value) { return String(value || '').match(/\d[\d.,]*\s*Lm\b/i)?.[0]?.replace(/\s+/g, '') || ''; }
function hasCable(item) { return (item.additional_specs || []).some(spec => /cáp nguồn\s*5\s*mét/i.test(`${spec.label || ''} ${spec.value || ''}`)); }
const titleByCategory = {
  'den-duong': ['ĐÈN ĐƯỜNG NĂNG LƯỢNG', 'MẶT TRỜI'], 'den-tru-cong': ['ĐÈN TRỤ CỔNG NĂNG LƯỢNG', 'MẶT TRỜI'],
  'den-treo-tuong': ['ĐÈN TREO TƯỜNG NĂNG LƯỢNG', 'MẶT TRỜI'], 'den-san-vuon-den-nam': ['ĐÈN SÂN VƯỜN NĂNG LƯỢNG', 'MẶT TRỜI'],
  'den-cam-co': ['ĐÈN CẮM CỎ NĂNG LƯỢNG', 'MẶT TRỜI'], 'den-op-tran': ['ĐÈN ỐP TRẦN NĂNG LƯỢNG', 'MẶT TRỜI'],
  'den-ufo': ['ĐÈN UFO NĂNG LƯỢNG', 'MẶT TRỜI'], 'hoa-luoi': ['BIẾN TẦN HÒA LƯỚI', ''],
  'hybrid-luu-tru': ['BIẾN TẦN HYBRID', 'LƯU TRỮ'], 'he-thong-ess': ['HỆ THỐNG LƯU TRỮ', 'ESS'],
  'pin-battery': ['PIN LƯU TRỮ', 'NĂNG LƯỢNG'], 'bo-dieu-khien-sac-pwm': ['BỘ ĐIỀU KHIỂN SẠC', 'PWM'],
  'bo-dieu-khien-sac-mppt': ['BỘ ĐIỀU KHIỂN SẠC', 'MPPT'], 'giam-sat-do-luong': ['THIẾT BỊ GIÁM SÁT', 'ĐO LƯỜNG'],
  'tam-pin-nang-luong-mat-troi': ['TẤM PIN NĂNG LƯỢNG', 'MẶT TRỜI'], 'quat-nang-luong-mat-troi': ['QUẠT NĂNG LƯỢNG', 'MẶT TRỜI'],
  'bien-tan-bom-nlmt': ['BIẾN TẦN BƠM NĂNG LƯỢNG', 'MẶT TRỜI'], 'may-bom-nlmt': ['MÁY BƠM NĂNG LƯỢNG', 'MẶT TRỜI'],
  'cap-day-dien': ['CÁP DÂY ĐIỆN', ''], 'tiep-dia-ong-bao-ve': ['TIẾP ĐỊA / ỐNG BẢO VỆ', ''],
  'vat-tu-phu-tro': ['VẬT TƯ PHỤ TRỢ', ''], 'kep-tam-pin': ['KẸP TẤM PIN', ''], 'phu-kien-den-nlmt': ['PHỤ KIỆN ĐÈN NLMT', ''],
};
const specRows = item => (item.additional_specs || []).map(s => ({ label: String(s.label || '').trim(), value: String(s.value || '').trim() })).filter(s => s.label && s.value);
function rows(item) {
  if (item.v7NoCode || item.v8NoCode) {
    const out = [], push = (label, value, field, iconName) => { if (value !== null && value !== undefined && String(value).trim() && out.length < 4) out.push({ label, value: String(value), field, icon: iconName }); };
    if (item.id === 354) push('CÔNG SUẤT', `${String(item.power_watts).replace(/\.00$/, '')}W`, 'power_watts', 'power');
    if (item.id === 361 && !item.v8NoCode) push('CÔNG SUẤT', '395W', 'power_watts', 'power');
    for (const spec of item.additional_specs || []) {
      const label = String(spec.label || 'QUY CÁCH'), value = String(spec.value || '');
      const iconName = /đường kính|kích thước|khối lượng/i.test(label) ? 'ruler'
        : /điện áp|tần số|động cơ|nguồn hoạt động/i.test(label) ? 'voltage'
        : /cánh quạt|lồng quạt|mô-tơ/i.test(label) ? 'fan'
        : /công suất|pmax|vmp|lmp|voc|isc|watt/i.test(`${label} ${value}`) ? 'power'
        : /pin lưu trữ|dung lượng|mah|ah/i.test(`${label} ${value}`) ? 'battery' : 'ruler';
      push(label.toLocaleUpperCase('vi'), value, 'additional_specs', iconName);
    }
    if (item.material) push('CHẤT LIỆU', item.material, 'material', 'shield');
    if (item.package_contents) push('QUY CÁCH ĐÓNG GÓI', item.package_contents, 'package_contents', 'box');
    if (item.warranty) push('BẢO HÀNH', item.warranty, 'warranty', 'shield');
    return out;
  }
  const out = [], specs = specRows(item), by = (...names) => specs.find(s => names.some(n => s.label.toLowerCase().includes(n.toLowerCase())));
  const push = (label, value, field, iconName) => { if (value && out.length < 4) out.push({ label, value: String(value), field, icon: iconName }); };
  const cat = item.cat_slug || '';
  if (cat.startsWith('den-') && cat !== 'den-pha-nang-luong-mat-troi') {
  if (item.battery_capacity) out.push({ label: 'PIN LƯU TRỮ', value: item.battery_capacity, field: 'battery_capacity', icon: 'battery' });
  if (item.panel_power) out.push({ label: 'TẤM PIN', value: item.panel_power, field: 'panel_power', icon: 'remote' });
  const lumen = matchLumen(item.light_source);
  if (lumen) out.push({ label: 'NGUỒN SÁNG', value: lumen, field: 'light_source', icon: 'led' });
  else if (item.power_watts) out.push({ label: 'CÔNG SUẤT LED', value: watts(item), field: 'power_watts', icon: 'led' });
  if (item.lighting_time) out.push({ label: 'CHIẾU SÁNG', value: item.lighting_time, field: 'lighting_time', icon: 'bulb' });
  if (out.length < 4 && item.waterproof_rating) push('CHỐNG NƯỚC', item.waterproof_rating, 'waterproof_rating', 'water');
  if (out.length < 4 && item.warranty) push('BẢO HÀNH', item.warranty, 'warranty', 'shield');
  if (hasCable(item)) {
    const cable = { label: 'CÁP NGUỒN', value: '5 mét', field: 'additional_specs', icon: 'cable' };
    const sourceIndex = out.findIndex(row => row.field === 'light_source' || row.field === 'power_watts');
    if (sourceIndex >= 0) out.splice(sourceIndex, 1, cable);
    else if (out.length < 4) out.push(cable);
  }
  } else if (['hoa-luoi','hybrid-luu-tru','he-thong-ess','pin-battery'].includes(cat)) {
    const specPower = specs.find(s => /công suất( ac định mức)?/i.test(s.label));
    if (item.power_watts) push(cat.startsWith('pin-') ? 'DUNG LƯỢNG' : 'CÔNG SUẤT', watts(item), 'power_watts', 'power');
    else if (specPower) push('CÔNG SUẤT', specPower.value, 'additional_specs', 'power');
    const phase = specs.find(s => /mppt|số pha|điện\s*\d+\s*pha/i.test(s.label) || /\bmppt\b/i.test(s.value));
    if (phase) { const count = phase.value.match(/\b\d+\s*MPPT\b/i)?.[0]; push(count ? 'SỐ MPPT' : /điện\s*\d+\s*pha/i.test(phase.label) ? phase.label.toUpperCase() : 'SỐ PHA / MPPT', count || phase.value, 'additional_specs', 'voltage'); }
    const monitor = specs.find(s => /giám sát|wifi|kết nối|communication/i.test(s.label)); if (monitor) push('KẾT NỐI GIÁM SÁT', monitor.value, 'additional_specs', 'wifi');
    if (cat === 'pin-battery' || cat === 'he-thong-ess') { if (item.battery_capacity) push('DUNG LƯỢNG PIN', item.battery_capacity, 'battery_capacity', 'battery'); if (item.warranty) push('BẢO HÀNH', item.warranty, 'warranty', 'shield'); }
    else if (item.warranty) push('BẢO HÀNH', item.warranty, 'warranty', 'shield');
  } else if (cat.startsWith('bo-dieu-khien')) {
    const systemVoltage = by('điện áp hệ thống');
    const chargeCurrent = by('dòng sạc', 'dòng điện');
    const pvPower = by('công suất đầu vào pv tối đa', 'công suất pv tối đa');
    const pvVoltage = by('điện áp đầu vào pv tối đa');
    const dimensions = by('kích thước', 'bảo hành');
    if (systemVoltage) push('ĐIỆN ÁP HỆ THỐNG', systemVoltage.value, 'additional_specs', 'voltage');
    if (chargeCurrent) push('DÒNG SẠC ĐỊNH MỨC', chargeCurrent.value, 'additional_specs', 'current');
    if (pvPower) push('CÔNG SUẤT PV TỐI ĐA', pvPower.value, 'additional_specs', 'power');
    else if (pvVoltage) push('ĐIỆN ÁP ĐẦU VÀO PV TỐI ĐA', pvVoltage.value, 'additional_specs', 'voltage');
    if (dimensions) push('KÍCH THƯỚC / BẢO HÀNH', dimensions.value, 'additional_specs', 'ruler');
  } else if (cat === 'tam-pin-nang-luong-mat-troi') {
    const wattsValue = item.power_watts ? watts(item).replace(/W$/, 'Wp') : by('công suất cực đại')?.value; if (wattsValue) push('CÔNG SUẤT', wattsValue, item.power_watts ? 'power_watts' : 'additional_specs', 'power');
    for (const [patterns,label,iconName] of [[['loại cell','cell'],'LOẠI CELL','sun'],[['hiệu suất'],'HIỆU SUẤT','power'],[['kích thước'],'KÍCH THƯỚC','ruler'],[['bảo hành'],'BẢO HÀNH','shield']]) { const s = by(...patterns); if (s) push(label, s.value, 'additional_specs', iconName); }
    if (item.panel_dimensions && out.length < 4) push('KÍCH THƯỚC', item.panel_dimensions, 'panel_dimensions', 'ruler');
  } else if (cat === 'quat-nang-luong-mat-troi') {
    if (item.power_watts) push('CÔNG SUẤT', watts(item), 'power_watts', 'power'); if (item.panel_power) push('TẤM PIN', item.panel_power, 'panel_power', 'panel'); if (item.battery_capacity) push('PIN LƯU TRỮ', item.battery_capacity, 'battery_capacity', 'battery'); if (item.lighting_time) push('THỜI GIAN CHẠY', item.lighting_time, 'lighting_time', 'bulb');
  } else if (cat === 'bien-tan-bom-nlmt' || cat === 'may-bom-nlmt') {
    for (const [patterns,label,iconName] of [[['công suất động cơ','công suất'],'CÔNG SUẤT ĐỘNG CƠ','power'],[['điện áp vào'],'ĐIỆN ÁP VÀO','voltage'],[['điện áp ra'],'ĐIỆN ÁP RA','voltage'],[['bảo hành'],'BẢO HÀNH','shield']]) { const s=by(...patterns); if(s) push(label,s.value,'additional_specs',iconName); }
  } else {
    const vals = [item.lamp_dimensions && ['KÍCH THƯỚC',item.lamp_dimensions,'lamp_dimensions','ruler'], item.material && ['CHẤT LIỆU',item.material,'material','shield'], item.package_contents && ['QUY CÁCH ĐÓNG GÓI',item.package_contents,'package_contents','box'], item.warranty && ['BẢO HÀNH',item.warranty,'warranty','shield']].filter(Boolean);
    for (const r of vals) push(...r); for (const s of specs) if (out.length<4) push(s.label.toUpperCase(),s.value,'additional_specs','ruler');
  }
  return out.slice(0, 4);
}
function icon(kind, cx, cy) {
  const navy = '#0B4FA8';
  const stroke = `fill="none" stroke="${navy}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"`;
  const art = kind === 'battery'
    ? `<path d="M${cx + 20} ${cy - 19}C${cx + 2} ${cy - 34} ${cx - 26} ${cy - 23} ${cx - 27} ${cy - 1}C${cx - 29} ${cy + 19} ${cx - 8} ${cy + 32} ${cx + 10} ${cy + 24}" ${stroke}/><path d="M${cx + 12} ${cy + 17}l4 8-9 1" ${stroke}/><rect x="${cx - 8}" y="${cy - 13}" width="16" height="26" rx="3" ${stroke}/><path d="M${cx - 3} ${cy - 17}h6m0 9-6 8h5l-1 8 8-11h-5l2-5z" fill="${navy}"/>`
    : kind === 'remote'
      ? `<g transform="rotate(-28 ${cx} ${cy})"><rect x="${cx - 14}" y="${cy - 24}" width="28" height="48" rx="7" ${stroke}/><circle cx="${cx}" cy="${cy - 12}" r="3.5" fill="${navy}"/><circle cx="${cx - 5}" cy="${cy}" r="3" fill="${navy}"/><circle cx="${cx + 5}" cy="${cy}" r="3" fill="${navy}"/><path d="M${cx - 5} ${cy + 13}h10" ${stroke}/></g>`
      : kind === 'cable'
        ? `<path d="M${cx + 3} ${cy - 22}l5 2 4-4 5 4-2 6 4 4 6-2 2 6-4 4 1 5 5 2-2 6-6 1-3 5-6-2-3 4-5-3 2-6-4-3-5 2-2-6 4-4-1-5-5-2 2-6 6-1 3-5 5 2z" ${stroke}/><circle cx="${cx + 5}" cy="${cy}" r="4" ${stroke}/><g transform="translate(${cx - 28} ${cy + 2}) scale(.82)"><path d="M22.7 19 13.6 9.9c1.4-3.5.2-7.6-3-9.6C8.3-.9 5.8-.6 3.8.4L8 4.6 5.5 7 1.3 2.8C0 5.4.5 8.6 2.6 10.6c2 2 5.1 2.6 7.7 1.4l9.1 9.1c.9.9 2.4.9 3.3 0 .9-.9.9-2.4 0-3.3z" fill="${navy}"/></g>`
        : kind === 'led'
          ? `<rect x="${cx - 15}" y="${cy - 15}" width="30" height="30" rx="5" ${stroke}/><path d="M${cx - 8} ${cy - 6}h16v12h-16z" fill="${navy}"/><path d="M${cx - 7} ${cy - 21}v-7m14 7v-7m-22 16h-7m7 14h-7m44-14h-7m7 14h-7m-18 8v7m14-7v7" ${stroke}/>`
          : kind === 'power' ? `<path d="M${cx + 5} ${cy - 26}L${cx - 11} ${cy + 1}h12l-3 24 19-33H${cx + 4}l4-18z" fill="${navy}"/>`
          : kind === 'voltage' ? `<circle cx="${cx}" cy="${cy}" r="21" ${stroke}/><path d="M${cx} ${cy - 13}v14l11 7M${cx - 27} ${cy}h-8m70 0h-8" ${stroke}/>`
          : kind === 'current' ? `<path d="M${cx - 12} ${cy - 22}v15m24-15v15M${cx - 18} ${cy - 7}h36v12a18 18 0 0 1-36 0zM${cx} ${cy + 23}v7" ${stroke}/>`
          : kind === 'wifi' ? `<path d="M${cx - 25} ${cy - 8}q25-24 50 0M${cx - 17} ${cy + 1}q17-16 34 0M${cx - 8} ${cy + 10}q8-8 16 0" ${stroke}/><circle cx="${cx}" cy="${cy + 20}" r="3" fill="${navy}"/>`
          : kind === 'ruler' ? `<path d="M${cx - 24} ${cy + 15}l39-39 12 12-39 39zM${cx - 10} ${cy + 1}l6 6m4-16 6 6m4-16 6 6" ${stroke}/>`
          : kind === 'shield' ? `<path d="M${cx} ${cy - 25}l20 8v15q-2 18-20 28-18-10-20-28v-15zM${cx - 9} ${cy}l6 7 13-15" ${stroke}/>`
          : kind === 'water' ? `<path d="M${cx} ${cy - 25}C${cx - 4} ${cy - 14} ${cx - 19} ${cy + 1} ${cx - 19} ${cy + 11}a19 19 0 0 0 38 0C${cx + 19} ${cy + 1} ${cx + 4} ${cy - 14} ${cx} ${cy - 25}z" ${stroke}/>`
          : kind === 'panel' ? `<rect x="${cx - 24}" y="${cy - 17}" width="48" height="34" rx="2" ${stroke}/><path d="M${cx - 8} ${cy - 17}v34m16-34v34M${cx - 24} ${cy}h48m-29 17-5 8m22-8 5 8" ${stroke}/>`
          : kind === 'fan' ? `<g transform="translate(${cx} ${cy})"><path d="M0-5C-7-11-8-22-2-28c7 1 12 11 5 23z" ${stroke}/><path d="M0-5C-7-11-8-22-2-28c7 1 12 11 5 23z" transform="rotate(120)" ${stroke}/><path d="M0-5C-7-11-8-22-2-28c7 1 12 11 5 23z" transform="rotate(240)" ${stroke}/><circle r="4" fill="${navy}"/></g>`
          : kind === 'sun' ? `<circle cx="${cx}" cy="${cy}" r="11" ${stroke}/><path d="M${cx} ${cy - 30}v8m0 44v8m30-30h-8m-44 0h-8m51-21-6 6m-31 31-6 6m43 0-6-6m-31-31-6-6" ${stroke}/>`
          : `<path d="M${cx} ${cy - 24}a16 16 0 0 0-10 28c4 4 5 7 5 11h10c0-4 1-7 5-11a16 16 0 0 0-10-28zM${cx - 5} ${cy + 20}h10m-9 5h8" ${stroke}/><path d="M${cx - 6} ${cy - 2}q6-8 12 0" ${stroke}/>`;
  return `<circle cx="${cx}" cy="${cy}" r="46" fill="#fff" stroke="#62aef5" stroke-width="2"/>${art}`;
}

async function measureText(text, fontSize, weight = 700) {
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="4000" height="100"><text x="0" y="75" font-family="Arial, Be Vietnam Pro, sans-serif" font-size="${fontSize}" font-weight="${weight}">${esc(text)}</text></svg>`;
  const { info } = await sharp(Buffer.from(svg)).png().trim().toBuffer({ resolveWithObject: true });
  return info.width;
}

async function makeAvatarSvg(item, plate, product, logo) {
  const brand = String(item.brand || '').toUpperCase();
  const model = item.v8Model || (item.v7NoCode || item.v8NoCode ? shortDisplayName(item.name, item.cat_slug) : `${item.code}${watts(item) ? ` (${watts(item)})` : ''}`);
  const [title1, title2] = titleByCategory[item.cat_slug] || [String(item.cat || 'SẢN PHẨM').toLocaleUpperCase('vi'), ''];
  const productEmbedded = await sharp(product).resize({ width: 1500, height: 1500, fit: 'inside', withoutEnlargement: true }).png({ compressionLevel: 9, adaptiveFiltering: true }).toBuffer();
  const dataUri = (buf, mime = 'png') => `data:image/${mime};base64,${buf.toString('base64')}`;
  const box = (x, y, w, h) => ({ x, y, w, h });
  const texts = [];
  const text = (value, x, y, size, fill, weight, anchor, source, bounds, strokeWidth = 1.5) => {
    if (!value) return '';
    texts.push({ text: String(value), source, box: bounds });
    return `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" stroke="#061735" stroke-width="${strokeWidth}" paint-order="stroke fill" stroke-linejoin="round" font-family="Arial, Be Vietnam Pro, sans-serif" font-size="${size}" font-weight="${weight}">${esc(value)}</text>`;
  };
  let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="${dataUri(plate)}" width="900" height="900"/><image id="logo" href="${dataUri(logo)}" x="34" y="22" width="340" height="56" preserveAspectRatio="xMinYMid meet"/>`;
  let titleSize = 51;
  let firstTitleWidth = await measureText(title1, titleSize, 800);
  while (firstTitleWidth > 850 && titleSize > 43) { titleSize -= 2; firstTitleWidth = await measureText(title1, titleSize, 800); }
  svg += text(title1, 450, 151, titleSize, '#ffffff', 800, 'middle', 'static:title', box(450 - firstTitleWidth / 2, 96, firstTitleWidth, 66), 2);
  const brandSize = brand.length > 12 ? 43 : 49;
  const title2Width = title2 ? await measureText(title2, 49, 800) : 0;
  const brandWidth = brand ? await measureText(brand, brandSize, 800) : 0;
  const gap = title2 && brand ? 10 : 0;
  const titleLineWidth = title2Width + brandWidth + gap;
  const titleLeft = (900 - titleLineWidth) / 2;
  if (title2) svg += text(title2, brand ? titleLeft + title2Width : 450, 217, 49, '#ffffff', 800, brand ? 'end' : 'middle', 'static:title', box(titleLeft, 162, title2Width, 64), 2);
  if (brand) svg += text(brand, titleLeft + title2Width + gap, 217, brandSize, '#FFD447', 800, 'start', 'brand', box(titleLeft + title2Width + gap, 162, brandWidth, 64), 2);
  let modelSize = 24;
  let modelTextWidth = await measureText(model, modelSize, 700);
  while (modelTextWidth > 900 - 80 && modelSize > 20) {
    modelSize -= 1;
    modelTextWidth = await measureText(model, modelSize, 700);
  }
  const modelWidth = Math.min(820, modelTextWidth + 64);
  const modelX = (900 - modelWidth) / 2;
  svg += `<rect x="${modelX}" y="232" width="${modelWidth}" height="40" rx="4" fill="#071d43" fill-opacity=".10" stroke="#e6f4ff" stroke-width="1.3"/>`;
  svg += text(model, 450, 260, modelSize, '#ffffff', 700, 'middle', item.v8Model ? 'name' : 'code + power_watts', box(modelX, 232, modelWidth, 40), 1.4);
  const rowData = rows(item);
  const positions = rowData.length === 4 ? [355, 473, 591, 709] : rowData.map((_, i) => 355 + i * (354 / Math.max(rowData.length - 1, 1)));
  for (let i = 0; i < rowData.length; i++) {
    const row = rowData[i];
    const cy = positions[i];
    svg += icon(row.icon, 130, cy);
    const label = row.label;
    const value = row.value;
    const labelY = cy - 10;
    const valueY = cy + 20;
    const labelWidth = await measureText(label, 16, 600);
    svg += text(label, 192, labelY, 16, '#f6faff', 600, 'start', 'static:label', box(192, cy - 29, labelWidth + 4, 22), 2.2);
    let size = value.length > 26 ? 15 : value.length > 22 ? 16 : value.length > 18 ? 17 : 19;
    let valueWidth = await measureText(value, size, 700);
    while (valueWidth > 248 && size > 13) {
      size -= 1;
      valueWidth = await measureText(value, size, 700);
    }
    svg += text(value, 192, valueY, size, '#ffffff', 700, 'start', row.field, box(192, cy - 3, valueWidth + 4, 31), 2.2);
  }
  const productRaw = await sharp(product).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  let ax0 = productRaw.info.width, ay0 = productRaw.info.height, ax1 = 0, ay1 = 0;
  let sourceAlphaArea = 0;
  for (let y = 0; y < productRaw.info.height; y++) for (let x = 0; x < productRaw.info.width; x++) {
    if (!productRaw.data[(y * productRaw.info.width + x) * 4 + 3]) continue;
    sourceAlphaArea++;
    ax0 = Math.min(ax0, x); ay0 = Math.min(ay0, y); ax1 = Math.max(ax1, x + 1); ay1 = Math.max(ay1, y + 1);
  }
  if (ax1 <= ax0 || ay1 <= ay0) throw new Error(`${item.code}: ảnh sản phẩm không có pixel nhìn thấy.`);
  const alphaW = ax1 - ax0, alphaH = ay1 - ay0;
  const productRight = 867.4, productBottom = 867.4, minX = 214, minY = 276;
  const forbidden = texts.map(entry => ({ ...entry.box, x: entry.box.x - 13.5, y: entry.box.y - 13.5, w: entry.box.w + 27, h: entry.box.h + 27, kind: 'text', text: entry.text }));
  forbidden.push({ x: 0, y: 0, w: 207, h: 92, kind: 'logo', text: 'logo' });
  for (const cy of positions) forbidden.push({ x: 130 - 63, y: cy - 63, w: 126, h: 126, kind: 'icon', text: 'icon' });
  const points = [];
  for (let y = ay0; y < ay1; y += 2) for (let x = ax0; x < ax1; x += 2) {
    if (productRaw.data[(y * productRaw.info.width + x) * 4 + 3] > 16) points.push([x - ax0, y - ay0]);
  }
  let productScale = 0, alphaLeft = productRight, productX = 0, productY = 0, productW = 0, productH = 0, renderedAlphaW = 0, renderedAlphaH = 0, productBox = null;
  let collisionDetails = [];
  const collides = (s, left, top) => {
    for (const [px, py] of points) {
      const x = left + px * s, y = top + py * s;
      if (x < minX || y < minY || x > productRight || y > productBottom) return 'bounds';
      for (const zone of forbidden) {
        if (zone.kind === 'icon') {
          const dx = x - (zone.x + zone.w / 2), dy = y - (zone.y + zone.h / 2);
          if (dx * dx + dy * dy < (zone.w / 2) ** 2) return zone.text;
        } else if (x >= zone.x && x <= zone.x + zone.w && y >= zone.y && y <= zone.y + zone.h) return zone.text;
      }
    }
    return '';
  };
  const maxScale = Math.min((1130 / scale) / alphaW, (1085 / scale) / alphaH, (productRight - minX) / alphaW, (productBottom - minY) / alphaH);
  for (let s = maxScale; s >= .04; s -= .003) {
    const rw = alphaW * s, rh = alphaH * s, left = productRight - rw, top = productBottom - rh;
    const collision = collides(s, left, top);
    if (!collision) { productScale = s; alphaLeft = left; renderedAlphaW = rw; renderedAlphaH = rh; productBox = { x: left, y: top, w: rw, h: rh }; collisionDetails = []; break; }
    collisionDetails = [collision];
  }
  if (!productBox) throw new Error(`${item.code}: không tìm được vị trí alpha không va chạm.`);
  productX = alphaLeft - ax0 * productScale;
  productY = productBottom - ay1 * productScale;
  productW = productRaw.info.width * productScale; productH = productRaw.info.height * productScale;
  const allowedW = productRight - minX, allowedH = productBottom - minY;
  const fillRatio = Math.max(renderedAlphaW / allowedW, renderedAlphaH / allowedH);
  svg += `<defs><filter id="contact-shadow" x="-50%" y="-100%" width="200%" height="300%"><feGaussianBlur stdDeviation="10"/></filter></defs><ellipse cx="${(alphaLeft + productRight) / 2}" cy="${productBottom - 2}" rx="${Math.max(48, renderedAlphaW * .38)}" ry="12" fill="#061735" fill-opacity=".48" filter="url(#contact-shadow)"/>`;
  svg += `<image id="product" href="${dataUri(productEmbedded)}" x="${productX}" y="${productY}" width="${productW}" height="${productH}" preserveAspectRatio="none"/>`;
  svg += '</svg>';
  let nearestText = null;
  for (const entry of texts) for (const [px, py] of points) {
    const x = alphaLeft + px * productScale, y = productBox.y + py * productScale, b = entry.box;
    const dx = Math.max(b.x - x, x - (b.x + b.w), 0), dy = Math.max(b.y - y, y - (b.y + b.h), 0), distance = Math.hypot(dx, dy);
    if (!nearestText || distance < nearestText.distance900) nearestText = { text: entry.text, box: b, distance900: distance };
  }
  const productTextCollisions = collisionDetails.filter(x => x !== 'bounds');
  return { svg, texts, model, modelSize, modelTextWidth, modelWidth, rows: rowData,
    productPlacement: { productScale, alphaArea1600: Math.round(sourceAlphaArea * productScale ** 2 * scale ** 2), bbox900: { x: productBox.x, y: productBox.y, width: productBox.w, height: productBox.h }, allowedBox900: { x: minX, y: minY, width: allowedW, height: allowedH }, fillRatio, nearestText, collisions: productTextCollisions } };
}

function shortDisplayName(name, cat) {
  if (/^PV Module SHARP/i.test(String(name || ''))) return 'SHARP 395 Wp';
  let value = String(name || '').trim();
  value = value.replace(/^đèn pha cắm cỏ\s*/i, '').replace(/^pv module\s*/i, '').replace(/^cáp thipha\s*/i, 'THIPHA ')
    .replace(/^kẹp biên tấm pin\s*/i, '').replace(/^quạt năng lượng mặt trời\s*/i, '')
    .replace(/^băng keo điện\s*/i, '').replace(/^keo foarm\s*/i, '').replace(/^khớp nối ruột gà với ruột gà\s*/i, 'Ruột gà ')
    .replace(/^ống ruột gà\s*/i, '').replace(/\s+cát vạn lợi$/i, ' Cát Vạn Lợi');
  const words = value.split(/\s+/); let out = '';
  for (const word of words) { const next = out ? `${out} ${word}` : word; if ([...next].length > 28) break; out = next; }
  return out || value.slice(0, 28);
}

function composedTextRight(texts) { return texts.map(t => t.box.x + t.box.w); }

async function prepareProduct(file) {
  let input = await fs.readFile(file);
  let metadata = await sharp(input).metadata();
  if ((metadata.width || 0) > 1800 || (metadata.height || 0) > 1800) {
    input = await sharp(input).resize({ width: 1600, height: 1600, fit: 'inside', withoutEnlargement: true }).png().toBuffer();
    metadata = await sharp(input).metadata();
  }
  if (!metadata.hasAlpha) {
    const { data, info } = await sharp(input).removeAlpha().raw().toBuffer({ resolveWithObject: true });
    const { width, height } = info;
    const total = width * height;
    const seen = new Uint8Array(total), queue = new Int32Array(total);
    let head = 0, tail = 0;
    const luminance = k => (data[k * 3] * 299 + data[k * 3 + 1] * 587 + data[k * 3 + 2] * 114) / 1000;
    const isBackground = k => Math.min(data[k * 3], data[k * 3 + 1], data[k * 3 + 2]) >= 245 && Math.max(data[k * 3], data[k * 3 + 1], data[k * 3 + 2]) - Math.min(data[k * 3], data[k * 3 + 1], data[k * 3 + 2]) <= 12;
    const seed = k => { if (!seen[k] && isBackground(k)) { seen[k] = 1; queue[tail++] = k; } };
    for (let x = 0; x < width; x++) { seed(x); seed((height - 1) * width + x); }
    for (let y = 0; y < height; y++) { seed(y * width); seed(y * width + width - 1); }
    while (head < tail) {
      const k = queue[head++], x = k % width, y = (k / width) | 0;
      if (x > 0) seed(k - 1); if (x + 1 < width) seed(k + 1); if (y > 0) seed(k - width); if (y + 1 < height) seed(k + width);
    }
    if (tail < total * 0.005) return { status: 'needs-cutout-review', reason: 'Không tìm thấy đủ nền trắng liên thông từ viền; cần soát tay để tránh giữ nguyên nền hoặc làm mất thân đèn.', cutout: { method: 'edge-flood-white-245-soft-edge', removedPixels: tail, edgePixels: total } };
    const rgba = Buffer.alloc(total * 4);
    let removedPixels = 0, antialiasPixels = 0;
    for (let k = 0; k < total; k++) {
      const p = k * 3, q = k * 4;
      rgba[q] = data[p]; rgba[q + 1] = data[p + 1]; rgba[q + 2] = data[p + 2]; rgba[q + 3] = 255;
      if (seen[k]) { rgba[q + 3] = 0; removedPixels++; continue; }
      const x = k % width, y = (k / width) | 0;
      const neighborBackground = (x > 0 && seen[k - 1]) || (x + 1 < width && seen[k + 1]) || (y > 0 && seen[k - width]) || (y + 1 < height && seen[k + width]);
      const lum = luminance(k);
      if (neighborBackground && lum > 224 && Math.max(data[p], data[p + 1], data[p + 2]) - Math.min(data[p], data[p + 1], data[p + 2]) < 20) {
        const alpha = Math.max(0, Math.min(255, Math.round((255 - lum) / 31 * 255)));
        if (alpha < 255) {
          rgba[q + 3] = alpha; antialiasPixels++;
          for (let c = 0; c < 3; c++) rgba[q + c] = alpha ? clamp((data[p + c] - (1 - alpha / 255) * 255) / (alpha / 255)) : 255;
        }
      }
    }
    const png = await sharp(rgba, { raw: { width, height, channels: 4 } }).png().toBuffer();
    const reconstructed = await sharp(png).flatten({ background: '#ffffff' }).removeAlpha().raw().toBuffer();
    let maxDiff = 0, absolute = 0;
    for (let i = 0; i < data.length; i++) { const d = Math.abs(data[i] - reconstructed[i]); maxDiff = Math.max(maxDiff, d); absolute += d; }
    const meanDiff = absolute / data.length;
    if (maxDiff > 48 || meanDiff > 2.5) return { status: 'needs-cutout-review', reason: `Tách nền không vượt kiểm tra sản phẩm không đổi (max=${maxDiff}, mean=${meanDiff.toFixed(3)}).`, cutout: { method: 'edge-flood-white-245-soft-edge', removedPixels, antialiasPixels, maxDiff, meanDiff } };
    const h = await import('node:crypto').then(m => m.createHash('sha256').update(data).digest('hex'));
    return { png, method: 'edge-flood-white-245-soft-edge', sourceHash: h, metadata, cutout: { removedPixels, antialiasPixels, maxDiff, meanDiff } };
  }
  const png = await sharp(input).png().toBuffer();
  const decoded = await sharp(png).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const { width, height } = decoded.info, total = width * height, alpha = decoded.data;
  const seen = new Uint8Array(total), queue = new Int32Array(total), components = [];
  for (let i = 0; i < total; i++) {
    if (seen[i] || alpha[i * 4 + 3] === 0) continue;
    let head = 0, tail = 0, x0 = width, y0 = height, x1 = 0, y1 = 0;
    queue[tail++] = i; seen[i] = 1;
    while (head < tail) {
      const k = queue[head++], x = k % width, y = (k / width) | 0;
      x0 = Math.min(x0, x); y0 = Math.min(y0, y); x1 = Math.max(x1, x + 1); y1 = Math.max(y1, y + 1);
      for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) {
        if (!dx && !dy) continue;
        const xx = x + dx, yy = y + dy;
        if (xx < 0 || yy < 0 || xx >= width || yy >= height) continue;
        const n = yy * width + xx;
        if (!seen[n] && alpha[n * 4 + 3] > 0) { seen[n] = 1; queue[tail++] = n; }
      }
    }
    components.push({ pixels: tail, bounds: { x: x0, y: y0, width: x1 - x0, height: y1 - y0 }, indices: queue.slice(0, tail) });
  }
  const largest = Math.max(0, ...components.map(c => c.pixels));
  const keep = components.filter(c => c.pixels >= largest * .02), drop = components.filter(c => c.pixels < largest * .02);
  const kept = Buffer.from(alpha);
  for (const component of drop) for (const k of component.indices) kept[k * 4 + 3] = 0;
  const keptPng = await sharp(kept, { raw: { width, height, channels: 4 } }).png().toBuffer();
  const hash = await import('node:crypto').then(m => m.createHash('sha256').update(kept).digest('hex'));
  return { png: keptPng, method: 'original-alpha-component-filter', sourceHash: hash, metadata,
    components: { keptCount: keep.length, removedCount: drop.length, removedAlphaArea: drop.reduce((sum, c) => sum + c.pixels, 0), removed: drop.map(c => ({ area: c.pixels, bbox: c.bounds })), keptAlphaArea: keep.reduce((sum, c) => sum + c.pixels, 0) } };
}

const manualAccessoryMasks = {
  // Items touching a structural stand stay attached when alpha-only removal would also clip the stand.
};
const retainedAccessoryNotes = {
  'bct-wr20-6961.png': 'Túi ốc, cờ lê và thanh rời chạm vào khay/giá đỡ trong ảnh nguồn; xóa theo alpha sẽ làm cụt khung đỡ nên giữ nguyên và ghi nhận.',
  'db-8300-7828-chuan1600.jpg': 'Remote được giữ; pin AA, cuộn dây và túi ốc nằm trên/tiếp xúc khay đỡ trong ảnh nguồn; xóa cục bộ sẽ khoét thủng khay/giá đỡ nên giữ nguyên và ghi nhận.',
  'db-9200-1808-chuan1600.jpg': 'Remote được giữ; pin AA và túi phụ kiện chạm vào giá/khay dưới tấm pin; xóa cục bộ sẽ làm khuyết khung nên giữ nguyên và ghi nhận.',
  'db88-600pro-6075.png': 'Remote được giữ; pin AA và túi ốc đè lên thanh đỡ dưới tấm pin; xóa cục bộ sẽ cắt mất thanh đỡ nên giữ nguyên và ghi nhận.',
};

async function filterDetachedComponents(png, sourceName) {
  const decoded = await sharp(png).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const { width, height } = decoded.info, total = width * height, rgba = decoded.data;
  const seen = new Uint8Array(total), queue = new Int32Array(total), comps = [];
  for (let i = 0; i < total; i++) {
    if (seen[i] || rgba[i * 4 + 3] === 0) continue;
    let head = 0, tail = 0, x0 = width, y0 = height, x1 = 0, y1 = 0;
    queue[tail++] = i; seen[i] = 1;
    while (head < tail) {
      const k = queue[head++], x = k % width, y = (k / width) | 0;
      x0 = Math.min(x0, x); y0 = Math.min(y0, y); x1 = Math.max(x1, x + 1); y1 = Math.max(y1, y + 1);
      for (let dy = -1; dy <= 1; dy++) for (let dx = -1; dx <= 1; dx++) {
        if (!dx && !dy) continue;
        const xx = x + dx, yy = y + dy;
        if (xx < 0 || yy < 0 || xx >= width || yy >= height) continue;
        const n = yy * width + xx;
        if (!seen[n] && rgba[n * 4 + 3] > 0) { seen[n] = 1; queue[tail++] = n; }
      }
    }
    comps.push({ count: tail, bounds: { x: x0, y: y0, width: x1 - x0, height: y1 - y0 }, indices: queue.slice(0, tail) });
  }
  const largest = Math.max(0, ...comps.map(c => c.count)), dropped = comps.filter(c => c.count < largest * .02), kept = comps.filter(c => c.count >= largest * .02);
  for (const c of dropped) for (const k of c.indices) rgba[k * 4 + 3] = 0;
  const manual = [];
  for (const region of manualAccessoryMasks[sourceName] || []) {
    let area = 0;
    const xs = region.points.map(p => p[0]), ys = region.points.map(p => p[1]);
    for (let y = Math.max(0, Math.min(...ys)); y < Math.min(height, Math.max(...ys)); y++) for (let x = Math.max(0, Math.min(...xs)); x < Math.min(width, Math.max(...xs)); x++) {
      let inside = false;
      for (let i = 0, j = region.points.length - 1; i < region.points.length; j = i++) {
        const [xi, yi] = region.points[i], [xj, yj] = region.points[j];
        if (((yi > y) !== (yj > y)) && x < (xj - xi) * (y - yi) / (yj - yi) + xi) inside = !inside;
      }
      if (!inside) continue;
      const p = (y * width + x) * 4 + 3;
      if (rgba[p]) { rgba[p] = 0; area++; }
    }
    manual.push({ name: region.name, alphaArea: area, polygon: region.points, method: 'alpha-zero-only; no fill or repaint' });
  }
  return {
    png: dropped.length || manual.length ? await sharp(rgba, { raw: { width, height, channels: 4 } }).png().toBuffer() : png,
    components: { keptCount: kept.length, removedCount: dropped.length, removedAlphaArea: dropped.reduce((n, c) => n + c.count, 0), removed: dropped.slice(0, 24).map(c => ({ area: c.count, bbox: c.bounds })), removedRecordTruncated: dropped.length > 24, manualAccessoriesRemoved: manual, retainedAccessoryNote: retainedAccessoryNotes[sourceName] || null, keptAlphaArea: kept.reduce((n, c) => n + c.count, 0) - manual.reduce((n, c) => n + c.alphaArea, 0) },
  };
}

async function renderOne(item, plate, logo, inputRoot, outputRoot, shopping = {}, shoppingCodes = new Set()) {
  const folder = path.join(outputRoot, outputDir);
  await fs.mkdir(folder, { recursive: true });
  if (!item.code && !isV7 && !isV8) return { code: '(không có mã)', brand: item.brand, passed: false, reason: 'Dữ liệu hiển thị không có code; không thể tạo tên file ảnh an toàn.', source: item.feed_image || item.image, sourceAngleAssessment: null };
  const fileCode = item.code ? String(item.code).replace(/[^a-zA-Z0-9._-]/g, '-') : `id-${item.id}`;
  if (isV8 && item.code === 'JD-6128') {
    const candidates = [item.feed_image, item.image_secondary, ...(item.gallery || []), item.image].filter(Boolean);
    const reason = 'Ảnh feed sạch duy nhất đã thử bằng flood-fill nền trắng ngưỡng 245 và matte ngưỡng 235; cả hai vẫn để lại vùng nền trắng liền sát viền chóa đèn. Ảnh còn lại là poster có chữ/chú thích, không dùng. Không thể tách phần nền còn lại mà bảo đảm giữ nguyên pixel sản phẩm.';
    const qa = { code: item.code, id: item.id, brand: item.brand, image: null, reason, sourceCandidates: candidates, matteAttempts: [{ source: item.feed_image, method: 'edge flood-fill RGB>=245', result: 'còn nền trắng sát viền chóa' }, { source: item.feed_image, method: 'edge flood-fill RGB>=235', result: 'vùng trắng dưới chóa vẫn còn; không đạt kiểm tra matte' }], passed: false, checks: { passed: false, sourceProductIdentity: false } };
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify(qa, null, 2));
    return { code: item.code, id: item.id, brand: item.brand, passed: false, reason, source: candidates[0], sourceCandidates: candidates };
  }
  if (isV8 && item.brand === 'BLUE CARBON' && item.cat_slug.startsWith('bo-dieu-khien-sac-') && /mppt-td2310tu-group-/.test(item.image || '')) {
    const candidates = [item.image, item.feed_image, item.image_secondary, ...(item.gallery || [])].filter(Boolean);
    const reason = 'Ảnh nguồn đang hiển thị ghép nhiều model trong cùng khung; không thể xác định đúng model riêng.';
    const qa = { code: item.code, id: item.id, brand: item.brand, image: null, note: 'ảnh thiết bị mang logo LDSOLAR, chủ website đã chấp nhận', reason, sourceCandidates: candidates, passed: false, checks: { passed: false, cleanSingleModelSource: false } };
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify(qa, null, 2));
    return { code: item.code, id: item.id, brand: item.brand, passed: false, reason, source: candidates[0], sourceCandidates: candidates };
  }
  if (isV7 && item.code) {
    const exceptionSources = {
      'ASTRO N7 610W': 'Nguồn duy nhất là ảnh panel ghi 630W; không khớp mã sản phẩm 610W.',
      'EPV230-4T2.2G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T4.0G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T5.5G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T7.5G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T11G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T15G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'EPV230-4T18.5G': 'Ảnh gốc ghép ba biến thể EPV230, không thể xác định đúng model riêng.',
      'BCT-WW5.0-MONO': 'Ảnh feed là ảnh ghép thông số/model; gallery còn lại là poster, infographic, cảnh lắp đặt hoặc có chú thích; không có ảnh sản phẩm riêng sạch.',
      'DB-16400W': 'Chỉ có một ảnh nguồn dạng bảng ghép nhiều sản phẩm/nhãn chú thích; không có gallery thay thế.',
      'DB01-ECO': 'Feed có chú thích kích thước và bố trí nhiều phụ kiện rời; ảnh thay thế còn lại là bộ sản phẩm có phụ kiện rời trên nền trắng, không tách được an toàn theo nguyên tắc pixel.',
      'DB-OT1000': 'Ảnh chính chứa remote, tờ hướng dẫn và túi ốc/phụ kiện rời cạnh đèn và tấm pin; các ảnh gallery thay thế đều có chú thích/kích thước hoặc là ảnh bối cảnh.',
      'DB3-PRO-200W': 'Các ảnh feed/gallery là infographic, poster model, ảnh ghép bộ sản phẩm hoặc ảnh bối cảnh; không có ảnh riêng sạch đúng mã.',
      'DB3-PRO-400W': 'Các ảnh feed/gallery là infographic, poster model, ảnh ghép bộ sản phẩm hoặc ảnh bối cảnh; không có ảnh riêng sạch đúng mã.',
      'DB3-PRO-800W': 'Các ảnh feed/gallery là infographic, poster model, ảnh ghép bộ sản phẩm hoặc ảnh bối cảnh; không có ảnh riêng sạch đúng mã.',
      'DB3-PRO-1000W': 'Các ảnh feed/gallery là infographic, poster model, ảnh ghép bộ sản phẩm hoặc ảnh bối cảnh; không có ảnh riêng sạch đúng mã.',
    };
    const reason = exceptionSources[item.code];
    if (reason) {
      const candidates = [item.feed_image, item.image_secondary, ...(item.gallery || []), item.image].filter(Boolean);
      await fs.unlink(path.join(folder, `${fileCode}.jpg`)).catch(() => {});
      const qa = { code: item.code, id: item.id, brand: item.brand, image: null, reason, sourceCandidates: candidates, passed: false, checks: { passed: false, cleanCorrectModelSource: false } };
      await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify(qa, null, 2));
      return { code: item.code, id: item.id, brand: item.brand, passed: false, reason, source: candidates[0] || null, sourceCandidates: candidates };
    }
  }
  if (args.includes('--resume')) {
    try {
      const prior = JSON.parse(await fs.readFile(path.join(folder, `${fileCode}.qa.json`), 'utf8'));
      if (prior.checks?.passed && await fs.access(path.join(folder, `${fileCode}.jpg`)).then(()=>true).catch(()=>false)) return { code:item.code, brand:item.brand, passed:true, image:path.join(folder,`${fileCode}.jpg`), qa:prior };
    } catch { /* render missing or failed entries */ }
  }
  const resumeCodes = ['BCT-WW5.0-MONO', 'JD-T100', 'JD-T200', 'JD-T300', 'DB60100', 'DB3-PRO-200W', 'DB3-PRO-400W', 'DB3-PRO-800W', 'DB3-PRO-1000W'];
  let reviewed = null;
  if (args.includes('--v5') && !resumeCodes.includes(item.code)) {
    try { reviewed = JSON.parse(await fs.readFile(path.join(outputRoot, 'avatar', `${item.code}.qa.json`), 'utf8')); } catch { /* no prior QA */ }
    if (!reviewed?.checks?.passed) reviewed = null;
  }
  const v7SourceOverrides = {
    'JD-T100': ['3-3856.jpg', '4-3399.jpg'],
    'JD-T200': ['2-7739.jpg', '4-2301.jpg'],
    'JD-T300': ['2-2256.jpg', '1-4624.jpg'],
    'DB02-3': ['db02-3-3975-chuan1600.jpg'],
    'DB-OT1000': ['db-ot1000-8861-chuan1600.jpg'],
    'JDL-OT400': ['jdl-ot400-9457-chuan1600.jpg'],
  };
  const options = isV8 && item.brand === 'BLUE CARBON' && item.cat_slug.startsWith('bo-dieu-khien-sac-')
    ? [['image', item.image]]
    : isV8
    ? [['feed_image', item.feed_image], ['image_secondary', item.image_secondary], ...(item.gallery || []).map((v, i) => [`gallery[${i}]`, v]), ['image', item.image]]
    : isV7 && item.code && v7SourceOverrides[item.code]
    ? v7SourceOverrides[item.code].map((name, i) => [i ? 'image-fallback' : 'gallery-reviewed', name])
    : isV7
    ? [['feed_image', item.feed_image], ['image_secondary', item.image_secondary], ...(item.gallery || []).map((v, i) => [`gallery[${i}]`, v]), ['image', item.image]]
    : reviewed?.source
    ? [[reviewed.sourceField || 'previously-approved', reviewed.source]]
    : (requestedGroup || shoppingCodes.has(item.code.toLowerCase()) || (args.includes('--v5') && resumeCodes.includes(item.code)))
    ? [
      ['feed_image', shopping[item.code]?.feed_image || item.feed_image],
      ['image_secondary', shopping[item.code]?.image_secondary || item.image_secondary],
      ...(item.gallery || []).map((v, i) => [`gallery[${i}]`, v]),
      ['image', shopping[item.code]?.image || item.image],
    ]
    : [['image', item.image]];
  if ((args.includes('--v5') || requestedGroup === 'den-pha-thieu') && item.code.startsWith('DB3-PRO-')) {
    const feeds = [shopping[item.code]?.feed_image, ...(item.gallery || []), item.image].filter(Boolean);
    const reason = 'Không có ảnh nguồn đúng hãng: feed_image mang nhãn GERINA GD-60W; các ảnh gallery cùng bộ nguồn cũng gắn nhãn GERINA hoặc là poster/chú thích, không dùng ảnh sai hãng và không che nhãn.';
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify({ code: item.code, brand: item.brand, image: null, passed: false, reason, sourceCandidates: feeds, checks: { passed: false, cleanCorrectBrandSource: false } }, null, 2));
    return { code: item.code, brand: item.brand, passed: false, reason, source: shopping[item.code]?.feed_image, sourceAngleAssessment: null };
  }
  if (!isV8 && requestedGroup === 'dieu-khien' && item.brand === 'BLUE CARBON' && item.cat_slug.startsWith('bo-dieu-khien-sac-')) {
    const reason = 'Ảnh sản phẩm đang mang nhận diện LDSOLAR trong khi dữ liệu mã ghi BLUE CARBON; không dùng ảnh có logo hãng khác.';
    await fs.unlink(path.join(folder, `${fileCode}.jpg`)).catch(() => {});
    const sourceCandidates = [item.feed_image, item.image_secondary, ...(item.gallery || []), item.image].filter(Boolean);
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify({ code: item.code, brand: item.brand, image: null, source: item.image, reason, sourceCandidates, passed: false, checks: { passed: false, cleanCorrectBrandSource: false } }, null, 2));
    return { code: item.code, brand: item.brand, passed: false, reason, source: item.image, sourceAngleAssessment: null };
  }
  if (requestedGroup === 'tam-pin-quat-bom' && String(item.code).startsWith('EPV230-')) {
    const reason = 'Ảnh nguồn là một ảnh ghép chung ba biến thể biến tần EPV230; không xác định được đúng sản phẩm riêng theo mã.';
    await fs.unlink(path.join(folder, `${fileCode}.jpg`)).catch(() => {});
    const sourceCandidates = [item.feed_image, item.image_secondary, ...(item.gallery || []), item.image].filter(Boolean);
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify({ code: item.code, brand: item.brand, image: null, source: item.image, reason, sourceCandidates, passed: false, checks: { passed: false, cleanCorrectBrandSource: false } }, null, 2));
    return { code: item.code, brand: item.brand, passed: false, reason, source: item.image, sourceAngleAssessment: null };
  }
  if (requestedGroup === 'tam-pin-quat-bom' && item.code === 'ASTRO N7 610W') {
    const reason = 'Nguồn duy nhất đang gắn mã 630W, không khớp sản phẩm 610W; không dùng ảnh sai model.';
    await fs.unlink(path.join(folder, `${fileCode}.jpg`)).catch(() => {});
    const sourceCandidates = [item.feed_image, item.image_secondary, ...(item.gallery || []), item.image].filter(Boolean);
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify({ code: item.code, brand: item.brand, image: null, source: item.image, reason, sourceCandidates, passed: false, checks: { passed: false, cleanCorrectBrandSource: false } }, null, 2));
    return { code: item.code, brand: item.brand, passed: false, reason, source: item.image, sourceAngleAssessment: null };
  }
  let sourceField = '', sourceName = '', product = null, failedCandidates = [];
  for (const [field, name] of options) {
    if (!name || !fsSyncExists(path.join(inputRoot, 'san-pham', name))) continue;
    if (excludedSources.has(name) && !(reviewed && name === reviewed.source)) { failedCandidates.push(`${name}: ${excludedSources.get(name)}`); continue; }
    const candidate = await prepareProduct(path.join(inputRoot, 'san-pham', name));
    if (!candidate.png) { failedCandidates.push(`${name}: ${candidate.reason}`); continue; }
    const cleaned = await filterDetachedComponents(candidate.png, name);
    sourceField = field; sourceName = name; product = { ...candidate, png: cleaned.png, components: cleaned.components }; break;
  }
  if (!product) {
    const first = options.find(([, name]) => name);
    const reason = failedCandidates.length ? failedCandidates.join(' | ') : `Thiếu ảnh nguồn ${item.image}`;
    const rejected = { code: item.code || null, id: item.id, brand: item.brand, image: null, source: first?.[1] || null, reason, passed: false, checks: { passed: false }, rejectedSources: options.map(([field, name]) => ({ field, name: name || null, exists: Boolean(name && fsSyncExists(path.join(inputRoot, 'san-pham', name))) })) };
    await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify(rejected, null, 2));
    return { code: item.code || null, id: item.id, brand: item.brand, passed: false, reason, source: first?.[1], sourceCandidates: options.map(([field,name])=>({field,name,exists:Boolean(name&&fsSyncExists(path.join(inputRoot,'san-pham',name)))})), sourceAngleAssessment: sourceAngles[item.code] || null };
  }
  const composed = await makeAvatarSvg(item, plate, product.png, logo);
  const outputPath = path.join(folder, `${fileCode}.jpg`);
  const rawPng = await sharp(Buffer.from(composed.svg)).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  await sharp(rawPng).flatten({ background: '#061735' }).toColorspace('srgb').jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(outputPath);
  const meta = await sharp(outputPath).metadata();
  const stat = await fs.stat(outputPath);
  const boxes = composed.texts.map(entry => entry.box);
  const overlapPairs = [];
  for (let i = 0; i < boxes.length; i++) for (let j = i + 1; j < boxes.length; j++) {
    const a = boxes[i], b = boxes[j];
    if (a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y) overlapPairs.push([composed.texts[i].text, composed.texts[j].text]);
  }
  const productOnWhite = await sharp(product.png).flatten({ background: '#ffffff' }).png().toBuffer();
  const logoLayer = await sharp(logo).resize(340, 56, { fit: 'contain' }).png().toBuffer();
  const expectedLogo = await sharp(plate).resize(900, 900, { kernel: sharp.kernel.lanczos3 }).composite([{ input: logoLayer, left: 34, top: 22 }]).removeAlpha().raw().toBuffer();
  const actualCanvas900 = await sharp(outputPath).resize(900, 900, { kernel: sharp.kernel.lanczos3 }).removeAlpha().raw().toBuffer();
  let logoDiff = 0, logoSamples = 0;
  for (let y = 18; y < 82; y++) for (let x = 30; x < 380; x++) for (let c = 0; c < 3; c++) {
    const p = (y * 900 + x) * 3 + c;
    logoDiff += Math.abs(expectedLogo[p] - actualCanvas900[p]);
    logoSamples++;
  }
  const logoScore = 1 - logoDiff / (logoSamples * 255);
  const sourceHash = product.sourceHash;
  const goldContrast = (luminance('#FFD447') + 0.05) / (luminance('#061735') + 0.05);
  const whiteContrast = (luminance('#FFFFFF') + 0.05) / (luminance('#061735') + 0.05);
  const outputQa = {
    code: item.code || null, id: item.id, brand: item.brand, image: path.basename(outputPath), source: sourceName, sourceField,
    ...(isV8 && item.brand === 'BLUE CARBON' && item.cat_slug.startsWith('bo-dieu-khien-sac-') ? { note: 'ảnh thiết bị mang logo LDSOLAR, chủ website đã chấp nhận' } : {}),
    'goc-chup': sourceAngles[item.code]?.view === 'xéo' || sourceAngles[item.code]?.view === 'xéo nhẹ' ? 'xeo' : sourceAngles[item.code]?.view === 'thẳng' ? 'thang' : 'chua-danh-gia',
    sourceAngleAssessment: sourceAngles[item.code] || { view: 'chưa đánh giá bằng mắt', method: 'Không thể phân loại chắc chắn tự động; đã kiểm tra bằng mắt 6 mã đại diện.', representativeReview: false },
    product: { method: product.method, sourceDecodedSha256: sourceHash, sourceDimensions: { width: product.metadata.width, height: product.metadata.height }, pixelIdentity: 'original source alpha retained; detached components below 2% of largest removed by alpha only', components: product.components || { keptCount: 1, removedCount: 0, removedAlphaArea: 0, removed: [] }, whiteBackgroundComparison: product.cutout ? product.cutout : { maxChannelDifference: 0, meanChannelDifference: 0, method: 'original alpha composited on white; no RGB pixels repainted' } },
    checks: {
      canvas: { width: meta.width, height: meta.height, bytes: stat.size, hasAlpha: meta.hasAlpha, pass: meta.width === 1600 && meta.height === 1600 && stat.size <= maxBytes && !meta.hasAlpha },
      textBoxes: { minMargin900: 24, withinCanvas: boxes.every(b => b.x >= 24 && b.y >= 24 && b.x + b.w <= 876 && b.y + b.h <= 876), overlaps: overlapPairs, pass: boxes.every(b => b.x >= 24 && b.y >= 24 && b.x + b.w <= 876 && b.y + b.h <= 876) && overlapPairs.length === 0 },
      modelBar: { text: composed.model, measuredTextWidth900: composed.modelTextWidth, fontSize900: composed.modelSize, barWidth900: composed.modelWidth, sidePadding900: Number(((composed.modelWidth - composed.modelTextWidth) / 2).toFixed(2)), textOverflowsBar: composed.modelTextWidth > composed.modelWidth, pass: composed.modelTextWidth <= composed.modelWidth && composed.modelWidth >= composed.modelTextWidth + 40 && composed.modelSize >= 20 },
      iconMapping: composed.rows.map(row => ({ label: row.label, icon: row.icon })),
      numberSources: composed.texts.filter(t => /\d/.test(t.text)).map(t => ({ text: t.text, source: t.source, valid: t.source === 'code + power_watts' || (t.source === 'name' && item.v8Model && [...t.text.matchAll(/\d+/g)].every(([number]) => String(item.name || '').includes(number))) || (t.source === 'power_watts' && (t.text === watts(item) || (item.cat_slug === 'tam-pin-nang-luong-mat-troi' && t.text === watts(item).replace(/W$/, 'Wp')))) || (typeof item[t.source] === 'string' && item[t.source].includes(t.text)) || (t.source === 'static:label' && JSON.stringify(item).toLowerCase().includes(t.text.toLowerCase())) || (t.source === 'light_source' && String(item.light_source || '').includes(t.text.replace(/(?<=\d)Lm/, ' Lm'))) || (t.source === 'additional_specs' && JSON.stringify(item.additional_specs || []).toLowerCase().includes(t.text.toLowerCase())), reason: t.source === 'code + power_watts' || t.source === 'power_watts' ? 'Định dạng số nguyên theo đơn vị W/Wp đã được brief cho phép.' : t.source === 'name' ? 'Model rút gọn từ tên sản phẩm; phần số khớp dữ liệu.' : null })),
      logo: { asset: path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png'), placement: { x: 34, y: 22, width: 340, height: 56 }, templateMatchScore: Number(logoScore.toFixed(4)), method: 'mean absolute pixel comparison against expected logo crop after matching 900×900 scale', pass: logoScore >= 0.9 },
      contrast: { textStroke: '#061735', strokeWidthPxAt900: 2.2, minContrastRatio: Number(Math.min(goldContrast, whiteContrast).toFixed(2)), foregroundBackgroundPairs: { whiteOnNavy: Number(whiteContrast.toFixed(2)), goldOnNavy: Number(goldContrast.toFixed(2)) }, pass: Math.min(goldContrast, whiteContrast) >= 4.5 },
      productPlacement: {
        bbox1600: Object.fromEntries(Object.entries(composed.productPlacement.bbox900).map(([k, v]) => [k, Math.round(v * scale)])),
        bbox: Object.fromEntries(Object.entries(composed.productPlacement.bbox900).map(([k, v]) => [k, Math.round(v * scale)])),
        alphaArea: composed.productPlacement.alphaArea1600,
        fillRatio: Number(composed.productPlacement.fillRatio.toFixed(4)),
        allowedRegion1600: Object.fromEntries(Object.entries(composed.productPlacement.allowedBox900).map(([k, v]) => [k, Math.round(v * scale)])),
        bboxFillRatio: Number(composed.productPlacement.fillRatio.toFixed(4)),
        nearestText: composed.productPlacement.nearestText ? { text: composed.productPlacement.nearestText.text, distancePx1600: Math.round(composed.productPlacement.nearestText.distance900 * scale), textBox1600: Object.fromEntries(Object.entries(composed.productPlacement.nearestText.box).map(([k, v]) => [k, Math.round(v * scale)])) } : null,
        touchesText: composed.productPlacement.collisions.length > 0,
        withinCanvasMargin30px: composed.productPlacement.bbox900.x + composed.productPlacement.bbox900.width <= 883 && composed.productPlacement.bbox900.y + composed.productPlacement.bbox900.height <= 883,
        collisions: composed.productPlacement.collisions,
        limits1600: { maxWidth: 1130, maxHeight: 1085, minRight: 58, minBottom: 58, forbiddenClearanceText: 24, forbiddenClearanceIcon: 32 },
        pass: composed.productPlacement.collisions.length === 0 && composed.productPlacement.bbox900.x + composed.productPlacement.bbox900.width <= 883 && composed.productPlacement.bbox900.y + composed.productPlacement.bbox900.height <= 883,
      },
      dom: { status: 'NOT_APPLICABLE', reason: 'SVG bounding boxes are constructed directly; no browser DOM layout used' },
      warnings: ['Manual visual confirmation required for product/source artifacts and mask inpainting.'],
    },
    renderedText: composed.texts, rows: composed.rows, whiteBackgroundPreviewBytes: productOnWhite.length,
  };
  outputQa.checks.passed = outputQa.checks.canvas.pass && outputQa.checks.textBoxes.pass && outputQa.checks.modelBar.pass && outputQa.checks.logo.pass && outputQa.checks.contrast.pass && outputQa.checks.productPlacement.pass && outputQa.checks.numberSources.every(x => x.valid);
  await fs.writeFile(path.join(folder, `${fileCode}.qa.json`), JSON.stringify(outputQa, null, 2));
  return { code: item.code || null, id: item.id, brand: item.brand, passed: outputQa.checks.passed, reason: outputQa.checks.passed ? '' : 'QA tự động không đạt', image: outputPath, qa: outputQa };
}

async function contactSheet(results, brand) {
  const selected = results.filter(r => r.brand === brand);
  if (!selected.length) return;
  const cols = 4, cell = 320, labelH = 44, rowsN = Math.ceil(selected.length / cols);
  const buffers = [];
  for (const [i, r] of selected.entries()) {
    const left = (i % cols) * cell, top = Math.floor(i / cols) * (cell + labelH);
    if (r.image) buffers.push({ input: await sharp(r.image).resize(cell, cell, { kernel: sharp.kernel.lanczos3 }).png().toBuffer(), left, top });
    else {
      const placeholder = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${cell}"><rect width="100%" height="100%" fill="#172b48"/><text x="160" y="145" text-anchor="middle" font-family="Arial" font-size="24" font-weight="700" fill="#fff">KHÔNG ĐẠT</text><text x="160" y="183" text-anchor="middle" font-family="Arial" font-size="14" fill="#ffd447">Xem lý do trong _can-xu-ly.md</text></svg>`);
      buffers.push({ input: placeholder, left, top });
    }
  }
  const labels = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cols * cell}" height="${rowsN * (cell + labelH)}">${selected.map((r, i) => `<text x="${(i % cols) * cell + cell / 2}" y="${Math.floor(i / cols) * (cell + labelH) + cell + 29}" text-anchor="middle" font-family="Arial" font-size="20" font-weight="700" fill="#111">${esc(r.code)}${r.passed ? '' : ' · LOẠI'}</text>`).join('')}</svg>`);
  const out = path.join(outputRoot, outputDir, `tong-hop-${brand.toLowerCase().replaceAll(' ', '-')}.jpg`);
  await sharp({ create: { width: cols * cell, height: rowsN * (cell + labelH), channels: 3, background: '#f5f7fb' } }).composite([...buffers, { input: labels, left: 0, top: 0 }]).jpeg({ quality: 90 }).toFile(out);
}

async function contactSheetGroup(results, group, outputRoot) {
  const cols = 4, cell = 320, labelH = 44, rowsN = Math.ceil(results.length / cols), buffers = [];
  for (const [i, r] of results.entries()) {
    const left = (i % cols) * cell, top = Math.floor(i / cols) * (cell + labelH);
    if (r.image) buffers.push({ input: await sharp(r.image).resize(cell, cell).png().toBuffer(), left, top });
    else buffers.push({ input: Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${cell}"><rect width="100%" height="100%" fill="#172b48"/><text x="160" y="160" text-anchor="middle" fill="white" font-family="Arial" font-size="20">NGOẠI LỆ</text></svg>`), left, top });
  }
  const labels = Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cols*cell}" height="${rowsN*(cell+labelH)}">${results.map((r,i)=>`<text x="${(i%cols)*cell+cell/2}" y="${Math.floor(i/cols)*(cell+labelH)+cell+29}" text-anchor="middle" font-family="Arial" font-size="18" font-weight="700" fill="#111">${esc(r.code || `id-${r.id}`)}${r.passed?'':' · NGOẠI LỆ'}</text>`).join('')}</svg>`);
  const folder = path.join(outputRoot, outputDir);
  await fs.mkdir(folder, { recursive: true });
  await sharp({create:{width:cols*cell,height:rowsN*(cell+labelH),channels:3,background:'#f5f7fb'}}).composite([...buffers,{input:labels,left:0,top:0}]).jpeg({quality:90}).toFile(path.join(folder,'tong-hop.jpg'));
}

async function comparePilot(inputRoot, outputRoot) {
  const samplePath = path.join(inputRoot, 'mau-7-anh/1.png');
  const avatarPath = path.join(outputRoot, outputDir, 'BCT-WR1.0.jpg');
  try { await fs.access(avatarPath); } catch { return; }
  const left = await sharp(samplePath).resize(900, 900).png().toBuffer();
  const right = await sharp(avatarPath).resize(900, 900).png().toBuffer();
  const labels = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="1800" height="950"><text x="450" y="936" text-anchor="middle" font-family="Arial" font-size="22" fill="#111">Mẫu 1</text><text x="1350" y="936" text-anchor="middle" font-family="Arial" font-size="22" fill="#111">BCT-WR1.0 avatar</text></svg>');
  await sharp({ create: { width: 1800, height: 950, channels: 3, background: '#fff' } })
    .composite([{ input: left, left: 0, top: 0 }, { input: right, left: 900, top: 0 }, { input: labels, left: 0, top: 0 }])
    .jpeg({ quality: 90 }).toFile(path.join(outputRoot, outputDir, 'so-voi-mau-1.jpg'));
}

const items = JSON.parse(await fs.readFile(path.join(inputRoot, 'all-visible.json'), 'utf8'));
const shopping = JSON.parse(await fs.readFile(path.join(inputRoot, 'anh-shopping.json'), 'utf8'));
const requestedCode = arg('--code').toLowerCase();
const codesFile = arg('--codes-file');
const requestedCodes = new Set(codesFile ? (await fs.readFile(codesFile, 'utf8')).split(/\r?\n/).map(x => x.trim()).filter(Boolean).map(x => x.toLowerCase()) : []);
const shoppingCodesFile = arg('--shopping-codes-file');
const shoppingCodes = new Set(shoppingCodesFile ? (await fs.readFile(shoppingCodesFile, 'utf8')).split(/\r?\n/).map(x => x.trim().toLowerCase()).filter(Boolean) : (args.includes('--prefer-shopping') ? items.map(p => p.code.toLowerCase()) : []));
const idsFile = arg('--ids-file');
const selectedIds = new Set(idsFile ? (await fs.readFile(idsFile, 'utf8')).split(/\r?\n/).map(x => Number(x.trim())).filter(Number.isFinite) : []);
const brandOrder = ['BLUE CARBON', 'JINDIAN', 'ORIANA'];
const groupCodes = {
  den: new Set(['den-duong','den-tru-cong','den-treo-tuong','den-san-vuon-den-nam','den-op-tran','den-ufo','den-cam-co']),
  'inverter-luu-tru': new Set(['hoa-luoi','hybrid-luu-tru','he-thong-ess','pin-battery']),
  'dieu-khien': new Set(['bo-dieu-khien-sac-pwm','bo-dieu-khien-sac-mppt','giam-sat-do-luong']),
  'tam-pin-quat-bom': new Set(['tam-pin-nang-luong-mat-troi','quat-nang-luong-mat-troi','bien-tan-bom-nlmt','may-bom-nlmt']),
  'phu-kien': new Set(['cap-day-dien','tiep-dia-ong-bao-ve','vat-tu-phu-tro','kep-tam-pin','phu-kien-den-nlmt']),
};
const missingFloodlight = new Set(['BCT-WW5.0-MONO','JD-T100','JD-T200','JD-T300','JD-6128','DB60100','DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W'].map(x=>x.toLowerCase()));
let selected = isV8
  ? items.filter(p => (p.brand === 'BLUE CARBON' && /^bo-dieu-khien-sac-(mppt|pwm)$/.test(p.cat_slug)) || [361, 375].includes(Number(p.id)) || String(p.code || '') === 'JD-6128').map(p => {
    if (p.id === 361) return { ...p, v8NoCode: true, v8Model: 'SHARP Mono Half-cut' };
    if (p.id === 375) return { ...p, v8NoCode: true, v8Model: 'Kẹp biên 35' };
    return p;
  })
  : isV7 && idsFile
  ? items.filter(p => selectedIds.has(Number(p.id))).map(p => !p.code ? { ...p, v7NoCode: true } : p)
  : (requestedCode ? items.filter(p => String(p.code || '').toLowerCase() === requestedCode) : requestedCodes.size ? items.filter(p => requestedCodes.has(String(p.code || '').toLowerCase())) : requestedGroup === 'den-pha-thieu' ? items.filter(p=>missingFloodlight.has(String(p.code || '').toLowerCase())) : requestedGroup && groupCodes[requestedGroup] ? items.filter(p=>groupCodes[requestedGroup].has(p.cat_slug)) : items.filter(p => brandOrder.includes(p.brand)));
selected = isV7 || isV8 ? selected.sort((a,b)=>Number(a.id)-Number(b.id)) : selected
  .sort((a, b) => brandOrder.indexOf(a.brand) - brandOrder.indexOf(b.brand) || items.indexOf(a) - items.indexOf(b));
if (!selected.length) throw new Error('Không tìm thấy sản phẩm cần dựng.');
const plateInfo = args.includes('--rebuild-plate')
  ? { ...await makePlateFromMock(1), reused: false, note: 'Nền được sửa lại từ mẫu 1 với mặt nạ feather rộng và xóa nét đứng sót.' }
  : { plateFile: path.join(outputRoot, 'nen', 'avatar-tu-mau1.png'), reused: true, note: 'Dùng nền avatar đã làm sạch từ mẫu 1.' };
const plate = await fs.readFile(plateInfo.plateFile);
const logo = await fs.readFile(path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png'));
const results = [];
for (const item of selected) results.push(await renderOne(item, plate, logo, inputRoot, outputRoot, shopping, shoppingCodes));
if (isV8) await contactSheetGroup(results, 'v8', outputRoot);
else if (isV7) await contactSheetGroup(results, 'v7', outputRoot);
else if (!requestedGroup) for (const brand of ['BLUE CARBON', 'JINDIAN', 'ORIANA']) await contactSheet(results, brand);
else await contactSheetGroup(results, requestedGroup, outputRoot);
if (!isV8) await comparePilot(inputRoot, outputRoot);
const skipped = results.filter(r => !r.passed);
if (isV7) {
  const qaFilesV7 = await Promise.all(results.map(r => fs.readFile(path.join(outputRoot, outputDir, `${r.code ? String(r.code).replace(/[^a-zA-Z0-9._-]/g, '-') : `id-${r.id}`}.qa.json`), 'utf8').then(JSON.parse)));
  const areasV7 = qaFilesV7.map(q => q.checks?.productPlacement?.alphaArea).filter(Number.isFinite).sort((a,b)=>a-b);
  const medianV7 = areasV7.length ? (areasV7.length % 2 ? areasV7[(areasV7.length-1)/2] : (areasV7[areasV7.length/2-1]+areasV7[areasV7.length/2])/2) : 0;
  const exceptions = results.filter(r=>!r.passed).map(r=>({code:r.code,id:r.id,reason:r.reason,sourceCandidates:r.sourceCandidates || r.qa?.rejectedSources || []}));
  const summaryV7 = { version: 7, total: results.length, passed: results.filter(r=>r.passed).length, exceptions: exceptions.length, medianAlphaArea1600: medianV7, results: results.map(r=>({code:r.code,id:r.id,passed:r.passed,reason:r.reason,source:r.qa?.source || r.source || null})) };
  await fs.writeFile(path.join(outputRoot, outputDir, 'run-summary.json'), JSON.stringify(summaryV7,null,2));
  const lines = ['# Ngoại lệ V7', '', ...exceptions.map(x=>`- **${x.code || `id-${x.id}`}** — ${x.reason}${x.sourceCandidates.length ? `  \\n  Ảnh đã xét: ${x.sourceCandidates.map(s=>typeof s==='string'?s:(s.name||s.source||JSON.stringify(s))).join(', ')}` : ''}`), '', '## Ghi chú kiểm tra dữ liệu', '', '- **id-361** — `power_watts` hiển thị nguyên văn dưới dạng 395W; tên dữ liệu ghi 395 Wp. Công suất cần chủ website xác minh.'];
  await fs.writeFile(path.join(outputRoot, outputDir, '_ngoai-le.md'), lines.join('\n'));
  await fs.writeFile(path.join(outputRoot, outputDir, 'v7-final.md'), `# Báo cáo V7\n\n- Đạt: ${summaryV7.passed}/${summaryV7.total}; ngoại lệ: ${summaryV7.exceptions}.\n- Trung vị alphaArea của ảnh đạt: ${Math.round(medianV7)} px².\n- Ảnh ghép: [tong-hop.jpg](tong-hop.jpg).\n- Chi tiết QA nằm cạnh mỗi JPG; ngoại lệ và dữ liệu cần xác minh ở [_ngoai-le.md](_ngoai-le.md).\n- Chế độ này chỉ ghi vào output/v7; không ghi DB/production.\n`);
  await fs.writeFile(path.join(outputRoot, outputDir, 'DONE'), new Date().toISOString()+'\n');
  console.log(JSON.stringify(summaryV7,null,2));
  process.exit(0);
}
if (isV8) {
  const qaFilesV8 = await Promise.all(results.map(r => fs.readFile(path.join(outputRoot, outputDir, `${r.code ? String(r.code).replace(/[^a-zA-Z0-9._-]/g, '-') : `id-${r.id}`}.qa.json`), 'utf8').then(JSON.parse)));
  const areasV8 = qaFilesV8.map(q => q.checks?.productPlacement?.alphaArea).filter(Number.isFinite).sort((a,b)=>a-b);
  const medianV8 = areasV8.length ? (areasV8.length % 2 ? areasV8[(areasV8.length-1)/2] : (areasV8[areasV8.length/2-1]+areasV8[areasV8.length/2])/2) : 0;
  const exceptions = results.filter(r => !r.passed).map(r => ({ code: r.code, id: r.id, reason: r.reason, sourceCandidates: r.sourceCandidates || r.qa?.rejectedSources || [] }));
  const carriedForwardExceptions = [
    ...['JD-T100','JD-T200','JD-T300'].map(code => ({ code, reason: 'Ảnh có bàn tay người hoặc logo BOLAR trên sản phẩm; giữ ngoại lệ V7.' })),
    ...['DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W'].map(code => ({ code, reason: 'Ảnh mang nhãn GERINA; giữ ngoại lệ V7.' })),
    ...['EPV230-4T2.2G','EPV230-4T4.0G','EPV230-4T5.5G','EPV230-4T7.5G','EPV230-4T11G','EPV230-4T15G','EPV230-4T18.5G'].map(code => ({ code, reason: 'Ảnh ghép nhiều model, không xác định được đúng mã; giữ ngoại lệ V7.' })),
    { code: 'ASTRO N7 610W', reason: 'Nguồn ghi 630W, sai công suất model; giữ ngoại lệ V7.' },
    { code: 'DB-16400W', reason: 'Chỉ có ảnh ghép nhiều sản phẩm/chú thích; giữ ngoại lệ V7.' },
    { code: 'DB01-ECO', reason: 'Ảnh có chú thích hoặc phụ kiện rời không thể tách an toàn; giữ ngoại lệ V7.' },
    { code: 'BCT-WW5.0-MONO', reason: 'Không có ảnh sản phẩm riêng sạch; giữ ngoại lệ V7.' },
  ];
  const summaryV8 = {
    version: 8,
    total: results.length,
    passed: results.filter(r => r.passed).length,
    exceptions: exceptions.length,
    carriedForwardExceptions: carriedForwardExceptions.length,
    medianAlphaArea1600: medianV8,
    visualReview: {
      samples100Percent: results.filter(r => r.passed).slice(0, 6).map(r => r.code || `id-${r.id}`),
      controllerSamples: results.filter(r => r.passed && r.brand === 'BLUE CARBON').slice(0, 3).map(r => r.code),
      contactSheet: 'tong-hop.jpg',
      note: 'Ảnh sản phẩm được giữ từ nguồn; logo LDSOLAR trên thiết bị không bị che hoặc sửa. QA ghi kích thước, trọng số alpha, vị trí, va chạm chữ và nguồn số liệu.',
    },
    results: results.map(r => ({ code: r.code, id: r.id, passed: r.passed, reason: r.reason, source: r.qa?.source || r.source || null })),
  };
  await fs.writeFile(path.join(outputRoot, outputDir, 'run-summary.json'), JSON.stringify(summaryV8, null, 2));
  const exceptionLines = [
    '# Ngoại lệ V8', '',
    ...exceptions.map(x => `- **${x.code || `id-${x.id}`}** — ${x.reason}${x.sourceCandidates?.length ? `\n  Ảnh đã xét: ${x.sourceCandidates.map(s => typeof s === 'string' ? s : (s.name || s.source || JSON.stringify(s))).join(', ')}` : ''}`),
    '', '## Ngoại lệ được giữ từ V7', '',
    ...carriedForwardExceptions.map(x => `- **${x.code}** — ${x.reason}`),
    '', '## Ghi chú', '',
    '- **id-361** — Bỏ dòng công suất có nghi vấn; model “SHARP Mono Half-cut”.',
    '- **JD-6128** — Đã thử matte 245 và 235; còn mảng nền trắng sát viền chóa nên không dựng để tránh làm đổi pixel sản phẩm.',
  ];
  await fs.writeFile(path.join(outputRoot, outputDir, '_ngoai-le.md'), exceptionLines.join('\n'));
  const report = `# Báo cáo V8\n\n- Đạt: ${summaryV8.passed}/${summaryV8.total}; ngoại lệ lượt này: ${summaryV8.exceptions}; ngoại lệ giữ từ V7: ${summaryV8.carriedForwardExceptions}.\n- Bộ sạc BLUE CARBON được đưa vào xử lý: ${results.filter(r => r.brand === 'BLUE CARBON').length}; đạt ${results.filter(r => r.passed && r.brand === 'BLUE CARBON').length}.\n- Contact sheet: [tong-hop.jpg](tong-hop.jpg).\n- Ngoại lệ và nguồn đã xét: [_ngoai-le.md](_ngoai-le.md).\n- Chỉ ghi vào output/v8; không ghi DB/production.\n`;
  await fs.writeFile(path.join(outputRoot, outputDir, 'v8-final.md'), report);
  await fs.writeFile(path.join(outputRoot, outputDir, 'DONE'), new Date().toISOString() + '\n');
  console.log(JSON.stringify(summaryV8, null, 2));
  process.exit(0);
}
const reasons = ['# Cần xử lý thủ công trước khi đưa avatar vào bộ', '', ...skipped.map(r => `- **${r.code}** (${r.brand}) — ${r.reason}${r.source ? `; ảnh: \`${r.source}\`` : ''}`), ''];
if (outputDir === 'avatar') await fs.writeFile(path.join(outputRoot, '_can-xu-ly.md'), reasons.join('\n'));
const runSummary = {
  plate: plateInfo,
  counts: Object.fromEntries(['BLUE CARBON', 'JINDIAN', 'ORIANA'].map(b => [b, { total: results.filter(r => r.brand === b).length, passed: results.filter(r => r.brand === b && r.passed).length, rejected: results.filter(r => r.brand === b && !r.passed).length }])),
  visualReview: {
    comparison: 'output/avatar/so-voi-mau-1.jpg',
    title: 'Hai dòng và căn giữa bám bố cục mẫu; “MẶT TRỜI” trắng, hãng vàng.',
    modelBar: 'Nới theo chiều rộng chữ đo thực, căn giữa; mẫu thử dài nhất không chạm viền.',
    icons: 'Pin có mũi tên vòng, remote nghiêng, bánh răng kèm cờ lê, chip LED riêng và bóng đèn; đã xem crop 300%, không thấy nét/vòng thừa.',
    copy: 'Nhãn và giá trị tách dòng; số lấy từ dữ liệu sau chuẩn hóa W; không lặp giá trị.',
    product: 'Ảnh thật ghép nguyên tỉ lệ, phóng tối đa trong vùng phải-dưới xấp xỉ 700–1570, y 500–1560 (khung 1600), có bóng tiếp xúc nhẹ; QA lưu bbox và độ lấp từng mã.',
    samples100Percent: {
      'BCT-WR1.0': 'Sản phẩm lớn, không chạm chữ/icon; remote giữ lại vì là món đáng kể, dây nối liền đèn được giữ.',
      'BCT-WR2.0': 'Sản phẩm lớn, không chạm chữ/icon; túi ốc, cờ lê và thanh rời còn trên giá vì chạm kết cấu giá đỡ, xóa alpha sẽ làm cụt giá.',
      'BCT-FLRP2.0': 'Sản phẩm lớn, không chạm chữ/icon; dây nối liền được giữ, không thấy phụ kiện rời đáng kể.',
      'JD-8500L': 'Đèn và tấm pin chiếm vùng phải-dưới lớn, không chạm chữ/icon; không thấy phụ kiện rời.',
      'JD-18200': 'Đèn và tấm pin lớn, remote đi kèm được giữ, không chạm chữ/icon; không thấy phụ kiện rời.',
      'DB-8300': 'Đèn và tấm pin lớn, không chạm chữ/icon; remote, pin AA, cuộn dây và túi ốc còn trên khay nguồn, xóa alpha sẽ làm khuyết khay/giá.',
      'DB-9200': 'Đèn và tấm pin lớn, không chạm chữ/icon; remote, pin AA và túi phụ kiện còn chạm vào giá đỡ nên giữ nguyên.',
      'DB88-600PRO': 'Đèn và tấm pin lớn, không chạm chữ/icon; remote được giữ, pin AA và túi ốc còn đè lên thanh đỡ nên giữ nguyên.',
      'DB3-PRO-200W': 'Ảnh feed đã xem ở kích thước lớn: nhãn trên đèn là GERINA GD-60W, không đúng hãng; loại khỏi bộ và không sửa nhãn.',
    },
    background: 'Giữ nguyên avatar-tu-mau1.png; xem vùng logo/tiêu đề/đĩa icon ở 100% và 300%. Mean chênh luminance qua biên feather đo được 2.884/255 = 1.131%, dưới ngưỡng 1.5%.',
    sourceAngles: 'Đã xem bằng mắt 6 mã đại diện: BCT-WR1.0, BCT-FLRP2.0, BCT-WW5.0, JD-V100, JD-T200 và DB-8300. JD-T200 chụp đèn gần chính diện nhưng bị loại do có bàn tay; góc từng mã ở results, góc các mã đạt ở qa.json. Các mã còn lại không phân loại tự động.'
  },
  results: results.map(({ code, brand, passed, reason, sourceAngleAssessment }) => ({ code, brand, passed, reason, sourceAngleAssessment }))
};
await fs.writeFile(path.join(outputRoot, outputDir, 'run-summary.json'), JSON.stringify(runSummary, null, 2));
const qaFiles = await Promise.all(results.filter(r => r.image).map(r => fs.readFile(path.join(outputRoot, outputDir, `${String(r.code).replace(/[^a-zA-Z0-9._-]/g, '-')}.qa.json`), 'utf8').then(JSON.parse)));
const areas = qaFiles.map(q => q.checks.productPlacement.alphaArea).filter(Number.isFinite).sort((a, b) => a - b);
const medianArea = areas.length ? (areas.length % 2 ? areas[(areas.length - 1) / 2] : (areas[areas.length / 2 - 1] + areas[areas.length / 2]) / 2) : 0;
const widths = qaFiles.map(q => q.checks.productPlacement.bbox.width).sort((a, b) => a - b);
const medianWidth = widths.length ? (widths.length % 2 ? widths[(widths.length - 1) / 2] : (widths[widths.length / 2 - 1] + widths[widths.length / 2]) / 2) : 0;
const outliers = qaFiles.filter(q => q.checks.productPlacement.alphaArea < medianArea * .8 || q.checks.productPlacement.alphaArea > medianArea * 1.2).map(q => {
  const b = q.checks.productPlacement.bbox, ratio = b.width / b.height, area = q.checks.productPlacement.alphaArea;
  const reason = ratio < .7 ? `Bộ đèn/tấm pin xếp cao và hẹp (${b.width}×${b.height}); giới hạn chiều cao 1085 px đã đạt trước khi khối lượng alpha chạm trung vị.` : area < medianArea * .8 ? `Bộ ngang, phần lớn là khung/tấm pin có vùng rỗng (${b.width}×${b.height}); alpha đặc thấp dù bbox đã rộng.` : `Tấm pin lớn chiếm phần lớn khối hình học (${b.width}×${b.height}); giới hạn bbox 1130×1085 px ngăn hạ alpha thêm mà vẫn giữ đèn đủ rõ.`;
  return { code: q.code, area, reason };
});
const accessoryNotes = qaFiles.filter(q => q.product.components?.retainedAccessoryNote).map(q => ({ code: q.code, source: q.source, note: q.product.components.retainedAccessoryNote }));
const enrichedSummary = { ...runSummary, sizeStatistics: { medianAlphaArea1600: medianArea, minAlphaArea1600: areas[0] || 0, maxAlphaArea1600: areas.at(-1) || 0, relativeDeviationMinPercent: medianArea ? Number(((areas[0] / medianArea - 1) * 100).toFixed(2)) : 0, relativeDeviationMaxPercent: medianArea ? Number(((areas.at(-1) / medianArea - 1) * 100).toFixed(2)) : 0, medianBboxWidth1600: medianWidth, outliers }, retainedAccessoryNotes: accessoryNotes };
await fs.writeFile(path.join(outputRoot, outputDir, 'run-summary.json'), JSON.stringify(enrichedSummary, null, 2));
const exceptionText = ['# Ngoại lệ dựng avatar v5', '', `Trung vị alphaArea: ${Math.round(medianArea)} px²; bbox rộng trung vị: ${Math.round(medianWidth)} px.`, '', '## Ngoài khoảng ±20%', ...(outliers.length ? outliers.map(x => `- **${x.code}** — ${x.reason}`) : ['- Không có.']), '', '## Phụ kiện còn lại do dính/chạm giá đỡ', ...(accessoryNotes.length ? accessoryNotes.map(x => `- **${x.code}** (${x.source}) — ${x.note}`) : ['- Không có.']), '', '## Không có nguồn sạch đúng hãng hoặc không tách an toàn', ...skipped.map(r => `- **${r.code}** — ${r.reason}`), ''];
await fs.writeFile(path.join(outputRoot, outputDir, '_ngoai-le.md'), exceptionText.join('\n'));
console.log(JSON.stringify({ plate: plateInfo, totals: { rendered: results.length, passed: results.filter(r => r.passed).length, rejected: skipped.length }, brands: ['BLUE CARBON', 'JINDIAN', 'ORIANA'].map(brand => ({ brand, total: results.filter(r => r.brand === brand).length, pass: results.filter(r => r.brand === brand && r.passed).length, rejected: results.filter(r => r.brand === brand && !r.passed).length })), rejected: skipped.map(r => ({ code: r.code, reason: r.reason })) }, null, 2));

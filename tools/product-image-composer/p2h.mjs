#!/usr/bin/env node
// Ảnh 02 và 07 CÁ NHÂN HÓA theo sản phẩm: sản phẩm thật đứng ở khoảng trống của mẫu (sàn lát ở mẫu 2, bục ở mẫu 7).
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { esc, luminance, contrastRatio, measureText, logoElementSvg, findTextOverlaps, prepareProduct, filterDetachedComponents } from './avatar.mjs';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const args = process.argv.slice(2);
const arg = (name, fallback) => { const i = args.indexOf(name); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const dataFile = path.join(inputRoot, arg('--data', 'den-pha-72.json'));
const outDir = path.join(outputRoot, arg('--out', 'p2h'));
const navy = '#061735', deep = '#082c5a', gold = '#ffd447', white = '#ffffff';
const logoPath = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const plate02 = path.join(outputRoot, 'nen/p2-nen-02.png');
const plate07 = path.join(outputRoot, 'nen/p2-nen-07.png');
const FONT = 'Arial, Be Vietnam Pro, sans-serif';
const safeCode = c => String(c || '').replace(/[^a-zA-Z0-9._-]/g, '-');
function dataUri(buf, mime='png') { return `data:image/${mime};base64,${buf.toString('base64')}`; }
function textEl(t, {x,y,size=18,fill=white,weight=700,anchor='start',stroke=navy,strokeWidth=0,italic=false}) {
  return `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}"${strokeWidth ? ` stroke="${stroke}" stroke-width="${strokeWidth}" paint-order="stroke fill" stroke-linejoin="round"` : ''} font-family="Arial, Be Vietnam Pro, sans-serif" font-size="${size}" font-weight="${weight}"${italic?' font-style="italic"':''}>${esc(t)}</text>`;
}
function icon(name, x, y, size=36, color='#ffffff') {
  const paths = {
    model: '<path d="M12 4h24v7H12zM17 16h14v17H17zM20 20h8m-8 5h8m-8 5h5"/><path d="m7 21 3 3 6-7"/>',
    package: '<path d="m8 14 16-9 16 9-16 9zM8 14v18l16 9 16-9V14M24 23v18M16 9l16 9"/>',
    shield: '<path d="m24 4 16 6v12c0 11-7 18-16 22C15 40 8 33 8 22V10z"/><path d="m16 24 6 6 12-14"/>',
    support: '<circle cx="24" cy="17" r="10"/><path d="M6 33a18 18 0 0 1 36 0M7 23v9h8v-9m26 0v9h-8v-9M17 42h14"/>',
    bolt: '<path d="M27 2 9 27h13l-3 19 20-27H25z"/>',
    circleBolt: '<circle cx="24" cy="24" r="19"/><path d="m27 7-13 19h10l-2 15 15-21H27z"/>',
    leaf: '<path d="M42 7C23 7 9 13 8 28c0 9 7 14 15 12 13-3 18-17 19-33Z"/><path d="M7 43c8-12 17-18 30-27M24 27c1 7 6 11 14 12"/>',
  };
  return `<svg xmlns="http://www.w3.org/2000/svg" x="${x}" y="${y}" width="${size}" height="${size}" viewBox="0 0 48 48" fill="none" stroke="${color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">${paths[name]}</svg>`;
}
function rgbaBox(x,y,w,h,fill,rx=10,stroke='none',strokeWidth=0) {
  return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="${fill}"${stroke!=='none'?` stroke="${stroke}" stroke-width="${strokeWidth}"`:''}/>`;
}
async function measuredText(t, size, weight=700, x, baseline, anchor='start', opts={}) {
  const w = await measureText(t, size, weight);
  const left = anchor === 'middle' ? x-w/2 : anchor === 'end' ? x-w : x;
  const ascent = size * .84;
  return { svg: textEl(t,{x,y:baseline,size,weight,anchor,...opts}), box:{x:left,y:baseline-ascent,w,h:size*1.12}, width:w };
}
function checkTextBoxes(texts, logoBox={x:34,y:22,w:340,h:56}) {
  const boxes = texts.map(t=>({...t.box}));
  const overlaps = findTextOverlaps(texts);
  const inside = boxes.every(b=>b.x >= 13.5 && b.y >= 13.5 && b.x+b.w <= 886.5 && b.y+b.h <= 886.5);
  const logoCollisions=texts.filter(t=>t.box.x<logoBox.x+logoBox.w&&t.box.x+t.box.w>logoBox.x&&t.box.y<logoBox.y+logoBox.h&&t.box.y+t.box.h>logoBox.y).map(t=>t.text);
  return { minOuterMargin1600:24, allWithinCanvas:inside, overlapPairs:overlaps, logoCollisions, pass:inside && overlaps.length===0 && logoCollisions.length===0 };
}
async function title(svgParts, texts, lines, options={}) {
  for (const line of lines) {
    let size = line.size, box;
    do {
      box = await measuredText(line.text,size,line.weight??800,line.x??450,line.y,line.anchor??'middle',{fill:line.fill??white,stroke:line.stroke??navy,strokeWidth:line.strokeWidth??2});
      if (box.width <= (line.maxWidth ?? 830) || size <= (line.minSize ?? 27)) break;
      size -= 1;
    } while (true);
    svgParts.push(box.svg); texts.push({text:line.text,box:box.box,section:'title',fontSize:size});
  }
}
let currentProduct = '', currentItem = null, currentSlide = '';
const NS = args.includes('--no-scenes');
function baseSvg(bg, logoUri, contents, logoBox={x:34,y:22,w:340,h:56}) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="${bg}" width="900" height="900"/>${NS && currentSlide === '02' ? '<path d="M0 640H900V900H0Z" fill="#082c5a"/><path d="M0 640H900" stroke="#77b9ff" stroke-opacity=".55" stroke-width="2"/>' : ''}${currentProduct}${logoElementSvg(logoUri,logoBox.x,logoBox.y,logoBox.w,logoBox.h)}${contents}</svg>`;
}
async function make02(logoUri) {
  const parts=[], texts=[];
  await title(parts,texts,[
    {text:'CHIẾU SÁNG LINH HOẠT',x:642,y:99,size:42,maxWidth:478,minSize:35},
    {text:'NGOÀI TRỜI',x:642,y:147,size:42,maxWidth:478,minSize:36},
  ]);
  const subText = NS ? `${String(currentItem?.cat || 'Năng lượng mặt trời')} · ${currentItem?.code}` : (args.includes('--street-labels') ? 'Gợi ý cho đường nội bộ, bãi xe và khu công cộng' : 'Gợi ý cho sân, kho và các vị trí lắp đặt');
  let subSize = NS ? 16 : 14, sub;
  do { sub = await measuredText(subText,subSize,NS?500:400,642,177,'middle',{fill:'#ffffff',stroke:navy,strokeWidth:1.4}); if(sub.width<=478||subSize<=12)break; subSize-=.5; } while(true);
  parts.push(sub.svg); texts.push({text:subText,box:sub.box,section:'subtitle',fontSize:subSize});
  const labels=NS?[]:(args.includes('--street-labels')?['Đường nội bộ, công viên','Bãi đỗ xe','Mặt tiền công trình','Khu công cộng']:['Sân vườn, khuôn viên','Bãi xe','Kho bãi','Khu vực sản xuất']);
  const lefts=[14,236,452,674], widths=[207,208,216,212], top=862, h=24;
  for(let i=0;i<labels.length;i++){
    const x=lefts[i],w=widths[i];
    parts.push(rgbaBox(x,top,w,h,'rgba(5,30,68,.88)',3,'rgba(255,255,255,.75)',.65));
    let size=13.5, item;
    while(size>=11){ item=await measuredText(labels[i],size,500,x+w/2,top+17,'middle',{fill:white}); if(item.width<=w-12)break; size-=.5; }
    parts.push(item.svg); texts.push({text:labels[i],box:item.box,section:'image-label',fontSize:size,container:{x,y:top,w,h}});
  }
  return { svg:baseSvg(dataUri(await sharp(plate02).resize(900,900).png().toBuffer()),logoUri,parts.join('')), texts, slide:'02' };
}
async function card(iconName, x, y, titleText, subText, parts, texts) {
  const w=285,h=56;
  parts.push(rgbaBox(x,y,w,h,'rgba(248,250,255,.97)',7,'rgba(255,255,255,.9)',.8));
  parts.push(`<rect x="${x+8}" y="${y+8}" width="42" height="40" rx="7" fill="url(#tile)"/>`);
  parts.push(icon(iconName,x+11,y+10,36));
  const head=await measuredText(titleText,18.2,800,x+69,y+24,'start',{fill:'#103c78'});
  const sub=await measuredText(subText,13.4,400,x+69,y+43,'start',{fill:'#183b70'});
  parts.push(head.svg,sub.svg);
  texts.push({text:titleText,box:head.box,section:'card-title',fontSize:18.2,container:{x:x+57,y:y+4,w:w-62,h:26}});
  texts.push({text:subText,box:sub.box,section:'card-subtitle',fontSize:13.4,container:{x:x+57,y:y+27,w:w-62,h:25}});
}
async function make07(logoUri, warranty) {
  const parts=['<defs><linearGradient id="tile" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#1257a4"/><stop offset="1" stop-color="#072d65"/></linearGradient></defs>'],texts=[];
  await title(parts,texts,[
    {text:'AN TÂM LỰA CHỌN',x:450,y:140,size:47,maxWidth:665,minSize:41},
    {text:'CÙNG PHÚC THỊNH SOLAR',x:450,y:205,size:47,maxWidth:710,minSize:39},
  ]);
  const cards=[
    ['model','ĐÚNG MODEL','và phân loại'],
    ['package','ĐÓNG GÓI','cẩn thận trước khi giao'],
    ['shield',`BẢO HÀNH ${warranty}`,'lỗi từ nhà sản xuất'],
    ['support','HỖ TRỢ TƯ VẤN','lựa chọn sản phẩm phù hợp'],
  ];
  for(let i=0;i<cards.length;i++)await card(cards[i][0],598,312+i*64,cards[i][1],cards[i][2],parts,texts);
  const cols=[{x:24,w:270,icon:'bolt',lines:['GIẢI PHÁP CHIẾU SÁNG','NĂNG LƯỢNG MẶT TRỜI']},{x:317,w:282,icon:'circleBolt',lines:['TIẾT KIỆM ĐIỆN NĂNG']},{x:613,w:270,icon:'leaf',lines:['PHÙ HỢP NHIỀU KHU','VỰC NGOÀI TRỜI']}];
  for(const c of cols){
    parts.push(icon(c.icon,c.x,816,51));
    const x=c.x+60, size=15.3;
    for(let i=0;i<c.lines.length;i++){
      const t=await measuredText(c.lines[i],size,500,x,836+i*19,'start',{fill:white});
      parts.push(t.svg); texts.push({text:c.lines[i],box:t.box,section:'footer',fontSize:size,container:{x,y:802,w:c.w-62,h:74}});
    }
  }
  return {svg:baseSvg(dataUri(await sharp(plate07).resize(900,900).png().toBuffer()),logoUri,parts.join('')),texts,slide:warranty==='2 NĂM'?'07-bh2':'07-bh5'};
}
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


async function productEl(item, box) { // box: {cx, bottom, maxW, maxH}
  const av = await avatarQa(item.code);
  const product = await selectedProduct(item, av);
  const meta = await sharp(product.buffer).metadata();
  const s = Math.min(box.maxW / meta.width, box.maxH / meta.height);
  const pw = Math.round(meta.width * s), ph = Math.round(meta.height * s);
  const layer = await sharp(product.buffer).resize(pw, ph, { fit: 'fill', kernel: sharp.kernel.lanczos3 }).png().toBuffer();
  const x = Math.round(box.cx - pw / 2), y = box.bottom - ph;
  const el = `<defs><filter id="pBlur"><feGaussianBlur stdDeviation="6"/></filter></defs><ellipse cx="${box.cx}" cy="${box.bottom + 3}" rx="${Math.round(Math.min(box.maxW / 2, pw * .5))}" ry="12" fill="#06101f" opacity=".5" filter="url(#pBlur)"/><image href="${dataUri(layer)}" x="${x}" y="${y}" width="${pw}" height="${ph}" preserveAspectRatio="none"/>`;
  return { el, box: { x, y, w: pw, h: ph }, source: product.source };
}
async function renderOne(slide, item, logo, logoUri) {
  currentItem = item; currentSlide = slide;
  const placing = slide === '02' ? (NS ? { cx: 470, bottom: 800, maxW: 570, maxH: 500 } : { cx: 470, bottom: 640, maxW: 570, maxH: 440 }) : { cx: 318, bottom: 748, maxW: 520, maxH: 330 };
  const pr = await productEl(item, placing);
  currentProduct = pr.el;
  const warranty = String(item.warranty || '').toLocaleUpperCase('vi');
  const job = slide === '02' ? await make02(logoUri) : await make07(logoUri, warranty);
  currentProduct = '';
  const composed = job.svg.replaceAll('LOGO_URI', dataUri(logo));
  const out = path.join(outDir, `${safeCode(item.code)}-${slide}.jpg`);
  await sharp(Buffer.from(composed)).resize(1600, 1600, { kernel: sharp.kernel.lanczos3 }).flatten({ background: navy }).toColorspace('srgb').jpeg({ quality: 90, mozjpeg: true, chromaSubsampling: '4:4:4' }).toFile(out);
  const m = await sharp(out).metadata(), st = await fs.stat(out);
  const textQA = checkTextBoxes(job.texts);
  const b = pr.box;
  const clear = slide === '02' ? !job.texts.some(t => t.section !== 'image-label' && t.box.x < b.x + b.w && t.box.x + t.box.w > b.x && t.box.y < b.y + b.h && t.box.y + t.box.h > b.y) && b.y + b.h <= (NS ? 800 : 640)
                               : b.x + b.w <= 590 && b.y >= 225;
  const qa = { code: item.code, slide, source: pr.source, productBox900: b, warranty: slide === '07' ? warranty : undefined, textBoxes: textQA, canvas: m.width === 1600 && m.height === 1600 && !m.hasAlpha && st.size < 5 * 1024 * 1024, productClear: clear };
  qa.pass = textQA.pass && qa.canvas && clear;
  await fs.writeFile(out.replace(/\.jpg$/, '.qa.json'), JSON.stringify(qa, null, 1));
  return { out, qa };
}
async function main() {
  await fs.mkdir(outDir, { recursive: true });
  const data = JSON.parse(await fs.readFile(dataFile, 'utf8')).filter(x => ['BLUE CARBON', 'JINDIAN', 'ORIANA'].includes(x.brand));
  const only = arg('--codes', null);
  const sel = args.includes('--pilot') ? data.filter(x => x.code === 'BCT-WR1.0') : only ? data.filter(x => only.split(',').includes(x.code)) : data;
  const logo = await fs.readFile(logoPath), logoUri = dataUri(logo);
  const exc = [], ok = [];
  for (const item of sel) for (const slide of (arg('--slides','02,07').split(','))) {
    try { const r = await renderOne(slide, item, logo, logoUri); ok.push(r); console.log(r.qa.pass ? 'PASS' : 'FAIL', item.code, slide); }
    catch (e) { exc.push({ code: item.code, slide, reason: e.message }); console.error('EXC', item.code, slide, e.message); }
  }
  console.log(JSON.stringify({ rendered: ok.length, passed: ok.filter(x => x.qa.pass).length, exceptions: exc }));
}
await main();

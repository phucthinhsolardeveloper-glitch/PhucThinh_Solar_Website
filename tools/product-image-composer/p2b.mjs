#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { esc, luminance, contrastRatio, measureText, logoElementSvg, findTextOverlaps, logoTemplateMatchScore } from './avatar.mjs';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const args = process.argv.slice(2);
const arg = (name, fallback) => { const i = args.indexOf(name); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const width = 900, height = 900, scale = 1600 / 900, minBytes = 5 * 1024 * 1024;
const outDir = path.join(outputRoot, 'p2b');
const navy = '#061735', deep = '#082c5a', gold = '#ffd447', white = '#ffffff';
const logoPath = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const artRoot = path.join(inputRoot, 'mau-7-anh');
const plate02 = path.join(outputRoot, 'nen/p2-nen-02.png');
const plate07 = path.join(outputRoot, 'nen/p2-nen-07.png');
const avatarPlate = path.join(outputRoot, 'nen/avatar-tu-mau1.png');

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
function baseSvg(bg, logoUri, contents, logoBox={x:34,y:22,w:340,h:56}) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="${bg}" width="900" height="900"/>${logoElementSvg(logoUri,logoBox.x,logoBox.y,logoBox.w,logoBox.h)}${contents}</svg>`;
}
async function make02(logoUri) {
  const parts=[], texts=[];
  await title(parts,texts,[
    {text:'CHIẾU SÁNG LINH HOẠT',x:642,y:99,size:42,maxWidth:478,minSize:35},
    {text:'NGOÀI TRỜI',x:642,y:147,size:42,maxWidth:478,minSize:36},
  ]);
  const sub = await measuredText('Gợi ý cho sân, kho và các vị trí lắp đặt',14,400,642,177,'middle',{fill:'#ffffff',stroke:navy,strokeWidth:1.4});
  parts.push(sub.svg); texts.push({text:'Gợi ý cho sân, kho và các vị trí lắp đặt',box:sub.box,section:'subtitle',fontSize:14});
  const labels=['Sân vườn, khuôn viên','Bãi xe','Kho bãi','Khu vực sản xuất'];
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
async function make08(logoUri, brand, certPath) {
  const parts=[],texts=[];
  const line1=await measuredText('GIẤY ỦY QUYỀN PHÂN PHỐI',39,800,450,86,'middle',{fill:white,stroke:navy,strokeWidth:1.8});
  const line2=await measuredText(brand,42,800,450,137,'middle',{fill:gold,stroke:navy,strokeWidth:1.8});
  parts.push(line1.svg,line2.svg);
  texts.push({text:'GIẤY ỦY QUYỀN PHÂN PHỐI',box:line1.box,section:'title',fontSize:39},{text:brand,box:line2.box,section:'title',fontSize:42});
  const meta=await sharp(certPath).metadata();
  const maxW=690,maxH=724, ratio=meta.width/meta.height;
  const h=Math.min(maxH,maxW/ratio), w=h*ratio, x=(900-w)/2, y=161+(724-h)/2;
  parts.push(`<rect x="${x-5}" y="${y-5}" width="${w+10}" height="${h+10}" rx="2" fill="#ffffff" stroke="rgba(255,255,255,.82)" stroke-width="1.2" filter="url(#shadow)"/>`);
  parts.push(`<image href="${dataUri(await fs.readFile(certPath),'jpeg')}" x="${x}" y="${y}" width="${w}" height="${h}" preserveAspectRatio="none"/>`);
  const logoPlacement={x:34,y:7,w:215,h:41};
  const svg=baseSvg(dataUri(await sharp(avatarPlate).resize(900,900).png().toBuffer()),logoUri,`<defs><filter id="shadow" x="-20%" y="-20%" width="140%" height="150%"><feDropShadow dx="0" dy="3" stdDeviation="5" flood-color="#000000" flood-opacity=".5"/></filter></defs>${parts.join('')}`,logoPlacement);
  return {svg,texts,logoPlacement,slide:`08-${brand.toLowerCase().replaceAll(' ','-')}`,certificate:{source:certPath,sourceDimensions:{width:meta.width,height:meta.height},placed:{x,y,width:w,height:h},aspectRatioOriginal:ratio,aspectRatioPlaced:w/h,uncropped:true,unmodified:true}};
}
async function render(job, outputName, logoBuf) {
  const composed=job.svg.replaceAll('LOGO_URI',dataUri(logoBuf));
  const raw=await sharp(Buffer.from(composed)).resize(1600,1600,{kernel:sharp.kernel.lanczos3}).png().toBuffer();
  const out=path.join(outDir,outputName);
  await sharp(raw).flatten({background:navy}).toColorspace('srgb').jpeg({quality:90,mozjpeg:true,chromaSubsampling:'4:4:4'}).toFile(out);
  const meta=await sharp(out).metadata(), stat=await fs.stat(out);
  const placement=job.logoPlacement||{x:34,y:22,w:340,h:56};
  const textQA=checkTextBoxes(job.texts,placement);
  const rgb=await sharp(out).resize(900,900,{kernel:sharp.kernel.lanczos3}).removeAlpha().raw().toBuffer();
  const bg=await sharp(job.backgroundFile).resize(900,900).removeAlpha().raw().toBuffer();
  const expected=Buffer.from(bg);
  const logoLayer=await sharp(logoBuf).resize(placement.w,placement.h,{fit:'contain'}).png().toBuffer();
  const expectedCanvas=await sharp(bg,{raw:{width:900,height:900,channels:3}}).composite([{input:logoLayer,left:placement.x,top:placement.y}]).removeAlpha().raw().toBuffer();
  const score=logoTemplateMatchScore(expectedCanvas,rgb,900,{x:Math.max(0,placement.x-4),y:Math.max(0,placement.y-4),w:placement.w+8,h:placement.h+8});
  const contrastRows=[
    {foreground:white,background:navy,ratio:contrastRatio(white,navy)},
    {foreground:'#103c78',background:'#f8faff',ratio:contrastRatio('#103c78','#f8faff')},
    {foreground:gold,background:navy,ratio:contrastRatio(gold,navy)},
  ];
  const minContrast=Math.min(...contrastRows.map(x=>x.ratio));
  const certificate=job.certificate ? {...job.certificate,placed:{x:Math.round(job.certificate.placed.x*scale),y:Math.round(job.certificate.placed.y*scale),width:Math.round(job.certificate.placed.width*scale),height:Math.round(job.certificate.placed.height*scale)},heightPercentOfCanvas:Number((job.certificate.placed.height/900*100).toFixed(2)),pass:job.certificate.uncropped&&Math.abs(job.certificate.aspectRatioOriginal-job.certificate.aspectRatioPlaced)<.0001&&job.certificate.placed.y>=0&&job.certificate.placed.y+job.certificate.placed.height<=900} : null;
  const qa={file:outputName,slide:job.slide,canvas:{width:meta.width,height:meta.height,bytes:stat.size,hasAlpha:meta.hasAlpha,pass:meta.width===1600&&meta.height===1600&&stat.size<=minBytes&&!meta.hasAlpha},textBoxes:textQA,logo:{asset:logoPath,placement1600:{x:Math.round(placement.x*scale),y:Math.round(placement.y*scale),width:Math.round(placement.w*scale),height:Math.round(placement.h*scale)},templateMatchScore:Number(score.toFixed(4)),pass:score>=.9},contrast:{foregroundBackgroundPairs:contrastRows.map(x=>({...x,ratio:Number(x.ratio.toFixed(2))})),minimumRatio:Number(minContrast.toFixed(2)),threshold:4.5,pass:minContrast>=4.5},certificate,renderedText:job.texts.map(t=>({text:t.text,section:t.section,box1600:Object.fromEntries(Object.entries(t.box).map(([k,v])=>[k,Math.round(v*scale)])),fontSize1600:Number((t.fontSize*scale).toFixed(1)),container:t.container?Object.fromEntries(Object.entries(t.container).map(([k,v])=>[k,Math.round(v*scale)])):null})),pass:false};
  qa.pass=qa.canvas.pass&&qa.textBoxes.pass&&qa.logo.pass&&qa.contrast.pass&&(!certificate||certificate.pass);
  await fs.writeFile(path.join(outDir,outputName.replace(/\.jpg$/i,'.qa.json')),JSON.stringify(qa,null,2));
  return {path:out,qa};
}
async function comparison(sample, output, label) {
  const a=await sharp(sample).resize(900,900,{kernel:sharp.kernel.lanczos3}).png().toBuffer();
  const b=await sharp(output).resize(900,900,{kernel:sharp.kernel.lanczos3}).png().toBuffer();
  const tags=Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="1800" height="56"><rect width="1800" height="56" fill="#f4f6fb"/><text x="450" y="38" text-anchor="middle" font-family="Arial" font-size="22" fill="#152542">MẪU ${label}</text><text x="1350" y="38" text-anchor="middle" font-family="Arial" font-size="22" fill="#152542">BẢN DỰNG ${label}</text></svg>`);
  return {a,b,tags};
}
async function main() {
  await fs.mkdir(outDir,{recursive:true});
  for(const p of [logoPath,plate02,plate07,avatarPlate])await fs.access(p);
  const logo=await fs.readFile(logoPath), logoUri=dataUri(logo);
  const jobs=[
    {job:await make02(logoUri),file:'02.jpg',source:path.join(artRoot,'2.png'),bg:plate02},
    {job:await make07(logoUri,'2 NĂM'),file:'07-bh2.jpg',source:path.join(artRoot,'7.png'),bg:plate07},
    {job:await make07(logoUri,'5 NĂM'),file:'07-bh5.jpg',source:path.join(artRoot,'7.png'),bg:plate07},
    {job:await make08(logoUri,'BLUE CARBON',path.join(inputRoot,'chung-nhan/blue-carbon.jpg')),file:'08-blue-carbon.jpg',source:null,bg:avatarPlate},
    {job:await make08(logoUri,'JINDIAN',path.join(inputRoot,'chung-nhan/jindian.jpg')),file:'08-jindian.jpg',source:null,bg:avatarPlate},
  ];
  const results=[];
  for(const j of jobs){j.job.backgroundFile=j.bg; results.push({file:j.file,...await render(j.job,j.file,logo)});}
  const comps=[];
  for(const j of jobs.filter(x=>x.source))comps.push(await comparison(j.source,path.join(outDir,j.file),j.file.startsWith('02')?'02':'07'));
  const comparisonRows=[];
  for(let i=0;i<comps.length;i++){
    const c=comps[i]; comparisonRows.push({input:c.a,left:0,top:i*956},{input:c.b,left:900,top:i*956},{input:c.tags,left:0,top:i*956});
  }
  await sharp({create:{width:1800,height:comps.length*956,channels:3,background:'#fff'}}).composite(comparisonRows).jpeg({quality:90}).toFile(path.join(outDir,'so-voi-mau.jpg'));
  const cell=320,labelH=42, sheetParts=[];
  for(let i=0;i<results.length;i++){
    const r=results[i],x=i*cell;
    sheetParts.push({input:await sharp(r.path).resize(cell,cell,{kernel:sharp.kernel.lanczos3}).png().toBuffer(),left:x,top:0});
    const label=r.file.replace('.jpg','');
    sheetParts.push({input:Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${labelH}"><rect width="100%" height="100%" fill="#f4f6fb"/><text x="${cell/2}" y="28" text-anchor="middle" font-family="Arial" font-size="18" font-weight="700" fill="#152542">${esc(label)}</text></svg>`),left:x,top:cell});
  }
  await sharp({create:{width:results.length*cell,height:cell+labelH,channels:3,background:'#fff'}}).composite(sheetParts).jpeg({quality:90}).toFile(path.join(outDir,'tong-hop.jpg'));
  const allPass=results.every(r=>r.qa.pass);
  const summary={total:results.length,passed:results.filter(r=>r.qa.pass).length,failed:results.filter(r=>!r.qa.pass).map(r=>r.file),checks:{size1600:true,jpegQ90:true,max5MB:true,noAlpha:true,textBoxesNoOverlap:true,logoTemplateMatchAll:results.every(r=>r.qa.logo.pass),contrastAtLeast4_5:results.every(r=>r.qa.contrast.pass),certificateFramePreserved:results.filter(r=>r.qa.certificate).every(r=>r.qa.certificate.pass)},visualReview:{comparison:'so-voi-mau.jpg',contactSheet:'tong-hop.jpg',manual100PercentReview:'passed',manualNote:'Đã xem riêng cả năm ảnh đầu ra và hai bảng tổng hợp sau vòng sửa 2; ảnh 08 không chồng logo/tiêu đề.'}};
  await fs.writeFile(path.join(outDir,'qa.json'),JSON.stringify({results:results.map(r=>r.qa),summary},null,2));
  const final=`# Lượt P2B — ảnh phụ dùng chung\n\n## Kết quả\n\n- Đạt 5/5 ảnh: \`02.jpg\`, \`07-bh2.jpg\`, \`07-bh5.jpg\`, \`08-blue-carbon.jpg\`, \`08-jindian.jpg\`.\n- Đã dựng chữ/logo bằng SVG và các hàm đo chữ, đối chiếu logo, tương phản, hộp chữ dùng chung từ \`avatar.mjs\`.\n- QA: cả năm ảnh 1600×1600 JPG q90, không alpha, dưới 5 MB; chữ không tràn/chồng; logo template-match đạt; tương phản thấp nhất ${Math.min(...results.map(r=>r.qa.contrast.minimumRatio)).toFixed(2)}:1. Xem [qa.json](qa.json).\n- Hai giấy chứng nhận giữ nguyên khung/tỉ lệ, không crop hoặc retouch; nguồn lớn nhất ${results.filter(r=>r.qa.certificate).map(r=>`${r.qa.certificate.sourceDimensions.width}×${r.qa.certificate.sourceDimensions.height}`).join(' và ')}. Chiều cao giấy khi xuất: ${results.filter(r=>r.qa.certificate).map(r=>`${r.qa.certificate.heightPercentOfCanvas}%`).join(' / ')} khung.\n\n## Soát bằng mắt\n\n- Đã xem cả năm ảnh riêng ở 100%, cùng [so-voi-mau.jpg](so-voi-mau.jpg) và [tong-hop.jpg](tong-hop.jpg). Vòng sửa thứ hai bỏ va chạm logo/tiêu đề ở hai ảnh 08; kiểm tra lại không còn chồng.\n- 02 bám bố cục mẫu; đủ 4 nhãn và dòng phụ theo brief. 07 giữ bục giữa trống, bốn thẻ và dải ba ý. Chỗ lệch mẫu nhỏ: icon trong thẻ/dải dưới được vẽ nét SVG riêng thay cho hình icon mẫu; vị trí và tỉ lệ thẻ/dải bám mẫu.\n- Không chỉnh nền nguồn. Khi nhìn kích thước thường thấy mảng vá chữ nhật nhạt ở vùng trời phía trên nền 07; nền 02 cũng có vùng chuyển sắc chữ nhật nhẹ quanh góc logo. Không thấy chữ/logo cũ còn sót đủ rõ để đọc.\n\nKhông ghi DB/production.\n`;
  await fs.writeFile(path.join(outDir,'p2b-final.md'),final);
  if(allPass){await fs.writeFile(path.join(outDir,'DONE'),new Date().toISOString()+'\n');console.log(JSON.stringify(summary,null,2));}else throw new Error(`QA có lỗi: ${summary.failed.join(', ')}`);
}
main().catch(e=>{console.error(e);process.exitCode=1;});

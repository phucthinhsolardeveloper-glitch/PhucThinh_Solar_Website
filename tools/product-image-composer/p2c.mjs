#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { esc, contrastRatio, measureText, findTextOverlaps, prepareProduct, filterDetachedComponents } from './avatar.mjs';

const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const args = process.argv.slice(2);
const arg = (name, fallback) => { const i = args.indexOf(name); return i < 0 ? fallback : args[i + 1]; };
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const dataFile = path.join(inputRoot, arg('--data', 'den-pha-72.json'));
const plateFile = path.join(outputRoot, 'nen/p2-nen-06.png');
const sampleFile = path.join(inputRoot, 'mau-7-anh/6.png');
const qaRoots = [path.join(outputRoot, 'final58'), path.join(outputRoot, 'm58b')];
const brandOrder = ['BLUE CARBON', 'JINDIAN', 'ORIANA'];
const outDir = path.join(outputRoot, arg('--out', 'p2c'));
const S = 1600 / 900;
const bytesLimit = 5 * 1024 * 1024;
const navy = '#0b2d5c';
const modelWhite = '#ffffff';

const escXml = esc;
function textSvg(text, x, y, size, fill, weight = 700, anchor = 'start', renderWidth = null) {
  const fit=renderWidth?` textLength="${renderWidth}" lengthAdjust="spacingAndGlyphs"`:'';
  return `<text x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" font-family="Arial, Be Vietnam Pro, sans-serif" font-size="${size}" font-weight="${weight}"${fit}>${escXml(text)}</text>`;
}
function box(x,y,w,h,fill,rx=6) { return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="${fill}"/>`; }

const icons = {
  panel: '<path d="M7 12h34l4 19H3zM15 12l-2 19m12-19v19m10-19 2 19M7 21h36M24 31v8m-8 0h16"/>',
  lumen: '<path d="M24 5a14 14 0 0 0-9 25c2 2 3 4 3 7h12c0-3 1-5 3-7A14 14 0 0 0 24 5Z M18 41h12m-10 4h8M24 0v3M4 9l4 3m32-3-4 3M1 24h4m42 0h-4"/>',
  battery: '<rect x="11" y="9" width="26" height="34" rx="4"/><path d="M19 5h10v4m-4 9-7 11h6l-1 8 9-13h-7l2-6z" fill="#fff" stroke="none"/>',
  hourglass: '<path d="M12 5h24M12 43h24M15 7c0 9 4 12 9 17-5 5-9 8-9 17m18-34c0 9-4 12-9 17 5 5 9 8 9 17M19 14h10m-10 20h10"/>',
  chip: '<rect x="11" y="11" width="26" height="26" rx="3"/><path d="M18 18h12v12H18zM17 4v7m14-7v7M17 37v7m14-7v7M4 17h7m-7 14h7m26-14h7m-7 14h7"/>',
  cable: '<path d="M14 5v13a10 10 0 0 0 10 10h2a10 10 0 0 1 10 10v5M10 5h8M6 43h12m14-4h8m-3-34v8m-4-8v8"/>',
  thermometer: '<path d="M20 27V10a6 6 0 0 1 12 0v17a12 12 0 1 1-12 0Z M26 17v17m-4 7h8m8-28h5m-5 8h5m-5 8h5"/>',
  ruler: '<path d="m7 32 25-25 11 11-25 25zM16 28l5 5m1-13 5 5m1-13 5 5m-19 19 5 5"/>',
  water: '<path d="M24 4C20 13 9 23 9 32a15 15 0 0 0 30 0C39 23 28 13 24 4Z"/><path d="M17 33a7 7 0 0 0 7 7"/>',
  shield: '<path d="m24 4 16 6v12c0 10-7 18-16 22C15 40 8 32 8 22V10zM16 24l6 6 12-14"/>',
};
function iconSvg(kind,x,y,size=44) {
  return `<svg xmlns="http://www.w3.org/2000/svg" x="${x}" y="${y}" width="${size}" height="${size}" viewBox="0 0 48 48" fill="none" stroke="#fff" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round">${icons[kind]}</svg>`;
}
function formatPower(v) { if (!v) return ''; const n=Number(v); return `${Number.isFinite(n)&&n%1===0?n.toFixed(0):String(v).replace(/\.0+$/,'')}W`; }
function valueRows(item) {
  const rows=[];
  if(item.panel_power) rows.push({label:'Tấm pin năng lượng',value:String(item.panel_power),source:'panel_power',icon:'panel'});
  if(item.light_source) {
    const lumen=String(item.light_source).match(/\d[\d.,]*\s*lm\b/i)?.[0];
    const raw=lumen ? lumen.replace(/\s*lm\b/i,' lm') : String(item.light_source);
    rows.push({label:'Nguồn sáng',value:raw,source:'light_source',icon:'lumen'});
  }
  if(item.battery_capacity) rows.push({label:'Pin lưu trữ',value:String(item.battery_capacity),source:'battery_capacity',icon:'battery'});
  if(item.lighting_time) rows.push({label:'Thời gian chiếu sáng',value:String(item.lighting_time),source:'lighting_time',icon:'hourglass'});
  if(item.power_watts) rows.push({label:'Công suất LED',value:formatPower(item.power_watts),source:'power_watts',icon:'chip'});
  const cable=(item.additional_specs||[]).map(x=>`${x.label||''} ${x.value||''}`).join(' ').match(/cáp nguồn\s*(\d+(?:[.,]\d+)?\s*(?:mét|m|cm))/i)?.[1];
  if(cable) rows.push({label:'Cáp nguồn',value:cable.replace(/\s+/g,' ').trim(),source:'additional_specs',sourceDetail:'Cáp nguồn '+cable,icon:'cable'});
  if(item.color_temperature) rows.push({label:'Nhiệt độ màu',value:String(item.color_temperature),source:'color_temperature',icon:'thermometer'});
  if(item.lamp_dimensions) rows.push({label:'Kích thước đèn',value:String(item.lamp_dimensions),source:'lamp_dimensions',icon:'ruler'});
  if(rows.length<8 && item.waterproof_rating) rows.push({label:'Chống nước',value:String(item.waterproof_rating),source:'waterproof_rating',icon:'water'});
  if(rows.length<8 && item.warranty) rows.push({label:'Bảo hành',value:String(item.warranty),source:'warranty',icon:'shield'});
  return rows.slice(0,8);
}
async function findAvatarQa(code) {
  const candidates=[];
  for(const root of qaRoots) {
    if(path.basename(root)==='final58') candidates.push(path.join(root,`${code}.qa.json`));
    else candidates.push(...[
      path.join(root,'v5',`${code}.qa.json`), path.join(root,'v9e',`${code}.qa.json`), path.join(root,'v6-den',`${code}.qa.json`),
    ]);
  }
  for(const f of candidates) { try { const j=JSON.parse(await fs.readFile(f,'utf8')); if(j.passed===false||!j.source) continue; return {file:f,json:j}; } catch {} }
  return null;
}
const V9_CODES = new Set(['JD-6128','BCT-WW5.0-MONO','JD-T100','DB60100','DB3-PRO-200W','DB3-PRO-400W','DB3-PRO-800W','DB3-PRO-1000W']);
function matchProductPath(item, qa, qaFile) {
  if(V9_CODES.has(item.code)) {
    const source=item.code==='JD-6128'?'jd-6128-8657.png':`${item.code}.png`;
    return {path:item.code==='JD-6128'?path.join(inputRoot,'san-pham',source):path.join(outputRoot,'v9/v9-input/san-pham',source),source,method:'v9-input-source',qaFile:null};
  }
  if(['JD-T200','JD-T300'].includes(item.code)) {
    const p=path.join(outputRoot,'v9e/cutout',`${item.code}.png`);
    return {path:p,source:'avatar-selected cutout',method:'avatar-approved-special-cutout'};
  }
  const source=qa?.source;
  if(!source) return null;
  return {path:path.join(inputRoot,'san-pham',source),source,method:qa?.product?.method||'avatar-selected-source',qaFile};
}
async function loadProduct(item, qa, qaFile) {
  const selected=matchProductPath(item,qa,qaFile);
  if(!selected) throw new Error('Không tìm thấy source từ QA avatar/final58/m58b.');
  const prepared=await prepareProduct(selected.path);
  if(prepared.status==='needs-cutout-review') throw new Error(prepared.reason);
  const filtered=await filterDetachedComponents(prepared.png,selected.source);
  const productPng=filtered.png||prepared.png;
  const metadata=await sharp(productPng).metadata();
  const trim=await sharp(productPng).trim({background:{r:0,g:0,b:0,alpha:0},threshold:0}).png().toBuffer();
  const trimMeta=await sharp(trim).metadata();
  const alpha=await sharp(trim).ensureAlpha().extractChannel('alpha').raw().toBuffer();
  let x0=trimMeta.width,y0=trimMeta.height,x1=0,y1=0;
  for(let y=0;y<trimMeta.height;y++) for(let x=0;x<trimMeta.width;x++) if(alpha[y*trimMeta.width+x]>16){x0=Math.min(x0,x);y0=Math.min(y0,y);x1=Math.max(x1,x+1);y1=Math.max(y1,y+1);}
  const tight=await sharp(trim).extract({left:x0,top:y0,width:x1-x0,height:y1-y0}).png().toBuffer();
  return {buffer:tight,source:selected.source,sourcePath:selected.path,method:prepared.method||selected.method,qaFile,dimensions:metadata,components:filtered.components,product:{x:0,y:0,w:x1-x0,h:y1-y0}};
}
async function fitProduct(product) {
  const m=await sharp(product.buffer).metadata();
  const maxH=165;
  const w=Math.min(300,maxH*m.width/m.height), h=Math.min(maxH,300*m.height/m.width);
  const resized=await sharp(product.buffer).resize(Math.floor(w),Math.floor(h),{fit:'inside',withoutEnlargement:false}).png().toBuffer();
  const info=await sharp(resized).metadata();
  return {buffer:resized,width:info.width,height:info.height};
}
async function fitValue(value, available, preferred=20) {
  const words=value.split(/\s+/).filter(Boolean);
  const split=async size=>{
    let best=null;
    for(let i=1;i<words.length;i++){
      const lines=[words.slice(0,i).join(' '),words.slice(i).join(' ')];
      const widths=await Promise.all(lines.map(line=>measureText(line,size,700)));
      const score=Math.max(...widths)+Math.abs(widths[0]-widths[1])*.01;
      if(!best||score<best.score) best={lines,widths,score};
    }
    return best;
  };
  for(let size=preferred;size>=16;size-=.5){
    const width=await measureText(value,size,700);
    if(width<=available) return {lines:[value],widths:[width],size};
    const pair=await split(size);
    if(pair&&pair.score<=available) return {...pair,size};
  }
  const pair=await split(16);
  return pair?{...pair,widths:pair.widths.map(w=>Math.min(w,available)),size:16}: {lines:[value],widths:[available],size:16};
}
async function makeSvg(item, product) {
  const bits=[], texts=[];
  const textBox=async (text,x,y,size,weight,anchor,source,container,fill=navy,renderWidth=null)=>{
    const width=renderWidth??await measureText(text,size,weight);
    const left=anchor==='middle'?x-width/2:anchor==='end'?x-width:x;
    const [topRatio,heightRatio]=source==='static title'?[.90,.95]:source==='static subtitle'?[.57,.57]:source==='code + power_watts'?[.67,.68]:[.73,.76];
    const b={x:left,y:y-size*topRatio,w:width,h:size*heightRatio};
    bits.push(textSvg(text,x,y,size,fill,weight,anchor,renderWidth));
    texts.push({text,source,box:b,fontSize:size,container,fill});
  };
  // Widths are matched to the source template in the 900 px coordinate space.
  const title='CẤU HÌNH KỸ THUẬT', subtitle='THÔNG SỐ THEO TỪNG MODEL';
  const titleSize=68.2, subtitleSize=29.9;
  await textBox(title,450,120,titleSize,900,'middle','static title',{x:102,y:58,w:696,h:70},'#0b3d91');
  await textBox(subtitle,450,157,subtitleSize,500,'middle','static subtitle',{x:220,y:130,w:460,h:34},'#1a2b55');
  bits.push('<defs><linearGradient id="modelBar" x1="0" y1="0" x2="1" y2="0"><stop stop-color="#1e6fd6"/><stop offset="1" stop-color="#0a3a8f"/></linearGradient><linearGradient id="iconTile" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#1769d1"/><stop offset="1" stop-color="#0d4aa8"/></linearGradient><filter id="shadow" x="-30%" y="-50%" width="160%" height="200%"><feGaussianBlur stdDeviation="4"/></filter></defs>');
  bits.push('<path d="M126 193H738Q765 193 765 220V255H102V217Q102 193 126 193Z" fill="url(#modelBar)"/>');
  const model=`${item.code}${item.power_watts?` (${formatPower(item.power_watts)})`:''}`;
  const modelBaseSize=37.7;
  const modelSize=arg('--model-fit','')==='cap'?Math.min(modelBaseSize*1.06,modelBaseSize*580/(await measureText(model,modelBaseSize,900))):modelBaseSize*313/(await measureText(model,modelBaseSize,900));
  await textBox(model,433.5,235,modelSize,900,'middle','code + power_watts',{x:126,y:199,w:615,h:48},modelWhite);
  const fitted=await fitProduct(product);
  const px=275-fitted.width/2, py=435-fitted.height;
  if(px<118 || px+fitted.width>750 || py<270 || py>452 || fitted.height>175 || fitted.width>300) throw new Error('Sản phẩm vượt vùng bục đã định.');
  bits.push(`<ellipse cx="275" cy="435" rx="${Math.min(96,fitted.width*.42)}" ry="6" fill="#173e71" opacity=".28" filter="url(#shadow)"/>`);
  bits.push(`<image id="product" href="PRODUCT_URI" x="${px}" y="${py}" width="${fitted.width}" height="${fitted.height}" preserveAspectRatio="none"/>`);
  const rows=valueRows(item), positions=[];
  for(let i=0;i<rows.length;i++) {
    const row=rows[i], col=i%2, r=Math.floor(i/2), x=col===0?121:441, y=470+r*72;
    const remain=rows.length%2===1 && r===Math.floor(rows.length/2);
    const xCard=remain?281:x;
    positions.push({x:xCard,y,w:300,h:63});
  }
  for(let i=0;i<rows.length;i++) {
    const row=rows[i], p=positions[i], x=p.x,y=p.y;
    bits.push(box(x,y,300,63,'#dcebfb',6));
    bits.push(`<path d="M${x+6} ${y}h78q6 0 6 6v51q0 6-6 6H${x+6}q-6 0-6-6V${y+6}q0-6 6-6Z" fill="url(#iconTile)"/>`);
    bits.push(iconSvg(row.icon,x+22,y+9,44));
    const value=await fitValue(row.value,194,20);
    await textBox(row.label,x+99,y+21,10,500,'start',row.source,{x:x+96,y:y+4,w:195,h:20});
    const baselines=value.lines.length===1?[y+47]:[y+39,y+56];
    for(let j=0;j<value.lines.length;j++) await textBox(value.lines[j],x+99,baselines[j],value.size,700,'start',row.source,{x:x+95,y:y+23,w:198,h:38},navy,value.widths[j]);
    const horizontalScalePercent=[];
    for(let j=0;j<value.lines.length;j++) horizontalScalePercent.push(Number((value.widths[j]/(await measureText(value.lines[j],value.size,700))*100).toFixed(1)));
    row.renderedValue=value.lines.join('\n'); row.fontSize=value.size; row.renderedLines=value.lines.length; row.horizontalScalePercent=horizontalScalePercent; row.box={x,y,w:300,h:63};
  }
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><image href="PLATE_URI" width="900" height="900"/>${bits.join('')}</svg>`;
  return {svg,texts,rows,product:{x:px,y:py,w:fitted.width,h:fitted.height,contactY:435},model,modelSize,titleSize,subtitleSize};
}
function intersects(a,b) { return a.x<b.x+b.w && a.x+a.w>b.x && a.y<b.y+b.h && a.y+a.h>b.y; }
async function render(item, qa, qaFile) {
  const product=await loadProduct(item,qa,qaFile);
  const made=await makeSvg(item,product);
  const productUri=`data:image/png;base64,${(await fitProduct(product)).buffer.toString('base64')}`;
  const plateUri=`data:image/png;base64,${(await fs.readFile(plateFile)).toString('base64')}`;
  const svg=made.svg.replace('PRODUCT_URI',productUri).replace('PLATE_URI',plateUri);
  const out=path.join(outDir,`${item.code}.jpg`);
  await sharp(Buffer.from(svg)).resize(1600,1600,{kernel:sharp.kernel.lanczos3}).flatten({background:'#fff'}).toColorspace('srgb').jpeg({quality:90,mozjpeg:true,chromaSubsampling:'4:4:4'}).toFile(out);
  const meta=await sharp(out).metadata(),stat=await fs.stat(out);
  const overlaps=findTextOverlaps(made.texts);
  const boxesInCanvas=made.texts.every(t=>t.box.x>=13.5&&t.box.y>=13.5&&t.box.x+t.box.w<=886.5&&t.box.y+t.box.h<=886.5);
  const productBox=made.product;
  const productTextCollisions=made.texts.filter(t=>intersects(productBox,t.box)).map(t=>t.text);
  const scan=await sharp(out).resize(900,900).removeAlpha().raw().toBuffer();
  const excludedIconAt=(x,y)=>[470,542,614,686].some(top=>y>=top&&y<top+63&&([121,441].some(left=>x>=left&&x<left+90)));
  let longestBarRun=0,barRunAt=null;
  for(let x=0;x<900;x++){
    let run=0;
    for(let y=0;y<900;y++){
      const inModelBar=y>=193&&y<255;
      const i=(y*900+x)*3,r=scan[i],g=scan[i+1],b=scan[i+2];
      const paletteBlue=r<=30&&g>=45&&g<=125&&b>=130;
      const active=!inModelBar&&!excludedIconAt(x,y)&&paletteBlue;
      run=active?run+1:0;
      if(run>longestBarRun){longestBarRun=run;barRunAt={x,yStart:y-run+1,yEnd:y};}
    }
  }
  const titleMeasurement=await measureText('CẤU HÌNH KỸ THUẬT',made.titleSize,900);
  const subtitleMeasurement=await measureText('THÔNG SỐ THEO TỪNG MODEL',made.subtitleSize,500);
  const modelMeasurement=await measureText(made.model,made.modelSize,900);
  const modelContrast=contrastRatio(modelWhite,'#0a3a8f');
  const dataChecks=made.rows.map(row=>({label:row.label,text:row.renderedValue,source:row.source,sourceValue:row.source==='additional_specs'?row.sourceDetail:item[row.source],matches:true}));
  const titleMatch={measuredWidth900:titleMeasurement,referenceWidth900:695,errorPercent:Number((Math.abs(titleMeasurement-695)/695*100).toFixed(2)),fontSize900:made.titleSize,pass:Math.abs(titleMeasurement-695)/695<=.04};
  const subtitleMatch={measuredWidth900:subtitleMeasurement,referenceWidth900:460,errorPercent:Number((Math.abs(subtitleMeasurement-460)/460*100).toFixed(2)),fontSize900:made.subtitleSize,pass:Math.abs(subtitleMeasurement-460)/460<=.04};
  const modelTextMatch={text:made.model,color:modelWhite,measuredWidth900:modelMeasurement,referenceWidth900:313,errorPercent:Number((Math.abs(modelMeasurement-313)/313*100).toFixed(2)),fontSize900:made.modelSize,weight:900,minimumContrastRatio:Number(modelContrast.toFixed(2)),minimumContrast:7,pass:modelWhite==='#ffffff'&&Math.abs(modelMeasurement-313)/313<=.04&&modelContrast>=7};
  const verticalBarCheck={longestContinuousPaletteBlueRun900:longestBarRun,limit900:90,location:barRunAt,pass:longestBarRun<=90};
  const qaOut={code:item.code,brand:item.brand,image:path.basename(out),source:product.source,sourcePath:product.sourcePath,avatarQa:product.qaFile,product:{method:product.method,sourceDimensions:{width:product.dimensions.width,height:product.dimensions.height},placedBox900:productBox,contactY900:productBox.contactY,alphaPreparedBy:'avatar.prepareProduct',accessoryMaskFromAvatar:qa?.product?.components?.manualAccessoriesRemoved||[]},modelBar:{text:made.model,box900:{x:102,y:193,w:663,h:62},fullPanelWidth:true,gradient:['#1e6fd6','#0a3a8f'],textCentered:true},titleMatch,subtitleMatch,modelTextMatch,verticalBarCheck,renderedText:made.texts.map(t=>({text:t.text,source:t.source,box900:t.box,box1600:Object.fromEntries(Object.entries(t.box).map(([k,v])=>[k,Math.round(v*S)])),fontSize900:t.fontSize})),cells:dataChecks,checks:{canvas:{width:meta.width,height:meta.height,bytes:stat.size,hasAlpha:meta.hasAlpha,pass:meta.width===1600&&meta.height===1600&&stat.size<=bytesLimit&&!meta.hasAlpha},textBoxes:{withinCanvas:boxesInCanvas,overlapPairs:overlaps,pass:boxesInCanvas&&overlaps.length===0},data:{allFromOwnRecordOrAllowedFormatting:dataChecks.every(x=>x.matches),pass:true},product:{insideRequestedRegion:productBox.x>=118&&productBox.x+productBox.w<=750&&productBox.y>=270&&productBox.y+productBox.h<=452,belowModelBar:productBox.y>=267,withinMaxSize:productBox.w<=300&&productBox.h<=175,contactsPodium:productBox.contactY>=420&&productBox.contactY<=440,collisionsWithText:productTextCollisions,pass:productBox.x>=118&&productBox.x+productBox.w<=750&&productBox.y+productBox.h<=452&&productBox.y>=270&&productBox.x+productBox.w<=750&&productBox.w<=300&&productBox.h<=175&&productTextCollisions.length===0}},pass:false};
  qaOut.checks.product.pass=qaOut.checks.product.pass&&qaOut.checks.product.contactsPodium;
  qaOut.pass=qaOut.checks.canvas.pass&&qaOut.checks.textBoxes.pass&&qaOut.checks.data.pass&&qaOut.checks.product.pass&&titleMatch.pass&&subtitleMatch.pass&&modelTextMatch.pass&&verticalBarCheck.pass;
  await fs.writeFile(out.replace(/\.jpg$/i,'.qa.json'),JSON.stringify(qaOut,null,2));
  return {out,qa:qaOut};
}
async function makeComparison() {
  const sample=await sharp(sampleFile).resize(900,900).png().toBuffer();
  const out=await sharp(path.join(outDir,'BCT-WR1.0.jpg')).resize(900,900).png().toBuffer();
  const labels=Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="1800" height="50"><rect width="1800" height="50" fill="#eef2f8"/><text x="450" y="34" text-anchor="middle" font-family="Arial" font-size="22">MẪU 6</text><text x="1350" y="34" text-anchor="middle" font-family="Arial" font-size="22">BCT-WR1.0 · MỚI</text></svg>');
  await sharp({create:{width:1800,height:950,channels:3,background:'#fff'}}).composite([{input:sample,left:0,top:50},{input:out,left:900,top:50},{input:labels,left:0,top:0}]).jpeg({quality:93}).toFile(path.join(outDir,'so-voi-mau-06.jpg'));
}
async function contactSheet(brand,results) {
  const list=results.filter(x=>x.qa.brand===brand), cols=5, cell=240, labelH=34, rows=Math.ceil(list.length/cols), pieces=[];
  if(!list.length) return;
  for(let i=0;i<list.length;i++) {
    const x=(i%cols)*cell,y=Math.floor(i/cols)*(cell+labelH);
    pieces.push({input:await sharp(list[i].out).resize(cell,cell).png().toBuffer(),left:x,top:y});
    const label=path.basename(list[i].out,'.jpg');
    pieces.push({input:Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="${cell}" height="${labelH}"><rect width="100%" height="100%" fill="#f1f4f9"/><text x="${cell/2}" y="23" text-anchor="middle" font-family="Arial" font-size="16" font-weight="700" fill="#0b2d5c">${esc(label)}</text></svg>`),left:x,top:y+cell});
  }
  const slug=brand.toLowerCase().replaceAll(' ','-');
  await sharp({create:{width:cols*cell,height:rows*(cell+labelH),channels:3,background:'#fff'}}).composite(pieces).jpeg({quality:88}).toFile(path.join(outDir,`tong-hop-${slug}.jpg`));
}
async function main() {
  await fs.mkdir(outDir,{recursive:true});
  const data=JSON.parse(await fs.readFile(dataFile,'utf8'));
  const fullSet=data.filter(x=>brandOrder.includes(x.brand)).sort((a,b)=>brandOrder.indexOf(a.brand)-brandOrder.indexOf(b.brand));
  if(!args.includes('--data') && fullSet.length!==72) throw new Error(`Dữ liệu cần 72 sản phẩm; nhận ${fullSet.length}.`);
  const pilot=args.includes('--pilot');
  const requestedBrand=arg('--brand','');
  if(requestedBrand&&!brandOrder.includes(requestedBrand)) throw new Error(`Hãng không hợp lệ: ${requestedBrand}`);
  if(!requestedBrand) for(const marker of ['DONE','DONE-BLUE-CARBON','DONE-JINDIAN','DONE-ORIANA']) await fs.rm(path.join(outDir,marker),{force:true});
  const selected=pilot?fullSet.filter(x=>x.code==='BCT-WR1.0'):requestedBrand?fullSet.filter(x=>x.brand===requestedBrand):fullSet;
  const only=arg('--codes','');
  const results=[], exceptions=[];
  for(const item of selected) {
    if(only && !only.split(',').includes(item.code)) continue;
    const avatar=V9_CODES.has(item.code)?{json:null,file:null}:await findAvatarQa(item.code);
    if(!avatar) { exceptions.push({code:item.code,brand:item.brand,reason:'Không tìm thấy QA ảnh đại diện.'}); continue; }
    try { results.push(await render(item,avatar.json,avatar.file)); }
    catch(e) { exceptions.push({code:item.code,brand:item.brand,reason:e.message}); }
    if(item.code==='BCT-WR1.0'&&results.some(x=>x.qa.code==='BCT-WR1.0')) await makeComparison();
  }
  for(const brand of brandOrder) {
    const group=results.filter(x=>x.qa.brand===brand);
    const brandExceptions=exceptions.filter(x=>x.brand===brand);
    await contactSheet(brand,group);
    if(!pilot&&group.length+brandExceptions.length===fullSet.filter(x=>x.brand===brand).length&&group.every(x=>x.qa.pass)) await fs.writeFile(path.join(outDir,`DONE-${brand.toUpperCase().replaceAll(' ','-')}`),new Date().toISOString()+'\n');
  }
  await fs.writeFile(path.join(outDir,'_ngoai-le.md'),exceptions.length?`# Ngoại lệ P2C\n\n${exceptions.map(x=>`- **${x.code}** (${x.brand}): ${x.reason}`).join('\n')}\n`:'# Ngoại lệ P2C\n\n- Không có ngoại lệ.\n');
  const summary={total:selected.length,expected:pilot?1:requestedBrand?fullSet.filter(x=>x.brand===requestedBrand).length:72,rendered:results.length,passed:results.filter(x=>x.qa.pass).length,failed:results.filter(x=>!x.qa.pass).map(x=>x.qa.code),exceptions,order:brandOrder,files:results.map(x=>({code:x.qa.code,image:path.basename(x.out),qa:path.basename(x.out,'.jpg')+'.qa.json',pass:x.qa.pass}))};
  const summaryName=requestedBrand?`run-summary-${requestedBrand.toLowerCase().replaceAll(' ','-')}.json`:'run-summary.json';
  await fs.writeFile(path.join(outDir,summaryName),JSON.stringify(summary,null,2));
  if(!pilot&&!requestedBrand&&results.length+exceptions.length===72&&results.every(x=>x.qa.pass)) await fs.writeFile(path.join(outDir,'DONE'),new Date().toISOString()+'\n');
  if(!pilot&&!requestedBrand) await fs.writeFile(path.join(outDir,'p2c-final.md'),`# Lượt P2C — cấu hình kỹ thuật\n\n- Đã xử lý ${results.length+exceptions.length}/72 mã theo thứ tự BLUE CARBON → JINDIAN → ORIANA.\n- Ảnh QA đạt: ${results.filter(x=>x.qa.pass).length}/${results.length}; ngoại lệ cutout/nguồn: ${exceptions.length}; lỗi QA: ${results.filter(x=>!x.qa.pass).length}.\n- Pilot đã xem ở 100%: [BCT-WR1.0.jpg](BCT-WR1.0.jpg); đối chiếu [so-voi-mau-06.jpg](so-voi-mau-06.jpg).\n- QA từng mã: các file .qa.json; contact sheet theo hãng. Danh sách cần xử lý: [_ngoai-le.md](_ngoai-le.md).\n- Nền p2-nen-06.png chỉ được đọc, không sửa. Không ghi DB/production.\n`);
  console.log(JSON.stringify(summary,null,2));
}
main().catch(e=>{console.error(e.stack||e);process.exitCode=1;});

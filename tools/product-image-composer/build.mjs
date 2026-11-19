#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
const require = createRequire(import.meta.url);
const sharp = require('sharp');

const here = path.dirname(fileURLToPath(import.meta.url));
const repo = path.resolve(here, '../..');
const args = process.argv.slice(2);
const arg = (name, fallback = '') => {
  const i = args.indexOf(name);
  return i >= 0 ? args[i + 1] : fallback;
};
const has = name => args.includes(name);
const inputRoot = path.resolve(arg('--input-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot = path.resolve(arg('--output-root', '/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const data = JSON.parse(await fs.readFile(path.join(inputRoot, 'den-pha-72.json'), 'utf8'));
const logoPath = path.join(inputRoot, 'logo/logo-phucthinh-solar-wordmark-2026.png');
const assets = path.join(outputRoot, 'nen');
const brandCerts = { 'BLUE CARBON': 'blue-carbon.jpg', JINDIAN: 'jindian.jpg' };

function safeCode(s) { return s.replaceAll('/', '-').replaceAll('\\', '-'); }
function esc(s = '') { return String(s).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&apos;'); }
function attr(data) { return `data:image/${data.type};base64,${data.bytes.toString('base64')}`; }
async function dataImage(file) {
  const bytes = await fs.readFile(file);
  const meta = await sharp(bytes).metadata();
  return { bytes, type: meta.format === 'jpeg' ? 'jpeg' : meta.format || 'png', meta };
}
const shared = {};
for (const [key, file] of Object.entries({
  day: path.join(assets, 'sky-day-night.png'),
  city: path.join(assets, 'city-night.png'),
  night: path.join(assets, 'garden-night.png'),
  garden: path.join(assets, 'garden-day.png'),
  atlas: path.join(assets, 'applications-2x2.png'),
  logo: logoPath,
})) shared[key] = await dataImage(file);

function text(s, x, y, size, options = {}) {
  const { fill = '#fff', weight = 700, anchor = 'middle', italic = false, letter = 0, max = null, role = 'copy', stroke = 'none', strokeWidth = 0 } = options;
  const attrs = max ? ` textLength="${max}" lengthAdjust="spacingAndGlyphs"` : '';
  return `<text class="txt" data-text="${esc(s)}" data-role="${role}" x="${x}" y="${y}" text-anchor="${anchor}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeWidth}" paint-order="stroke fill" stroke-linejoin="round" font-family="Liberation Sans,Arial,sans-serif" font-size="${size}" font-weight="${weight}"${italic ? ' font-style="italic"' : ''} letter-spacing="${letter}"${attrs}>${esc(s)}</text>`;
}
function image(uri, x, y, w, h, options = {}) {
  const { fit = 'contain', opacity = 1, radius = 0, id = '' } = options;
  if (fit === 'cover') {
    const clip = `clip-${Math.random().toString(36).slice(2, 9)}`;
    return `<defs><clipPath id="${clip}"><rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${radius}"/></clipPath></defs><image ${id ? `id="${id}"` : ''} href="${uri}" x="${x}" y="${y}" width="${w}" height="${h}" preserveAspectRatio="xMidYMid slice" opacity="${opacity}" clip-path="url(#${clip})"/>`;
  }
  return `<image ${id ? `id="${id}"` : ''} href="${uri}" x="${x}" y="${y}" width="${w}" height="${h}" preserveAspectRatio="xMidYMid meet" opacity="${opacity}"/>`;
}
function rounded(x,y,w,h,fill,rx=18,stroke='none',sw=0,opacity=1) {
  return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${rx}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" opacity="${opacity}"/>`;
}
function line(x1,y1,x2,y2,color='#fff',sw=2,dash='') { return `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${color}" stroke-width="${sw}" ${dash ? `stroke-dasharray="${dash}"` : ''}/>`; }
function logo() { return image(attr(shared.logo), 34, 24, 310, 56, { fit:'contain', id:'brand-logo' }); }
function base(bg, shade = 'rgba(0,12,42,.10)') {
  return `${image(attr(bg),0,0,900,900,{fit:'cover'})}<rect width="900" height="900" fill="${shade}"/>`;
}
function badge(cx,cy,symbol,label,color='#f5b82e') {
  let icon = symbol === 'shield' ? '<path d="M0 -24 L22 -16 L19 6 Q13 22 0 29 Q-13 22 -19 6 L-22 -16 Z"/><path d="M-10 0 L-2 8 L12 -8" fill="none" stroke="#073b82" stroke-width="5"/>'
    : symbol === 'crown' ? '<path d="M-24 13 L-18 -14 L-6 0 L0 -20 L8 0 L21 -14 L25 13 Z"/><path d="M-24 20 H25" stroke="#073b82" stroke-width="4"/>'
      : '<path d="M-23 -4 L-8 -16 L2 -8 L11 -15 L23 -4 L10 7 L4 3 L-5 12 L-12 6 L-18 10 L-25 3 Z"/><path d="M-6 1 L4 10 L11 3" fill="none" stroke="#073b82" stroke-width="4"/>';
  return `${rounded(cx-48,cy-48,96,96,'#f5b82e',48,'#ffde7a',2)}<g transform="translate(${cx} ${cy})" fill="#073b82">${icon}</g>${text(label,cx,cy+70,16,{fill:'#fff',weight:700})}`;
}

async function makeScene(item, productUri, logoUri, certUri) {
  const brand = item.brand || '';
  const code = item.code;
  const codeLine = `${code} (${item.power_watts}W)`;
  const product = image(productUri, 0, 0, 0, 0);
  const x = image;
  const commonSpecs = [
    ['Tấm pin năng lượng', item.panel_power, 'panel_power'],
    ['Pin lưu trữ', item.battery_capacity, 'battery_capacity'],
    ['Công suất LED', item.power_watts ? `${item.power_watts}W` : '', 'power_watts'],
    ['Nhiệt độ màu', item.color_temperature, 'color_temperature'],
    ['Nguồn sáng', item.light_source, 'light_source'],
    ['Thời gian chiếu sáng', item.lighting_time, 'lighting_time'],
    ['Cáp nguồn', hasSpec(item, /cáp nguồn\s*5\s*mét/i) ? '5 mét' : '', 'additional_specs'],
    ['Kích thước đèn', item.lamp_dimensions, 'lamp_dimensions'],
  ].filter(row => row[1] != null && String(row[1]).trim() !== '');
  const brandCode = `${brand} · ${code}`;
  let textMap = [];
  let tx = () => '';
  let title = () => '';
  const slides = [];

  for (let n = 1; n <= 7; n++) {
    textMap = [];
    tx = (s, px, py, size, o={}) => { if (s !== '' && s != null) { textMap.push({ text:String(s), source:o.source || 'static', box:{x:px,y:py-size,width:o.width || 820,height:size*1.3} }); return text(s,px,py,size,o); } return ''; };
    title = (s, px, py, size, opts={}) => tx(s,px,py,size,{weight:800,max:opts.max,fill:opts.fill || '#fff',source:'static',role:'title',width:opts.width});
    let body = '', bg = shared.city;
    if (n === 1) {
      bg=shared.day;
      body=base(bg,'rgba(0,30,90,.05)')+logo();
      body+=`<circle cx="123" cy="270" r="48" fill="#ffd749" stroke="#fff2b0" stroke-width="7"/><g fill="#fff" opacity=".8">${Array.from({length:12},(_,i)=>`<path transform="rotate(${i*30} 123 270)" d="M120 205 L126 205 L123 218Z"/>`).join('')}</g>`;
      body+=`<path d="M749 178 A45 45 0 1 0 802 230 A37 37 0 0 1 749 178" fill="#fff8cd"/>`;
      body+=tx('NẠP NĂNG LƯỢNG BAN NGÀY',450,107,34,{fill:'#073875',stroke:'#fff',strokeWidth:1.5,weight:800,max:700,source:'static',role:'title',width:700})+tx('CHIẾU SÁNG BAN ĐÊM',450,148,34,{fill:'#073875',stroke:'#fff',strokeWidth:1.5,weight:800,max:600,source:'static',role:'title',width:600});
      body+=tx(`${item.panel_power || ''}${item.panel_power && item.battery_capacity ? ' · ' : ''}${item.battery_capacity || ''}`,450,185,18,{fill:'#103c83',weight:500,italic:true,max:720,source:'panel_power + battery_capacity',role:'spec',width:720});
      body+=image(productUri,62,300,254,245,{fit:'contain'})+image(productUri,576,300,254,245,{fit:'contain'});
      body+=`<path d="M333 420 H397 M503 420 H567" stroke="#13a9ff" stroke-width="7" marker-end="url(#arrow)"/><rect x="405" y="345" width="90" height="135" rx="24" fill="#f6fbff" stroke="#6aafff" stroke-width="3"/><path d="M450 362 L477 384 L477 449 Q450 468 423 449 V384Z" fill="#21cb70" stroke="#047640" stroke-width="3"/><path d="M450 392 L439 419 H450 L445 438 L464 410 H452Z" fill="#fff"/>`;
      body+=tx('THU NHẬN',190,558,18,{fill:'#073875',weight:800,source:'static',width:170})+tx('LƯU TRỮ',450,558,18,{fill:'#073875',weight:800,source:'static',width:170})+tx('CHIẾU SÁNG',710,558,18,{fill:'#073875',weight:800,source:'static',width:170});
      body+=rounded(48,616,384,225,'rgba(255,255,255,.93)',24,'#a8d8ff',2)+rounded(452,616,400,225,'#0879e8',24,'#fff',2);
      const panelRow = item.panel_power ? `TẤM PIN  ${item.panel_power}` : '';
      const batteryRow = item.battery_capacity ? `PIN  ${item.battery_capacity}` : '';
      body+=tx(panelRow,75,671,21,{anchor:'start',fill:'#073875',weight:800,source:'panel_power',width:335});
      body+=tx(batteryRow,75,718,20,{anchor:'start',fill:'#073875',weight:800,source:'battery_capacity',width:335});
      if (extraContains(item,'Bật tắt theo nhu cầu')) body+=tx('BẬT/TẮT theo nhu cầu',75,766,17,{anchor:'start',fill:'#073875',weight:700,source:'additional_specs',width:335});
      if (extraContains(item,'Có đèn báo sạc')) body+=tx('Có đèn báo sạc',75,805,17,{anchor:'start',fill:'#073875',weight:700,source:'additional_specs',width:335});
      body+=`<circle cx="510" cy="728" r="44" fill="none" stroke="#fff" stroke-width="3"/><path d="M510 690 L491 731 H507 L499 764 L530 718 H514Z" fill="#fff"/>`;
      body+=tx('NẠP PIN',690,686,17,{fill:'#fff',weight:600,source:'static',width:280})+tx(item.charging_time ? item.charging_time : 'THEO NẮNG THỰC TẾ',690,729,23,{fill:'#fff',weight:800,source:item.charging_time?'charging_time':'static: định tính',width:300});
      body+=tx('THỜI GIAN CHIẾU SÁNG',690,775,16,{fill:'#fff',weight:600,source:'static',width:300})+tx(item.lighting_time || '',690,810,22,{fill:'#fff',weight:800,source:'lighting_time',width:300});
    }
    if (n === 2) {
      bg=shared.garden;
      body=base(bg,'rgba(241,249,255,.12)')+title('CẤU HÌNH KỸ THUẬT',450,93,46,{fill:'#064586',max:700})+tx('THÔNG SỐ THEO TỪNG MODEL',450,129,25,{fill:'#073875',weight:500,source:'static',width:680});
      body+=rounded(100,160,700,650,'#f5fbff',28,'#0a4e9d',2)+rounded(100,160,700,66,'#0e67bd',28)+rounded(100,194,700,32,'#0e67bd',0);
      body+=tx(codeLine,450,206,29,{fill:'#fff',weight:800,source:'code + power_watts',width:640});
      body+=`<ellipse cx="450" cy="387" rx="242" ry="36" fill="#d7efff" stroke="#68b4ef" stroke-width="2"/><ellipse cx="450" cy="398" rx="230" ry="21" fill="#a9dafa"/>`;
      body+=image(productUri,278,228,344,225,{fit:'contain'});
      const rows=commonSpecs.slice(0,8), cols=[120,460], startY=470, rowH=76;
      rows.forEach((row,i)=>{ const col=i%2, rr=Math.floor(i/2), cx=cols[col], yy=startY+rr*rowH; body+=rounded(cx,yy,320,65,'#dff2ff',12); body+=tx(row[0],cx+16,yy+24,14,{anchor:'start',fill:'#174982',weight:500,source:'static',width:290}); body+=tx(row[1],cx+16,yy+52,19,{anchor:'start',fill:'#063f7d',weight:800,source:row[2],width:290}); });
    }
    if (n === 3) {
      bg=shared.night;
      body=base(bg,'rgba(0,15,43,.28)')+logo();
      body+=title(`ĐÈN PHA NĂNG LƯỢNG MẶT TRỜI`,450,117,31,{max:770})+title(brand,450,157,31,{fill:'#ffc833',max:770});
      body+=rounded(300,176,300,44,'rgba(5,57,119,.82)',12,'#fff',1)+tx(codeLine,450,206,20,{weight:800,source:'code + power_watts',width:280});
      const rows=[['LiFePO₄',item.battery_capacity,'battery_capacity'],['TẤM PIN',item.panel_power,'panel_power'],['CÁP NGUỒN',hasSpec(item,/cáp nguồn\s*5\s*mét/i)?'5 mét':'','additional_specs'],['CHIẾU SÁNG',item.lighting_time,'lighting_time']].filter(r=>r[1]);
      rows.forEach((r,i)=>{let yy=284+i*113;body+=`<circle cx="132" cy="${yy}" r="37" fill="#f4f8ff"/><circle cx="132" cy="${yy}" r="24" fill="none" stroke="#0756a0" stroke-width="4"/><path d="M132 ${yy-15} V${yy+10} M120 ${yy} L132 ${yy+12} L144 ${yy}" stroke="#0756a0" stroke-width="4" fill="none"/>`;body+=tx(r[0],194,yy-4,17,{anchor:'start',weight:500,source:'static',width:340})+tx(r[1],194,yy+26,20,{anchor:'start',weight:800,source:r[2],width:345});});
      body+=image(productUri,478,280,390,420,{fit:'contain'});
    }
    if (n === 4) {
      bg=shared.city;
      body=base(bg,'rgba(1,14,46,.2)')+logo()+title('ĐẶC ĐIỂM NHẬN DIỆN',450,126,40,{max:720})+title('SẢN PHẨM',450,172,40,{max:500});
      body+=image(productUri,220,205,460,345,{fit:'contain'});
      body+=`<defs><linearGradient id="gold-${brandCode.length}" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#ffdc77"/><stop offset=".55" stop-color="#edaa18"/><stop offset="1" stop-color="#c77d03"/></linearGradient></defs>`;
      body+=badge(160,687,'shield',item.warranty?`Bảo hành ${item.warranty}`:'Bảo hành', '#f5b82e');
      body+=badge(450,687,'crown',brandCode);
      body+=badge(740,687,'handshake',certUri?'Nhà phân phối ủy quyền':'Phúc Thịnh Solar phân phối');
      textMap.push({text:item.warranty?`Bảo hành ${item.warranty}`:'Bảo hành',source:item.warranty?'warranty':'static',box:{x:95,y:740,width:130,height:25}},{text:brandCode,source:'brand + code',box:{x:345,y:740,width:210,height:25}},{text:certUri?'Nhà phân phối ủy quyền':'Phúc Thịnh Solar phân phối',source:certUri?'chung-nhan/'+brandCerts[brand]:'static',box:{x:660,y:740,width:160,height:25}});
    }
    if (n === 5) {
      bg=shared.night;
      body=base(bg,'rgba(2,20,50,.18)')+logo()+title('CHIẾU SÁNG LINH HOẠT',608,92,38,{max:560})+title('NGOÀI TRỜI',608,135,38,{max:380})+tx('Gợi ý cho sân, kho và các vị trí lắp đặt',608,169,18,{fill:'#fff',weight:400,source:'static',width:560});
      const labels=['Sân vườn, khuôn viên','Bãi xe','Kho bãi','Khu vực sản xuất'];
      const insetFiles=['garden','parking','warehouse','factory'];
      for(let i=0;i<4;i++){
        const col=i%4, xx=14+col*220;
        const cut=await sharp(shared.atlas.bytes).extract({left:(i%2)*627,top:Math.floor(i/2)*627,width:627,height:627}).png().toBuffer();
        body+=image(attr({bytes:cut,type:'png'}),xx,640,210,155,{fit:'cover',radius:12});
        body+=rounded(xx,799,210,34,'rgba(4,42,103,.92)',6,'#fff',1)+tx(labels[i],xx+105,823,14,{fill:'#fff',weight:600,source:'static',width:194});
      }
    }
    if (n === 6) {
      bg=shared.garden;
      body=base(bg,'rgba(255,255,255,.12)')+logo()+title('BỘ SẢN PHẨM GỒM',450,116,40,{fill:'#063f82',max:680})+tx(item.package_contents || '',450,154,23,{fill:'#073875',weight:500,source:'package_contents',width:760});
      body+=`<ellipse cx="450" cy="746" rx="291" ry="54" fill="#d1e7f4" stroke="#9bc9e8" stroke-width="3"/><rect x="194" y="744" width="512" height="80" fill="#b3d5eb"/><ellipse cx="450" cy="824" rx="256" ry="35" fill="#8ebcdb"/>`;
      body+=image(productUri,234,285,432,447,{fit:'contain'});
      const parts=(item.package_contents||'').split(/[·•,;]/).map(v=>v.trim()).filter(Boolean);
      body+=rounded(28,386,190,68,'rgba(4,55,121,.94)',14,'#fff',1)+tx(parts[0] || `Đèn ${brand}`,123,413,15,{fill:'#fff',weight:600,italic:true,source:parts[0]?'package_contents':'brand',width:170})+tx(code,123,437,16,{fill:'#fff',weight:800,source:'code',width:170});
      body+=line(218,421,297,491,'#fff',3,'4 5');
      const panelPart=parts.find(v=>/tấm pin/i.test(v)) || '';
      body+=rounded(680,286,195,70,'rgba(4,55,121,.94)',14,'#fff',1)+tx(panelPart,777,326,15,{fill:'#fff',weight:600,italic:true,source:'package_contents',width:174});
      body+=line(680,335,602,451,'#fff',3,'4 5');
    }
    if (n === 7) {
      bg=shared.garden;
      body=base(bg,'rgba(0,0,0,.1)')+logo()+title('AN TÂM LỰA CHỌN',450,115,38,{max:620})+title('CÙNG PHÚC THỊNH SOLAR',450,164,38,{max:720});
      body+=image(productUri,118,260,374,360,{fit:'contain'});
      const cards=[['✓','ĐÚNG MODEL'],['▣','ĐÓNG GÓI'],['♢',`BẢO HÀNH ${item.warranty || ''}`],['☎','HỖ TRỢ TƯ VẤN']];
      cards.forEach((c,i)=>{let yy=306+i*70;body+=rounded(520,yy,344,58,'#f5f9ff',10,'#cfe4ff',1)+rounded(534,yy+8,43,42,'#0a4e9d',8)+tx(c[0],555,yy+38,22,{fill:'#fff',weight:800,source:'static',width:36})+tx(c[1],594,yy+37,17,{anchor:'start',fill:'#073875',weight:800,source:i===2?'warranty':'static',width:254});});
      cards.forEach((c,i)=>textMap.push({text:c[1],source:i===2?'warranty':'static',box:{x:594,y:306+i*70,width:254,height:25}}));
      body+=rounded(0,764,900,136,'#073a80',38)+line(302,785,302,879,'#fff',2)+line(600,785,600,879,'#fff',2);
      body+=tx('GIẢI PHÁP CHIẾU SÁNG',151,817,16,{fill:'#fff',weight:600,source:'static',width:265})+tx('NĂNG LƯỢNG MẶT TRỜI',151,843,16,{fill:'#fff',weight:600,source:'static',width:265})+tx('TIẾT KIỆM ĐIỆN NĂNG',451,833,16,{fill:'#fff',weight:600,source:'static',width:265})+tx('PHÙ HỢP NHIỀU KHU VỰC',750,817,15,{fill:'#fff',weight:600,source:'static',width:265})+tx('VÀ CẢNH QUAN NGOÀI TRỜI',750,843,15,{fill:'#fff',weight:600,source:'static',width:265});
    }
    textMap.splice(0,0,
      {text:brandCode,source:'brand + code',kind:'badge'},
    );
    const defs=`<defs><linearGradient id="gold-${brandCode.length}" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#ffe391"/><stop offset=".5" stop-color="#e8a91d"/><stop offset="1" stop-color="#bd7400"/></linearGradient><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><path d="M0 0 L8 4 L0 8 Z" fill="#0b9bee"/></marker></defs>`;
    const svg=`<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="900" height="900" viewBox="0 0 900 900">${defs}<style>.txt{dominant-baseline:auto}</style>${body}</svg>`;
    const slide={ number:n, svg, textMap, item, productUri, logoUri };
    if (n===1) textMap.push({text:'THEO NẮNG THỰC TẾ',source:'static: định tính, charging_time trống',box:{x:500,y:694,width:320,height:52}});
    slides.push(slide);
  }
  return slides;
}
function extraText(item) { return (item.additional_specs||[]).map(x=>`${x.label||''} ${x.value||''}`).join(' '); }
function hasSpec(item, re) { return re.test(extraText(item)); }
function extraContains(item, phrase) { return extraText(item).toLowerCase().includes(phrase.toLowerCase()); }

async function cutout(productPath) {
  const source=await fs.readFile(productPath), meta=await sharp(source).metadata();
  const before=await sharp(source).metadata();
  if (meta.hasAlpha) return {bytes:await sharp(source).png().toBuffer(), method:'original-alpha', changedMax:0, source:before};
  const {data,info}=await sharp(source).ensureAlpha().raw().toBuffer({resolveWithObject:true});
  const {width:w,height:h}=info, seen=new Uint8Array(w*h), q=new Int32Array(w*h); let head=0,tail=0;
  const near=(i)=>data[i*4]>245&&data[i*4+1]>245&&data[i*4+2]>245;
  const seed=(x,y)=>{const k=y*w+x;if(!seen[k]&&near(k)){seen[k]=1;q[tail++]=k;}};
  for(let x=0;x<w;x++){seed(x,0);seed(x,h-1)} for(let y=0;y<h;y++){seed(0,y);seed(w-1,y)}
  while(head<tail){const k=q[head++],x=k%w,y=(k/w)|0;for(const [dx,dy] of [[1,0],[-1,0],[0,1],[0,-1]]){const xx=x+dx,yy=y+dy;if(xx>=0&&yy>=0&&xx<w&&yy<h)seed(xx,yy)}}
  for(let k=0;k<w*h;k++) if(seen[k]) data[k*4+3]=0;
  const bytes=await sharp(data,{raw:{width:w,height:h,channels:4}}).png().toBuffer();
  return {bytes,method:'edge-flood-white-245',source:before,removed:tail};
}

async function renderSlide(slide, outputPath) {
  const svg=Buffer.from(slide.svg);
  const png=await sharp(svg,{density:96}).resize(1600,1600,{fit:'fill',kernel:'lanczos3'}).png().toBuffer();
  await sharp(png).flatten({background:'#fff'}).toColorspace('srgb').jpeg({quality:90,mozjpeg:true,chromaSubsampling:'4:4:4'}).toFile(outputPath);
  return png;
}

const requestedCode=arg('--code');
const requestedBrand=arg('--brand');
const selected=has('--all') ? data : data.filter(p=>(requestedCode && p.code.toLowerCase()===requestedCode.toLowerCase()) || (requestedBrand && p.brand.toLowerCase()===requestedBrand.toLowerCase()));
if (!selected.length) { console.error('Không có mã phù hợp. Dùng --code <mã>, --brand <tên> hoặc --all.'); process.exit(2); }

for (const item of selected) {
  const target=path.join(outputRoot,safeCode(item.code));
  await fs.mkdir(target,{recursive:true});
  const productPath=path.join(inputRoot,'san-pham',item.image);
  try { await fs.access(productPath); } catch { console.warn(`BỎ ${item.code}: thiếu ảnh ${item.image}`); continue; }
  const cut=await cutout(productPath);
  const productUri=attr({bytes:cut.bytes,type:'png'});
  const certName=brandCerts[item.brand];
  const certPath=certName ? path.join(inputRoot,'chung-nhan',certName) : null;
  let certUri='';
  if(certPath) { try { certUri=attr(await dataImage(certPath)); } catch {} }
  const slides=await makeScene(item,productUri,attr(shared.logo),certUri);
  for(const slide of slides) {
    const jpg=path.join(target,`${String(slide.number).padStart(2,'0')}.jpg`);
    await renderSlide(slide,jpg);
  }
  if(certUri) {
    const cert=await dataImage(certPath);
    const title=`Giấy ủy quyền phân phối ${item.brand}`;
    const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900"><defs><linearGradient id="bg" x2="1" y2="1"><stop stop-color="#071b4e"/><stop offset="1" stop-color="#06408b"/></linearGradient></defs><rect width="900" height="900" fill="url(#bg)"/>${image(attr(shared.logo),32,22,300,58)}<rect x="50" y="105" width="800" height="735" rx="12" fill="#fff"/><image href="${certUri}" x="54" y="109" width="792" height="727" preserveAspectRatio="xMidYMid meet"/><text x="450" y="885" text-anchor="middle" fill="#fff" font-family="Liberation Sans,Arial" font-size="21" font-weight="700">${esc(title)}</text></svg>`;
    const png=await sharp(Buffer.from(svg)).resize(1600,1600,{fit:'fill'}).png().toBuffer();
    await sharp(png).flatten({background:'#071b4e'}).toColorspace('srgb').jpeg({quality:90,mozjpeg:true}).toFile(path.join(target,'08.jpg'));
  }
  await fs.writeFile(path.join(target,'qa.json'),JSON.stringify({code:item.code,brand:item.brand,renderEngine:'sharp SVG fallback; Playwright unavailable in this sandbox',source:item.image,cutout:{method:cut.method,connectedComponentCount:cut.method==='original-alpha'?null:'one flood-filled matte generated',changedMax:cut.changedMax ?? null,removedPixels:cut.removed??null},slides:slides.map(s=>({image:`${String(s.number).padStart(2,'0')}.jpg`,text:s.textMap})),certificate:certUri?{image:'08.jpg',source:certName,brand:item.brand}:null},null,2));
  console.log(`Rendered ${item.code}: ${certUri?'8':'7'} images (preview build; QA pending)`);
}

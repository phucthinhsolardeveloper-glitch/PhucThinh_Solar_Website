
import {readFileSync,mkdirSync} from 'node:fs';
import {join} from 'node:path';
import sharp from '../../frontend/node_modules/sharp/lib/index.js';

const base=process.cwd();
const dir=join(base,'frontend/public/assets/images/editorial');
const logo=readFileSync(join(dir,'brand/logo-dai.png')).toString('base64');
mkdirSync(dir,{recursive:true});
const raw=readFileSync(join(base,'backend/resources/editorial/2026-11-12/calendar-180.csv'),'utf8');
function parseCsv(text){
 const rows=[];let row=[],cell='',quoted=false;
 for(let i=0;i<text.length;i++){
  let c=text[i];
  if(c==='"'){
   if(quoted&&text[i+1]==='"'){cell+='"';i++}else quoted=!quoted;
  }else if(c===','&&!quoted){row.push(cell);cell=''}
  else if((c==='\n'||c==='\r')&&!quoted){
   if(c==='\r'&&text[i+1]==='\n')i++;
   row.push(cell);cell='';
   if(row.some(v=>v!==''))rows.push(row);
   row=[];
  }else cell+=c;
 }
 if(quoted)throw new Error('Invalid quote in CSV');
 if(row.length||cell){row.push(cell);rows.push(row)}
 const keys=rows.shift().map(k=>k.replace(/^\uFEFF/,''));
 return rows.map(r=>{
  if(r.length!==keys.length)throw new Error('CSV length mismatch for '+r[1]);
  return Object.fromEntries(keys.map((k,i)=>[k,r[i]]));
 });
}
const rows=parseCsv(raw);
if(rows.length!==180)throw new Error('Expected 180 rows; got '+rows.length);
const xml=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');
const t=(s,x,y,size,color,weight=500,other='')=>'<text x="'+x+'" y="'+y+'" font-size="'+size+'" fill="'+color+'" font-weight="'+weight+'" '+other+'>'+xml(s)+'</text>';
const r=(x,y,w,h,c,rad=0)=>'<rect x="'+x+'" y="'+y+'" width="'+w+'" height="'+h+'" rx="'+rad+'" fill="'+c+'"/>';
const l=(x,y,x2,y2,c,w=3)=>'<line x1="'+x+'" y1="'+y+'" x2="'+x2+'" y2="'+y2+'" stroke="'+c+'" stroke-width="'+w+'"/>';
const hexNum=id=>parseInt(id.slice(-3),10);
function titleLines(title){
 const words=String(title).replace(/\s+/g,' ').trim().split(' ');
 for(let size=74;size>=47;size-=3){
  const max=Math.floor(830/(size*.74));
  let lines=[''];
  for(let w of words){let n=lines.length-1;if(lines[n]&&lines[n].length+w.length+1>max)lines.push(w);else lines[n]+=(lines[n]?' ':'')+w}
  if(lines.length<=5&&lines.every(x=>x.length<max+6))return {lines,size};
 }
 return {lines:words.reduce((a,w)=>{let n=a.length-1;if(a[n]&&a[n].length+w.length+1>34)a.push(w);else a[n]+=(a[n]?' ':'')+w;return a},['']).slice(0,6),size:43};
}
function motif(row,num){
 const text=(row.title+' '+row.pillar).toLowerCase();
 let out='',navy='#0A2251',gold='#C9962E';
 if(/bơm|tưới|nông nghiệp|cà phê|hồ tiêu|sầu riêng|vườn/.test(text)){
  for(let j=0;j<7;j++){let y=310+j*68;out+='<path d="M 1070 '+y+' Q 1150 '+(y-38)+' 1230 '+y+' T 1450 '+y+'" fill="none" stroke="'+(j%2?gold:navy)+'" stroke-width="'+(3+j%3)+'" opacity=".65"/>';}
 }else if(/đèn|lumen|lux|ánh sáng|chiếu sáng/.test(text)){
  out+='<circle cx="1260" cy="475" r="174" fill="#FFF1CF"/>';
  for(let j=0;j<12;j++){let a=j*Math.PI/6;out+=l(1260+Math.cos(a)*189,475+Math.sin(a)*189,1260+Math.cos(a)*220,475+Math.sin(a)*220,gold,5)}
  out+='<circle cx="1260" cy="475" r="117" fill="none" stroke="'+navy+'" stroke-width="5"/>';
 }else if(/inverter|biến tần|mppt|dòng điện|điện áp|lưu trữ|backup/.test(text)){
  out+=r(1073,329,367,278,'#FFFFFF',24)+r(1106,355,302,144,'#E9F0F8',13)+t('DC',1130,447,52,navy,800)+l(1251,421,1308,421,gold,6)+t('AC',1325,447,52,navy,800)+r(1125,539,246,12,navy,6)+r(1125,570,148,10,gold,5);
 }else if(row.menu==='Tin mới nhất'||/thị trường|xu hướng|báo cáo/.test(text)){
  for(let j=0;j<6;j++){let h=70+((num*43+j*99)%320);out+=r(1090+j*57,682-h,34,h,j%2?navy:gold,5)}
  out+=l(1073,687,1460,687,'#AABBD1',4);
 }else{
  out+='<polygon points="1068,312 1400,312 1468,622 1134,622" fill="'+navy+'" stroke="'+gold+'" stroke-width="5"/>';
  for(let j=1;j<=5;j++)out+=l(1068+j*55,315,1134+j*55,622,'#719BCC',3);
  for(let j=1;j<=4;j++)out+=l(1075+j*14,313+j*62,1413+j*14,313+j*62,'#719BCC',3);
 }
 return out;
}
function cover(row){
 const id=hexNum(row.id);
 const item=titleLines(row.title);
 const news=row.menu==='Tin mới nhất';
 let art='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900"><style>text{font-family:"Noto Sans","DejaVu Sans",Arial,sans-serif}</style>';
 art+=r(0,0,1600,900,'#F3F7FA')+r(0,0,993,900,'#061537')+r(986,0,10,900,'#C9962E');
 art+='<image href="data:image/png;base64,'+logo+'" x="77" y="68" width="451" height="63"/>';
 art+=r(78,178,12,40,'#C9962E',2)+t(news?'TIN NGÀNH & CÔNG NGHỆ':'KIẾN THỨC NĂNG LƯỢNG MẶT TRỜI',113,207,25,'#E0B654',750);
 let start=282;let step=item.size+20;
 for(let j=0;j<item.lines.length;j++)art+=t(item.lines[j],78,start+j*step,item.size,'#FFFFFF',800);
 art+=l(78,733,906,733,'#E0B654',4)+t('CHIA SẺ KIẾN THỨC  ·  PHÚC THỊNH SOLAR',78,793,22,'#D8E1ED',600)+t('Từ nguồn nghiên cứu và tài liệu kỹ thuật quốc tế',78,843,18,'#99AAC2',400);
 art+=r(1064,185,440,90,'#FFFFFF',17)+t(news?'BẢN TIN & PHÂN TÍCH':'HƯỚNG DẪN & GIẢI THÍCH',1083,241,23,'#0A2251',750);
 art+=motif(row,id);
 art+=l(1070,749,1485,749,'#CED8E7',2)+t('PTS EDITORIAL',1083,807,22,'#6D819B',700)+t('№ '+String(id).padStart(3,'0'),1468,807,21,'#A0844A',650,'text-anchor="end"');
 art+='</svg>';
 return Buffer.from(art);
}
let queue=rows.slice(3),done=0;
async function worker(){
 while(queue.length){let row=queue.shift();const file=join(dir,'pts-plan-'+row.id.slice(-3)+'.webp');await sharp(cover(row)).webp({quality:82,effort:4}).toFile(file);done++;}
}
await Promise.all(Array.from({length:5},worker));
console.log('Generated '+done+' branded editorial concept covers, 1600x900, official long logo');

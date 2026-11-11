import {mkdirSync,writeFileSync,readFileSync} from 'node:fs';
import {join} from 'node:path';
import sharp from '../../frontend/node_modules/sharp/lib/index.js';

const DIR=join(process.cwd(),'backend/resources/editorial/2026-11-11/figures-src');
const OUT=join(process.cwd(),'frontend/public/assets/images/editorial');
mkdirSync(DIR,{recursive:true});mkdirSync(OUT,{recursive:true});
// Real, unaltered corporate logo from the provided source artwork.
// Embed in SVG source so re-renders preserve the exact shape and lettering.
const LOGO_WIDE_URI='data:image/png;base64,'+readFileSync(join(OUT,'brand/logo-dai.png')).toString('base64');
const C={navy:'#0A2251',deep:'#061537',gold:'#C9962E',gold2:'#E0B654',ink:'#172A44',gray:'#5B6D83',muted:'#EDF2F8',white:'#FFFFFF',line:'#DDE5EF',blue:'#3573BD',green:'#2F8C72'};
const esc=s=>String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
const el=(tag,attrs={},inner='')=>'<'+tag+' '+Object.entries(attrs).map(([k,v])=>k+'="'+String(v).replaceAll('"','&quot;')+'"').join(' ')+'>'+inner+'</'+tag+'>';
const rect=(x,y,w,h,fill=C.white,r=0,stroke='none')=>el('rect',{x,y,width:w,height:h,rx:r,fill,stroke});
const line=(x1,y1,x2,y2,color=C.line,width=3)=>el('line',{x1,y1,x2,y2,stroke:color,'stroke-width':width,'stroke-linecap':'round'});
const txt=(s,x,y,size=24,color=C.ink,weight=400,more={})=>el('text',{x,y,'font-size':size,'font-weight':weight,fill:color,...more},esc(s));
const textLines=(s,x,y,size=24,color=C.ink,weight=400,step=34)=>s.split('\n').map((t,i)=>txt(t,x,y+i*step,size,color,weight)).join('');
const frame=(title,subtitle,footer)=>rect(0,0,1600,900,'#F6F9FC')+rect(0,0,1600,145,C.deep)+rect(0,139,1600,6,C.gold)+el('image',{x:70,y:14,width:365,height:51,href:LOGO_WIDE_URI,preserveAspectRatio:'xMinYMid meet'})+txt(title,70,109,38,C.white,750)+txt(subtitle,70,179,22,C.gray,450)+line(65,818,1535,818)+txt(footer,70,854,19,C.gray,400);
const svg=(body)=>'<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900"><style>text{font-family:"Noto Sans",Arial,sans-serif}</style>'+body+'</svg>';
const arrow=(x,y,w)=>line(x,y,x+w,y,C.gold,6)+el('path',{d:'M '+(x+w-18)+' '+(y-12)+' L '+(x+w)+' '+y+' L '+(x+w-18)+' '+(y+12),fill:'none',stroke:C.gold,'stroke-width':6,'stroke-linecap':'round','stroke-linejoin':'round'});
const pill=(s,x,y,w,fill=C.muted)=>rect(x,y,w,42,fill,18)+txt(s,x+20,y+29,20,C.navy,650);
const specs=[
 ['Pmax tại STC','650 W'],['Hiệu suất module','24,1 %'],['Voc','53,90 V'],['Vmp','44,56 V'],['Isc','15,29 A'],['Imp','14,59 A'],['Hệ số nhiệt Pmax','-0,260 %/°C']
];

function figureFlow(){
 let b=frame('ĐIỆN MẶT TRỜI HOẠT ĐỘNG NHƯ THẾ NÀO?','Sơ đồ đơn giản: năng lượng mặt trời → điện một chiều → điện xoay chiều','Sơ đồ minh họa nguyên lý; cấu hình kỹ thuật thực tế phụ thuộc inverter và hệ thống bảo vệ.');
 b+=rect(70,225,1460,425,C.white,28,C.line);
 const centers=[230,610,990,1370];
 // sun with rays
 b+=el('circle',{cx:230,cy:339,r:58,fill:'#F8C849'});for(let i=0;i<12;i++){const a=i*Math.PI/6; b+=line(230+Math.cos(a)*73,339+Math.sin(a)*73,230+Math.cos(a)*95,339+Math.sin(a)*95,'#E3A929',6);}
 b+=txt('ÁNH SÁNG',230,495,28,C.navy,750,{'text-anchor':'middle'});
 // PV panel
 b+=el('polygon',{points:'519,284 686,284 710,428 540,428',fill:C.navy,stroke:C.blue,'stroke-width':4});
 for(let i=1;i<5;i++)b+=line(520+i*32,289,540+i*34,425,'#85B8E3',3);
 for(let i=1;i<4;i++)b+=line(524+i*6,284+i*36,694+i*6,284+i*36,'#85B8E3',3);
 b+=txt('TẤM PIN PV',610,495,28,C.navy,750,{'text-anchor':'middle'});
 b+=txt('Tạo điện DC',610,532,21,C.gray,450,{'text-anchor':'middle'});
 // inverter icon
 b+=rect(906,285,168,148,C.white,18,C.navy)+rect(925,312,130,52,C.muted,8)+txt('DC → AC',990,348,24,C.navy,800,{'text-anchor':'middle'})+line(946,389,1034,389,C.gold,6);
 b+=txt('INVERTER',990,495,28,C.navy,750,{'text-anchor':'middle'});
 b+=txt('Điện xoay chiều',990,532,21,C.gray,450,{'text-anchor':'middle'});
 // house
 b+=el('path',{d:'M 1294 357 L 1370 296 L 1446 357 L 1446 434 L 1294 434 Z',fill:C.muted,stroke:C.navy,'stroke-width':5,'stroke-linejoin':'round'});
 b+=el('path',{d:'M 1282 361 L 1370 287 L 1458 361',fill:'none',stroke:C.gold,'stroke-width':9,'stroke-linejoin':'round'});
 b+=rect(1350,373,42,61,C.navy,2)+rect(1309,380,29,29,'#8AC6EF',4)+rect(1401,380,29,29,'#8AC6EF',4);
 b+=txt('PHỤ TẢI',1370,495,28,C.navy,750,{'text-anchor':'middle'});
 b+=txt('Thiết bị sử dụng',1370,532,21,C.gray,450,{'text-anchor':'middle'});
 b+=arrow(338,356,132)+arrow(730,356,130)+arrow(1094,356,145);
 b+=rect(72,674,704,110,'#ECF5FC',18)+txt('Hòa lưới:',98,714,23,C.navy,750)+txt('Dùng điện mặt trời tại chỗ,',242,714,22,C.ink)+txt('nhận điện lưới khi thiếu nguồn phát.',98,751,21,C.gray);
 b+=rect(796,674,732,110,'#FFF5DE',18)+txt('Hybrid + lưu trữ:',820,714,23,C.navy,750)+txt('Có thể tích điện và cấp nguồn dự phòng',1048,714,21,C.ink)+txt('theo cấu hình, không mặc định cấp cả nhà khi mất lưới.',820,751,20,C.gray);
 return svg(b);
}
function figureDatasheet(){
 let b=frame('CÁCH ĐỌC DATASHEET TẤM PIN 650W','Ví dụ LONGi LR7-72HVH-650M • Giá trị tham khảo từ datasheet BGV02','Thông số cần đối chiếu lại theo mã hàng, phiên bản datasheet và inverter trước khi thiết kế.');
 b+=rect(65,214,470,570,C.deep,26);
 b+=txt('MODEL THAM KHẢO',100,267,23,C.gold2,700);
 b+=txt('LR7-72HVH',100,328,35,C.white,750);
 b+=txt('650M',100,374,38,C.white,750);
 b+=txt('650',98,540,150,C.white,850);
 b+=txt('Wp',393,539,43,C.gold2,750);
 b+=line(98,577,494,577,C.gold,4);
 b+=txt('Hiệu suất module',100,624,23,'#CAD6E9');
 b+=txt('24,1%',100,685,53,C.gold2,800);
 b+=txt('Công suất danh định tại STC',100,750,20,'#AEBFD7');
 b+=rect(567,214,964,570,C.white,26,C.line);
 b+=txt('THÔNG SỐ KỸ THUẬT',609,270,27,C.navy,800);
 b+=txt('GIÁ TRỊ',1340,270,23,C.navy,750);
 b+=line(603,289,1488,289,C.line,2);
 specs.forEach(([name,val],i)=>{const y=337+i*58; if(i%2===0)b+=rect(597,y-35,899,51,'#F3F7FB',7); b+=txt(name,614,y,23,C.ink,520)+txt(val,1440,y,24,i===0?C.gold:C.navy,750,{'text-anchor':'end'});});
 return svg(b);
}
function figureMarket(){
 let b=frame('ĐIỆN MẶT TRỜI TOÀN CẦU: SỐ LIỆU NĂM 2025','Tổng hợp từ IEA PVPS – Trends in Photovoltaic Applications 2026 (tháng 9/2026)','Số liệu làm tròn; 2025 là năm lắp đặt, 2026 là năm báo cáo. Nguồn: IEA PVPS.');
 b+=rect(65,218,445,558,C.deep,25);
 b+=txt('LŨY KẾ CUỐI NĂM 2025',97,272,23,C.gold2,750);
 b+=txt('2,96',93,398,112,C.white,800);
 b+=txt('TW',355,395,48,C.gold2,750);
 b+=line(97,423,476,423,C.gold,4);
 b+=txt('Công suất lắp mới 2025',97,484,22,'#C9D7EC');
 b+=txt('~690',97,575,79,C.white,800);
 b+=txt('GW',324,573,37,C.gold2,750);
 b+=pill('Tăng khoảng 15% so với 2024',98,638,367,'#F5DFB3');
 b+=rect(539,218,995,558,C.white,25,C.line);
 b+=txt('THỊ TRƯỜNG LẮP MỚI 2025',579,272,28,C.navy,800);
 b+=txt('Đơn vị: GW',1328,274,19,C.gray);
 const countries=[['Trung Quốc',415],['Ấn Độ',54],['Hoa Kỳ',43],['Đức',18],['Pakistan',14]];
 countries.forEach(([name,v],i)=>{const y=339+i*80; b+=txt(name,580,y,22,C.ink,560); b+=rect(786,y-27,565,33,C.muted,8); b+=rect(786,y-27,Math.max(25,v/415*565),33,i===0?C.gold:C.blue,8); b+=txt('~'+v+' GW',1465,y,21,C.navy,760,{'text-anchor':'end'});});
 b+=line(579,737,1485,737,C.line,2);
 return svg(b);
}
for(const [name,body] of [
 ['pts-diagram-solar-workflow',figureFlow()],
 ['pts-infographic-panel-650w',figureDatasheet()],
 ['pts-infographic-global-pv-2026',figureMarket()]
]){
 const input=join(DIR,name+'.svg');const target=join(OUT,name+'.webp');
 writeFileSync(input,body);
 await sharp(Buffer.from(body)).webp({quality:89,effort:5}).toFile(target);
 console.log(name+' '+(await sharp(target).metadata()).width+'x'+(await sharp(target).metadata()).height);
}

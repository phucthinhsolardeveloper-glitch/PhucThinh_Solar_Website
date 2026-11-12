// Six original, fact-checked inline editorial figures: illustration, not product photography.
import sharp from '../../frontend/node_modules/sharp/lib/index.js';
import {readFileSync} from 'node:fs';
import {join} from 'node:path';

const ROOT=process.cwd(),OUT=join(ROOT,'frontend/public/assets/images/editorial');
const LOGO='data:image/png;base64,'+readFileSync(join(OUT,'brand/logo-dai.png')).toString('base64');
const C={navy:'#061537',blue:'#0A2251',gold:'#C9962E',gold2:'#E0B654',ink:'#213D55',muted:'#60758C',bg:'#F5F8FB',line:'#DEE6F0',white:'#FFFFFF',teal:'#2A8B82',light:'#E8F2F7'};
const esc=x=>String(x).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
const txt=(s,x,y,size=26,color=C.ink,w=500,extra='')=>'<text x="'+x+'" y="'+y+'" font-family="Noto Sans,Arial,sans-serif" font-size="'+size+'" font-weight="'+w+'" fill="'+color+'" '+extra+'>'+esc(s)+'</text>';
const rect=(x,y,w,h,fill=C.white,r=0,stroke='none')=>'<rect x="'+x+'" y="'+y+'" width="'+w+'" height="'+h+'" rx="'+r+'" fill="'+fill+'" stroke="'+stroke+'"/>';
const line=(x,y,xx,yy,c=C.line,w=3)=>'<path d="M'+x+' '+y+' L'+xx+' '+yy+'" stroke="'+c+'" stroke-width="'+w+'" fill="none" stroke-linecap="round"/>';
const circle=(x,y,r,color)=>'<circle cx="'+x+'" cy="'+y+'" r="'+r+'" fill="'+color+'"/>';
function frame(title,sub,footer) {
 let s='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900">';
 s+=rect(0,0,1600,900,C.bg)+rect(0,0,1600,151,C.navy)+rect(0,144,1600,7,C.gold);
 s+='<image href="'+LOGO+'" x="62" y="19" width="340" height="49"/>';
 s+=txt(title,63,110,38,C.white,780)+txt(sub,65,194,23,C.muted,480);
 s+=line(65,821,1532,821,C.line,2)+txt(footer,65,861,18,C.muted,460);
 return s;
}
function box(x,y,w,h,label,sub,color=C.white) {
 return rect(x,y,w,h,color,20)+txt(label,x+24,y+46,26,C.blue,740)+txt(sub,x+24,y+87,22,C.muted,520);
}
const arrow=(x,y,length)=>line(x,y,x+length,y,C.gold,7)+'<path d="M'+(x+length-17)+' '+(y-11)+' L'+(x+length)+' '+y+' L'+(x+length-17)+' '+(y+11)+'" fill="none" stroke="'+C.gold+'" stroke-width="6" stroke-linejoin="round"/>';
const end=s=>s+'</svg>';
function outage(){
 let s=frame('CÓ TẤM PIN NHƯNG MẤT ĐIỆN LƯỚI THÌ SAO?','Hai cấu hình có cách ứng xử khác nhau khi mất nguồn lưới điện','Hình minh họa: Phúc Thịnh Solar • Chỉ mô tả nguyên tắc, không phải sơ đồ đấu nối');
 s+=rect(65,236,703,531,C.white,22,C.line)+rect(799,236,735,531,C.white,22,C.line);
 s+=txt('HÒA LƯỚI THÔNG THƯỜNG',105,295,29,C.blue,770)+txt('HYBRID CÓ NHÁNH BACKUP',842,295,29,C.blue,770);
 s+=box(104,350,181,113,'Tấm pin','Nguồn DC',C.light)+arrow(299,410,60)+box(382,350,190,113,'Inverter','Hòa lưới',C.light);
 s+=circle(657,414,42,'#FCE7E5')+txt('×',644,431,47,'#B84A4A',750)+txt('Mất lưới:',110,534,23,C.blue,720)+txt('dừng phát theo bảo vệ',110,566,22,C.muted);
 s+=box(837,350,176,113,'Tấm pin','Nguồn DC',C.light)+arrow(1025,410,49)+box(1090,350,187,113,'Hybrid','Đã cấu hình',C.light)+arrow(1293,410,40)+box(1345,350,153,113,'Tải','Ưu tiên',C.light);
 s+=txt('Cần thiết kế backup và cách ly lưới đúng tiêu chuẩn',839,537,23,C.teal,720)+txt('Không mặc định cấp điện cho toàn bộ nhà',839,575,22,C.muted);
 return end(s);
}
function ratings(){
 let s=frame('600W, 620W, 650W HAY 720W?', 'So sánh công suất danh định, không đánh đồng watt với chất lượng','Hình minh họa: Phúc Thịnh Solar • Công suất STC không phải sản lượng thực tế');
 s+=rect(65,238,900,536,C.white,21,C.line)+rect(990,238,540,536,C.white,21,C.line);
 const vals=[600,620,650,720];
 vals.forEach((v,i)=>{let x=126+i*206,h=275*(v/720);s+=rect(x,644-h,126,h,i%2?C.blue:C.teal,10);s+=txt(v+' Wp',x+63,682,24,C.blue,770,'text-anchor="middle"');});
 s+=txt('CHỈ LÀ CÔNG SUẤT DANH ĐỊNH',110,306,24,C.muted,650);
 s+=txt('CÒN CẦN XEM',1030,302,30,C.blue,750);
 ['Hiệu suất / diện tích','Dòng và điện áp DC','Nhiệt độ, độ bền','Bảo hành đúng model'].forEach((x,i)=>{s+=circle(1041,367+i*75,7,C.gold)+txt(x,1072,375+i*75,25,C.ink,580);});
 return end(s);
}
function iea(){
 let s=frame('DỰ BÁO ĐIỆN TÁI TẠO: 2025 → 2031','Tỷ trọng trong sản lượng điện toàn cầu, không phải công suất module','Nguồn: IEA, Renewables 2026 (6/11/2026) • Biểu đồ biên soạn: Phúc Thịnh Solar');
 s+=rect(75,250,940,509,C.white,24,C.line)+rect(1037,250,485,509,C.white,24,C.line);
 [2025,2031].forEach((yr,i)=>{let pct=i?43:33,x=215+i*395,h=382*pct/50;s+=rect(x,656-h,185,h,i?C.gold:C.teal,16);s+=txt(pct+'%',x+92,687,41,C.blue,800,'text-anchor="middle"')+txt(yr,x+92,735,26,C.muted,680,'text-anchor="middle"');});
 s+=txt('CÁCH ĐỌC',1077,319,31,C.blue,790)+txt('33% năm 2025',1078,394,28,C.ink,630)+txt('43% năm 2031',1078,446,28,C.ink,630)+txt('(tức gần 45%)',1078,495,25,C.muted,470)+line(1074,545,1473,545,C.line)+txt('Đây là dự báo,',1078,605,24,C.muted,530)+txt('không phải kết quả đã đạt.',1078,640,22,C.muted,530);
 return end(s);
}
function loadProfile(){
 let s=frame('HÓA ĐƠN ĐIỆN CHƯA NÓI HẾT NHU CẦU','Muốn chọn công suất PV cần hiểu thêm phụ tải theo giờ trong ngày','Hình minh họa: Phúc Thịnh Solar • Không sử dụng các con số tiết kiệm giả định');
 s+=rect(67,240,1464,517,C.white,22,C.line);
 let x=112,y=367,w=1356,seg=w/4;
 ['00–06','06–12','12–18','18–24'].forEach((q,i)=>{s+=rect(x+i*seg,y,seg-8,172,i===1||i===2?'#DFF0E5':'#E8EEF8',12);s+=txt(q,x+i*seg+seg/2-4,y+58,28,C.blue,780,'text-anchor="middle"');s+=txt(i===1||i===2?'Giờ có nắng':'Ngoài giờ nắng',x+i*seg+seg/2-4,y+109,23,C.muted,570,'text-anchor="middle"');});
 s+=txt('Hóa đơn (kWh)',115,637,25,C.blue,740)+txt('→',350,637,33,C.gold,800)+txt('Đồ thị phụ tải',405,637,25,C.blue,740)+txt('→',697,637,33,C.gold,800)+txt('Mái / thiết bị',757,637,25,C.blue,740)+txt('→',1038,637,33,C.gold,800)+txt('Cấu hình phù hợp',1108,637,25,C.blue,740);
 return end(s);
}
function inverter(){
 let s=frame('INVERTER HÒA LƯỚI VS. INVERTER HYBRID','Khác biệt lớn nằm ở điều khiển lưu trữ và khả năng dự phòng','Hình minh họa: Phúc Thịnh Solar • Luôn kiểm tra datasheet đúng model');
 s+=rect(70,239,716,529,C.white,22,C.line)+rect(817,239,716,529,C.white,22,C.line);
 s+=txt('HÒA LƯỚI',119,308,32,C.blue,790)+txt('HYBRID',863,308,32,C.blue,790);
 const rows=[['Chuyển DC → AC','Có','Có'],['Kết hợp điện lưới','Có','Có'],['Lưu trữ năng lượng','Không mặc định','Tùy cấu hình'],['Cấp điện khi mất lưới','Thường không','Nếu có backup phù hợp']];
 rows.forEach((r,i)=>{
  let yy=366+i*86;
  s+=line(113,yy+37,1482,yy+37,C.line,2);
  s+=txt(r[0],120,yy,22,C.muted,550);
  s+=txt(r[1],574,yy,21,C.blue,710,'text-anchor="middle"');
  s+=txt(r[2],1308,yy,21,C.teal,710,'text-anchor="middle"');
 });
 return end(s);
}
function cellColors(){
 let s=frame('TẤM PIN ĐEN VÀ TẤM PIN XANH','Không thể kết luận hiệu suất chỉ dựa vào màu sắc bên ngoài','Hình minh họa: Phúc Thịnh Solar • Màu sắc không thay thế datasheet');
 s+=rect(80,236,702,526,C.white,20,C.line)+rect(818,236,702,526,C.white,20,C.line);
 for(let side=0;side<2;side++){
  let x=153+side*740,colour=side?'#174A7A':'#111E31';
  s+=rect(x,315,564,342,colour,12,'#A8C0CC');
  for(let k=1;k<7;k++)s+=line(x+k*80,316,x+k*80,656,'#80AFCC',2);
  for(let k=1;k<4;k++)s+=line(x,315+k*85,x+564,315+k*85,'#80AFCC',2);
  s+=txt(side?'TẤM MÀU XANH':'TẤM MÀU ĐEN',x+282,713,26,C.blue,750,'text-anchor="middle"');
 }
 return end(s);
}
const figures=[
 ['pts-explainer-grid-outage.webp',outage],
 ['pts-explainer-power-ratings.webp',ratings],
 ['pts-explainer-iea-renewables-2026.webp',iea],
 ['pts-explainer-load-profile.webp',loadProfile],
 ['pts-explainer-inverter-types.webp',inverter],
 ['pts-explainer-module-colors.webp',cellColors],
];
for(const [name,fn] of figures){const p=join(OUT,name);await sharp(Buffer.from(fn())).webp({quality:88,effort:5}).toFile(p);console.log(name,(await sharp(p).metadata()).width);}

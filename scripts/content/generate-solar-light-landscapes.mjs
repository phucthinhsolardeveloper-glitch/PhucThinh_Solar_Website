import sharp from '../../frontend/node_modules/sharp/lib/index.js';
import {readFileSync} from 'node:fs';
import {join} from 'node:path';

const ROOT=process.cwd(), OUT=join(ROOT,'frontend/public/assets/images/editorial');
const logo='data:image/png;base64,'+readFileSync(join(OUT,'brand/logo-dai.png')).toString('base64');
const presets=[
['pts-light-benefits.webp','#69BDEA','#EAF7E6','#FAD48F','#569A7A',805,false],
['pts-light-advantages.webp','#91CFE8','#E4F4E8','#FAE2B3','#6DAE83',710,false],
// The two photographic covers are selected from Pexels and carry source attribution.
['pts-light-why.webp','#427BA0','#83B6C2','#F5C483','#4B8B76',830,true],
];
const R=(x,y,w,h,c,rx=0)=>'<rect x="'+x+'" y="'+y+'" width="'+w+'" height="'+h+'" rx="'+rx+'" fill="'+c+'"/>';
const L=(x,y,a,b,color,w=3)=>'<path d="M '+x+' '+y+' L '+a+' '+b+'" stroke="'+color+'" stroke-width="'+w+'" stroke-linecap="round" fill="none"/>';
const P=(points,color)=>'<polygon points="'+points+'" fill="'+color+'"/>';
const C=(x,y,r,color)=>'<circle cx="'+x+'" cy="'+y+'" r="'+r+'" fill="'+color+'"/>';
function lamp(x,y,h,side,night){
  const top=y-h;
  let s=L(x,y,x,top,'#1A4056',Math.max(2,h*.035));
  s+=L(x,top+15,x+side*h*.35,top+h*.075,'#1A4056',Math.max(2,h*.023));
  s+=P((x+side*h*.27)+','+(top+h*.07)+' '+(x+side*h*.50)+','+(top+h*.06)+' '+(x+side*h*.50)+','+(top+h*.095)+' '+(x+side*h*.28)+','+(top+h*.105),night?'#FFE0A5':'#E8F4F3');
  s+=P((x-h*.27)+','+(top-h*.08)+' '+(x+h*.25)+','+(top-h*.14)+' '+(x+h*.30)+','+(top-h*.055)+' '+(x-h*.21)+','+(top+h*.006),'#0C3152');
  for(let k=1;k<5;k++)s+=L(x-h*.27+k*h*.104,top-h*.10,x-h*.21+k*h*.104,top-h*.012,'#70ABC0',Math.max(0.5,h*.005));
  if(night)s+=C(x+side*h*.42,top+h*.09,h*.035,'#FFE0A0');
  return s;
}
function tree(x,y,r,night){
  return R(x-r*.075,y-r*.3,r*.15,r*.5,'#50664A',2)+C(x,y-r*.43,r*.33,night?'#44795D':'#4B9565')+C(x-r*.2,y-r*.36,r*.28,'#6EAF77')+C(x+r*.2,y-r*.36,r*.26,night?'#6E9D75':'#86BD8C');
}
function draw(v,index){
 const [file,top,mid,horizon,green,focus,night]=v;
 let b='<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900"><defs><linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop stop-color="'+top+'"/><stop offset=".60" stop-color="'+mid+'"/><stop offset="1" stop-color="'+horizon+'"/></linearGradient><linearGradient id="road" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#7E9295"/><stop offset="1" stop-color="#29485A"/></linearGradient></defs>';
 b+=R(0,0,1600,900,'url(#sky)')+C(1320,180,75,'#FFE0A5');
 b+='<path d="M0 451 Q200 341 400 412 T805 403 T1200 411 T1600 384 L1600 590 L0 590Z" fill="#769DA0"/>';
 b+='<path d="M0 483 Q210 411 448 466 T920 442 T1600 454 L1600 900 L0 900Z" fill="'+green+'"/>';
 for(let k=0;k<31;k++){let x=1040+k*20,h=34+((k*37+index*51)%105);b+=R(x,480-h,17,h,night?'#6D8790':'#A0BDC0',2);}
 const x=focus;
 b+=P((x-97)+',457 '+(x+93)+',457 1500,900 95,900','#B9CBBC');
 b+=P((x-71)+',457 '+(x+68)+',457 1400,900 195,900','url(#road)');
 b+=P('0,900 '+(x-120)+',455 '+(x-96)+',458 120,900','#BCD1B0');
 b+=P('1600,900 '+(x+127)+',457 '+(x+103)+',457 1500,900','#D1DEB8');
 for(let k=1;k<6;k++){let t=k/6,y=462+445*t*t,ax=x+(1400-x)*t*.44; b+=L(ax-2,y,ax+3,y+14*t,'#E8D89E',Math.max(1,4*t));}
 for(let j=7;j>=0;j--){
  const t=(j+.8)/9,depth=Math.pow(t,1.5),y=469+446*depth,r=14+65*depth;
  const xl=x-103+(85-x+103)*depth, xr=x+103+(1490-x-103)*depth;
  b+=tree(xl-45,y,r*.89,night)+tree(xr+42,y,r*.81,night);
  b+=lamp(xl+16,y,42+r*2.5,-1,night)+lamp(xr-15,y,41+r*2.5,1,night);
 }
 for(let j=0;j<15;j++)b+=C((j*79+17)%165,yPad(j),24+j%3*5,j%3?'#6FAA79':'#9EBD8C');
 b+=R(45,39,442,101,'#F8FAF5',18);
 b+='<image href="'+logo+'" x="75" y="64" width="385" height="54" preserveAspectRatio="xMidYMid meet"/>';
 b+='</svg>';
 return Buffer.from(b);
}
function yPad(j){return 785+(j%3)*38;}
for(let i=0;i<presets.length;i++){
 const dest=join(OUT,presets[i][0]);
 await sharp(draw(presets[i],i)).webp({quality:88,effort:5}).toFile(dest);
 console.log(presets[i][0],(await sharp(dest).metadata()).width);
}

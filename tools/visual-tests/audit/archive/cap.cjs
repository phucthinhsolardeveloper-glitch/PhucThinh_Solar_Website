const {chromium}=require('playwright');const fs=require('fs');const OUT='/home/admin01/src/Web_PTS_PHP/frontend/public/pts-living-energy/assets/';
(async()=>{const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist']});
const shots=[['desktop-day',1600,900,1.5,'09:00'],['phone-day',412,844,2,'09:00'],['desktop-night',1600,900,1.5,'21:00'],['phone-night',412,844,2,'21:00']];
for(const [name,w,h,d,hh] of shots){const c=await b.newContext({viewport:{width:w,height:h},deviceScaleFactor:d,isMobile:w<500,hasTouch:w<500,timezoneId:'Asia/Ho_Chi_Minh'});const p=await c.newPage();await p.clock.setFixedTime(new Date(`2026-11-07T${hh}:00+07:00`));
await p.goto('http://localhost:3100/pts-living-energy/index.html?embed=1&v=cap',{waitUntil:'load'});
await p.waitForFunction(()=>document.querySelector('#scenePanel')?.dataset.ready==='true'||document.querySelector('canvas'),{timeout:30000});await p.waitForTimeout(16000);
await p.addStyleTag({content:'html,body,*{background:transparent!important;box-shadow:none!important}body *{visibility:hidden!important}canvas{visibility:visible!important}*::before,*::after{display:none!important}'});await p.waitForTimeout(1500);
const png=await p.screenshot({omitBackground:true,type:'png'});
const webp=await p.evaluate(async(b64)=>{const i=new Image();i.src='data:image/png;base64,'+b64;await i.decode();const c=document.createElement('canvas');c.width=i.naturalWidth;c.height=i.naturalHeight;c.getContext('2d').drawImage(i,0,0);return c.toDataURL('image/webp',.84).split(',')[1]},png.toString('base64'));
fs.writeFileSync(OUT+`pts-model-${name}.webp`,Buffer.from(webp,'base64'));console.log(name,Math.round(Buffer.from(webp,'base64').length/1024)+'KB');await c.close()}
await b.close()})()

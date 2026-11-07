const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist']});
// news on phone
const m=await b.newContext({viewport:{width:412,height:924},isMobile:true,hasTouch:true,locale:'vi-VN'});const q=await m.newPage();await q.route('https://cdn.gtranslate.net/**',r=>r.abort());await q.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});await q.goto('http://localhost:3100/tin-tuc/',{waitUntil:'load'});await q.waitForTimeout(1200);await q.evaluate(()=>scrollTo(0,650));await q.waitForTimeout(500);await q.screenshot({path:S+'/news-m.png'});await m.close();
// load while resting at top, then jump
const c=await b.newContext({viewport:{width:1600,height:900},timezoneId:'Asia/Ho_Chi_Minh'});const p=await c.newPage();await p.clock.setFixedTime(new Date('2026-11-07T09:00:00+07:00'));await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});await p.goto('http://localhost:3100/',{waitUntil:'load'});
let readyAt=0;for(let k=0;k<25;k++){await p.waitForTimeout(1000);if(await p.evaluate(()=>!!document.querySelector('.energy-scene-frame iframe.is-ready'))){readyAt=k+1;break}}console.log('ready while at top after',readyAt,'s, scrollY',await p.evaluate(()=>scrollY));
await p.evaluate(()=>{window.__g=[];let l=performance.now();(function f(){const n=performance.now();window.__g.push(n-l);l=n;requestAnimationFrame(f)})()});
await p.mouse.move(800,450);for(let i=0;i<14;i++){await p.mouse.wheel(0,150);await p.waitForTimeout(60)}await p.waitForTimeout(400);
const g=await p.evaluate(()=>window.__g.slice(1));g.sort((a,b)=>a-b);console.log('scroll into ready section frames',g.length,'p95',Math.round(g[Math.floor(g.length*.95)]),'max',Math.round(g[g.length-1]),'>50ms',g.filter(x=>x>50).length);
const pe=()=>p.locator('.energy-scene-frame iframe').evaluate(e=>getComputedStyle(e).pointerEvents);console.log('after scroll pointer',await pe());await p.mouse.move(820,470,{steps:3});await p.waitForTimeout(300);console.log('after mouse move pointer',await pe());
await p.locator('.energy-scene-frame').screenshot({path:S+'/final-3d.png'});await b.close()})()

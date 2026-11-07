const {chromium}=require('playwright');
const run=async(pauseOn)=>{const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist']});const c=await b.newContext({viewport:{width:1600,height:900}});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());
await p.addInitScript((pauseOn)=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{};if(!pauseOn&&location.pathname.includes('living-energy')){window.addEventListener('message',e=>{if(e.data?.type==='pts-living-energy:scrolling')e.stopImmediatePropagation()},true)}},pauseOn);
await p.goto('http://localhost:3100/',{waitUntil:'load'});await p.waitForTimeout(600);await p.evaluate(()=>document.querySelector('.energy-scene-frame').scrollIntoView({behavior:'instant'}));
for(let k=0;k<25;k++){await p.waitForTimeout(1000);if(await p.evaluate(()=>!document.querySelector('.energy-scene-poster')))break}
await p.waitForTimeout(2500);
await p.mouse.move(60,300);
await p.evaluate(()=>{window.__f=[];let l=performance.now();(function f(){const n=performance.now();window.__f.push(n-l);l=n;requestAnimationFrame(f)})()});
for(let i=0;i<40;i++){await p.mouse.wheel(0,i<20?90:-90);await p.waitForTimeout(40)}
const f=await p.evaluate(()=>window.__f.slice(1));f.sort((a,b)=>a-b);const pct=q=>Math.round(f[Math.floor(f.length*q)]);console.log(pauseOn?'PAUSE ON ':'PAUSE OFF','frames',f.length,'p50',pct(.5),'p95',pct(.95),'max',Math.round(f[f.length-1]),'>50ms',f.filter(x=>x>50).length);await b.close()};
(async()=>{await run(true);await run(false);await run(true);await run(false)})()

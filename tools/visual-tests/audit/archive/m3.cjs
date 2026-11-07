const {chromium}=require('playwright');const S=process.argv[2];
const run=async(tag,opt)=>{const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist']});const c=await b.newContext({...opt,timezoneId:'Asia/Ho_Chi_Minh'});const p=await c.newPage();await p.clock.setFixedTime(new Date('2026-11-07T09:00:00+07:00'));await p.route('https://cdn.gtranslate.net/**',r=>r.abort());
await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}window.__lt=[];try{new PerformanceObserver(l=>{for(const e of l.getEntries())window.__lt.push([Math.round(e.startTime),Math.round(e.duration)])}).observe({type:'longtask',buffered:true})}catch{}});
await p.goto('http://localhost:3100/',{waitUntil:'load'});
await p.evaluate(()=>{window.__m=[];window.addEventListener('message',e=>{if(e.data?.type==='pts-living-energy:ready')window.__m.push(Math.round(performance.now()))})});
const fr=p.locator('.energy-scene-frame');await fr.evaluate(e=>e.scrollIntoView({behavior:'instant',block:'center'}));await p.waitForTimeout(100);
// poster only: hide the iframe if already there
await p.addStyleTag({content:'.energy-scene-frame iframe{visibility:hidden!important}'});await p.waitForTimeout(700);await fr.screenshot({path:`${S}/s-${tag}-poster.png`});
const box=await fr.boundingBox();console.log(tag,'stage',Math.round(box.width),Math.round(box.height));
await p.evaluate(()=>document.querySelectorAll('style').forEach(s=>{if(s.textContent.includes('visibility:hidden!important'))s.remove()}));
for(let k=0;k<30;k++){await p.waitForTimeout(1000);if(await p.evaluate(()=>!!document.querySelector('.energy-scene-frame iframe.is-ready')))break}await p.waitForTimeout(1500);
await fr.screenshot({path:`${S}/s-${tag}-ready.png`});const r=await p.evaluate(()=>({lt:window.__lt.filter(x=>x[1]>200),m:window.__m,gone:!document.querySelector('.energy-scene-frame picture')}));console.log(tag,'ready',r.m,'posterGone',r.gone,'long>200',JSON.stringify(r.lt.slice(0,8)));await b.close()};
(async()=>{await run('d',{viewport:{width:1600,height:900}});await run('m',{viewport:{width:412,height:924},isMobile:true,hasTouch:true,deviceScaleFactor:2})})()

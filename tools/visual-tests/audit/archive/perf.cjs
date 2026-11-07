const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader','--ignore-gpu-blocklist']});const c=await b.newContext({viewport:{width:1600,height:900}});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());
await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}window.__lt=[];try{new PerformanceObserver(l=>{for(const e of l.getEntries())window.__lt.push([Math.round(e.startTime),Math.round(e.duration)])}).observe({type:'longtask',buffered:true})}catch{}});
await p.goto('http://localhost:3100/',{waitUntil:'load'});await p.waitForTimeout(800);
await p.evaluate(()=>{window.__m=[];window.addEventListener('message',e=>{if(e.data?.type)window.__m.push([Math.round(performance.now()),e.data.type])});window.__gaps=[];let last=performance.now();(function f(){const n=performance.now();if(n-last>50)window.__gaps.push([Math.round(last),Math.round(n-last)]);last=n;requestAnimationFrame(f)})();});
await p.mouse.move(800,450);
const start=await p.evaluate(()=>performance.now());
// user wheels down gradually past the 3D section's top, pausing briefly as a real reader does
for(let i=0;i<9;i++){await p.mouse.wheel(0,200);await p.waitForTimeout(90)}
await p.waitForTimeout(250);for(let i=0;i<4;i++){await p.mouse.wheel(0,120);await p.waitForTimeout(90)}
const y=await p.evaluate(()=>scrollY);console.log('scrollY',y,'t',Math.round(await p.evaluate(()=>performance.now())-start));await p.screenshot({path:S+'/t-arrive.png'});
for(let k=0;k<30;k++){await p.waitForTimeout(1000);if(await p.evaluate(()=>!document.querySelector('.energy-scene-poster')))break}
const r=await p.evaluate(()=>{const f=document.querySelector('.energy-scene-frame iframe');return {lt:window.__lt,gaps:window.__gaps,m:window.__m,cls:f&&f.className,src:!!f,poster:!!document.querySelector('.energy-scene-poster'),now:Math.round(performance.now())}});
const after=r.lt.filter(x=>x[0]>start);console.log('now',r.now,'start',Math.round(start),'iframe',r.src,r.cls,'poster',r.poster);console.log('long tasks since scroll',after.length,'total',after.reduce((s,x)=>s+x[1],0),'max',Math.max(0,...after.map(x=>x[1])));console.log(JSON.stringify(after.slice(0,20)));console.log('rAF gaps>50ms',r.gaps.length,'max',Math.max(0,...r.gaps.map(x=>x[1])),JSON.stringify(r.gaps.slice(0,20)));console.log('msgs',JSON.stringify(r.m.filter(x=>!/atmos/.test(x[1])).slice(0,4)));
await p.screenshot({path:S+'/t-ready.png'});
await b.close()})()

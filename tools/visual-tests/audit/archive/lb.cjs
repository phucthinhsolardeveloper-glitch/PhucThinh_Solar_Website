const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch();const c=await b.newContext({viewport:{width:412,height:924},isMobile:true,hasTouch:true,locale:'vi-VN'});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
const res=[];p.on('response',r=>{const u=r.url();if(/upload|thumbs|_media|image/.test(u))res.push([r.status(),r.headers()['content-length']||'',u.replace('http://localhost:3100','').slice(0,120)])});p.on('requestfailed',r=>{if(/upload|thumbs/.test(r.url()))res.push(['FAIL',r.failure()?.errorText,r.url().slice(0,120)])});
await p.goto('http://localhost:3100/san-pham/pin-luu-truhe-thong-luu-tru-solax-t-bat-sys-lv-d150-pro',{waitUntil:'load'});await p.waitForTimeout(2000);res.length=0;
await p.locator('.pdp-studio-stage').tap();await p.waitForTimeout(800);
for(let i=0;i<3;i++){const im=p.locator('[role=dialog] img').first();const info=await im.evaluate(e=>({src:e.getAttribute('src'),nw:e.naturalWidth,complete:e.complete,w:Math.round(e.getBoundingClientRect().width),h:Math.round(e.getBoundingClientRect().height)})).catch(e=>String(e));const txt=await p.locator('[role=dialog]').innerText();console.log(i,JSON.stringify(info),txt.replace(/\s+/g,' ').slice(-20));
const hit=await p.evaluate(()=>{const b=document.querySelector('[aria-label="Ảnh trước"]');if(!b)return null;const r=b.getBoundingClientRect();const e=document.elementFromPoint(r.left+r.width/2,r.top+r.height/2);return {top:e.tagName+'.'+String(e.className).slice(0,50),isBtn:b.contains(e)}});console.log(' prev hit',JSON.stringify(hit));
await p.screenshot({path:`${S}/lb${i}.png`});const nx=p.getByRole('button',{name:'Ảnh tiếp theo'});if(await nx.count())await nx.tap();await p.waitForTimeout(1200)}
console.log(res.slice(0,14).map(x=>x.join(' ')).join('\n'));
await b.close()})()

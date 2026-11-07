const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch();
const c=await b.newContext({viewport:{width:1920,height:900},locale:'vi-VN'});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.route('**/pts-living-energy/index.html*',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
await p.goto('http://localhost:3100/tin-tuc/',{waitUntil:'load'});await p.waitForTimeout(1200);await p.screenshot({path:S+'/h-g.png',clip:{x:1100,y:0,width:820,height:130}});
const rail=p.locator('nav:has(.rail-pill)').first();await rail.screenshot({path:S+'/p0.png'});
await p.locator('nav .public-filter-chip').nth(2).click();for(const [n,t] of [['p1',90],['p2',150]]){await p.waitForTimeout(t);await rail.screenshot({path:`${S}/${n}.png`}).catch(()=>{})}
await p.waitForTimeout(900);await p.locator('nav:has(.rail-pill)').first().screenshot({path:S+'/p3.png'});console.log(p.url());
const q=await (await b.newContext({viewport:{width:412,height:924},isMobile:true,hasTouch:true,deviceScaleFactor:2})).newPage();await q.route('https://cdn.gtranslate.net/**',r=>r.abort());await q.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});await q.goto('http://localhost:3100/giai-phap/dien-mat-troi-ho-gia-dinh/',{waitUntil:'load'});await q.waitForTimeout(1200);await q.locator('.solution-rail').screenshot({path:S+'/q0.png'});await q.locator('.solution-rail a').nth(1).tap();await q.waitForTimeout(150);await q.locator('.solution-rail').screenshot({path:S+'/q1.png'});await q.waitForTimeout(1500);await q.locator('.solution-rail').screenshot({path:S+'/q2.png'});
await b.close()})()

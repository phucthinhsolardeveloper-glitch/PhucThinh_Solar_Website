const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch();const c=await b.newContext({viewport:{width:1440,height:900},locale:'vi-VN'});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
const B='http://localhost:3100';
const rep=async(name)=>{const v=p.locator('.photo-viewer');await p.waitForTimeout(500);const n=await v.count();console.log(name,'viewer',n);if(!n)return;await p.screenshot({path:`${S}/v-${name}.png`});await p.keyboard.press('Escape');await p.waitForTimeout(900);console.log(' esc closes',await v.count()===0)};
// dealer
await p.goto(B+'/dai-ly/',{waitUntil:'load'});await p.waitForTimeout(1200);const ds=await p.locator('main button[aria-label*="thông số"], main button[aria-label*="Thông số"], .dealer-spec-chip').count();console.log('dealer triggers',ds);const t=p.locator('.dealer-spec-chip').first();if(await t.count()){await t.scrollIntoViewIfNeeded();await t.click({force:true});await rep('dealer')}
// solution
const api=await (await p.request.get(B+'/_migration/solutions')).json();const js=JSON.stringify(api);const m=[...new Set([...js.matchAll(/"(\/[a-z0-9-]+\/)"/g)].map(x=>x[1]))].filter(x=>x!=='/giai-phap/');console.log('sol candidates',m.slice(0,8));
for(const u of m.slice(0,10)){await p.goto(B+u,{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(800);const g=p.locator('main button:has(img)').first();if(await g.count()&&await g.isVisible()){console.log('at',u);await g.scrollIntoViewIfNeeded();await g.click();await rep('solution');break}}
// album
const pg=await p.request.get(B+'/_migration/gallery');const j=JSON.stringify(await pg.json().catch(()=>({})));const am=[...new Set([...j.matchAll(/"(\/[a-z0-9-]+\/?)"/g)].map(x=>x[1]))];console.log('album',am.slice(0,6));
for(const u of am.slice(0,6)){await p.goto(B+u,{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(800);const g=p.locator('main button:has(img)').first();if(await g.count()&&await g.isVisible()){console.log('at',u);await g.click();await rep('album');break}}
// pdp multi
await p.goto(B+'/san-pham/',{waitUntil:'load'});await p.waitForTimeout(800);const hs=await p.evaluate(()=>[...new Set([...document.querySelectorAll('article a[href*="/san-pham/"]')].map(a=>a.getAttribute('href')))]);for(const h of hs){await p.goto(B+h,{waitUntil:'load'});await p.waitForTimeout(600);await p.locator('.pdp-studio-stage').click();await p.waitForTimeout(400);if(await p.getByRole('button',{name:'Ảnh tiếp theo'}).count()){await rep('pdpmulti');break}await p.keyboard.press('Escape');await p.waitForTimeout(500)}
await b.close()})()

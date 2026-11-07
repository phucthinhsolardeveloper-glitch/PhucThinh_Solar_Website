const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch();const c=await b.newContext({viewport:{width:1440,height:900},locale:'vi-VN'});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
const B='http://localhost:3100';
const links=async(u,re)=>{await p.goto(B+u,{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(1000);return p.evaluate(s=>{const r=new RegExp(s);return [...new Set([...document.querySelectorAll('a[href]')].map(a=>a.getAttribute('href')).filter(h=>r.test(h)))]},re)};
const report=async(name)=>{const v=p.locator('.photo-viewer');const n=await v.count();if(!n){console.log(name,'NO VIEWER');return}await p.waitForTimeout(500);await p.screenshot({path:`${S}/v-${name}.png`});const i=await v.evaluate(e=>{const s=getComputedStyle(e);const btn=e.querySelector('.photo-viewer__btn');return{bg:s.backgroundColor,bf:s.backdropFilter,btn:btn&&getComputedStyle(btn).color}});console.log(name,JSON.stringify(i));await p.keyboard.press('Escape');await p.waitForTimeout(250);console.log(' esc closes',await v.count()===0)};
// solution
const sol=await links('/giai-phap/','^/giai-phap/[^/]+');console.log('sol',sol.length,sol[0]);
for(const u of sol.slice(0,6)){await p.goto(B+u,{waitUntil:'load'});await p.waitForTimeout(900);const g=p.locator('main button[aria-label*="Xem ảnh"], main button[aria-label*="Phóng to"], main button:has(img)').first();if(await g.count()){await g.scrollIntoViewIfNeeded();await g.click();await report('solution');break}}
// project
const cat=await links('/du-an-da-thuc-hien/','du-an');console.log('pj',cat.slice(0,6));let done=false;
for(const u of cat){ if(done)break; const sub=await links(u,'^/du-an|^/[a-z0-9-]{20,}/?$');for(const h of sub.slice(0,10)){await p.goto(B+h,{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(800);const g=p.locator('main button:has(img)').first();if(await g.count()){await g.scrollIntoViewIfNeeded();await g.click();console.log('at',h);await report('project');done=true;break}}}
// dealer
await p.goto(B+'/dai-ly/',{waitUntil:'load'});await p.waitForTimeout(1000);const d=p.locator('main button:has(img)').first();console.log('dealer btn',await d.count());if(await d.count()){await d.scrollIntoViewIfNeeded();await d.click();await report('dealer')}
// album
const al=await links('/thu-vien-anh/','thu-vien|album');console.log('album',al.slice(0,4));for(const h of al.slice(0,5)){await p.goto(B+h,{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(800);const g=p.locator('main button:has(img)').first();if(await g.count()){await g.click();await report('album');break}}
// PDP multi
const cats=await links('/san-pham/','^/.*san-pham/[^/?]+');for(const h of cats.slice(0,12)){await p.goto(B+h,{waitUntil:'load'});await p.waitForTimeout(500);if(await p.locator('button[aria-label^="Xem ảnh"], .pdp-studio-stage ~ button').count()>1){await p.locator('.pdp-studio-stage').click();await p.mouse.move(2,2);await report('pdpmulti');break}}
await b.close()})()

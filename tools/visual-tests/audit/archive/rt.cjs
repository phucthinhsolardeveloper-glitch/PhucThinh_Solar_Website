const {chromium}=require('playwright');
(async()=>{const b=await chromium.launch({headless:true,args:['--no-sandbox']});
for(const v of ['no-preference','reduce']){const c=await b.newContext({viewport:{width:390,height:844},locale:'vi-VN',isMobile:true,hasTouch:true});const p=await c.newPage();await p.route('**/pts-living-energy/index.html*',x=>x.abort());const s=await c.newCDPSession(p);await s.send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-transparency',value:v}]});const B='http://127.0.0.1:3100';
const st=(e)=>{const g=getComputedStyle(e);return (g.backgroundImage!=='none'?g.backgroundImage:g.backgroundColor).slice(0,70)+' | '+g.backdropFilter};
await p.goto(B+'/san-pham/',{waitUntil:'load'});await p.waitForTimeout(600);console.log(v,'match',await p.evaluate(()=>matchMedia('(prefers-reduced-transparency: reduce)').matches));console.log(' dock  ',await p.locator('.dock').evaluate(st));
await p.locator('.pts-mobile-nav-trigger').first().tap();await p.waitForTimeout(500);console.log(' panel ',await p.locator('#ptsMobileMenu').evaluate(st));await p.screenshot({path:'mob/rt-'+v+'.png'});
await p.goto(B+'/san-pham/',{waitUntil:'load'});await p.waitForTimeout(500);await p.locator('article button').first().tap();await p.waitForTimeout(900);await p.goto(B+'/gio-hang/',{waitUntil:'load'});await p.waitForTimeout(700);console.log(' island',await p.locator('.m-island').evaluate(st));await c.close()}
await b.close()})().catch(e=>{console.error('ERR',e.message.slice(0,200));process.exit(1)});

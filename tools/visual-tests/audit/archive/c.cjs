const {chromium}=require('playwright');const S=process.argv[2];
(async()=>{const b=await chromium.launch();const c=await b.newContext({viewport:{width:412,height:924},isMobile:true,hasTouch:true,locale:'vi-VN'});const p=await c.newPage();await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
await p.goto('http://localhost:3100/san-pham/',{waitUntil:'load'});await p.waitForTimeout(1000);const adds=p.getByRole('button',{name:/Thêm giỏ hàng|vào giỏ/});for(let i=0;i<5;i++){await adds.nth(i).click();await p.waitForTimeout(700)}
await p.goto('http://localhost:3100/gio-hang/',{waitUntil:'load'});await p.waitForTimeout(1200);
const st=()=>p.evaluate(()=>{const i=document.querySelector('.m-island');const a=document.querySelector('a.cart-summary__cta').getBoundingClientRect();return{y:Math.round(scrollY),ctaTop:Math.round(a.top),ctaBot:Math.round(a.bottom),island:getComputedStyle(i).visibility,doc:document.documentElement.scrollHeight}});
console.log('5 items top',JSON.stringify(await st()));await p.screenshot({path:S+'/cart5a.png'});
for(const y of [400,800,1200,1600,2000,2400]){await p.evaluate(y=>scrollTo({top:y,behavior:'instant'}),y);await p.waitForTimeout(350);console.log(JSON.stringify(await st()))}
await p.evaluate(()=>document.querySelector('a.cart-summary__cta').scrollIntoView({block:'center',behavior:'instant'}));await p.waitForTimeout(400);await p.screenshot({path:S+'/cart5b.png'});
await b.close()})()

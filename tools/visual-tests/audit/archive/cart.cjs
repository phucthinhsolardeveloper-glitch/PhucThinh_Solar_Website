const {chromium}=require('playwright');
const S=process.argv[2];
(async()=>{const b=await chromium.launch();const c=await b.newContext({viewport:{width:390,height:844},isMobile:true,hasTouch:true,locale:'vi-VN'});const p=await c.newPage();
await p.route('https://cdn.gtranslate.net/**',r=>r.abort());await p.addInitScript(()=>{try{sessionStorage.setItem('pts-popup-campaign','current')}catch{}});
await p.goto('http://localhost:3100/san-pham/',{waitUntil:'load'});await p.waitForTimeout(1200);
await p.getByRole('button',{name:/Thêm giỏ hàng|Thêm .* vào giỏ/}).first().click();await p.waitForTimeout(1500);
const stat=async(tag)=>{const r=await p.evaluate(()=>{const vis=e=>{if(!e)return null;const s=getComputedStyle(e);const r=e.getBoundingClientRect();return {d:s.display,top:Math.round(r.top),bot:Math.round(r.bottom),inView:r.bottom>0&&r.top<innerHeight&&s.display!=='none'&&s.visibility!=='hidden'}};
const bar=document.querySelector('.checkout-bar');const btns=[...document.querySelectorAll('main a,main button')].filter(e=>/Tiến hành thanh toán|Đặt hàng|Hoàn tất|Xác nhận đặt/.test(e.innerText)).map(e=>({t:e.innerText.trim().slice(0,30),...vis(e)}));
return {path:location.pathname,y:Math.round(scrollY),docH:document.documentElement.scrollHeight,bar:vis(bar),barText:bar?bar.innerText.replace(/\s+/g,' '):'',btns}});console.log(tag,JSON.stringify(r))};
await p.goto('http://localhost:3100/gio-hang/',{waitUntil:'load'});await p.waitForTimeout(1500);await stat('cart-top');await p.screenshot({path:S+'/c0.png'});
const H=await p.evaluate(()=>document.documentElement.scrollHeight);
for(let y=300;y<H;y+=300){await p.evaluate(y=>scrollTo(0,y),y);await p.waitForTimeout(150);await stat('cart@'+y)}
await p.evaluate(()=>{const e=[...document.querySelectorAll('main a,main button')].find(e=>/Tiến hành thanh toán/.test(e.innerText));e&&e.scrollIntoView({block:'center'})});await p.waitForTimeout(300);await stat('cart-main-center');await p.screenshot({path:S+'/c1.png'});
await p.goto('http://localhost:3100/thanh-toan/',{waitUntil:'load'}).catch(()=>{});await p.waitForTimeout(1500);await stat('checkout-top');await p.screenshot({path:S+'/k0.png'});
const K=await p.evaluate(()=>document.documentElement.scrollHeight);for(let y=500;y<K;y+=500){await p.evaluate(y=>scrollTo(0,y),y);await p.waitForTimeout(150);await stat('ck@'+y)}
await p.evaluate(()=>scrollTo(0,document.documentElement.scrollHeight));await p.waitForTimeout(300);await p.screenshot({path:S+'/k1.png'});
await b.close()})()

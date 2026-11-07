const {chromium}=require('playwright');
const routes=['/','/san-pham/','/giai-phap/','/gioi-thieu/','/dai-ly/','/lien-he/','/tin-tuc/','/du-an-da-thuc-hien/','/gio-hang/','/thanh-toan/'];
(async()=>{const b=await chromium.launch({headless:true,args:['--no-sandbox']});const c=await b.newContext({viewport:{width:390,height:844},locale:'vi-VN',isMobile:true,hasTouch:true});const p=await c.newPage();await p.route('**/pts-living-energy/index.html*',x=>x.abort());
await p.goto('http://127.0.0.1:3100/san-pham/',{waitUntil:'load'});await p.waitForTimeout(400);const pdp=await p.locator('article a[href*="/san-pham/"]').first().getAttribute('href').catch(()=>null);if(pdp)routes.splice(2,0,pdp);
const ht={},cta={},gut={};
for(const u of routes){const res=await p.goto('http://127.0.0.1:3100'+u,{waitUntil:'load'}).catch(()=>null);await p.waitForTimeout(500);console.log('--',u.slice(0,30),res&&res.status());if(!res||res.status()>=400)continue;
 await p.evaluate(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=700){scrollTo(0,y);await new Promise(r=>setTimeout(r,40))}scrollTo(0,0)});
 const r=await p.evaluate(()=>{const vis=e=>{const r=e.getBoundingClientRect();const s=getComputedStyle(e);return r.width>1&&r.height>1&&s.visibility!=='hidden'&&s.display!=='none'&&!e.closest('header,footer,.dock-root,.sr-only')};
 const hs={};for(const t of ['h1','h2','h3']){const v=new Set();for(const e of document.querySelectorAll(t))if(vis(e)){const s=getComputedStyle(e);v.add(Math.round(parseFloat(s.fontSize))+'/'+s.fontWeight+(s.textTransform==='uppercase'?'/UP':''))}hs[t]=[...v]}
 const btns=[];for(const e of document.querySelectorAll('main button, main a, button, a')){if(!vis(e))continue;const s=getComputedStyle(e),q=e.getBoundingClientRect();const filled=(s.backgroundImage!=='none'||(s.backgroundColor!=='rgba(0, 0, 0, 0)'&&s.backgroundColor!=='rgb(255, 255, 255)'))||parseFloat(s.borderTopWidth)>0;if(!filled||q.height>72||q.width<70||!(e.innerText||'').trim())continue;btns.push(Math.round(q.height)+'h '+s.fontSize+' '+s.fontWeight+' '+(s.backgroundImage!=='none'?'grad':s.backgroundColor)+' c='+s.color+' :: '+e.innerText.trim().replace(/\s+/g,' ').slice(0,20))}
 const lefts={};for(const e of document.querySelectorAll('h2,h3,p')){if(!vis(e))continue;const l=Math.round(e.getBoundingClientRect().left);lefts[l]=(lefts[l]||0)+1}
 return {hs,btns,lefts,end:document.documentElement.scrollHeight}});
 console.log(JSON.stringify(r.hs),'lefts',JSON.stringify(r.lefts));for(const x of r.btns){const k=x.split(' :: ')[0];(cta[k]=cta[k]||{n:0,ex:new Set()}).n++;if(cta[k].ex.size<3)cta[k].ex.add(x.split(' :: ')[1])}
}
console.log('== CTA');for(const k of Object.keys(cta).sort((a,b)=>cta[b].n-cta[a].n).slice(0,45))console.log(String(cta[k].n).padStart(3),k,'::',[...cta[k].ex].join(' ; '));
await b.close()})().catch(e=>{console.error('ERR',e.message.slice(0,300));process.exit(1)});

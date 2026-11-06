const {chromium}=require('playwright');
const routes=['/','/san-pham/','/giai-phap/','/gioi-thieu/','/dai-ly/','/lien-he/','/tin-tuc/','/du-an-da-thuc-hien/','/gio-hang/','/thanh-toan/'];
(async()=>{const b=await chromium.launch({headless:true,args:['--no-sandbox']});const c=await b.newContext({viewport:{width:390,height:844},locale:'vi-VN',isMobile:true,hasTouch:true});const p=await c.newPage();await p.route('**/pts-living-energy/index.html*',x=>x.abort());
await p.goto('http://127.0.0.1:3100/san-pham/',{waitUntil:'load'});await p.waitForTimeout(400);const pdp=await p.locator('article a[href*="/san-pham/"]').first().getAttribute('href').catch(()=>null);if(pdp)routes.splice(2,0,pdp);
const agg={};const glass={};const tiny={};
for(const u of routes){const res=await p.goto('http://127.0.0.1:3100'+u,{waitUntil:'load'}).catch(()=>null);await p.waitForTimeout(500);if(!res||res.status()>=400){console.log('SKIP',u,res&&res.status());continue}
 await p.evaluate(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=700){scrollTo(0,y);await new Promise(r=>setTimeout(r,40))}scrollTo(0,0)});
 const rows=await p.evaluate(()=>{const out=[];const vis=e=>{const r=e.getBoundingClientRect();const s=getComputedStyle(e);return r.width>0&&r.height>0&&s.visibility!=='hidden'&&s.display!=='none'};
 const label=e=>(e.innerText||e.getAttribute('aria-label')||e.placeholder||'').trim().replace(/\s+/g,' ').slice(0,26);
 for(const e of document.querySelectorAll('button,a[class*="rounded"],a[class*="btn"],a[class*="button"],input:not([type=checkbox]):not([type=radio]):not([type=hidden]),select,textarea,article,[class*="card"],[class*="rounded-"]')){if(!vis(e))continue;const s=getComputedStyle(e),r=e.getBoundingClientRect();const tag=e.tagName;
  const hasSurface=s.backgroundColor!=='rgba(0, 0, 0, 0)'||s.backgroundImage!=='none'||parseFloat(s.borderTopWidth)>0||s.boxShadow!=='none';if(!hasSurface)continue;
  const kind=(tag==='INPUT'||tag==='SELECT'||tag==='TEXTAREA')?'field':(tag==='BUTTON'||(tag==='A'&&r.height<72))?(r.height<72?'control':'tilebtn'):'surface';
  const rad=Math.min(parseFloat(s.borderTopLeftRadius)||0,999);const R=s.borderTopLeftRadius.includes('%')?'50%':(rad>=r.height/2-0.5?'pill':Math.round(rad)+'px');
  out.push({kind,R,bf:s.backdropFilter||s.webkitBackdropFilter||'none',bg:s.backgroundImage!=='none'?s.backgroundImage.slice(0,60):s.backgroundColor,h:Math.round(r.height),w:Math.round(r.width),label:label(e),cls:String(e.className).slice(0,40),tiny:(tag==='BUTTON'||tag==='A'||tag==='SELECT'||tag==='INPUT')&&(r.height<40)})}
 return out});
 for(const x of rows){const k=x.kind+' | '+x.R;(agg[k]=agg[k]||{n:0,routes:new Set(),ex:new Set()});agg[k].n++;agg[k].routes.add(u.slice(0,14));if(agg[k].ex.size<3)agg[k].ex.add(x.label||x.cls);
  if(x.bf!=='none'){const g=x.bf+' || '+x.bg;(glass[g]=glass[g]||{n:0,ex:new Set()});glass[g].n++;if(glass[g].ex.size<3)glass[g].ex.add(x.label||x.cls)}
  if(x.tiny){const t=u.slice(0,14)+' '+x.h+'px '+(x.label||x.cls);tiny[t]=1}}
}
console.log('== RADIUS');for(const k of Object.keys(agg).sort())console.log(k.padEnd(20),String(agg[k].n).padStart(4),[...agg[k].routes].length+'p',[...agg[k].ex].join(' ; '));
console.log('== GLASS');for(const k of Object.keys(glass))console.log(glass[k].n,k,'::',[...glass[k].ex].join(' ; '));
console.log('== TINY');console.log(Object.keys(tiny).slice(0,60).join('\n'));
await b.close()})().catch(e=>{console.error('ERR',e.message.slice(0,300));process.exit(1)});

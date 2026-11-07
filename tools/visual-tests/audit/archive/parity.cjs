const {chromium}=require('playwright');
(async()=>{
const b=await chromium.launch();
const pages=['/','/gioi-thieu/','/giai-phap/','/san-pham/','/lien-he/','/dai-ly/','/du-an/','/tin-tuc/'];
const base='http://localhost:3100';
const ctxOpt=(w,h,m)=>({viewport:{width:w,height:h},isMobile:m,hasTouch:m,locale:'vi-VN'});
async function grab(ctx,url){const p=await ctx.newPage();try{await p.goto(base+url,{waitUntil:'domcontentloaded',timeout:30000});await p.waitForTimeout(1500);
 for(let y=0;y<30;y++){await p.evaluate(()=>window.scrollBy(0,window.innerHeight));await p.waitForTimeout(80);}
 await p.waitForTimeout(400);
 return await p.evaluate(()=>{const out=new Set();const main=document.querySelector('main')||document.body;
  const w=document.createTreeWalker(main,NodeFilter.SHOW_TEXT);
  while(w.nextNode()){const n=w.currentNode;const t=n.textContent.replace(/\s+/g,' ').trim();if(t.split(' ').length<6)continue;const e=n.parentElement;const s=getComputedStyle(e);
   let vis=e.checkVisibility?e.checkVisibility({checkOpacity:false,checkVisibilityCSS:true}):true;
   const r=e.getBoundingClientRect();
   const fold=e.closest('[data-fold]');
   const clamp=s.webkitLineClamp!=='none'&&e.scrollHeight>e.clientHeight+2;
   out.add(JSON.stringify({t,vis:vis&&r.height>0,fold:!!fold,clamp}));}
  return [...out].map(x=>JSON.parse(x));});}catch(e){return {err:e.message}}finally{await p.close();}}
const d=await b.newContext(ctxOpt(1440,900,false)),m=await b.newContext(ctxOpt(390,844,true));
// include a PDP
const pp=await d.newPage();await pp.goto(base+'/san-pham/',{waitUntil:'domcontentloaded'});await pp.waitForTimeout(1500);const href=await pp.evaluate(()=>{const a=[...document.querySelectorAll('main a[href*="/san-pham/"]')].map(x=>x.getAttribute('href')).filter(h=>h.split('/').filter(Boolean).length>=2);return a[0]});await pp.close();if(href)pages.push(href);
for(const u of pages){const D=await grab(d,u),M=await grab(m,u);if(D.err||M.err){console.log(u,'ERR',D.err||M.err);continue}
 const mt=M.filter(x=>x.vis).map(x=>x.t);const mall=M.map(x=>x.t);
 const dv=D.filter(x=>x.vis).map(x=>x.t);
 const miss=dv.filter(t=>!mt.includes(t));
 const clampM=M.filter(x=>x.clamp).map(x=>x.t);const foldM=M.filter(x=>x.fold).length;
 console.log(`\n${u} desktop=${dv.length} phone=${mt.length} missingOnPhone=${miss.length} clampedPhone=${clampM.length} inFold=${foldM}`);
 miss.slice(0,12).forEach(t=>console.log('  MISS'+(mall.includes(t)?'(hidden)':'(absent)')+': '+t.slice(0,150)));
 clampM.slice(0,5).forEach(t=>console.log('  CLAMP: '+t.slice(0,120)));
}
await b.close();})();

#!/usr/bin/env node
/* Reproducible PTS Design Picker QA. node docs/15-design-delivery/qa-design-picker.cjs */
'use strict';
const assert=require('node:assert/strict');
const path=require('node:path');
const {pathToFileURL}=require('node:url');
const {chromium}=require('../../tools/visual-tests/node_modules/playwright');
const root=__dirname, url=pathToFileURL(path.join(root,'design-picker.html')).href;
(async()=>{
 const b=await chromium.launch({headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
 let samples=0;
 try{
  for(const w of [320,390,768,1024,1440,1920]){
   const p=await b.newPage({viewport:{width:w,height:w<=390?844:900},deviceScaleFactor:1,reducedMotion:'reduce'});
   let errors=[];p.on('pageerror',e=>errors.push(e.message));p.on('console',e=>{if(e.type()==='error')errors.push('console '+e.text())});
   await p.goto(url,{waitUntil:'load'});
   assert.equal(await p.locator('h1').count(),1);
   assert.equal(await p.locator('.layout-card').count(),12);
   assert.equal(await p.locator('.effect-card').count(),16);
   for(const [page,pattern] of [['projects','cinema'],['home','bleed'],['solutions','energy'],['pdp','atelier'],['crm','workspace'],['knowledge','precision']]){
    await p.locator('#page-select').selectOption(page);
    assert.equal(await p.locator('.layout-card.chosen').getAttribute('data-pick'),pattern);
    assert((await p.locator('#brief-text').inputValue()).includes(pattern==='cinema'?'Solar Cinema':pattern==='atelier'?'Product Atelier':pattern==='energy'?'Spatial Energy Lab':pattern==='workspace'?'Instrument Workspace':pattern==='bleed'?'Immersive Full-bleed':'Precision Paper'));
   }
   await p.locator('#page-select').selectOption('projects');
   for(const cat of ['cinematic','editorial','technical','commerce','experimental']){
    await p.locator('button[data-filter="'+cat+'"]').click();
    assert((await p.locator('.layout-card').count())>0,'filter '+cat);
    assert.equal(await p.locator('button[data-filter="'+cat+'"]').getAttribute('aria-pressed'),'true');
   }
   await p.locator('button[data-filter="all"]').click();
   await p.locator('[data-pick="bleed"]').click();
   assert((await p.locator('#selected-title').innerText()).includes('Full-bleed'));
   await p.locator('#compare-button').click();
   await p.locator('[data-pick="editorial"]').click();await p.locator('#compare-button').click();
   await p.locator('[data-pick="frame"]').click();await p.locator('#compare-button').click();
   assert.equal(await p.locator('.compare-chip').count(),3);
   await p.locator('[data-pick="cinema"]').click();await p.locator('#compare-button').click();
   assert.equal(await p.locator('.compare-chip').count(),3);
   assert.equal(await p.locator('.compare-chip').first().locator('strong').innerText(),'Editorial Split');
   await p.locator('button[data-remove="editorial"]').click();
   assert.equal(await p.locator('.compare-chip').count(),2);
   for(const id of ['chapters','lightbox','gallery'])await p.locator('[data-effect="'+id+'"]').check();
   assert.equal(await p.locator('#effects-count').innerText(),'3 / 3');
   await p.locator('[data-effect="video"]').click();
   assert.equal(await p.locator('[data-effect="video"]').isChecked(),false);
   assert((await p.locator('#effect-warning').innerText()).includes('tối đa'));
   await p.locator('#goal-input').fill('Khách thấy được năng lực thực tế và tìm được dự án liên quan.');
   const brief=await p.locator('#brief-text').inputValue();
   assert(brief.includes('Khách thấy được năng lực thực tế'));
   assert(brief.includes('Solar Cinema'));
   assert(brief.includes('Sticky Chapter Progress'));
   const info=await p.evaluate(()=>({over:document.documentElement.scrollWidth-document.documentElement.clientWidth,scroll:document.documentElement.scrollHeight,img:document.querySelector('#preview-photo').naturalWidth}));
   assert(info.over<=1,'overflow '+w+': '+info.over);assert(info.img>0);
   assert.deepEqual(errors,[],'console errors '+w);
   if(w===390||w===1440){
    await p.locator('button[data-filter="all"]').click();
    await p.locator('[data-pick="cinema"]').click();
    await p.evaluate(async()=>{const imgs=[...document.images];for(const img of imgs){await img.decode().catch(()=>{});}});
    await p.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
    await p.waitForTimeout(100);
    await p.screenshot({path:path.join(root,'evidence','design-picker-'+w+'.jpg'),type:'jpeg',quality:78,fullPage:true,animations:'disabled'});
   }
   samples++;
   await p.close();
  }
  console.log('PASS '+samples+' screen sizes; 12 layout cards; 16 effects; 6 pages; filters, compare, preview, brief, max effects; no overflow / page errors');
 }finally{await b.close();}
})().catch(e=>{console.error('FAIL '+e.stack);process.exitCode=1});

#!/usr/bin/env node
/* PTS Layout Lab reproducible visual smoke. Run: node docs/15-design-delivery/qa-layout-lab.cjs */
'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const { pathToFileURL } = require('node:url');
const { chromium } = require('../../tools/visual-tests/node_modules/playwright');

const root=__dirname;
const href=pathToFileURL(path.join(root,'layout-lab.html')).href;
const widths=[320,390,768,1440,1920];
const layouts=['bleed','editorial','frame'];

async function warmImages(page) {
  const images=await page.locator('main img').all();
  for(const img of images){
    await img.scrollIntoViewIfNeeded();
    await img.evaluate(async node=>{
      if(node.getAttribute('src')) await node.decode().catch(()=>{});
    });
  }
  await page.evaluate(()=>window.scrollTo({top:0,behavior:'instant'}));
  await page.waitForTimeout(80);
}

(async()=>{
  const browser=await chromium.launch({headless:true,args:['--no-sandbox','--disable-dev-shm-usage']});
  const checked=[];
  try{
    for(const width of widths){
      const page=await browser.newPage({viewport:{width,height:width===390||width===320?844:900},deviceScaleFactor:1,reducedMotion:'reduce'});
      const errors=[];
      page.on('pageerror',e=>errors.push(e.message));
      page.on('console',e=>{if(e.type()==='error')errors.push(e.text())});
      await page.goto(href,{waitUntil:'load'});
      assert.equal(await page.locator('h1').count(),1);
      for(const layout of layouts){
        await page.locator('button[data-layout="'+layout+'"]').click();
        await page.locator('#hero-photo').evaluate(img=>img.decode());
        const measure=await page.evaluate(()=>({
          over:document.documentElement.scrollWidth-document.documentElement.clientWidth,
          current:document.getElementById('demo-site').dataset.layout,
          image:document.getElementById('hero-photo').naturalWidth,
        }));
        assert(measure.over<=1,'horizontal overflow at '+width+' '+layout+': '+measure.over);
        assert.equal(measure.current,layout);
        assert(measure.image>0,'missing hero image at '+width+' '+layout);
        if(width===390||width===1440){
          await warmImages(page);
          await page.screenshot({path:path.join(root,'evidence','layout-'+layout+'-'+width+'.jpg'),
            type:'jpeg',quality:78,fullPage:true,animations:'disabled'});
        }
        checked.push(width+':'+layout);
      }
      await page.locator('button[data-layout="bleed"]').click();
      await page.locator('#shade-range').fill('88');
      assert.equal(await page.locator('#shade-value').innerText(),'88%');
      for(const kind of ['farm','device','lighting']){
        await page.locator('button[data-story="'+kind+'"]').click();
        assert.equal(await page.locator('button[data-story="'+kind+'"]').getAttribute('aria-pressed'),'true');
        await page.locator('#story-image').evaluate(img=>img.decode());
      }
      await page.locator('#open-lightbox').click();
      assert(await page.locator('#lightbox').evaluate(x=>x.open));
      await page.keyboard.press('Escape');
      assert(!await page.locator('#lightbox').evaluate(x=>x.open));
      if(width<=390){
        const menu=page.locator('#menu-toggle');
        await menu.click();
        assert.equal(await menu.getAttribute('aria-expanded'),'true');
        await page.locator('#site-nav a[href="#gallery"]').click();
        assert.equal(await menu.getAttribute('aria-expanded'),'false');
      }
      await page.evaluate(()=>window.scrollTo({top:document.getElementById('principles').offsetTop,behavior:'instant'}));
      await page.waitForTimeout(60);
      assert.equal(await page.locator('#demo-site').getAttribute('data-scrolled'),'true');
      assert.deepEqual(errors,[],'browser errors at '+width);
      await page.close();
    }
    const control=await browser.newPage({viewport:{width:1440,height:900},reducedMotion:'no-preference'});
    await control.goto(href);
    await control.locator('#header-toggle').click();
    assert.equal(await control.locator('#demo-site').getAttribute('data-header'),'solid');
    await control.locator('#motion-toggle').click();
    assert.equal(await control.locator('#demo-site').getAttribute('data-motion'),'off');
    await control.close();
    console.log('PASS: '+checked.length+' responsive layouts, 0 overflow; gallery, dialog, menu, header, motion, shade; 6 JPG evidence files');
  } finally {await browser.close()}
})().catch(error=>{console.error('FAIL: '+error.stack);process.exitCode=1});

/**
 * Deterministic photo-only presentation: EXACT source bitmap from internal supplier workbook,
 * white square with contain (no text, claims, object edits, AI, cropping).
 * Run in project root: node scripts/content/render-oriana-real-photos-20261117.cjs
 */
const fs=require('fs');const path=require('path');const crypto=require('crypto');
const {chromium}=require('../../tools/visual-tests/node_modules/playwright');
const ROOT=path.resolve(__dirname,'../..'),DIR=path.join(ROOT,'backend/resources/product-gallery/2026-11-17/batch-p0-real-photo');
const manifestPath=path.join(DIR,'source-manifest.json'),manifest=JSON.parse(fs.readFileSync(manifestPath));
(async()=>{
 const browser=await chromium.launch({headless:true});
 try{
 const page=await browser.newPage({viewport:{width:1200,height:1200},deviceScaleFactor:1});
 for(const i of manifest){
  const original=fs.readFileSync(i.source_file);
  const hash=crypto.createHash('sha256').update(original).digest('hex');
  if(hash!==i.source_sha256)throw new Error('Source SHA mismatch for '+i.sku);
  const src='data:image/png;base64,'+original.toString('base64');
  await page.setContent('<!doctype html><meta charset="utf-8"><style>html,body{width:1200px;height:1200px;margin:0;overflow:hidden;background:#fff}body{display:flex;align-items:center;justify-content:center}img{width:90%;height:90%;object-fit:contain;image-rendering:auto}</style><img src="'+src+'">',{waitUntil:'load'});
  const dest=path.join(DIR,i.output_file);
  await page.screenshot({path:dest,type:'jpeg',quality:91,animations:'disabled'});
  i.output_sha256=crypto.createHash('sha256').update(fs.readFileSync(dest)).digest('hex');
  i.output_bytes=fs.statSync(dest).size;
  i.output_format='JPEG 1200x1200';
  console.log('RENDERED',i.product_id,i.output_file,i.output_bytes,'sha256',i.output_sha256.slice(0,12));
 }
 fs.writeFileSync(manifestPath,JSON.stringify(manifest,null,2)+'\n');
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

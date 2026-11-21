const fs=require('fs'),path=require('path'),crypto=require('crypto');
const {chromium}=require('../../tools/visual-tests/node_modules/playwright');
const root=path.resolve(__dirname,'../..'),dir=path.join(root,'backend/resources/product-gallery/2026-11-17/batch-p0-solax-official');
const file=path.join(dir,'official-source-manifest.json');let data=JSON.parse(fs.readFileSync(file));
(async()=>{
 const browser=await chromium.launch({headless:true});
 try{
  const page=await browser.newPage({viewport:{width:1200,height:1200},deviceScaleFactor:1});
  for(const a of data.assets){
   const original=fs.readFileSync(a.source_file);
   if(crypto.createHash('sha256').update(original).digest('hex')!==a.source_sha256)throw Error('Original changed');
   const html='<html><meta charset="utf-8"><style>html,body{width:1200px;height:1200px;margin:0;background:#fff;overflow:hidden}body{display:flex;align-items:center;justify-content:center}img{height:94%;width:94%;object-fit:contain}</style><img src="data:image/png;base64,'+original.toString('base64')+'"></html>';
   await page.setContent(html,{waitUntil:'load'});
   const out=path.join(dir,a.output_file);
   await page.screenshot({path:out,type:'jpeg',quality:92,animations:'disabled'});
   a.output_sha256=crypto.createHash('sha256').update(fs.readFileSync(out)).digest('hex');
   a.output_bytes=fs.statSync(out).size;a.output_format='JPEG 1200x1200';
   console.log('READY',a.view,a.output_file,a.output_bytes,a.output_sha256.slice(0,16));
  }
  data.status='READY_FOR_QA';
  fs.writeFileSync(file,JSON.stringify(data,null,2)+'\n');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});

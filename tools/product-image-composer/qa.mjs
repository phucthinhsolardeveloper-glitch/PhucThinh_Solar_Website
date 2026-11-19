#!/usr/bin/env node
import fs from 'node:fs/promises';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
const require = createRequire(import.meta.url);
const sharp = require('sharp');
const here = path.dirname(fileURLToPath(import.meta.url));
const argv = process.argv.slice(2);
const arg=(key,fallback='')=>{const i=argv.indexOf(key);return i<0?fallback:argv[i+1]};
const inputRoot=path.resolve(arg('--input-root','/home/admin01/orca/artifacts/pts-floodlight-images-20261117/input'));
const outputRoot=path.resolve(arg('--output-root','/home/admin01/orca/artifacts/pts-floodlight-images-20261117/output'));
const maxBytes=5*1024*1024;
const fields=JSON.parse(await fs.readFile(path.join(inputRoot,'den-pha-72.json'),'utf8'));
const code=arg('--code');
const products=code?fields.filter(p=>p.code.toLowerCase()===code.toLowerCase()):fields.filter(p=>fsSyncExists(path.join(outputRoot,p.code,'qa.json')));
function fsSyncExists(file){try{return require('node:fs').existsSync(file)}catch{return false}}
if(!products.length){console.error('Không tìm thấy bộ ảnh cần QA.');process.exit(2)}
let failed=0;
for(const p of products){
  const dir=path.join(outputRoot,p.code), qaPath=path.join(dir,'qa.json');
  const qa=JSON.parse(await fs.readFile(qaPath,'utf8'));
  const expected=qa.certificate?8:7;
  const files=Array.from({length:expected},(_,i)=>path.join(dir,`${String(i+1).padStart(2,'0')}.jpg`));
  const result={code:p.code,checks:{images:[],numberSources:[],logoLocations:[],productIntegrity:{status:'source hash recorded; decoded-pixel identity not verified'},backgroundVisualReview:{status:'manual review recorded in prompts.json'},dom:{status:'NOT_RUN',reason:'Playwright Chromium launch is blocked by this sandbox; DOM geometry is a release gate'}},passed:false};
  for(const file of files){
    try{
      const st=await fs.stat(file),m=await sharp(file).metadata();
      const pass=m.width===1600&&m.height===1600&&!m.hasAlpha&&st.size<=maxBytes;
      result.checks.images.push({file:path.basename(file),width:m.width,height:m.height,hasAlpha:m.hasAlpha,sizeBytes:st.size,passed:pass});
      if(!pass)failed++;
    }catch(e){result.checks.images.push({file:path.basename(file),error:e.message,passed:false});failed++}
  }
  const map=qa.slides||[];
  const sourceValues=new Set(Object.entries(p).flatMap(([key,value])=>[
    typeof value==='string'?value:'', Array.isArray(value)?value.map(x=>typeof x==='string'?x:JSON.stringify(x)).join(' '):''
  ]).filter(Boolean));
  for(const slide of map){
    for(const row of slide.text||[]){
      if(/\d/.test(row.text)){
        const valid=[...sourceValues].some(v=>v.includes(row.text)) || row.source==='static: định tính, charging_time trống';
        result.checks.numberSources.push({image:slide.image,text:row.text,source:row.source,valid});
        if(!valid)failed++;
      }
    }
  }
  for(const i of [1,4,5,6,7])result.checks.logoLocations.push({image:`${String(i).padStart(2,'0')}.jpg`,expectedCanvasBox900:{x:34,y:24,width:310,height:56},reportedOutputBox1600:{x:60,y:43,width:551,height:100},verification:'placement from source asset manifest; CV template-match not run'});
  result.passed=result.checks.images.length===expected&&result.checks.images.every(x=>x.passed)&&result.checks.numberSources.every(x=>x.valid)&&result.checks.dom.status==='PASS';
  result.checks.dom.status='NOT_RUN';
  qa.qaRun=result;
  await fs.writeFile(qaPath,JSON.stringify(qa,null,2));
  console.log(`${p.code}: ${result.checks.images.filter(x=>x.passed).length}/${expected} canvas checks; ${result.checks.numberSources.filter(x=>x.valid).length}/${result.checks.numberSources.length} numeric source checks; DOM=NOT_RUN; gate=${result.passed?'PASS':'FAIL'}`);
}
if(failed)process.exit(1);


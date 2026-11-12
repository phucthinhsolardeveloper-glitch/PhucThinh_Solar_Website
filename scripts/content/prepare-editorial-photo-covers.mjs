// Run from repository root. Reproducible licensed-photo covers, *no* AI filters or logos.
// All source URLs, credits and usage rights live in photo-cover-selections.json.
import sharp from '../../frontend/node_modules/sharp/lib/index.js';
import {readFileSync,existsSync,statSync,unlinkSync} from 'node:fs';
import {execFileSync} from 'node:child_process';
import {join} from 'node:path';
const root=process.cwd();
const manifest=JSON.parse(readFileSync(join(root,'backend/resources/editorial/2026-11-12/photo-cover-selections.json'),'utf8'));
const folder=join(root,'frontend/public/assets/images/editorial');
for(const item of manifest.entries){
 const file=join(folder,item.cover_file);
 if(item.type!=='licensed_photo'){
   if(!existsSync(file))throw new Error('Missing original diagram '+file);
   continue;
 }
 if(!/^PTS-KT-0\d{2}$/.test(item.plan_key)||!/^pts-photo-\d{3}\.webp$/.test(item.cover_file)
     ||!Number.isInteger(item.image_id)||!item.source_url.startsWith('https://www.pexels.com/photo/')) {
   throw new Error('Invalid photo manifest entry '+item.plan_key);
 }
 const tmp=file+'.download';
 const url='https://images.pexels.com/photos/'+item.image_id+'/pexels-photo-'+item.image_id+'.jpeg?auto=compress&cs=tinysrgb&w=2400';
 try{
   execFileSync('curl',['--fail','--location','--silent','--show-error','--retry','2','--connect-timeout','8','--max-time','35',url,'--output',tmp],{stdio:'pipe'});
   // Editorial photos are real photographs. Use a full-bleed crop, not blurred borders
   // and do not bake in a logo (which could imply PTS owns the pictured installation).
   await sharp(tmp).rotate().resize(1600,900,{fit:'cover',position:sharp.strategy.attention})
      .webp({quality:86,effort:5}).toFile(file);
   const meta=await sharp(file).metadata();
   if(meta.width!==1600||meta.height!==900||statSync(file).size<15000)
      throw new Error('Unexpected photo result '+item.cover_file);
   console.log(item.plan_key,item.cover_file,statSync(file).size);
 } finally { if(existsSync(tmp))unlinkSync(tmp); }
}

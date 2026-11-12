// Prepare licensed editorial cover photos before release, not at deploy time.
import { mkdirSync, readFileSync, writeFileSync, unlinkSync, existsSync, statSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { join } from 'node:path';
import sharp from '../../frontend/node_modules/sharp/lib/index.js';
const out=join(process.cwd(),'frontend/public/assets/images/editorial');
mkdirSync(out,{recursive:true});
const images=[
 {file:'pts-solar-how-it-works.webp',id:29206495,credit:'Stefan de Vries',page:'https://www.pexels.com/photo/modern-home-with-rooftop-solar-panels-29206495/'},
 {file:'pts-solar-datasheet-guide.webp',id:6961110,credit:'Hoan Ngọc',page:'https://www.pexels.com/photo/solar-technician-installing-solar-panels-6961110/'},
 {file:'pts-global-pv-trends-2026.webp',id:35105436,credit:'Quang Nguyen Vinh',page:'https://www.pexels.com/photo/aerial-view-of-vast-solar-farm-with-mountains-35105436/'}
];
const ledger=[];
for(const item of images) {
 const dest=join(out,item.file);
 if(!existsSync(dest) || (process.argv.includes('--refresh-light') && item.file.startsWith('pts-light-'))) {
  const tmp=dest+'.download';
  const url='https://images.pexels.com/photos/'+item.id+'/pexels-photo-'+item.id+'.jpeg?auto=compress&cs=tinysrgb&w=2200';
  execFileSync('curl',['-fLsS','--retry','2','--connect-timeout','10','--max-time','40',url,'-o',tmp],{stdio:'inherit'});
  if(item.file.startsWith('pts-light-')) {
    // Solar street lamps are tall; a full-bleed crop cuts the panel or lamp head.
    // Keep the entire subject on a softly blurred landscape photograph.
    const background=await sharp(tmp).rotate().resize(1600,900,{fit:'cover'}).blur(34).modulate({brightness:0.7}).toBuffer();
    const subject=await sharp(tmp).rotate().resize(1200,900,{fit:'inside'}).modulate({brightness:item.file==='pts-light-benefits.webp'?1.35:1}).toBuffer();
    const m=await sharp(subject).metadata();
    await sharp(background).composite([{input:subject,left:Math.floor((1600-m.width)/2),top:Math.floor((900-m.height)/2)}])
      .webp({quality:83,effort:5}).toFile(dest);
  } else {
    await sharp(tmp).rotate().resize(1600,900,{fit:'cover',position:'attention'}).webp({quality:82,effort:5}).toFile(dest);
  }
  unlinkSync(tmp);
 }
 const meta=await sharp(dest).metadata();
 if(meta.width!==1600 || meta.height!==900 || statSync(dest).size<3000)throw new Error('Unexpected media: '+item.file);
 ledger.push({name:item.file,source:item.page,photographer:item.credit,license:'https://www.pexels.com/license/',originalId:item.id,dimensions:'1600x900',type:'Photo illustration; not a PTS project image'});
 console.log(item.file,statSync(dest).size);
}
writeFileSync(join(process.cwd(),'backend/resources/editorial/2026-11-11/photo-sources.json'),JSON.stringify(ledger,null,2)+'\n');

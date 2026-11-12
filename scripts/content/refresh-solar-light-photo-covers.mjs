// Editorial photographs of street-light installations; Pexels License.
// No blur-pillar background, no pseudo-project claim, original author credited on the page.
import sharp from '../../frontend/node_modules/sharp/lib/index.js';
import {execFileSync} from 'node:child_process';
import {mkdirSync,rmSync} from 'node:fs';
import {join} from 'node:path';
const out=join(process.cwd(),'frontend/public/assets/images/editorial');
mkdirSync(out,{recursive:true});
const photos=[

 {file:'pts-light-installation.webp',id:32915849,crop:'attention',brightness:1.10,saturation:1.10},
 {file:'pts-light-choice.webp',id:35828688,crop:1150,brightness:1.22,saturation:1.16}
];
for (const item of photos){
 const source=join('/tmp','pts-editorial-raw-'+item.id+'.jpg');
 const url='https://images.pexels.com/photos/'+item.id+'/pexels-photo-'+item.id+'.jpeg?auto=compress&cs=tinysrgb&w=2200';
 execFileSync('curl',['-fLsS','--retry','2','--connect-timeout','7','--max-time','35',url,'-o',source]);
 let pipeline=sharp(source).rotate();
 const meta=await pipeline.metadata();
 if (typeof item.crop==='number'){
  // Widescreen crop of the scene, keeping the installation and road/path in view.
  const cropWidth=Math.min(meta.width,1600);
  const cropHeight=Math.min(meta.height,900);
  const x=Math.floor((meta.width-cropWidth)/2);
  const y=Math.max(0,Math.min(meta.height-cropHeight,item.crop));
  pipeline=pipeline.extract({left:x,top:y,width:cropWidth,height:cropHeight});
 }
 const dest=join(out,item.file);
 await pipeline.resize(1600,900,{fit:'cover',position:item.crop==='attention'?'attention':'centre'})
    .modulate({brightness:item.brightness,saturation:item.saturation})
    .webp({quality:86,effort:5}).toFile(dest);
 const actual=await sharp(dest).metadata();
 if(actual.width!==1600||actual.height!==900)throw new Error('bad dimensions '+dest);
 console.log(item.file,actual.size);
 rmSync(source,{force:true});
}

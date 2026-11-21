#!/usr/bin/env python3
"""QA-first lossless product photo cleanup (no production writes).

Source originals only. Removes background only for whiteside catalog shots using
offline rembg. Preserves official alpha instead of re-generating product geometry.
Sharpening is modest and cannot restore true detail absent in 500px sources.

Usage:
 /home/admin01/src/phuc-thinh-image-process/.venv/bin/python scripts/content/preview-product-photo-upgrade-20261117.py --mode sample
"""
import json,sys,hashlib,time,math
from pathlib import Path
from PIL import Image,ImageFilter,ImageEnhance,ImageChops
import numpy as np
from rembg import remove,new_session

BASE=Path('/home/admin01/src/Web_PTS_PHP')
SRC1=BASE/'backend/resources/product-gallery/2026-11-17/batch-p0-solax-official/source'
SRC2=Path('/home/admin01/src/Data/missing_photo_20261007')
OUT=BASE/'backend/resources/product-gallery/2026-11-17/qa-transparent-rework'
OUT.mkdir(parents=True,exist_ok=True)
ITEMS=[
 ('solax-front',SRC1/'2656fff320.png','keep_original_alpha'),
 ('oriana-db03-2',SRC2/'DB03-2/3.png','isnet-general-use'),
 ('oriana-solar-light-bl120w',SRC2/'SOLAR-LIGHT-BL120W/3.png','isnet-general-use'),
 ('oriana-ys80w',SRC2/'YS-80W/2.png','isnet-general-use'),
 ('oriana-hb01-60w',SRC2/'HB01-60W/2.png','isnet-general-use'),
 ('oriana-pl0307',SRC2/'PL-0307_300_300_H380/1.png','isnet-general-use'),
]
def metrics(img):
 rgba=img.convert('RGBA');arr=np.asarray(rgba.getchannel('A'))
 bbox=rgba.getchannel('A').getbbox()
 return {'size':list(rgba.size),'transparency_fraction':round(float((arr<8).mean()),3),
   'opaque_fraction':round(float((arr>247).mean()),3),
   'soft_alpha_fraction':round(float(((arr>=8)&(arr<=247)).mean()),3),
   'alpha_bbox':list(bbox) if bbox else None,
   'alpha_bbox_fraction':round((bbox[2]-bbox[0])*(bbox[3]-bbox[1])/img.width/img.height,3) if bbox else 0}
def fit(img,canvas=1600,pad=0.09):
 img=img.convert('RGBA')
 a=img.getchannel('A')
 box=a.getbbox()
 if not box:raise ValueError('Transparent empty')
 img=img.crop(box)
 maxside=round(canvas*(1-2*pad))
 ratio=min(maxside/img.width,maxside/img.height)
 sz=(max(1,round(img.width*ratio)),max(1,round(img.height*ratio)))
 img=img.resize(sz,Image.Resampling.LANCZOS)
 rgba=img.convert('RGBA')
 rgb=rgba.convert('RGB')
 # subtle only; avoid restoring invented detail, preserve labels as-is
 sharp=rgb.filter(ImageFilter.UnsharpMask(radius=0.72,percent=72,threshold=4))
 sharp.putalpha(rgba.getchannel('A'))
 out=Image.new('RGBA',(canvas,canvas),(0,0,0,0))
 out.alpha_composite(sharp,((canvas-sharp.width)//2,(canvas-sharp.height)//2))
 return out
def checker(img):
 bg=Image.new('RGBA',img.size,(240,244,248,255))
 bg.alpha_composite(img)
 return bg.convert('RGB')
def write(name,orig,cut,model,elapsed):
 dst=OUT/(name+'-1600-transparent.png')
 final=fit(cut);final.save(dst,optimize=True)
 check=checker(final)
 check.thumbnail((600,600),Image.Resampling.LANCZOS)
 check.save(OUT/(name+'-on-grey.jpg'),quality=89)
 m={'name':name,'model':model,'source':str(orig),'source_sha256':hashlib.sha256(orig.read_bytes()).hexdigest(),'source_size':list(Image.open(orig).size),
    'output':str(dst),'output_sha256':hashlib.sha256(dst.read_bytes()).hexdigest(),'output_bytes':dst.stat().st_size,
    'alpha_before':metrics(Image.open(orig)), 'alpha_after':metrics(final), 'elapsed_s':round(elapsed,2), 'status':'QA_DRAFT_NOT_PUBLISHED'}
 (OUT/(name+'-qa.json')).write_text(json.dumps(m,indent=2))
 print('DONE',name,model,'elapsed',m['elapsed_s'],'source',m['source_size'],'alpha',m['alpha_after']['transparency_fraction'],'bbox',m['alpha_after']['alpha_bbox_fraction'],'bytes',m['output_bytes'],flush=True)
 return m
selected=ITEMS if '--all' in sys.argv else ITEMS[:2]
report=[];sessions={}
for name,orig,kind in selected:
 try:
  t0=time.time()
  img=Image.open(orig).convert('RGBA')
  a=np.asarray(img.getchannel('A'))
  if kind=='keep_original_alpha':
   if (a<20).mean()<0.05:raise RuntimeError('Original alpha not meaningfully transparent')
   cut=img
  else:
   if kind not in sessions: sessions[kind]=new_session(kind,providers=['CPUExecutionProvider'])
   cut=remove(img,session=sessions[kind])
  m=write(name,orig,cut,kind,time.time()-t0);report.append(m)
 except Exception as e:
  print('ERROR',name,repr(e),flush=True);report.append({'name':name,'error':repr(e),'status':'FAIL'})
(OUT/'batch-qa-manifest.json').write_text(json.dumps(report,indent=2))
print('SUMMARY',len(report),'OK',sum(x['status']=='QA_DRAFT_NOT_PUBLISHED' for x in report),flush=True)

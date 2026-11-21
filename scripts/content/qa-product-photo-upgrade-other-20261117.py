#!/usr/bin/env python3
"""Finish extra missing QA samples without touching production."""
import hashlib,json,time
from pathlib import Path
from PIL import Image,ImageFilter
import numpy as np
from scipy import ndimage as ndi
from rembg import remove,new_session

R=Path('/home/admin01/src/Web_PTS_PHP/backend/resources/product-gallery/2026-11-17')
OUT=R/'qa-transparent-rework';OUT.mkdir(parents=True,exist_ok=True)
SOLAX=R/'batch-p0-solax-official/source'
OR=Path('/home/admin01/src/Data/missing_photo_20261007')
def fit(img,canvas=1600):
 img=img.convert('RGBA')
 a=img.getchannel('A');bbox=a.getbbox()
 if not bbox:raise RuntimeError('Alpha is blank')
 img=img.crop(bbox)
 s=int(canvas*.82);r=min(s/img.width,s/img.height)
 img=img.resize((round(img.width*r),round(img.height*r)),Image.Resampling.LANCZOS)
 rgb=img.convert('RGB').filter(ImageFilter.UnsharpMask(radius=.72,percent=72,threshold=4))
 rgb.putalpha(img.getchannel('A'))
 out=Image.new('RGBA',(canvas,canvas),(0,0,0,0));out.alpha_composite(rgb,((canvas-rgb.width)//2,(canvas-rgb.height)//2))
 return out
def write(name,src,cut,method):
 f=OUT/(name+'-1600-transparent.png');a=fit(cut);a.save(f,optimize=True)
 sample=Image.new('RGBA',a.size,(237,242,247,255));sample.alpha_composite(a)
 sample.thumbnail((600,600),Image.Resampling.LANCZOS)
 sample.convert('RGB').save(OUT/(name+'-on-grey.jpg'),quality=88)
 m={'name':name,'source':str(src),'method':method,'output':str(f),'source_sha256':hashlib.sha256(src.read_bytes()).hexdigest(),'output_sha256':hashlib.sha256(f.read_bytes()).hexdigest(),'output_bytes':f.stat().st_size,'status':'DRAFT_QA_REQUIRED'}
 (OUT/(name+'-qa.json')).write_text(json.dumps(m,ensure_ascii=False,indent=2))
 print('FINISHED',name,method,'bytes',f.stat().st_size,'alpha_fraction',round(np.mean(np.asarray(a.getchannel('A'))<8),3),flush=True)
def db03_flood(img):
 rgb=np.array(img.convert('RGB'));low=rgb.min(axis=2);high=rgb.max(axis=2);mask=(low>=242)&((high.astype('i2')-low.astype('i2'))<30)
 seed=np.zeros(mask.shape,bool);seed[0,:]=seed[-1,:]=True;seed[:,0]=seed[:,-1]=True
 bg=ndi.binary_propagation(seed&mask,mask=mask)
 labels,n=ndi.label(~bg);sz=np.bincount(labels.ravel());sz[0]=0;ids=np.flatnonzero(sz>=200)
 if len(ids)!=3:raise RuntimeError(f'Expected 3 views, got {len(ids)}')
 alpha=Image.fromarray((np.isin(labels,ids).astype('uint8')*255),'L').filter(ImageFilter.GaussianBlur(.56))
 out=img.convert('RGBA');out.putalpha(alpha);return out
src=OR/'DB03-2/3.png';write('oriana-db03-2-all3views',src,db03_flood(Image.open(src)),'white_flood_242_retained_3_views')
for n,fn in [('solax-left','e3a1b8240e.png'),('solax-right','44ff1369f2.png')]:
 src=SOLAX/fn;img=Image.open(src).convert('RGBA')
 if np.mean(np.asarray(img.getchannel('A'))<8)<.1:raise RuntimeError('Source no alpha '+fn)
 write(n,src,img,'original_manufacturer_alpha_preserved')
src=OR/'DB-16300W/1.png'
s=new_session('isnet-general-use',providers=['CPUExecutionProvider'])
cut=remove(Image.open(src).convert('RGBA'),session=s)
write('oriana-db16300',src,cut,'isnet_general_use')

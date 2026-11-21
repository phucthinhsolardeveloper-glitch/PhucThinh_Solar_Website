#!/usr/bin/env python3
"""White-background foreground selection that keeps all catalog viewpoints.

Unlike semantic background-removal, anchored flood fill does not drop a third
side view just because the view is thin. The result still needs visual QA.
"""
from pathlib import Path
import numpy as np
from PIL import Image, ImageFilter
from scipy import ndimage as ndi
from collections import Counter
INP=Path('/home/admin01/src/Data/missing_photo_20261007/DB03-2/3.png')
ROOT=Path('/home/admin01/src/Web_PTS_PHP/backend/resources/product-gallery/2026-11-17/qa-transparent-rework')
ROOT.mkdir(exist_ok=True,parents=True)
img=Image.open(INP).convert('RGB')
a=np.asarray(img).astype(np.uint8)
minc=np.min(a,axis=2)
maxc=np.max(a,axis=2)
for threshold in (235,242,248,252):
 # background whites, low chroma, connected to edges
 mask=(minc>=threshold) & ((maxc.astype('int16')-minc.astype('int16'))<30)
 seed=np.zeros(mask.shape,np.uint8);seed[0,:]=1;seed[-1,:]=1;seed[:,0]=1;seed[:,-1]=1
 background=ndi.binary_propagation(seed.astype(bool)&mask,mask=mask)
 fg=~background
 label,n=ndi.label(fg)
 sizes=np.bincount(label.ravel());sizes[0]=0
 keep=np.zeros_like(fg)
 components=[]
 for compid in np.argsort(sizes)[::-1][:30]:
  if sizes[compid]<200:continue
  ys,xs=np.where(label==compid)
  bbox=(int(xs.min()),int(ys.min()),int(xs.max()),int(ys.max()))
  components.append((int(sizes[compid]),bbox))
  keep|=(label==compid)
 alpha=Image.fromarray((keep.astype('uint8')*255),'L').filter(ImageFilter.GaussianBlur(0.56))
 rgba=img.convert('RGBA');rgba.putalpha(alpha)
 rgba.save(ROOT/f'oriana-db03-2-flood-{threshold}.png',optimize=True)
 bg=Image.new('RGBA',img.size,(234,240,246,255));bg.alpha_composite(rgba)
 bg.convert('RGB').save(ROOT/f'oriana-db03-2-flood-{threshold}-on-grey.jpg',quality=90)
 print('THR',threshold,'comps',components[:9], 'ratio',round(float(keep.mean()),3),'alphaExt',alpha.getextrema())

#!/usr/bin/env python3
"""Stage EXACT floodlight product photos for review; NO DB, server or deployment writes.

Sources: internal supplier EMF converted with Windows GDI+, internal JD-6128 real photo,
manufacturer BCT direct (rights pending), Sunny Solar JD-V review reference (rights pending).
Background segmentation only, no generated lights, logos, hardware or specifications.
"""
import csv, json, time, hashlib, math
from pathlib import Path
from PIL import Image, ImageFilter, ImageDraw, ImageFont
import numpy as np
from scipy import ndimage as ndi

BASE=Path('/home/admin01/src/Web_PTS_PHP')
DST=BASE/'backend/resources/product-gallery/2026-11-17/den-pha-qa-batch-1'
EMF=DST/'emf-converted'
EXT=DST/'external-review-only'
DATA=Path('/home/admin01/src/Data/File_DuLieu_SanPham/Ảnh sản phẩm')
ALPHA=DST/'candidate-alpha'
ALPHA.mkdir(parents=True,exist_ok=True)

JOBS=[
 {'sku':'DB-760','kind':'EMF_BACKGROUND_CUTOUT','src':EMF/'DB-760-gdi-render.png','origin':'internal-supplier-EMF','usage':'candidate_primary_alpha_upgrade','model_check':'exact model label DB760 on lamp visible','source_native':'EMF bounds about 548x619; rendered to 1644x1857; extra detail NOT guaranteed','crop':None},
 {'sku':'DB-7300','kind':'EMF_BACKGROUND_CUTOUT','src':EMF/'DB-7300-gdi-render.png','origin':'internal-supplier-EMF','usage':'candidate_primary_alpha_upgrade','model_check':'exact model label DB7300 on lamp visible','source_native':'EMF bounds about 633x587; rendered to 1899x1761','crop':None},
 {'sku':'DB-7500','kind':'EMF_BACKGROUND_CUTOUT','src':EMF/'DB-7500-gdi-render.png','origin':'internal-supplier-EMF','usage':'candidate_primary_alpha_upgrade','model_check':'exact model label DB7500 on lamp visible','source_native':'EMF bounds about 603x615; rendered to 1809x1845','crop':None},
 {'sku':'DB-71000','kind':'EMF_BACKGROUND_CUTOUT','src':EMF/'DB-71000-gdi-render.png','origin':'internal-supplier-EMF','usage':'candidate_primary_alpha_upgrade','model_check':'exact model label DB71000 on lamp visible','source_native':'EMF bounds about 448x571; rendered to 1344x1713','crop':None},
 {'sku':'BCT-WR1.0','kind':'WHITE_CUTOUT','src':EXT/'BCT-WR1-0-official-04.jpg','origin':'blue-carbon-manufacturer-direct','usage':'candidate_gallery_front_side','model_check':'manufacturer product page BCT-WR1.0, physical lamp has 4 rectangular LED modules like live SKU','crop':[0.14,0.1,0.93,0.64],'source_page':'https://www.bctvnergy.com/solar-light/solar-flood-light/blue-carbon-ce-solar-flood.html','requires_usage_permission':True},
 {'sku':'BCT-WR1.0','kind':'KEEP_TECH_DIAGRAM','src':EXT/'BCT-WR1-0-official-06.jpg','origin':'blue-carbon-manufacturer-direct','usage':'candidate_gallery_dimensions_reference','model_check':'official dimensions graphic; validate physical revision prior to publishing','crop':None,'source_page':'https://www.bctvnergy.com/solar-light/solar-flood-light/blue-carbon-ce-solar-flood.html','requires_usage_permission':True},
 {'sku':'BCT-WR4.0','kind':'WHITE_CUTOUT','src':EXT/'BCT-WR4-0-official-03.jpg','origin':'blue-carbon-manufacturer-direct','usage':'candidate_gallery_lamp_front','model_check':'manufacturer product page BCT-WR4.0, lamp visibly consistent with live 4x4 LED face','crop':[0.11,0.13,0.94,0.8],'source_page':'https://www.bctvnergy.com/solar-light/solar-flood-light/30w-led-solar-flood-light.html','requires_usage_permission':True},
 {'sku':'BCT-WR4.0','kind':'WHITE_CUTOUT','src':EXT/'BCT-WR4-0-official-04.jpg','origin':'blue-carbon-manufacturer-direct','usage':'candidate_gallery_lamp_angle','model_check':'manufacturer same BCT-WR4.0 page and visible housing','crop':[0.10,0.1,0.95,0.79],'source_page':'https://www.bctvnergy.com/solar-light/solar-flood-light/30w-led-solar-flood-light.html','requires_usage_permission':True},
 {'sku':'BCT-WR4.0','kind':'WHITE_CUTOUT','src':EXT/'BCT-WR4-0-official-06.jpg','origin':'blue-carbon-manufacturer-direct','usage':'candidate_gallery_back_bracket','model_check':'manufacturer same BCT-WR4.0 page; rear bracket pictorial','crop':[0.09,0.43,0.96,0.93],'source_page':'https://www.bctvnergy.com/solar-light/solar-flood-light/30w-led-solar-flood-light.html','requires_usage_permission':True},
 {'sku':'JD-6128','kind':'ISNET_CUTOUT','src':DATA/'JD-6128.jpg','origin':'internal-supplier-original','usage':'candidate_gallery_real_lamp_closeup','model_check':'same circular reflector fixture as live JD-6128 and local source file code','crop':None},
 {'sku':'JD-V200','kind':'KEEP_REFERENCE','src':EXT/'JD-V200-sunny.jpeg','origin':'third-party-retailer','usage':'reference_only_not_new_gallery_same_view_as_current','model_check':'JD-V200 LED arrangement; source and live view very similar','crop':None,'source_page':'https://sunnysolar.vn/en/product/den-pha-nang-luong-mat-troi-jd-v-100w-500w/','requires_usage_permission':True},
 {'sku':'JD-V300','kind':'KEEP_REFERENCE','src':EXT/'JD-V300-sunny.jpeg','origin':'third-party-retailer','usage':'reference_only_not_new_gallery_same_view_as_current','model_check':'JD-V300 LED arrangement; source and live view very similar','crop':None,'source_page':'https://sunnysolar.vn/en/product/den-pha-nang-luong-mat-troi-jd-v-100w-500w/','requires_usage_permission':True}
]

def white_flood_remove(rgb, threshold=245):
    a=np.asarray(rgb.convert('RGB'))
    lo=a.min(axis=2); hi=a.max(axis=2)
    white=(lo>=threshold)&(hi.astype('i2')-lo.astype('i2')<=26)
    seed=np.zeros(white.shape,bool);seed[0,:]=True;seed[-1,:]=True;seed[:,0]=True;seed[:,-1]=True
    bg=ndi.binary_propagation(seed&white,mask=white)
    fg=~bg
    tags,n=ndi.label(fg)
    sizes=np.bincount(tags.ravel());sizes[0]=0
    minarea=max(20,int(fg.size*0.00012))
    retained=(tags!=0)&(sizes[tags]>=minarea)
    mask=Image.fromarray(retained.astype('uint8')*255,'L').filter(ImageFilter.GaussianBlur(0.6))
    result=rgb.convert('RGBA');result.putalpha(mask)
    return result

def transparent_fit(img, size=1400):
    img=img.convert('RGBA')
    a=img.getchannel('A')
    b=a.getbbox()
    if not b: raise ValueError('empty foreground')
    img=img.crop(b)
    side=int(size*.84)
    scale=min(side/max(1,img.width),side/max(1,img.height))
    img=img.resize((max(1,round(img.width*scale)),max(1,round(img.height*scale))),Image.Resampling.LANCZOS)
    res=Image.new('RGBA',(size,size),(0,0,0,0))
    res.alpha_composite(img,((size-img.width)//2,(size-img.height)//2))
    return res

session=None
def process(job,n):
    global session
    src=Path(job['src'])
    if not src.is_file():raise ValueError('Source not found '+str(src))
    im=Image.open(src)
    native=list(im.size)
    area=[round(v,3) for v in job['crop']] if job['crop'] else None
    if area:
        w,h=im.size
        bbox=tuple(int(v*s) for v,s in zip(area,[w,h,w,h]))
        im=im.crop(bbox)
    method=job['kind']
    if method in ('WHITE_CUTOUT','EMF_BACKGROUND_CUTOUT'):
        cut=white_flood_remove(im)
    elif method=='ISNET_CUTOUT':
        from rembg import remove,new_session
        if session is None:session=new_session('isnet-general-use',providers=['CPUExecutionProvider'])
        cut=remove(im.convert('RGBA'),session=session)
    else:
        cut=im.convert('RGBA')
    if method in ('KEEP_TECH_DIAGRAM','KEEP_REFERENCE'):
        result=cut # do not upscale a technical drawing
    else:result=transparent_fit(cut)
    a=np.asarray(result.getchannel('A'))
    coverage=float((a>80).mean())
    fully_transparent=float((a<7).mean())
    bbox=result.getchannel('A').getbbox()
    alpha_valid=fully_transparent>=.10 and coverage>=.01 and coverage<=.85
    fn='pts-denpha-'+job['sku'].lower().replace('.','-')+'-'+str(n).zfill(2)+'-qa-20261117.png'
    out=ALPHA/fn
    result.save(out,optimize=True)
    if alpha_valid:
        webp=out.with_suffix('.webp')
        result.save(webp,'WEBP',lossless=True,method=6,exact=True)
    else:
        webp=None
    return {**{k:v for k,v in job.items() if k not in ('src',)},'source_path':str(src),'native_size':native,
      'source_sha256':hashlib.sha256(src.read_bytes()).hexdigest(),'output_file':str(out),'output_sha256':hashlib.sha256(out.read_bytes()).hexdigest(),
      'output_size':list(result.size),'output_bytes':out.stat().st_size,'webp':str(webp) if webp else None,
      'webp_bytes':webp.stat().st_size if webp else None,
      'mask_coverage':round(coverage,3),'transparency_fraction':round(fully_transparent,3),
      'alpha_bbox':list(bbox)if bbox else None,
      'auto_alpha_pass':alpha_valid if method not in ('KEEP_REFERENCE','KEEP_TECH_DIAGRAM') else None,
      'qa_state':'DRAFT_REVIEW_ONLY_NO_PUBLISH','quality_flag':'NATIVE_SOURCE_UNDER_900PX' if min(native)<900 else 'SOURCE_SUFFICIENT_TO_REVIEW',
      'crop':area}

results=[]
for i,job in enumerate(JOBS,1):
    st=time.time()
    try:
        r=process(job,i)
        results.append(r)
        print('DONE',i,job['sku'],job['usage'],'size',r['output_size'],'alpha',r['auto_alpha_pass'],'quality',r['quality_flag'],'seconds',round(time.time()-st,1),flush=True)
    except Exception as e:
        results.append({'sku':job['sku'],'source_path':str(job['src']),'qa_state':'ERROR','error':repr(e)})
        print('ERROR',i,job['sku'],repr(e),flush=True)

# Build a review contact sheet using checkerboard for real alpha (rather than false white impression).
font_path='/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'
f=ImageFont.truetype(font_path,14)if Path(font_path).exists()else ImageFont.load_default()
cols=4;cw=350;ch=385;rows=math.ceil(len(results)/cols)
sheet=Image.new('RGB',(cols*cw,rows*ch),'#e9eef4');dr=ImageDraw.Draw(sheet)
for j,item in enumerate(results):
    x=j%cols*cw;y=j//cols*ch
    dr.rounded_rectangle((x+3,y+3,x+cw-4,y+ch-4),radius=7,fill='white',outline='#cdd8e5')
    dr.text((x+10,y+10),f"{j+1}. {item['sku']}",font=f,fill='#0a2251')
    dr.text((x+10,y+30),item.get('usage','ERROR')[:41],font=f,fill='#555555')
    if item.get('output_file'):
        img=Image.open(item['output_file']).convert('RGBA')
        img.thumbnail((cw-27,ch-110),Image.Resampling.LANCZOS)
        tx=x+(cw-img.width)//2;ty=y+58+(ch-116-img.height)//2
        # Dark 15px grid reveals alpha and white fringes.
        s=15;bg=Image.new('RGBA',img.size,'#f0f4f8');dd=ImageDraw.Draw(bg)
        for a in range(0,img.width,s):
            for b in range(0,img.height,s):
                if (a//s+b//s)%2:dd.rectangle((a,b,a+s-1,b+s-1),fill='#d8e0e9')
        bg.alpha_composite(img);sheet.paste(bg.convert('RGB'),(tx,ty))
        dr.text((x+10,y+ch-43),f"origin {item.get('native_size')} • alpha={item.get('auto_alpha_pass')}",font=f,fill='#566477')
        dr.text((x+10,y+ch-22),item.get('quality_flag','')[:34],font=f,fill='#a76b13'if item.get('quality_flag')=='NATIVE_SOURCE_UNDER_900PX'else '#2c7165')
    else:dr.text((x+12,y+90),'ERROR',font=f,fill='red')
contact=ALPHA/'review-contact.jpg';sheet.save(contact,quality=88)
manifest={'created_at':'2026-11-17','batch':'den-pha-phase1','production_modified':False,'not_ready_to_publish':True,
 'items':results,'held':[{'sku':'JD-18300','reason':'JD-18300.emf actually reads JD-18200/200W; 10/11 in folder reads JDE-8300; all excluded'},{'sku':'BCT-WR1.0','reason':'local pricebook/4.png uses LED pattern different from live BCT-WR1.0, excluded'},{'sku':'BCT-WR4.0','reason':'local pricebook/8.png displays different LED pattern vs live, excluded'}]}
(ALPHA/'manifest.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n')
print('RESULT',len(results),'files',sum(1 for r in results if r.get('output_file')),'alpha passed',sum(r.get('auto_alpha_pass') is True for r in results),'CONTACT',contact,flush=True)

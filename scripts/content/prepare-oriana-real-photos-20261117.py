#!/usr/bin/env python3
"""Prepare traceable, REAL product images extracted from the company's ORIANA pricebook.
No DB or production changes; staging only. Fail closed on missing/wrong provenance.
"""
from pathlib import Path
import hashlib,json,csv,subprocess
ROOT=Path('/home/admin01/src/Web_PTS_PHP')
BASE=Path('/home/admin01/src/Data/missing_photo_20261007')
OUT=ROOT/'backend/resources/product-gallery/2026-11-17/batch-p0-real-photo'
ITEMS=[
 (510,'DB03-2','DB03-2/3.png'),
 (511,'DB03-3','DB03-3/4.png'),
 (512,'DB03-4','DB03-4/5.png'),
 (513,'SOLAR-LIGHT-BL120W','SOLAR-LIGHT-BL120W/3.png'),
 (519,'YS-80W','YS-80W/2.png'),
 (520,'YS-100W','YS-100W/2.png'),
 (521,'YS-120W','YS-120W/3.png'),
 (522,'HB01-60W','HB01-60W/2.png'),
 (523,'HB02-80W','HB02-80W/1.png'),
 (524,'HB02-100W','HB02-100W/1.png'),
 (525,'HB02-120W','HB02-120W/1.png'),
 (526,'DB-16300W','DB-16300W/1.png'),
]
source_rows={row['Ảnh']:row for row in csv.DictReader((BASE/'index.csv').open(encoding='utf-8-sig'))}
items=[]
for pid,code,rel in ITEMS:
 r=source_rows.get(rel)
 if r is None or r['Mã sản phẩm']!=code or r['File bảng giá']!='2. Bảng Giá Đèn Oriana 28.07.xlsx' or r['Sheet']!='BẢNG GIÁ ORIANA 01.01.2026':
  raise RuntimeError('Unexpected/missing original pricebook provenance for '+rel)
 p=BASE/rel
 if not p.is_file() or hashlib.sha256(p.read_bytes()).hexdigest()!=r['SHA256']:
  raise RuntimeError('Source changed '+str(p))
 info=subprocess.run(['file','-b',str(p)],capture_output=True,text=True,check=True).stdout.strip()
 if 'PNG image' not in info:raise RuntimeError('Unknown image mime '+str(p))
 d={
  'product_id':pid,'sku':code,'source_relative_path':rel,
  'source_file':str(p),
  'source_sha256':r['SHA256'],
  'source_workbook':r['File bảng giá'],'source_sheet':r['Sheet'],
  'source_row':r['Dòng'],'source_media':r['Media gốc'],'source_info':info,
  'output_file':f'pts-oriana-{code.lower()}-photo-20261117.jpg',
  'status':'STAGED_NOT_PUBLISHED','claim':'Product photograph extracted from internal supplier pricebook',
  'review_note': 'Series photos shared in source pricebook across wattages; product image does not claim unique physical variant' if code.startswith(('DB03-','YS-','HB02-')) else 'Image exact model code in workbook'
 }
 items.append(d)
OUT.mkdir(parents=True,exist_ok=True)
(OUT/'source-manifest.json').write_text(json.dumps(items,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
print('STAGED',len(items),'from original internal ORIANA workbook')
for i in items:print(i['product_id'],i['sku'],i['source_relative_path'],i['source_info'].split('(')[0][:55],i['source_sha256'][:12])

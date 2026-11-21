#!/usr/bin/env python3
"""Huawei official per-model specs: HTML div structure, no inferred values.
Only emit model-specific rows where column counts align, or one explicitly shared value.
"""
import csv,json,sys,re
from datetime import datetime,timezone
from pathlib import Path
sys.path.insert(0,"/home/admin01/.cache/pts-catalog-crawl-libs")
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/"docs/10-audits/catalog-expansion-20261117"
URLS=[
"https://solar.huawei.com/en/products/SUN2000-12-15-17-20-25K-MB0/specs/",
"https://solar.huawei.com/en/products/SUN5000-Series/SUN5000-17-25K-MB0-specs/",
"https://solar.huawei.com/en/products/SUN5000-Series/SUN5000-8-12K-MAP0-specs/",
"https://solar.huawei.com/en/products/sun2000-5-6-8-10-12k-map0/specs/",
"https://solar.huawei.com/en/products/SmartDongle-WLAN-FE/specs/",
]
def clean(n):
 return " ".join(n.get_text(" ",strip=True).split()) if n else ""
def main():
 specs=[];pages=[]
 for url in URLS:
  try:r=requests.get(url,timeout=(5,13),headers={"User-Agent":"PhucThinhSolar-CatalogueResearch/2026"});status="HTTP_"+str(r.status_code)
  except requests.RequestException as ex:pages.append({"url":url,"status":type(ex).__name__});continue
  if r.status_code!=200:pages.append({"url":url,"status":status});continue
  soup=BeautifulSoup(r.content,"html.parser")
  boxes=soup.select(".specs-content-box")
  if not boxes:pages.append({"url":url,"status":"NO_SPECS_BOX"});continue
  head=boxes[0].select_one(".specs-content")
  model_names=[clean(x) for x in head.select(".spaceParams")] if head else []
  if not model_names and head:model_names=[clean(x) for x in head.find_all(["div","span"],recursive=False)]
  model_names=[x for x in model_names if x and len(x)<100]
  if not model_names:model_names=["UNRESOLVED"]
  count=0;ambig=0
  for box in boxes[1:]:
   title=clean(box.select_one(".specs-title"))
   cont=box.select_one(".specs-content")
   if not title or not cont:continue
   entries=[clean(x) for x in cont.select(".specs-content-marginRight-drawing")]
   if not entries:
    # The specs page often stores a shared value in a nested div.
    entries=[clean(x) for x in cont.find_all(["div"],recursive=False) if clean(x)]
   entries=[x for x in entries if x]
   if len(entries)==len(model_names) and len(model_names)>0:
    assignments=list(zip(model_names,entries));status="MODEL_COLUMN_MATCHED"
   elif len(entries)==1 and len(model_names)>0:
    assignments=[(name,entries[0]) for name in model_names];status="SINGLE_SHARED_VALUE"
   else:
    assignments=[("UNASSIGNED"," | ".join(entries)[:700])];status="COLUMN_ALIGNMENT_REVIEW";ambig+=1
   for model,value in assignments:
    if not value:continue
    specs.append({"manufacturer":"Huawei","model":model,"spec_name_original":title,"spec_value_original":value,"source_url":url,"mapping":status,"review_state":"EXTRACT_NOT_REVIEWED"})
    count+=1
  pages.append({"url":url,"status":"HTTP_200","models":model_names,"spec_rows":count,"ambiguous_sections":ambig})
 with (OUT/"huawei-model-level-specs.csv").open("w",newline="",encoding="utf-8-sig") as f:
  fields=["manufacturer","model","spec_name_original","spec_value_original","source_url","mapping","review_state"]
  writer=csv.DictWriter(f,fieldnames=fields);writer.writeheader();writer.writerows(specs)
 (OUT/"huawei-model-level-summary.json").write_text(json.dumps({"date":datetime.now(timezone.utc).isoformat(),"pages":pages,"spec_rows":len(specs)},ensure_ascii=False,indent=2),encoding="utf-8")
 print(json.dumps({"pages":pages,"spec_rows":len(specs)},ensure_ascii=False,indent=2))
if __name__=="__main__":main()

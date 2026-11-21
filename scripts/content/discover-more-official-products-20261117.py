#!/usr/bin/env python3
"""Discover additional real manufacturer product URLs from official product indexes only.
Evidence-only. Names as listed (or url slug if label missing), NO SKU/stock claims.
"""
import csv,json,re,sys,unicodedata
from pathlib import Path
from urllib.parse import urljoin,urlparse,unquote
from concurrent.futures import ThreadPoolExecutor,as_completed
sys.path.insert(0,"/home/admin01/.cache/pts-catalog-crawl-libs")
import requests
from bs4 import BeautifulSoup
ROOT=Path(__file__).resolve().parents[2];DIR=ROOT/"docs/10-audits/catalog-expansion-20261117"
SITES={
"Deye":("https://vi.deyeinverter.com/product/",r"^/product/[^/]+/[^/]+\.html$"),
"Growatt":("https://en.growatt.com/products",r"^/products/[^/#?]+$"),
"SolaX":("https://vn.solaxpower.com/products.html",r"^/products/[^/]+\.html$"),
"SOFAR":("https://www.sofarsolar.com/product/index.html",r"^/product/.+\.html$"),
"TCL Solar":("https://www.tclsolar.com/products",r"^/products/[^/#?]+$"),
"Huawei":("https://solar.huawei.com/en/products/residential/",r"^/en/products/[^/?]+/(?:specs/)?$"),
"JINDIAN":("https://www.zsjdtyn.com/products.html",r"^/jd-[^/]+\.html$"),
}
def norm(v):
 v=unicodedata.normalize("NFKD",str(v)).lower()
 return "".join(c for c in v if c.isascii() and c.isalnum())
def get_items(brand,url,pattern):
 try:
  r=requests.get(url,timeout=(5,14),headers={"User-Agent":"PhucThinhSolar-CatalogueResearch/2026"})
  if r.status_code!=200:return brand,[],"HTTP_"+str(r.status_code)
  soup=BeautifulSoup(r.content,"html.parser");items={}
  for a in soup.find_all("a",href=True):
   raw=urljoin(r.url,a["href"]);pa=urlparse(raw)
   if pa.scheme!="https" or pa.hostname!=urlparse(url).hostname:continue
   if not re.match(pattern,unquote(pa.path),re.I):continue
   # Use exact label if meaningful; otherwise use slug. Never invent a SKU.
   label=" ".join(a.get_text(" ",strip=True).split())
   for img in a.find_all("img"):
    if len(label)<5:label=img.get("alt",label)
   if len(label)<5 or label.lower() in ("view details","read more","learn more","details","xem chi tiết"):
    label=unquote(pa.path).split("/")[-1].removesuffix(".html")
   label=label[:170];canonical=raw.split("#")[0]
   items[canonical]={"brand":brand,"raw_title":label,"official_detail_url":canonical,"source_index":url,"model_not_verified":"YES"}
  return brand,list(items.values()),"HTTP_200"
 except Exception as ex:return brand,[],type(ex).__name__
def main():
 known=list(csv.DictReader((DIR/"official-family-detail-links.csv").open(encoding="utf-8-sig")))
 existing_urls={r["official_detail_url"].rstrip("/").lower() for r in known if r.get("official_detail_url")}
 existing_names={(r["manufacturer"],norm(r["family_or_series"])) for r in known}
 extra=json.load(open(DIR/"manufacturer-extra-models-20261117.json"))["sources"]
 for brand in extra:
  existing_names|={(brand["brand"],norm(m["family"])) for m in brand["models"]}
 found=[];states={}
 with ThreadPoolExecutor(max_workers=4) as pool:
  futures=[pool.submit(get_items,brand,*args) for brand,args in SITES.items()]
  for future in as_completed(futures):
   brand,items,state=future.result();found+=items;states[brand]={"found_links":len(items),"state":state}
 new=[]
 for item in found:
  slug=norm(item["raw_title"]);url=item["official_detail_url"]
  if url.rstrip("/").lower() in existing_urls:continue
  if (item["brand"],slug) in existing_names:continue
  if any(bad in slug for bad in ("privacy","service","contactus","qualitypolicy","about","video","companynews","review")):continue
  new.append({**item,"status":"DISCOVERED_OFFICIAL_LINK_NO_SPECS_VERIFIED","category":"REVIEW","matches_existing":"NOT_CHECKED"})
 new=sorted({x["official_detail_url"]:x for x in new}.values(),key=lambda x:(x["brand"],x["raw_title"]))
 with (DIR/"more-official-product-links.csv").open("w",encoding="utf-8-sig",newline="") as f:
  fields=["brand","raw_title","official_detail_url","source_index","status","category","matches_existing","model_not_verified"];w=csv.DictWriter(f,fieldnames=fields);w.writeheader();w.writerows(new)
 report={"source_indexes":states,"new_unique_urls":len(new),"note":"Links are not confirmed new SKUs. Similar aliases may exist across previous research and 399 CMS products. No import."}
 (DIR/"more-official-product-links-summary.json").write_text(json.dumps(report,indent=2,ensure_ascii=False),encoding="utf-8")
 print(json.dumps(report,indent=2,ensure_ascii=False))
if __name__=="__main__":main()

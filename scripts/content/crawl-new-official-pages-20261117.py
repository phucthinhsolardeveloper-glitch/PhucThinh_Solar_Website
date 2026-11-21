#!/usr/bin/env python3
"""Stage read-only technical details for additional manufacturer links. No CMS writes."""
import csv,json,sys,importlib.util,logging
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor,as_completed
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[2];OUT=ROOT/"docs/10-audits/catalog-expansion-20261117"
spec=importlib.util.spec_from_file_location("deep_extract",ROOT/"scripts/content/crawl-official-specs-deep-20261117.py")
deep=importlib.util.module_from_spec(spec);sys.modules[spec.name]=deep;spec.loader.exec_module(deep)
logging.getLogger("pypdf").setLevel(logging.ERROR)
def main():
 items=list(csv.DictReader((OUT/"more-official-product-links.csv").open(encoding="utf-8-sig")))
 jobs=[{"manufacturer":r["brand"],"family_or_series":r["raw_title"],"official_detail_url":r["official_detail_url"],"source_url":r["source_index"]} for r in items]
 pages=[]
 with ThreadPoolExecutor(max_workers=5) as ex:
  futures=[ex.submit(deep.process_page,row) for row in jobs]
  for f in as_completed(futures):
   try:pages.append(f.result())
   except Exception as e:pages.append({"key":("ERROR","UNKNOWN"),"page_state":type(e).__name__})
 pages.sort(key=lambda x:(x["key"][0],x["key"][1]))
 rich=[];spec_rows=[];tables=[];pdfs=[];images=[]
 for p in pages:
  brand,family=p["key"];source=p.get("url","")
  rich.append({"brand":brand,"research_name":family,"official_detail_url":source,"fetch_status":p["page_state"],"manufacturer_page_title":p.get("title",""),"h1":p.get("h1",""),"description":p.get("meta_description",""),"spec_rows_found":len(p.get("specs",[])),"table_count":len(p.get("tables",[])),"pdf_candidate_count":len(p.get("pdfs",[])),"image_candidate_count":len(p.get("images",[])),"already_in_old_260":"NOT_CHECKED","qa_status":"RESEARCH_ONLY","cms_publish":"NO"})
  for s in p.get("specs",[]):
   spec_rows.append({"brand":brand,"research_name":family,"variant_heading":s["variant_heading"],"label":s["spec_label"],"value":s["spec_value"],"source_type":s["cell_origin"],"source_url":source,"qa_status":"NOT_VERIFIED"})
  for d in p.get("pdfs",[]):pdfs.append({"brand":brand,"research_name":family,"source_page":source,**d,"qa_status":"NOT_VERIFIED"})
  for d in p.get("images",[]):images.append({"brand":brand,"research_name":family,"source_page":source,**d,"image_qa":"NOT_VERIFIED","reuse_permission":"NOT_VERIFIED"})
  if p.get("tables"):tables.append({"brand":brand,"research_name":family,"source":source,"tables":p["tables"]})
 def save(fn,arr,keys):
  with (OUT/fn).open("w",newline="",encoding="utf-8-sig") as fh:
   writer=csv.DictWriter(fh,fieldnames=keys);writer.writeheader();writer.writerows(arr)
 save("new-deep-official-pages.csv",rich,list(rich[0]) if rich else [])
 save("new-deep-extracted-specs.csv",spec_rows,list(spec_rows[0]) if spec_rows else [])
 save("new-deep-pdf-urls.csv",pdfs,["brand","research_name","source_page","url","label","qa_status"])
 save("new-deep-image-urls.csv",images,["brand","research_name","source_page","url","alt","image_qa","reuse_permission"])
 (OUT/"new-deep-html-tables.json").write_text(json.dumps(tables,ensure_ascii=False,indent=2),encoding="utf-8")
 report={"created_at":datetime.now(timezone.utc).isoformat(),"links_attempted":len(jobs),"pages_ok":sum(r["fetch_status"]=="HTTP_200" for r in rich),"html_spec_rows":len(spec_rows),"tables":len(tables),"pdf_candidate_links":len(pdfs),"image_candidate_links":len(images),"status":"UNVERIFIED_NO_CMS_IMPORT","note":"New URLs may refer to already listed families; do not sum URLs as unique SKUs"}
 (OUT/"new-deep-summary.json").write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding="utf-8")
 print(json.dumps(report,ensure_ascii=False,indent=2))
if __name__=="__main__":main()

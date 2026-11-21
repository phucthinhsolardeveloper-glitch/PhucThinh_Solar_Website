#!/usr/bin/env python3
"""Normalize evidence to model-level staging, preserve conflicts, no CMS / production edits."""
import csv,hashlib,json,re,unicodedata
from collections import defaultdict,Counter
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/"docs/10-audits/catalog-expansion-20261117"
def read(filename):
 with (OUT/filename).open(encoding="utf-8-sig") as stream:return list(csv.DictReader(stream))
def norm(x):
 return "".join(c for c in unicodedata.normalize("NFKD",str(x)).upper() if c.isascii() and c.isalnum())
def clean(x):
 return re.sub(r"\s+"," ",str(x or "")).strip()
def key(brand,model):return hashlib.sha256((norm(brand)+"|"+norm(model)).encode()).hexdigest()[:20]
def accepted_model(model):
 x=clean(model)
 return bool(4<=len(x)<=100 and len(norm(x))>=5 and re.search(r"\d",x)
   and not any(s in x.lower() for s in ["unresolved","unassigned","not found","http","need check","view details"]))
def main():
 raw_sources=[]
 for name in ["deep-extracted-specs.csv","new-deep-extracted-specs.csv"]:
  for r in read(name):
   if r["source_type"]=="PDF_LAYOUT_TEXT_REQUIRES_PARSING":continue
   model=clean(r.get("variant_heading")) or ""
   raw_sources.append({"brand":r["brand"],"family":r.get("family_or_model") or r.get("research_name"),"model":model,"spec":r.get("specification_label_original") or r.get("label"),"value":r.get("value_original") or r.get("value"),"source":r["source_url"],"kind":r["source_type"]})
 for r in read("huawei-model-level-specs.csv"):
  raw_sources.append({"brand":"Huawei","family":r["model"],"model":r["model"],"spec":r["spec_name_original"],"value":r["spec_value_original"],"source":r["source_url"],"kind":r["mapping"]})
 distinct={}
 values=defaultdict(set)
 for s in raw_sources:
  if not accepted_model(s["model"]):continue
  model=clean(s["model"]);brand=s["brand"];label=clean(s["spec"]);value=clean(s["value"])
  if not label or not value:continue
  k=(brand,model,label,value,s["source"])
  distinct[k]=s
  values[(brand,model,norm(label))].add(value)
 public=json.loads((OUT/"public-products-snapshot.json").read_text())["products"]
 public_names=[norm(p.get("name","")) for p in public]
 models={}
 specs=[]
 for (brand,model,label,value,source),v in distinct.items():
  mk=key(brand,model);modelkey=(brand,model)
  if mk not in models:
   overlap=sum(norm(model) in name for name in public_names if len(norm(model))>=8)
   models[mk]={"staging_key":mk,"brand":brand,"model_original":model,"family_example":v["family"],"model_granularity":"EXPLICIT_MODEL_HEADING","public_catalogue_name_overlap_count":overlap,"existing_399_check":"NOT_COMPLETED","manufacturer_detail_url":source,"is_visible":False,"quote_only":True,"commercial_status_proposed":"catalogue_unconfirmed","verification_state":"EXTRACTED_NOT_APPROVED","technical_attribute_rows":0,"conflicting_attribute_labels":0,"cms_imported":False}
  status="CONFLICTING_VALUES" if len(values[(brand,model,norm(label))])>1 else "EXTRACTED_UNVERIFIED"
  specs.append({"staging_key":mk,"brand":brand,"model_original":model,"label_original":label,"value_original":value,"source_url":source,"extraction_type":v["kind"],"qa_status":status})
 for item in specs:
  models[item["staging_key"]]["technical_attribute_rows"]+=1
 for (brand,model,label),vals in values.items():
  if len(vals)>1:models[key(brand,model)]["conflicting_attribute_labels"]+=1
 merged=list(models.values())
 merged.sort(key=lambda r:(r["brand"],r["model_original"]))
 specs.sort(key=lambda r:(r["brand"],r["model_original"],r["label_original"]))
 def csvout(fn,arr,fields):
  with (OUT/fn).open("w",newline="",encoding="utf-8-sig") as f:
   w=csv.DictWriter(f,fieldnames=fields);w.writeheader();w.writerows(arr)
 csvout("normalized-models-staging.csv",merged,list(merged[0]) if merged else [])
 csvout("normalized-model-specifications.csv",specs,list(specs[0]) if specs else [])
 summary={
  "model_keys":len(merged),"model_spec_evidence_rows":len(specs),
  "by_brand":dict(Counter(r["brand"] for r in merged)),
  "models_with_conflicts":sum(r["conflicting_attribute_labels"]>0 for r in merged),
  "models_with_public_name_overlaps":sum(r["public_catalogue_name_overlap_count"]>0 for r in merged),
  "cms_imported":0,"source_status":"NOT_APPROVED",
  "notes":["Only explicit variant headings from source tables; models lacking tabular headings remain in family-level evidence CSVs", "Different values for same label are retained and flagged, not silently reconciled", "Overlap is against 353 public product names only; must also compare 46 hidden products before import", "Brand and model heading may be mistranslated by manufacturer page; product identity must be human-reviewed"]
 }
 (OUT/"normalized-models-summary.json").write_text(json.dumps(summary,ensure_ascii=False,indent=2),encoding="utf-8")
 print(json.dumps(summary,ensure_ascii=False,indent=2))
if __name__=="__main__":main()

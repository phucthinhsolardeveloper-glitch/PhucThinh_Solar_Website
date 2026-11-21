#!/usr/bin/env python3
"""Read-only, traceable manufacturer catalogue research. NEVER writes CMS/production.
Python 3.14, requests+beautifulsoup4+pypdf in a private cache directory.
All extracted values are candidates, not approved or licensed for publication.
"""
from __future__ import annotations
import csv, hashlib, io, json, os, re, sys, time, unicodedata
from collections import Counter
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urljoin, urlparse, unquote
sys.path.insert(0, "/home/admin01/.cache/pts-catalog-crawl-libs")
import requests
from bs4 import BeautifulSoup
from pypdf import PdfReader

ROOT=Path(__file__).resolve().parents[2]
DIR=ROOT/"docs/10-audits/catalog-expansion-20261117"
UA={"User-Agent":"PhucThinhSolar-CatalogueResearch/2026 (evidence-only; non-commercial staging)","Accept":"text/html,application/pdf;q=0.9,*/*;q=0.2"}
MAX_HTML=4_500_000
MAX_PDF=7_000_000
TIMEOUT=(5, 13)

def norm(v):
    v=unicodedata.normalize("NFKD",str(v or "")).lower()
    return "".join(c for c in v if c.isascii() and c.isalnum())
def clean(v):
    return re.sub(r"\s+"," ",str(v or "")).strip()
def ensure_url(url,domain):
    p=urlparse(url)
    if p.scheme!="https" or not p.hostname:return False
    return p.hostname.lower().split(".")[-2:]==domain.lower().split(".")[-2:]
def fetch(url,kind="html",domain=None):
    try:
        with requests.get(url,headers=UA,timeout=TIMEOUT,allow_redirects=True,stream=True) as r:
            if r.status_code!=200:return None,"HTTP_"+str(r.status_code),None
            if not r.url.startswith("https://"):return None,"NON_HTTPS",None
            if domain and not ensure_url(r.url,domain):return None,"CROSS_DOMAIN",None
            ct=(r.headers.get("content-type") or "").lower()
            if kind=="html" and "html" not in ct:return None,"NOT_HTML",None
            size_limit=MAX_HTML if kind=="html" else MAX_PDF
            chunks=[];length=0
            for piece in r.iter_content(65536):
                length+=len(piece)
                if length>size_limit:return None,"OVERSIZE",None
                chunks.append(piece)
            result=b"".join(chunks)
            if kind=="pdf" and not (result[:5]==b"%PDF-" or "pdf" in ct):return None,"NOT_PDF",None
            return result,"HTTP_200",r.url
    except requests.RequestException as e:
        return None,type(e).__name__,None

def item_urls():
    rows=list(csv.DictReader((DIR/"official-family-detail-links.csv").open(encoding="utf-8-sig")))
    ext=json.loads((DIR/"manufacturer-extra-models-20261117.json").read_text())["sources"]
    for source in ext:
        for model in source["models"]:
            rows.append({
                "manufacturer":source["brand"],"family_or_series":model["family"],
                "proposed_category":model["category"],"source_url":source["source_url"],
                "source_evidence":"ADDITIONAL_OFFICIAL_SOURCE_RESEARCH",
                "official_detail_url":"","site_name_match_count":"","cms_publish":"NO"
            })
    assert len(rows)==260, len(rows)
    return rows

def text_one(node,max_chars=300):
    return clean(node.get_text(" ",strip=True))[:max_chars] if node else ""

def parse_html(html_bytes,url,brand,family):
    soup=BeautifulSoup(html_bytes,"html.parser")
    title=text_one(soup.title,220)
    h1=text_one(soup.find("h1"),230)
    title_blob=norm(title+" "+h1+" "+unquote(urlparse(url).path))
    itemkey=norm(family)
    subject_ok= bool(len(itemkey)>=5 and itemkey in title_blob)
    meta_description=""
    meta=soup.find("meta",attrs={"name":re.compile("^description$",re.I)})
    if meta:meta_description=clean(meta.get("content"))[:1000]
    images=[]
    imgs=soup.select("img")
    for img in imgs[:90]:
        alt=clean(img.get("alt"))[:200]
        src=img.get("data-src") or img.get("src") or img.get("data-original") or ""
        if src and (norm(family) in norm(alt+" "+src) or (norm(family)[:6] in norm(alt+" "+src) and len(norm(family))>=6)):
            src=urljoin(url,src)
            if ensure_url(src,urlparse(url).hostname):images.append({"url":src,"alt":alt})
    og=soup.find("meta",attrs={"property":"og:image"})
    og_src=urljoin(url,og.get("content")) if og and og.get("content") else ""
    if og_src and ensure_url(og_src,urlparse(url).hostname):
        images.append({"url":og_src,"alt":"OG image candidate; often generic logo"})
    images=list({r["url"]:r for r in images}.values())[:10]
    pdf_links=[]
    for a in soup.select('a[href]'):
        href=clean(a.get("href")); label=text_one(a,120)
        lower=(href+" "+label).lower()
        # PDF/direct download only, not generic "resources?type=datasheet" search pages.
        if re.search(r"\.pdf([?#]|$)",href,re.I) or "download_type=datasheet" in href.lower():
            absolute=urljoin(url,href)
            if absolute.startswith("https://") and (ensure_url(absolute,urlparse(url).hostname) or urlparse(absolute).hostname in ["api.ae-solar.com"]):
                pdf_links.append({"url":absolute,"label":label})
    pdf_links=list({v["url"]:v for v in pdf_links}.values())[:8]
    tables=[];specs=[]
    for index,table in enumerate(soup.find_all("table")[:35]):
        rows=[]
        for tr in table.find_all("tr",recursive=True):
            cols=[text_one(c,300) for c in tr.find_all(["td","th"],recursive=False)]
            if len(cols)<2 or not any(cols):continue
            if len(cols)>13:cols=cols[:13]
            rows.append(cols)
        if not rows:continue
        # Store tables exactly (unpivot only with explicit manufacturer model headings).
        tables.append({"table_index":index,"rows":rows[:200]})
        header=rows[0]
        if len(header)>2 and any(norm(family)[:5] in norm(x) or re.search(r"(sun2000|sg\d|hsph|kw|model)",x,re.I) for x in header[1:]):
            headers=header[1:]
            for vals in rows[1:]:
                label=clean(vals[0])
                if not label or not any(re.search(r"[a-zA-Z]",label) for _ in [0]):continue
                for j,value in enumerate(vals[1:]):
                    if j>=len(headers) or not value or clean(value)==label:continue
                    specs.append({"spec_label":label,"spec_value":value,"variant_heading":headers[j],"table":index,"cell_origin":"HTML_TABLE_VARIANT"})
        else:
            for vals in rows:
                if len(vals)==2:
                    label,value=map(clean,vals)
                    if len(label)>2 and len(label)<125 and len(value)<300 and value and label!=value:
                        specs.append({"spec_label":label,"spec_value":value,"variant_heading":"","table":index,"cell_origin":"HTML_TABLE_PAIR"})
                elif len(vals)>2 and len(vals)<=6 and len(vals[0])>3:
                    specs.append({"spec_label":vals[0],"spec_value":" | ".join(vals[1:]),"variant_heading":"","table":index,"cell_origin":"HTML_TABLE_UNMAPPED"})
    # Specification lists in dt/dd pairs.
    for dl in soup.find_all("dl")[:30]:
        for dt in dl.find_all("dt"):
            dd=dt.find_next_sibling("dd")
            if dd:
                label=text_one(dt,120);value=text_one(dd,300)
                if label and value:specs.append({"spec_label":label,"spec_value":value,"variant_heading":"","table":-1,"cell_origin":"HTML_DL_PAIR"})
    # Structured facts from English/Vietnamese "Label : Value" sections (conservative).
    for li in soup.select("main li, article li")[:150]:
        raw=text_one(li,450)
        match=re.match(r"^([^\n:：]{3,75})\s*[:：]\s*(.{1,220})$",raw)
        if match:
            label,value=map(clean,match.groups())
            specs.append({"spec_label":label,"spec_value":value,"variant_heading":"","table":-2,"cell_origin":"HTML_LIST_PAIR"})
    clean_specs=[]; seen=set()
    for s in specs:
        pair=(s["spec_label"].lower(),s["spec_value"],s["variant_heading"],s["cell_origin"])
        if pair not in seen and len(s["spec_value"])<=320:
            seen.add(pair);clean_specs.append(s)
    return {
        "title":title,"h1":h1,"meta_description":meta_description,"subject_match":"TEXT_MATCH_WEAK" if subject_ok else "MANUAL_MODEL_CHECK_REQUIRED",
        "images":images,"pdfs":pdf_links,"tables":tables,"specs":clean_specs[:400]
    }

def process_page(row):
    url=row.get("official_detail_url") or ""
    if not url:return {"key":(row["manufacturer"],row["family_or_series"]),"page_state":"NO_DETAIL_URL"}
    body,state,resolved=fetch(url,"html",urlparse(url).hostname)
    if body is None:return {"key":(row["manufacturer"],row["family_or_series"]),"page_state":state,"url":url}
    data=parse_html(body,resolved,row["manufacturer"],row["family_or_series"])
    data.update({"key":(row["manufacturer"],row["family_or_series"]),"page_state":"HTTP_200","url":resolved,"sha256_html":hashlib.sha256(body).hexdigest(),"html_bytes":len(body)})
    return data

def download_pdf(page_url,pdf):
    url=pdf["pdf_url"]
    body,state,resolved=fetch(url,"pdf",None)
    output={"pdf_url":url,"linked_from":page_url,"http_state":state,"sha256":"","pages":0,"extracted_text_chars":0,"model_mentions":[],"spec_lines":[]}
    if body is None:return output
    output["sha256"]=hashlib.sha256(body).hexdigest()
    output["size_bytes"]=len(body)
    try:
        reader=PdfReader(io.BytesIO(body),strict=False)
        output["pages"]=len(reader.pages)
        # Intentional bounded evidence extraction; no full copyrighted datasheet redistribution.
        texts=[]
        for i,page in enumerate(reader.pages[:8]):
            text=page.extract_text(extraction_mode="layout") or page.extract_text() or ""
            texts.extend([clean(line) for line in text.splitlines() if clean(line)])
        output["extracted_text_chars"]=sum(len(t) for t in texts)
        picked=[]
        pat=re.compile(r"(?i)\b(efficien|voltage|current|mppt|rated|nominal|input|output|frequency|power|capacity|dimension|weight|ip\d\d|operat|temperature|warrant|cell|voltage|hiệu suất|điện áp|dòng điện|công suất|dung lượng|kích thước|trọng lượng|độ sâu)\b")
        for line in texts:
            if len(line)>14 and len(line)<225 and pat.search(line):
                picked.append(line)
        output["spec_lines"]=list(dict.fromkeys(picked))[:150]
        output["sample_first_line"]=next((line for line in texts[:30] if len(line)>=8),"")[:160]
    except Exception as ex:
        output["parse_error"]=type(ex).__name__
    return output

def main():
    DIR.mkdir(parents=True,exist_ok=True)
    rows=item_urls()
    detail_rows=[r for r in rows if r.get("official_detail_url")]
    results=[]
    # Bounded concurrent GET (4) without bypassing 403/robots/login gates.
    with ThreadPoolExecutor(max_workers=4) as pool:
        futures=[pool.submit(process_page,row) for row in detail_rows]
        for future in as_completed(futures):results.append(future.result())
    results.sort(key=lambda o:(o["key"][0],o["key"][1]))
    page_map={tuple(r["key"]):r for r in results}
    candidate_index=[]
    spec_rows=[]; docs=[];imgs=[]
    for row in rows:
        key=(row["manufacturer"],row["family_or_series"])
        page=page_map.get(key,{})
        status=page.get("page_state","NOT_INDEXED")
        family=row["family_or_series"]
        candidate_index.append({
            "brand":key[0],"family_or_model":family,"category_research":row.get("proposed_category",""),
            "detail_url":page.get("url",row.get("official_detail_url","")),
            "index_url":row.get("source_url",""),
            "fetch_status":status,"subject_match":page.get("subject_match","NOT_CHECKED"),
            "page_title":page.get("title",""),
            "html_spec_cells":len(page.get("specs",[])),
            "tables_found":len(page.get("tables",[])),
            "pdf_candidates":len(page.get("pdfs",[])),
            "image_candidates":len(page.get("images",[])),
            "existing_match_weak":row.get("site_name_match_count",""),
            "qa_status":"RESEARCH_NOT_APPROVED","cms_action":"NONE","public_status":"HIDDEN"
        })
        for s in page.get("specs",[]):
            spec_rows.append({
                "brand":key[0],"family_or_model":family,"variant_heading":s["variant_heading"],
                "specification_label_original":s["spec_label"],
                "value_original":s["spec_value"],
                "source_type":s["cell_origin"],"source_url":page.get("url",""),
                "table_index":s["table"],"qa_status":"UNVERIFIED_EXTRACT"
            })
        for img in page.get("images",[]):
            imgs.append({"brand":key[0],"family_or_model":family,"source_url":page.get("url",""),"image_url":img["url"],"alt_text":img["alt"],"right_to_reuse":"UNVERIFIED","correct_model":"UNVERIFIED"})
        for pdf in page.get("pdfs",[]):
            docs.append({"brand":key[0],"family_or_model":family,"source_page":page.get("url",""),"pdf_url":pdf["url"],"link_label":pdf["label"]})
    # Avoid downloading shared footer PDFs or unrelated docs; 1-2 per candidate, max 75 unique.
    unique={}; priority=[]
    for doc in docs:
        u=doc["pdf_url"]
        if u in unique:continue
        unique[u]=doc
        nm=norm(doc["family_or_model"])
        p=norm(unquote(urlparse(u).path))
        score= int(nm in p and len(nm)>5)*10+int("datasheet" in u.lower())*3+int("specification" in u.lower())*2
        priority.append((score,doc))
    priority.sort(key=lambda z:z[0],reverse=True)
    selected=[doc for _,doc in priority[:70]]
    pdf_results=[]
    with ThreadPoolExecutor(max_workers=4) as pool:
        futures=[pool.submit(download_pdf,doc["source_page"],doc) for doc in selected]
        for future in as_completed(futures):pdf_results.append(future.result())
    pdf_results.sort(key=lambda d:d["pdf_url"])
    pdf_map={p["pdf_url"]:p for p in pdf_results}
    for doc in docs:
        p=pdf_map.get(doc["pdf_url"])
        doc.update({"http_state":p["http_state"] if p else "NOT_FETCHED","sha256":p["sha256"] if p else "",
            "pages":p["pages"] if p else "","extracted_text_chars":p["extracted_text_chars"] if p else "",
            "qa_status":"PDF_EXTRACT_ONLY_NOT_VERIFIED"})
    for p in pdf_results:
        if not p.get("spec_lines"):continue
        # Only structured context lines (not authoritative label-value pairs); keep evidence separately.
        doc=unique[p["pdf_url"]]
        for i,line in enumerate(p["spec_lines"]):
            spec_rows.append({"brand":doc["brand"],"family_or_model":doc["family_or_model"],
                "variant_heading":"","specification_label_original":"PDF text excerpt "+str(i+1),
                "value_original":line,"source_type":"PDF_LAYOUT_TEXT_REQUIRES_PARSING",
                "source_url":p["pdf_url"],"table_index":"","qa_status":"UNVERIFIED_EXTRACT"})
    def to_csv(file,items,columns):
        with (DIR/file).open("w",newline="",encoding="utf-8-sig") as stream:
            w=csv.DictWriter(stream,fieldnames=columns,extrasaction="ignore");w.writeheader();w.writerows(items)
    to_csv("deep-candidates-260.csv",candidate_index,list(candidate_index[0]))
    to_csv("deep-extracted-specs.csv",spec_rows,list(spec_rows[0]) if spec_rows else ["brand","family_or_model"])
    to_csv("deep-document-evidence.csv",docs,["brand","family_or_model","source_page","pdf_url","link_label","http_state","sha256","pages","extracted_text_chars","qa_status"])
    to_csv("deep-image-evidence.csv",imgs,["brand","family_or_model","source_url","image_url","alt_text","right_to_reuse","correct_model"])
    (DIR/"deep-raw-html-tables.json").write_text(json.dumps([{"brand":r["key"][0],"family":r["key"][1],"url":r.get("url",""),"tables":r.get("tables",[])} for r in results if r.get("tables")],ensure_ascii=False,indent=2),encoding="utf-8")
    (DIR/"deep-pdf-evidence.json").write_text(json.dumps(pdf_results,ensure_ascii=False,indent=2),encoding="utf-8")
    summary={
        "collected_at":datetime.now(timezone.utc).isoformat(),
        "catalog_candidates":len(candidate_index),"unique_brands":len(set(r["brand"] for r in candidate_index)),
        "detail_urls":len(detail_rows),"html_pages_ok":sum(r["page_state"]=="HTTP_200" for r in results),
        "html_pages_failed":[{"brand":r["key"][0],"family":r["key"][1],"status":r["page_state"]} for r in results if r["page_state"]!="HTTP_200"],
        "html_tables":sum(r["tables_found"] for r in candidate_index),
        "html_structured_spec_rows":sum(len(r.get("specs",[])) for r in results),
        "pdf_urls_discovered":len(unique),"pdfs_download_attempted":len(selected),
        "pdfs_parsed":sum(p["http_state"]=="HTTP_200" and p["pages"]>0 for p in pdf_results),
        "pdf_text_evidence_lines":sum(len(p["spec_lines"]) for p in pdf_results),
        "total_evidence_spec_rows":len(spec_rows),
        "image_url_candidates":len(imgs),"image_model_and_rights_QA_pass":0,
        "sku_verified":0,"cms_products_imported":0,
        "notes":"All rows are extract candidates, model/variant/pdf/rights must be verified; no production mutation."
    }
    (DIR/"deep-crawl-summary.json").write_text(json.dumps(summary,ensure_ascii=False,indent=2)+"\n",encoding="utf-8")
    print(json.dumps(summary,ensure_ascii=False,indent=2))
if __name__=="__main__":main()

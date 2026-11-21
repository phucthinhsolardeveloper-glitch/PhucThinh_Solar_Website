#!/usr/bin/env python3
"""Enrich staged brand families with URLs from official HTML anchors.
Read-only official website requests, no production database mutation, no media rehosting.
"""
import csv, json, re, unicodedata
from collections import Counter
from concurrent.futures import ThreadPoolExecutor, as_completed
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin, urlparse, unquote
import requests
ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'docs/10-audits/catalog-expansion-20261117'
USER_AGENT={'User-Agent':'PhucThinhSolar-InternalCatalogueResearch/1.0','Accept':'text/html'}
def norm(v):
    v=unicodedata.normalize('NFKD',str(v)).lower()
    return ''.join(c for c in v if c.isascii() and c.isalnum())
class Anchors(HTMLParser):
    def __init__(self):
        super().__init__(); self.current=None;self.links=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='a':
            self.current={'href':a.get('href',''),'text':[]}
        if tag=='img' and self.current is not None:self.current['text'].append(a.get('alt',''))
    def handle_data(self,data):
        if self.current is not None:self.current['text'].append(data)
    def handle_endtag(self,tag):
        if tag=='a' and self.current is not None:
            self.links.append((' '.join(self.current['text']).strip(),self.current['href']))
            self.current=None
class Details(HTMLParser):
    def __init__(self):
        super().__init__();self.title=[];self.in_title=False;self.og_image='';self.meta_description='';self.pdfs=[];self.images=[];self.h1=[];self.in_h1=False
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='title':self.in_title=True
        if tag=='h1':self.in_h1=True
        if tag=='meta':
            key=a.get('property',a.get('name','')).lower()
            if key in ['og:image','twitter:image'] and not self.og_image:self.og_image=a.get('content','')
            if key=='description':self.meta_description=a.get('content','')
        if tag=='a':
            href=a.get('href','')
            if re.search(r'\\.pdf(?:[?#]|$)',href,re.I) or 'datasheet' in href.lower():self.pdfs.append(href)
        if tag=='img':
            src=a.get('src','') or a.get('data-src','')
            if src:self.images.append({'src':src,'alt':a.get('alt','')})
    def handle_endtag(self,tag):
        if tag=='title':self.in_title=False
        if tag=='h1':self.in_h1=False
    def handle_data(self,data):
        if self.in_title:self.title.append(data)
        if self.in_h1:self.h1.append(data)
def get(url):
    try:
        r=requests.get(url,headers=USER_AGENT,timeout=(5,12),allow_redirects=True)
        if r.status_code!=200 or not r.url.startswith('https://') or 'html' not in r.headers.get('Content-Type','').lower():return None, 'HTTP_'+str(r.status_code)
        if len(r.content)>5_000_000:return None,'TOO_LARGE'
        return r,'HTTP_200'
    except requests.RequestException as err:return None,err.__class__.__name__
def get_links(source):
    response,status=get(source['source_url'])
    if response is None:return source['brand'],[],status
    p=Anchors();p.feed(response.text)
    domain=urlparse(source['source_url']).hostname or ''
    links=[]
    for label,raw in p.links:
        link=urljoin(response.url,raw)
        target=urlparse(link)
        if target.scheme!='https' or not target.hostname:continue
        # Official index may use another subdomain, do not include third parties.
        if (target.hostname.split('.')[-2:]!=domain.split('.')[-2:]):continue
        links.append((label,link))
    return source['brand'],links,status
def link_for(family,links):
    n=norm(family)
    if len(n)<5:return ''
    found=[]
    for label,link in links:
        lp=norm(unquote(urlparse(link).path))
        if n==norm(label):
            found.append((0,len(urlparse(link).path),link))
        elif n in lp and len(n)>=6:
            found.append((1,len(urlparse(link).path),link))
    return sorted(found)[0][2] if found else ''
def detail(row):
    url=row['official_detail_url'];res,status=get(url)
    result={'manufacturer':row['manufacturer'],'family_or_series':row['family_or_series'],'detail_url':url,'page_state':status,'page_title':'','page_h1':'','meta_description':'','suggested_image_url':'','pdf_links':[],'images_detected':0,'datasheet_qa':'NOT_STARTED','image_rights_qa':'NOT_STARTED','model_level_confirmed':'NO','detail_subject_match':'NOT_CHECKED'}
    if res is None:return result
    p=Details();p.feed(res.text)
    result.update({'page_title':' '.join(p.title).strip()[:260],'page_h1':' '.join(p.h1).strip()[:260],'meta_description':p.meta_description[:750],'suggested_image_url':urljoin(res.url,p.og_image) if p.og_image else '','pdf_links':list(dict.fromkeys(urljoin(res.url,h) for h in p.pdfs))[:16],'images_detected':len(p.images)})
    # A linked page can mention a product without being a detail page for that product.
    # This weak check must not be interpreted as verified SKU identity.
    subject=norm(result['page_title']+' '+result['page_h1']+' '+urlparse(url).path)
    needle=norm(row['family_or_series'])
    result['detail_subject_match']='SUBJECT_TEXT_MATCH' if len(needle)>=5 and needle in subject else 'NEEDS_HUMAN_IDENTITY_CHECK'
    # Captured HTML is evidence only; do not use pictures or PDF as licensed source.
    return result
def main():
    manifest=json.loads((OUT/'manufacturer-seeds.json').read_text(encoding='utf-8'))
    items=list(csv.DictReader((OUT/'official-family-candidates.csv').open(encoding='utf-8-sig')))
    links_by={}
    fetch_status={}
    with ThreadPoolExecutor(max_workers=4) as exe:
        futures=[exe.submit(get_links,s) for s in manifest['sources']]
        for fut in as_completed(futures):
            brand,links,status=fut.result(); links_by[brand]=links;fetch_status[brand]=status
    enriched=[]
    for item in items:
        url=link_for(item['family_or_series'],links_by.get(item['manufacturer'],[]))
        enriched.append({**item,'official_detail_url':url,'detail_link_discovery':'ANCHOR_MATCH' if url else 'UNRESOLVED','review_required':'YES'})
    with (OUT/'official-family-detail-links.csv').open('w',newline='',encoding='utf-8-sig') as file:
        writer=csv.DictWriter(file,fieldnames=list(enriched[0]));writer.writeheader();writer.writerows(enriched)
    # Page sample: up to 2 per brand, emphasis on primary high-gap categories.
    sample=[];by_brand=Counter()
    for item in sorted((r for r in enriched if r['official_detail_url'] and r['source_evidence']=='INDEX_TEXT_MATCH'),key=lambda r:(r['manufacturer'], r['family_or_series'])):
        if by_brand[item['manufacturer']]>=2:continue
        by_brand[item['manufacturer']]+=1;sample.append(item)
    fetched_details=[]
    with ThreadPoolExecutor(max_workers=4) as exe:
        futures=[exe.submit(detail,r) for r in sample]
        for fut in as_completed(futures):fetched_details.append(fut.result())
    fetched_details.sort(key=lambda r:(r['manufacturer'],r['family_or_series']))
    (OUT/'official-detail-page-samples.json').write_text(json.dumps({'samples':fetched_details,'note':'Evidence snapshots, not approval of images/rights/specifications'},ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print(json.dumps({'candidates':len(enriched),'with_detail_links':sum(bool(r['official_detail_url']) for r in enriched),'sample_detail_pages':len(fetched_details),'sample_ok':sum(r['page_state']=='HTTP_200' for r in fetched_details),'index_fetch':fetch_status,'top_samples':[{'brand':r['manufacturer'],'family':r['family_or_series'],'url':r['detail_url'],'state':r['page_state'],'pdfs':len(r['pdf_links']),'has_image':bool(r['suggested_image_url'])} for r in fetched_details[:25]]},ensure_ascii=False,indent=2))
if __name__=='__main__':main()

import re, json, time, sys, urllib.request, html
from concurrent.futures import ThreadPoolExecutor
S=sys.argv[1]
urls=[html.unescape(u) for u in re.findall(r'<loc>([^<]+)</loc>', open(S+'/sitemap.xml').read())]
UA='Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
class NoRedir(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**k): return None
op=urllib.request.build_opener(NoRedir)
def one(u):
    t=time.time()
    try:
        r=op.open(urllib.request.Request(u,headers={'User-Agent':UA}),timeout=40); code=r.status; body=r.read().decode('utf-8','replace'); loc=''
    except urllib.error.HTTPError as e:
        code=e.code; body=''; loc=e.headers.get('Location','')
    except Exception as e:
        return {'url':u,'code':0,'err':str(e)}
    dt=round(time.time()-t,2)
    def g(p):
        m=re.search(p,body,re.S|re.I); return html.unescape(m.group(1)).strip() if m else None
    types=[]
    for m in re.finditer(r'<script[^>]*application/ld\+json[^>]*>(.*?)</script>',body,re.S):
        try:
            d=json.loads(m.group(1)); t2=d.get('@type'); types+= t2 if isinstance(t2,list) else [t2]
            if t2=='Product': prod=d
        except Exception: types.append('PARSE_ERR')
    prod=None
    for m in re.finditer(r'<script[^>]*application/ld\+json[^>]*>(.*?)</script>',body,re.S):
        try:
            d=json.loads(m.group(1))
            if d.get('@type')=='Product': prod=d
        except Exception: pass
    imgs=re.findall(r'<img\b[^>]*>',body)
    noalt=sum(1 for i in imgs if not re.search(r'\balt="[^"]+',i))
    return {'url':u,'code':code,'loc':loc,'t':dt,'kb':len(body)//1024,
        'title':g(r'<title[^>]*>(.*?)</title>'),'desc':g(r'<meta name="description" content="([^"]*)"'),
        'canon':g(r'<link rel="canonical" href="([^"]*)"'),'robots':g(r'<meta name="robots" content="([^"]*)"'),
        'ogtype':g(r'property="og:type" content="([^"]*)"') or g(r'og:type" content="([^"]*)"'),
        'h1':len(re.findall(r'<h1\b',body)),'types':types,'imgs':len(imgs),'noalt':noalt,
        'offer':bool(prod and prod.get('offers')),'prod':bool(prod),'avail':bool(prod and isinstance(prod.get('offers'),dict) and prod['offers'].get('availability')),
        'words':len(re.sub(r'<[^>]+>',' ',re.sub(r'<(script|style)[^>]*>.*?</\1>','',body,flags=re.S)).split())}
with ThreadPoolExecutor(3) as ex: res=list(ex.map(one,urls))
json.dump(res,open(S+'/crawl.json','w'),ensure_ascii=False)
print(len(res))

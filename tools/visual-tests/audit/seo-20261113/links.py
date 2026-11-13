import sys,re,json,urllib.request,urllib.error,html,collections
from concurrent.futures import ThreadPoolExecutor
S=sys.argv[1]; base='https://phucthinhsolar.com'
urls=[html.unescape(u) for u in re.findall(r'<loc>([^<]+)</loc>',open(S+'/sitemap.xml').read())]
UA={'User-Agent':'Mozilla/5.0 (compatible; Googlebot/2.1)'}
def page(u):
    try: return u, urllib.request.urlopen(urllib.request.Request(u,headers=UA),timeout=40).read().decode('utf-8','replace')
    except Exception as e: return u,''
with ThreadPoolExecutor(3) as ex: pages=list(ex.map(page,urls))
src=collections.defaultdict(set)
for u,h in pages:
    for href in re.findall(r'<a\b[^>]*href="([^"#]+)',h):
        href=html.unescape(href)
        if href.startswith(base): href=href[len(base):]
        if href.startswith('/') and not href.startswith('//') and not re.match(r'^/(upload|thumbs|_nuxt|assets)/',href): src[href.split('#')[0]].add(u.replace(base,''))
print('unique internal links',len(src))
class NR(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**k): return None
op=urllib.request.build_opener(NR)
def st(p):
    try: r=op.open(urllib.request.Request(base+urllib.request.quote(p,safe='/?=&%'),headers=UA),timeout=30); return p,r.status,''
    except urllib.error.HTTPError as e: return p,e.code,e.headers.get('Location','')
    except Exception as e: return p,0,str(e)[:30]
with ThreadPoolExecutor(4) as ex: res=list(ex.map(st,sorted(src)))
bad=[r for r in res if r[1]!=200]
print(collections.Counter(r[1] for r in res))
for p,c,l in bad: print(c,p,'->',l,'| on',len(src[p]),'pages e.g.',sorted(src[p])[:2])

import sys,urllib.request,urllib.error
from concurrent.futures import ThreadPoolExecutor
S=sys.argv[1]; base='https://phucthinhsolar.com'
class NR(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**k): return None
op=urllib.request.build_opener(NR)
def go(path):
    url=base+urllib.request.quote(path,safe='/?=&%'); hops=[]
    for _ in range(6):
        try:
            r=op.open(urllib.request.Request(url,method='GET',headers={'User-Agent':'Mozilla/5.0 (compatible; Googlebot/2.1)'}),timeout=30); return (path,r.status,url.replace(base,''),len(hops))
        except urllib.error.HTTPError as e:
            if e.code in(301,302,307,308):
                loc=e.headers.get('Location',''); url=loc if loc.startswith('http') else base+loc; hops.append(e.code); continue
            return (path,e.code,url.replace(base,''),len(hops))
        except Exception as e: return (path,0,str(e)[:40],len(hops))
    return (path,'LOOP',url,len(hops))
paths=open(S+'/old-urls.txt').read().split('\n')
with ThreadPoolExecutor(4) as ex: res=list(ex.map(go,paths))
open(S+'/old-check.tsv','w').write('\n'.join('\t'.join(map(str,r)) for r in res))
import collections
print(collections.Counter((r[1],r[3]) for r in res))

import re,html,urllib.request
sql=open('scripts/content/shorten-public-copy-20261106.sql',encoding='utf8').read()
olds=re.findall(r"REPLACE\(\w+, '((?:[^']|'')*)', '((?:[^']|'')*)'\)",sql)
def get(p):
    r=urllib.request.Request('https://phucthinhsolar.com'+p,headers={'User-Agent':'pts-readonly-check'})
    return html.unescape(html.unescape(urllib.request.urlopen(r,timeout=25).read().decode('utf8','ignore')))
pages=['/','/gioi-thieu/','/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w']
txt={p:get(p) for p in pages}
print('pairs',len(olds))
for o,n in olds:
    o=html.unescape(html.unescape(o));n=html.unescape(html.unescape(n))
    f=[p for p in pages if o in txt[p]]; g=[p for p in pages if n in txt[p]]
    print(('OLD-LIVE' if f else 'old-absent'),('NEW-LIVE' if g else 'new-absent'),repr(o[:70]),f)

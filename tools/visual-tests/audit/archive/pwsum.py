import json,sys,re
try: d=json.load(open(sys.argv[1]))
except Exception: print(sys.argv[2],'NO RESULT (timeout)'); raise SystemExit
print(sys.argv[2],d['stats']['expected'],'pass',d['stats']['unexpected'],'fail',d['stats']['skipped'],'skip')
def walk(s):
    for sp in s.get('specs',[]):
        for t in sp['tests']:
            if t['status']=='unexpected':
                e=(t['results'][-1].get('error') or {}).get('message','').replace('\n',' ')
                print('  FAIL',t['projectName'][:7],sp['title'][:60],'::',re.sub(r'\x1b\[[0-9;]*m','',e)[:130])
    for c in s.get('suites',[]): walk(c)
for s in d['suites']: walk(s)
